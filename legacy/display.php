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
   *      User Page Display      *
   *                             *
  \*******************************/
 
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
              array('class'=>'container'));
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

    return HTMLTag("div", $recent_posts, array('class'=>'container'));
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

    return HTMLTag("div", $recent_karma, array('class'=>'container'));
  }

  // Generate user's favorite threads.
  function GenerateUserFavorites($uid)
  {
    $fav_list = $this->forum->GenerateUserFavorites($uid);
    $threads_display = $this->GenerateThreadsList($fav_list);
    return HTMLTag("div",
                   HTMLTag("h2", "Favorite Threads"),
                   array('class'=>'container'))
      . HTMLTag("div",
                $threads_display,
                array('class'=>''));
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
                            array('class'=>"usrp user_prof_{$user_info['uid']} container"));
    $boxed_profile = HTMLTag("div",
                             $user_profile,
                             array('class'=>'usrp_box'));
    return $boxed_profile;
  }
}


