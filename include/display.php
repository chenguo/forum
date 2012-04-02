<?php
require_once("./include/defines.php");
ini_set('display_errors', 1); error_reporting(E_ALL | E_STRICT);

class Display
{
  var $title;
  var $session;
  var $forum;

  function Display($forum, $session, $title)
  {
    $this->forum = $forum;
    $this->title = $title;
    $this->session = $session;
  }

  /*  Display general forum title. */
  function DisplayTitle($type = Title::COMMON, $id = -1)
  {
    echo COMMON_CSS;
    echo COMMON_JQUERY;
    $title = $this->title;
    if (Title::THREAD == $type && $id >= 0)
      {
        // Get title info for thread.
        $title .= " - " . $this->forum->GetThreadTitle($id);
      }
    else if (Title::USER == $type && $id >= 0)
    {
      // Get user name.
      $title .= " - " . $this->session->GetUserName();
    }
    echo HTMLTag("title", $title);
  }

  // Display site banner
  function DisplayBanner()
  {
    echo HTMLTag("div", $this->title, array('class'=>"banner"));
    echo "\n";
    //echo "<audio autoplay='autoplay'><source src='buff.ogg' type='audio/ogg' /></audio>\n";
  }

  /* Display sidebar
     welcome:  welcome message

   */
  function DisplaySidebar()
  {
    $sidebar_info = $this->forum->GetSidebarInfo();

    // Hover area to trigger sidebar
    echo HTMLTag("div",
                 HTMLTag("div", "sidebar", array('class'=>'sidebar_trigger_text'))
                 ,
                 array('class'=>'sidebar_trigger', 'onmouseover'=>'showSidebar()', 'id'=>'sidebar_trigger'));

    // Actual sidebar
    echo HTMLTag("div",
                 HTMLTag("div", $sidebar_info['welcome'], array('class'=>'sidebar_item', 'id'=>'sidebar_welcome'))
                 . HTMLTag("div", $sidebar_info['chat'], array('class'=>'sidebar_item', 'id'=>'sidebar_chat'))
                 . HTMLTag("div", $sidebar_info['board'], array('class'=>'sidebar_item'))
                 . HTMLTag("div", $sidebar_info['bookmark'], array('class'=>'sidebar_item'))
                 . HTMLTag("div", $sidebar_info['privmsg'], array('class'=>'sidebar_item'))
                 . HTMLTag("div", $sidebar_info['cur_users'], array('class'=>'sidebar_item sidebar_users'))
                 . HTMLTag("div", $sidebar_info['day_users'], array('class'=>'sidebar_item sidebar_users'))
                 . HTMLTag("div", $sidebar_info['logout'], array('class'=>'sidebar_item'))
                 . HTMLTag("div", $sidebar_info['version'], array('class'=>'sidebar_item', 'id'=>'sidebar_version'))
                 ,
                 array('class'=>'sidebar', 'id'=>'sidebar', 'onmouseout'=>'hideSidebar(event)'));
    echo "\n";
  }

  // Display a board
  function DisplayBoard()
  {

  }

  /* Display a thread
     $thread_info array fields
     title:     title of thread
     posts:     array of individual posts
  */
  function DisplayThread($tid, $page=1)
  {
    $posts_per_page = DEFAULT_ITEMS_PER_PAGE;
    $thread_info = $this->forum->GetThreadInfo($tid, $posts_per_page, $page);

    // Header
    echo $this->GenerateThreadTitle($thread_info);
    echo "\n";

    // Individual posts
    foreach ($thread_info['posts'] as $post_info)
      {
        echo $this->GeneratePost($post_info);
        echo "\n";
      }

    // Footer
    echo $this->GenerateThreadTitle($thread_info);
    echo "\n";

    // Make post form
    echo HTMLTag("div", "", array('id'=>'new_post_preview'));
    echo "\n";
    echo HTMLTag("form",
                 // Form input
                 HTMLTag('textarea', '', array('class'=>'new_post', 'name'=>'content', 'id'=>'newpost_form'))
                 . "</br>"
                 . "<input type='hidden' name='tid' value='$tid'>"
                 . "<input type='hidden' name='action' value='post'>"
                 . "<input type='submit' value='submit' class='button new_post_button'>"
                 . "<input type='button' value='preview' class='button preview_post_button' onclick='previewNewPost($tid)'>"
                 ,
                 array('id'=>'new_post', 'name'=>'post', 'action'=>'action.php', 'method'=>'post',
                       'onsubmit'=>'button.disabled=true; return true;'));
    echo "\n";
  }

  // Generate thread titlebar
  function GenerateThreadTitle($thread_info)
  {
    return HTMLTag("div",
                   HTMLTag("div", $thread_info['title'], array('class'=>'thread_title'))
                   . HTMLTag("div", $thread_info['board'], array('class'=>'thread_board'))
                   . HTMLTag("div", $thread_info['pages'], array('class'=>'thread_pages'))
                   ,
                   array('class'=>'thread_title_bar'));
  }

  /* Display a post
     post array fields:
     pid:      post id
     uid:      user id of poster
     content:  content of the post
     controls: post action controls
     karma:    post karma information
     time:     post time
     edit:     edit time
  */
  function GeneratePost($post_info)
  {
    $post = HTMLTag("div",
                    // User profile
                    $this->GenerateUserProfile($post_info['uid'])
                    . $this->GeneratePostContent($post_info)
                    // Clear float
                    . HTMLTag("div", "", array('class'=>'clear'))
                    ,
                    array('class'=>'post', 'id'=>"post{$post_info['pid']}"));
    return $post;
  }

  // Create a post footer
  function GeneratePostContent($post_info)
  {
    $pid = $post_info['pid'];
    $content = HTMLTag("div",
                       // Content text
                       HTMLTag("div", $post_info['content'], array('class'=>'post_text',
                                                                   'id'=>"post{$pid}_text"))
                       . "<hr>"
                       // Post karma
                       . HTMLTag("div", $post_info['karma']['plus_karma'], array('class'=>'post_karma'))
                       . HTMLTag("div", $post_info['karma']['minus_karma'], array('class'=>'post_karma'))
                       . HTMLTag("div",
                                 // Post times
                                 HTMLTag("div", $post_info['time'], array('class'=>'post_time'))
                                 // Post controls
                                 . HTMLTag("div", $post_info['controls'], array('class'=>'post_controls',
                                                                                'id'=>"post{$pid}_controls"))
                                 ,
                                 array('class'=>'post_footer'))
                       ,
                       array('class'=>'post_content', 'id'=>"post{$post_info['pid']}"));
    return $content;
  }

  /* Display a user profile
     user_info array fields:
     uid:      user id
     name:     name
     posts:    post count
     avatar:   link to avatar
     t_online: last online time
     plus:     positive karma
     minus:    negative karma
  */
  function GenerateUserProfile($uid)
  {
    $user_info = $this->forum->GetCachedUser($uid);
    $user_profile = HTMLTag("div",
                            // User name
                            HTMLTag("div", makeUserLink($user_info['uid'], $user_info['name']), array('class'=>'user_prof_name'))
                            // User avatar
                            . showImg($user_info['avatar'], array('class'=>'user_prof_avatar'))
                            // Post count
                            . HTMLTag("div", $user_info['posts'] . " posts", array('class'=>'user_prof_posts'))
                            // Positive karma
                            . HTMLTag("div",
                                      HTMLTag("div", $user_info['plus'], array('class'=>'user_prof_karma_val'))
                                      . HTMLTag("div", " " . Karma::PLUSpl, array('class'=>'user_prof_karma_desc')),
                                      array('class'=>'user_karma'))
                            // Negative karma
                            . HTMLTag("div",
                                      HTMLTag("div", $user_info['minus'], array('class'=>'user_prof_karma_val'))
                                      . HTMLTag("div", " " . Karma::MINUSpl, array('class'=>'user_prof_karma_desc')),
                                      array('class'=>'user_karma'))
                            ,
                            array('class'=>'user_prof'));
    return $user_profile;
  }
}


