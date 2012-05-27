<?php
require_once("./include/defines.php");
ini_set('display_errors', 1); error_reporting(E_ALL | E_STRICT);

class Display
{
  var $title;
  var $session;
  var $forum;
  var $db;

  function Display($forum, $db, $session, $title)
  {
    $this->forum = $forum;
    $this->title = $title;
    $this->session = $session;
    $this->db = $db;
  }

  /*******************************\
   *                             *
   *       Common Display        *
   *                             *
  \*******************************/

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
                 HTMLTag("div", "sidebar", array('class'=>'sbtrig_txt'))
                 ,
                 array('class'=>'sbtrig', 'onmouseover'=>'showSidebar()', 'id'=>'sbtrig'));

    // Actual sidebar
    echo HTMLTag("div",
                 HTMLTag("div", $sidebar_info['welcome'], array('class'=>'sb_head', 'id'=>'sb_welc'))
                 //. HTMLTag("div", $sidebar_info['chat'], array('class'=>'sidebar_item', 'id'=>'sidebar_chat'))
                 . HTMLTag("div", $sidebar_info['board'], array('class'=>'sb_elem'))
                 . HTMLTag("div", $sidebar_info['bookmark'], array('class'=>'sb_elem'))
                 . HTMLTag("div", $sidebar_info['privmsg'], array('class'=>'sb_elem'))
                 . HTMLTag("div", $sidebar_info['cur_users'], array('class'=>'sb_elem sb_usrs'))
                 . HTMLTag("div", $sidebar_info['day_users'], array('class'=>'sb_elem sb_usrs'))
                 . HTMLTag("div", $sidebar_info['logout'], array('class'=>'sb_elem'))
                 . HTMLTag("div", $sidebar_info['version'], array('class'=>'sb_elem sb_last', 'id'=>'sb_ver'))
                 ,
                 array('class'=>'sidebar', 'id'=>'sidebar', 'onmouseout'=>'hideSidebar(event)'));
    echo "\n";
  }

  /*******************************\
   *                             *
   *        Login Display        *
   *                             *
  \*******************************/

  // Display login page
  function DisplayLogin()
  {
    $form = HTMLTag("form",
                    HTMLTag("div",
                            "<input type='text' size='20' name='username' maxlength='32' value='username' onclick='clearField(this)'>",
                            array('class'=>'field'))
                    . HTMLTag("div",
                              "<input type='password' size='20' name='password' maxlength='32' value='password' onclick='clearField(this)'>",
                              array('class'=>'field'))
                    . "<input type='hidden' name='action' value='login'>"
                    . HTMLTag("div", "<input type='submit' value='log in' class='button'>")
                    . HTMLTag("div", "<input type='checkbox' name='cookie' value='set'>", array('class'=>'remember'))
                    . HTMLTag("div", "remember me", array('class'=>'remember'))
                    ,
                    array('class'=>'login_form', 'action'=>Pages::ACTION, 'method'=>'post'));
    echo $form;
  }



  /*******************************\
   *                             *
   *        Board Display        *
   *                             *
  \*******************************/

  // Display a board
  function DisplayBoard($page=1)
  {
    $threads_per_page = $this->session->threads_per_page;
    $posts_per_page = $this->session->posts_per_page;
    /* $board_info array fields:
       pages: links to pages of the board
       new_thr: link to make new threads page
       threads: table of thread summary information
    */
    $board_info = $this->forum->GetBoardDisplayInfo($threads_per_page, $posts_per_page, $page);

    // Header
    echo $this->GenerateBoardHeader($board_info);
    echo "\n";

    // Display posts in thread.
    /*$title_row =
      HTMLTag("tr",
              HTMLTag("th", "title", array('class'=>'brd_thr_title'))
              . HTMLTag("th", "posts", array('class'=>'brd_thr_num'))
              . HTMLTag("th", "views", array('class'=>'brd_thr_num'))
              . HTMLTag("th", "created", array('class'=>'brd_thr_time'))
              . HTMLTag("th", "last post", array('class'=>'brd_thr_time'))
              );*/
    $title_row = HTMLTag("div",
                         HTMLTag("div", "title", array('class'=>'brd_thr_title'))
                         . HTMLTag("div", "posts", array('class'=>'brd_thr_num'))
                         . HTMLTag("div", "views", array('class'=>'brd_thr_num'))
                         . HTMLTag("div", "created", array('class'=>'brd_thr_time'))
                         . HTMLTag("div", "last post", array('class'=>'brd_thr_time'))
                         . HTMLTag("div", "", array('class'=>'clear'))
                         ,
                         array('class'=>'brd_head'));

    $thread_list = "";
    foreach ($board_info['threads'] as $thread_info)
      {
        $thread_list .= $this->GenerateBoardThreadRow($thread_info);
      }

    /*echo HTMLTag("div",
                 HTMLTag("table",
                         $title_row
                         . $thread_list,
                         array('class'=>'board_table'))
                 ,
                 array('class'=>'board_threads'));*/

    echo HTMLTag("div",
                 $title_row
                 . $thread_list
                 . HTMLTag("div", "", array('class'=>'board_bottom'))
                 ,
                 array('class'=>'brd_threads'));

    echo $this->GenerateBoardHeader($board_info);
    echo "\n";
  }

  // Generate header
  function GenerateBoardHeader($board_info)
  {
    $board_header = HTMLTag("div",
                            HTMLTag("div", $board_info['pages'], array('class'=>'brd_pages'))
                            . HTMLTag("div", $board_info['new_thr'], array('class'=>'brd_new_thr'))
                            // Clear float
                            . HTMLTag("div", "", array('class'=>'clear'))
                            ,
                            array('class'=>'title_bar'));

    return $board_header;
  }

  /* Generate a row for thread info
     $thread_info array fields
     link: title link of thread
     pages: links to pages in thread
     flags: user flags
     create_time: create time of thread
     post_time: time of last post in thread
     posts: number of posts
     views: thread views
     creator: creator of thread
     last_poster: last poster in thread
  */
  function GenerateBoardThreadRow($thread_info)
  {
    /*$thread_row =
      HTMLTag("tr",
              HTMLTag("td",
                      HTMLTag("div", $thread_info['link'])
                      . HTMLTag("div", $thread_info['pages'], array('class'=>'board_thr_page_links'))
                      . HTMLtag("div", $thread_info['flags'], array('class'=>'board_thr_flags'))
                      ,
                      array('class'=>'board_thr_title'))
              . HTMLTag("td", $thread_info['posts'])
              . HTMLTag("td", $thread_info['views'])
              . HTMLtag("td", $thread_info['creator'])
              . HTMLtag("td", $thread_info['last_poster']));
    */
    $board_thr_link = HTMLTag("div", $thread_info['link']);
    $board_thr_page_links = "";
    if ($thread_info['pages'] != "")
      $board_thr_page_links = HTMLTag("div", $thread_info['pages'], array('class'=>'brd_thr_page_links'));

    $board_thr_flags = "";
    if ($thread_info['flags'] != "")
      $board_thr_flags = HTMLTag("div", $thread_info['flags'], array('class'=>'brd_thr_flags'));

    $thread_row =
      HTMLTag("div",
              HTMLTag("div",
                      $board_thr_link . $board_thr_page_links . $board_thr_flags,
                      array('class'=>'brd_thr_title'))
              . HTMLTag("div", $thread_info['posts'], array('class'=>'brd_thr_num'))
              . HTMLTag("div", $thread_info['views'], array('class'=>'brd_thr_num'))
              . HTMLtag("div",
                        HTMLTag("div", $thread_info['creator'])
                        . HTMLTag("div", $thread_info['create_time'], array('class'=>'time'))
                        ,
                        array('class'=>'brd_thr_time'))
              . HTMLtag("div",
                        HTMLTag("div",$thread_info['last_poster'])
                        . HTMLTag("div", $thread_info['post_time'], array('class'=>'time'))
                        ,
                        array('class'=>'brd_thr_time'))
              . HTMLTag("div", "", array('class'=>'clear'))
              ,
              array('class'=>'brd_thread_row')
              );

    return $thread_row;
  }

  /*******************************\
   *                             *
   *        Thread Display       *
   *                             *
  \*******************************/

  /* Display a thread
     $thread_info array fields
     title:     title of thread
     posts:     array of individual posts
  */
  function DisplayThread($tid, $page=1)
  {
    $posts_per_page = $this->session->posts_per_page;
    $thread_info = $this->forum->GetThreadDisplayInfo($tid, $posts_per_page, $page);

    // Header containing title and links
    echo $this->GenerateThreadTitle($thread_info);
    echo "\n";

    // Individual posts
    foreach ($thread_info['posts'] as $post_info)
      {
        echo $this->GeneratePost($post_info);
        echo "\n";
      }

    // Footer containing title and links
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
                   HTMLTag("div", $thread_info['title'], array('class'=>'thr_ttl'))
                   . HTMLTag("div", $thread_info['board'], array('class'=>'thr_brd'))
                   . HTMLTag("div", $thread_info['pages'], array('class'=>'thr_pg'))
                   ,
                   array('class'=>'title_bar'));
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
    $user_info = $this->forum->GetCachedUser($post_info['uid']);
    $post = HTMLTag("div",
                    // User profile
                    $this->GenerateUserProfile($user_info)
                    . $this->GeneratePostContent($post_info, $user_info)
                    // Clear float
                    . HTMLTag("div", "", array('class'=>'clear'))
                    ,
                    array('class'=>'post', 'id'=>"post{$post_info['pid']}"));
    return $post;
  }

  // Create a post footer
  function GeneratePostContent($post_info, $user_info)
  {
    $pid = $post_info['pid'];
    $content = HTMLTag("div",
                       // Content text
                       HTMLTag("div", $post_info['content'], array('class'=>'post_text',
                                                                   'id'=>"post{$pid}_text"))
                       . "<hr>"
                       // Signature
                       . HTMLTag("div", prepContent($user_info['signature'], FALSE)
                                 , array('class'=>"post_sig user{$user_info['uid']}_sig}"))
                       // Post karma
                       . HTMLTag("div", $post_info['karma']['plus_karma'], array('class'=>'post_karma_plus'))
                       . HTMLTag("div", $post_info['karma']['minus_karma'], array('class'=>'post_karma_minus'))
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


  /*******************************\
   *                             *
   *      User Page Display      *
   *                             *
  \*******************************/
  function DisplayUserPage($uid, $subpage = Profile::PROFILE)
  {
    $user_info = $this->db->GetUserProfile($uid, TRUE, TRUE);

    echo HTMLTag("div",
                 // Display basic user profile
                 $this->GenerateUserProfile($user_info)
                 // Display links within user profile
                 . $this->GenerateUserProfileLinks($uid)
                 ,
                 array('class'=>'usrp_leftcol'));
    echo "\n";

    $page_content = "";
    if ($subpage == Profile::PROFILE)
      {
        // Display basic user information
        $page_content =
          HTMLTag("div",
                  $this->GenerateUserDetails($user_info)
                  ,
                  array('class'=>'usrp_content'));
      }

    echo $page_content;
    echo "\n";
  }

  // Generate links to sub-areas of user profile
  function GenerateUserProfileLinks($uid)
  {
    return HTMLTag("div",
                   HTMLTag("ul",
                           HTMLTag("li", makeLink("javascript:void(0)", "profile",
                                                  array('onclick'=>"userProfView(\"profile\", $uid)")))
                           . HTMLTag("li", makeLink("javascript:void(0)", "edit profile",
                                                    array('onclick'=>"userProfView(\"edit\", $uid)")))
                           . HTMLTag("li", makeLink("javascript:void(0)", "recent",
                                                    array('onclick'=>"userProfView(\"recent\", $uid)")))
                           . HTMLTag("li", makeLink("javascript:void(0)", "messages",
                                                    array('onclick'=>"userProfView(\"message\", $uid)")))

                           )
                   ,
                   array('class'=>'usrp_links'));
  }

  // Generate a table of user details.
  function GenerateUserDetails($user_info)
  {
    $user_details_table =
      table(tableRow( HTMLTag("th", "", array('class'=>'usrp_tbl_label'))
                      . HTMLTag("th", "", array('class'=>'usrp_tbl_value'))
                      )
            . tableRow( tableCol("email") . tableCol($user_info['email']) )
            . tableRow( tableCol("profile views") . tableCol($user_info['views']) )
            . tableRow( tableCol("birthday") . tableCol($user_info['birth']) )
            . tableRow( tableCol("joined on") . tableCol($user_info['join']) )
            . tableRow( tableCol("last login") . tableCol($user_info['t_online']) )
            . tableRow( tableCol("signature") . tableCol(prepContent($user_info['signature'], FALSE)) )
            ,
            array('class'=>'noshow usrp_tbl'));
    return HTMLTag("div",
                   $user_details_table,
                   array('class'=>'usrp_container'));
  }

  // Generate edit fields for user profile
  function GenerateUserSettings($user_info)
  {
    $msg_div = HTMLTag("div", "", array('class'=>'usrp_edit_msg'));

    // Header row (not shown)
    $header = tableRow( HTMLTag("th", "", array('class'=>'usrp_tbl_label'))
                        . HTMLTag("th", "", array('class'=>'usrp_tbl_value'))
                        );

    // Table of input fields for basic settings
    $basic_settings =
      table($header
            // email
            . tableRow( tableCol("email")
                        . tableCol("<input type='text' id='email' value={$user_info['email']}>"))
            // posts per page
            . tableRow( tableCol("posts per page")
                        . tableCol("<input type='text' id='post_disp' value={$user_info['posts_display']}>"))
            // threads per page
            . tableRow( tableCol("threads per page")
                        . tableCol("<input type='text' id='thr_disp' value={$user_info['threads_display']}>"))
            // avatar
            . tableRow( tableCol("avatar")
                        . tableCol("<input type='text' id='avatar' value={$user_info['avatar']}>"))
            // signature
            . tableRow( tableCol("signature")
                        . tableCol(HTMLTag("textarea", $user_info['signature'], array('class'=>'prof_edit_sig', 'maxlength'=>'255'))))
            ,
            array('class'=>'noshow usrp_tbl'));
    $save_button = makeButton("save", array('onclick'=>"userProfSave({$user_info['uid']})", 'class'=>'settings_btn'));
    $cancel_button = makeButton("cancel", array('onclick'=>"userProfCancel({$user_info['uid']})", 'class'=>'settings_btn'));

    return HTMLTag("div",
                   $msg_div . $basic_settings . $save_button . $cancel_button,
                   array('class'=>'usrp_container'));
  }

  // Generate controls to change user password
  function GenerateUserPWChange($user_info)
  {
    $msg_div = HTMLTag("div", "", array('class'=>'usrp_pw_msg'));

    // Header row (not shown)
    $header = tableRow( HTMLTag("th", "", array('class'=>'usrp_tbl_label'))
                        . HTMLTag("th", "", array('class'=>'usrp_tbl_value'))
                        );

    // Password change fields
    $password_table =
      table($header
            . tableRow( tableCol("current password")
                        . tableCol("<input type='password' id='cur_pw'>"))
            . tableRow( tableCol("new password")
                        . tableCol("<input type='password' id='new_pw'>"))
            . tableRow( tableCol("confirm new password")
                        . tableCol("<input type='password' id='cnf_pw'>")),
            array('class'=>'noshow usrp_tbl')
            );
    $pw_button = makeButton("change", array('onclick'=>"userProfPW({$user_info['uid']})", 'class'=>'settings_btn'));
    return
      HTMLTag("div",
              $msg_div . $password_table . $pw_button,
              array('class'=>'usrp_container'));
  }

  // Generate a list of user's recent posts
  function GenerateUserRecentPosts($uid)
  {
    $recent_posts_array = $this->forum->GenerateUserRecentPosts($uid);
    $rows = "";
    // Tabularize posts
    foreach ($recent_posts_array as $post)
      $rows .= tableRow( tableCol($post['post'] . HTMLTag("div", $post['time'], array('class'=>'time'))) . tableCol($post['content']));
    $recent_posts = HTMLTag("h2", "Recent Posts") . table($rows, array('class'=>'usrp_tbl'));

    return HTMLTag("div", $recent_posts, array('class'=>'usrp_container'));
  }

  // Generate a list of user's recent posts
  function GenerateUserRecentKarma($uid, $received)
  {
    $recent_karma = "";
    $karma_array;
    if ($received == 0)
      $karma_array = $this->forum->GenerateUserRecentKarmaGiven($uid);
    else
      $karma_array = $this->forum->GenerateUserRecentKarmaRecvd($uid);

    // Tabularize list of karma actions
    $rows = "";
    foreach ($karma_array as $karma_info)
      {
        $rows .= tableRow( tableCol($karma_info['action'])
                           . tableCol($karma_info['recip'])
                           . tableCol($karma_info['thread'])
                           . tableCol(HTMLTag("div", $karma_info['time'], array('class'=>'time'))));
      }

    // Add a header
    if ($received == 0)
      $recent_karma = HTMLTag("h2", "Recent Karma Given") . table($rows, array('class'=>'usrp_tbl'));
    else
      $recent_karma = HTMLTag("h2", "Recent Karma Received") . table($rows, array('class'=>'usrp_tbl'));

    return HTMLTag("div", $recent_karma, array('class'=>'usrp_container'));
  }

  /*******************************\
   *                             *
   *       Utility Display       *
   *                             *
  \*******************************/

  /* Display a user profile
     user_info array fields:
     uid:      user id
     name:     name
     posts:    post count
     avatar:   link to avatar
     t_online: last online time
     plus:     positive karma
     minus:    negative karma
     posts_display: posts to show per page
     threads_display: threads to show per page
     signature: user signature
  */
  function GenerateUserProfile($user_info)
  {
    $user_profile = HTMLTag("div",
                            // User name
                            HTMLTag("div",
                                    makeUserLink($user_info['uid'], $user_info['name']),
                                    array('class'=>'usrp_name'))
                            // User avatar
                            . showImg($user_info['avatar'], array('class'=>'usrp_avatar'))
                            // Post count
                            . HTMLTag("div", $user_info['posts'] . " posts",
                                      array('class'=>'usrp_posts'))
                            // Karma
                            . HTMLTag("div",
                                      HTMLTag("div", $user_info['plus'],
                                              array('class'=>'usrp_karma_p'))
                                      . " " . Karma::PLUSpl
                                      ,
                                      array('class'=>'usrp_karma'))
                            . HTMLTag("div",
                                      HTMLTag("div", $user_info['minus'],
                                              array('class'=>'usrp_karma_m'))
                                      . " " . Karma::MINUSpl
                                      ,
                                      array('class'=>'usrp_karma'))
                            ,
                            array('class'=>"usrp user_prof_{$user_info['uid']}"));
    return $user_profile;
  }
}


