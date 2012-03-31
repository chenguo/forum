<?php
require_once("./include/defines.php");
require_once("./include/db.php");
require_once("./include/session.php");
ini_set('display_errors', 1); error_reporting(E_ALL | E_STRICT);

class Forum
{
  var $title;
  var $db;
  var $version;
  var $cur_users;
  var $day_users;
  var $user_cache;

  function Forum($db, $title, $version)
  {
    $this->title = $title;
    $this->version = $version;
    $this->db = $db;
    $this->user_cache = array();
  }

  /*******************************\
   *                             *
   *   Common field generators   *
   *                             *
  \*******************************/

  /*  Display general forum title. */
  function DisplayTitle($type = Title::COMMON, $id = -1)
  {
    echo COMMON_CSS;
    $title = $this->title;
    if (Title::THREAD == $type && $id >= 0)
      {
        /* Get title info for thread. */
        $thread = $this->db->GetThread($id, FALSE /* Only title info */);
        $title .= " - " . $thread['title'];
      }
    else if (Title::USER == $type && $id >= 0)
      $title .= " - " . $this->db->GetUserName($id, FALSE);
    echo HTMLTag("title", $title);
  }

  // Display site banner
  function DisplayBanner()
  {
    echo HTMLTag("div", $this->title, array('id'=>'banner'));
  }

  /* Display common header for forum. */
  function DisplayCommonHeader($session)
  {
    echo HTMLTag("div", $this->title, array('id'=>'banner'));
    return;
    /* If user is logged in, display some things. */
    $welcome_col = "";
    $logout_col = "";
    $welcome = "welcome";
    if ($session->CheckLogin())
      {
        $welcome .= " {$this->db->GetUserName($session->GetUID())}!";
        $welcome_col = tableCol($welcome);
        $logout_col = tableCol(makeLink('logout.php', 'logout'));
      }

    /* Get the online users. */
    $this->cur_users = $this->GetOnlineUsers(15);
    $this->day_users = $this->GetOnlineUsers(1440);
    $user_str = count($this->cur_users) . " user";
    $user_str .= (count($this->cur_users) != 1)? "s" : "";
    $user_str .= " online";
    $user_col = tableCol($user_str);

    echo HTMLTag("div",
                 table( tableRow($welcome_col . $user_col . $logout_col),
                        array('id'=>'header')),
                 array('id'=>'common_header'));
    echo "</br>\n";
  }

  /* Display common footer for forum */
  function DisplayCommonFooter()
  {
    /* Assume we've called GetOnlineUsers already (which we should have in header function. */
    $cur_user_links = array();
    foreach ($this->cur_users as $user)
      array_push ($cur_user_links, makeUserLink($user['uid'], $user['name']));
    $online_usr_str = "online users: ";
    $online_usr_str .= (count($cur_user_links) == 0)? "none" : implode(", ", $cur_user_links);

    $day_user_links = array();
    foreach ($this->day_users as $user)
      array_push ($day_user_links, makeUserLink($user['uid'], $user['name']));
    $day_usr_str = "users in past day: ";
    $day_usr_str .= (count($day_user_links) == 0)? "none" : implode(", ", $day_user_links);

    echo HTMLTag("div",
                 table( tableRow( tableCol($online_usr_str) . tableCol($day_usr_str) )
                        . tableRow( tableCol("LOLBros beta v{$this->version}  " . makeLink("changelog.txt", "changelog"), array('colspan'=>'2')) ),
                        array('id'=>'footer')),
                 array('id'=>'common_footer'));
  }

  /* Get the number of users online within the past $
  . */
  function GetOnlineUsers($time)
  {
    return $this->db->GetOnlineUsers($time);
  }

  /*******************************\
   *                             *
   *      Thread generators      *
   *                             *
  \*******************************/

  // Display threads in the forum.
  function DisplayThreads($session, $page=1)
  {
    $threads_per_page = DEFAULT_ITEMS_PER_PAGE;
    $uid = $session->GetUID();
    $threads = $this->db->GetThreads($page, $threads_per_page);

    // Make a table for links to other pages and make thread link.
    $page_links = $this->MakePageLinks ($page, $threads_per_page, $this->db->GetNumThreads(), "threads.php?");
    $link_table = table( tableRow( tableCol ( $page_links, array('class'=>'noshow') )
                                   . tableCol( makeLink("makethread.php", "new thread", "new_thr"), array('class'=>'noshow') )
                                   . tableCol ("", array('class'=>'noshow'))
                                   ),
                         array('class'=>'thread_header')
                         );
    echo $link_table;

    // Display posts in thread.
    $title_row = HTMLTag("th", "title", array('class'=>'thr_title'))
      . HTMLTag("th", "posts", array('class'=>'num'))
      . HTMLTag("th", "views", array('class'=>'num'))
      . HTMLTag("th", "created", array('class'=>'thr_time'))
      . HTMLTag("th", "last post", array('class'=>'thr_time'));

    echo "<table id='threads'>";
    echo tableRow($title_row);
    echo "\n";

    $posts_per_page = DEFAULT_ITEMS_PER_PAGE;
    foreach ($threads as $thread)
      {
        // Get any thread flags.
        $thread_flags = $this->GetThreadFlags($uid, $thread);
        // Get the link to thread as well as links to pages in thread.
        $thread_link = makeLink("thread.php?tid={$thread['tid']}", $thread['title'], 'thread')
          . "</br>"
          . $this->MakeThreadPageLinks($posts_per_page, $thread['posts'], "thread.php?tid={$thread['tid']}")
          . " " . $this->GetThreadFlags($uid, $thread);

        $create_time = GetTime(TIME_FULL, $thread['create_time']);
        $post_time = GetTime(TIME_FULL, $thread['post_time']);

        echo tableRow ( tableCol($thread_link)
                        . tableCol($thread['posts'])
                        . tableCol($thread['views'])
                        . tableCol($this->db->GetUserName($thread['uid']) . "</br>" . fontSize($create_time, 1))
                        . tableCol($this->db->GetUserName($thread['last_uid']) . "</br>" . fontSize($post_time, 1))
                        );
        echo "\n";
      }
    echo "</table>\n";
    echo $link_table;
    echo "</br>\n";
  }

  // Make links to pages in a thread below thread.
  function MakeThreadPageLinks ($items_per_page, $max_items, $link)
  {
    $max_page = GetPageCount($max_items, $items_per_page);
    $page_links = "";
    if ($max_page > 1)
      $page_links .= makeLink("$link", "1", "thr_page_link") . " " . makeLink("$link&page=2", "2", "thr_page_link");
    if ($max_page > 2)
      $page_links .= " " . makeLink("$link&page=3", "3", "thr_page_link");
    if ($max_page > 3)
      $page_links .= " " . makeLink("$link&page=4", "4", "thr_page_link");
    if ($max_page > 4)
      $page_links .= " " . makeLink("$link&page=5", "5", "thr_page_link");
    if ($max_page > 5)
      $page_links .= " " . makeLink("$link&page=$max_page", "last", "thr_page_link");
    return $page_links;
  }

  // For a particular user and thread, get notifications for user pertaining to that thread.
  function GetThreadFlags ($uid, $thread)
  {
    $flags = "";

    // Check the last post the user has viewed
    $num_viewed = $this->db->GetUserPostView($uid, $thread['tid']);
    if ($num_viewed < $thread['posts'])
      $flags .= "new";

    return HTMLTag("label", $flags, array('class'=>'thr_flags'));
  }

  // Display thread.
  function DisplayThread($thread_id, $session, $page=1)
  {
    $thread = $this->db->GetThread($thread_id, TRUE /* update viewcount */);

    // Initialize some post-counting information.
    $posts_per_page = DEFAULT_ITEMS_PER_PAGE;
    $posts = $this->db->GetPosts($thread_id, $page, $posts_per_page);
    $session_id = $session->GetUID();

    // Create links to board and other pages
    $page_links = $this->MakePageLinks ($page, $posts_per_page, $thread['posts'], "thread.php?tid=$thread_id");
    $links = table( tableRow( tableCol( makeLink("threads.php", "board") )
                              . tableCol($page_links) ),
                    array('class'=>'noshow', 'id'=>'board_links'));

    // Make the table
    echo "<table id='posts' class='posts'>";
    echo HTMLTag("thead", tableRow(HTMLTag("th", $links, array('class'=>'profile')) . HTMLTag("th", $thread['title'])), array('id'=>'posts_head'));

    foreach ($posts as $post)
      $this->DisplayPost($session_id, $post);
    // Mark user as at least having read last post on this page.
    $last_post = end($posts);
    $this->db->UpdateUserPostView($session_id, $thread_id, $last_post['pid'], $last_post['tpid']);

    // Make a table footer with links
    echo HTMLTag("tfoot", tableRow(tableCol($links) . tableCol("")), array('id'=>'posts_foot'));
    echo "</table></br>";
  }

  // Make page links. Format: << < p-2 p-1 page p+1 p+2 > >>
  function MakePageLinks ($page, $items_per_page, $max_items, $link)
  {
    $max_page = GetPageCount($max_items, $items_per_page);
    $page_links = "";

    // Only generate if there's multiple pages.
    if ($max_page > 1)
      {
        if ($page > 3)
          $page_links .= makeLink("$link&page=1", "<<", "page_link") . "  ";
        if ($page > 2)
          $page_links .= makeLink("$link&page=" . ($page-2), $page - 2, "page_link") . " ";
        if ($page > 1)
          $page_links .= makeLink("$link&page=" . ($page-1), $page - 1, "page_link") . " ";
        $page_links .= "$page";
        if ($page < $max_page)
          $page_links .= " " . makeLink("$link&page=" . ($page+1), $page + 1, "page_link");
        if ($page < $max_page - 1)
          $page_links .= " " . makeLink("$link&page=" . ($page+2), $page + 2, "page_link");
        if ($page < $max_page - 2)
          $page_links .= "  " . makeLink("$link&page=" . $max_page, ">>", "page_link");
      }
    return $page_links;
  }

  /*******************************\
   *                             *
   *        Post Functions       *
   *                             *
  \*******************************/

  // Display a single post.
  function DisplayPost($session_id, $post)
  {
    // Generate profile.
    $user_info = $this->GetCachedUser($post['uid']);
    $user_profile = $this->GenerateUserProfile($user_info);

    // List karma providers of post.
    $post_karma = $this->db->GetPostKarma($post['pid']);
    $karma_stats = "";
    $plus_names = array();
    $minus_names = array();

    foreach ($post_karma as $karma)
    {
      $user_info = $this->GetCachedUser($karma['uid']);
      if ($karma['type'] === 'plus')
        array_push($plus_names, makeLink("user.php?uid={$user_info['uid']}", $user_info['name']));
      else
        array_push($minus_names, makeLink("user.php?uid={$user_info['uid']}", $user_info['name']));
    }

    if (count($plus_names) > 0)
    {
      $karma_stats .= Karma::PLUSact . " by: " . implode(", ", $plus_names);
      if (count($minus_names) > 0)
        $karma_stats .= "</br>";
    }
    if (count($minus_names) > 0)
      $karma_stats .= Karma::MINUSact . " by: " . implode(", ", $minus_names);
    $karma_table = table( tableRow( tableCol($karma_stats, array('id'=>"karma_stats{$post['pid']}")) ),
                          array('class'=>'karma_table noshow'));

    // Create content table.
    $content = prepContent($post['content'], $post['tid']);
    $content_table = table( $this->GeneratePostHeader($session_id, $post)
                            . tableRow( tableCol($content, array('colspan'=>'3', 'class'=>'content',
                                                                 'id'=>"post{$post['pid']}")) )
                            . tableRow( tableCol($karma_table, array('colspan'=>'3')) )
                            . $this->GeneratePostFooter($session_id, $post)
                            ,array('class'=>'content_table noshow')
                            );
    echo tableRow( tableCol($user_profile, array('class'=>'profile')) . tableCol($content_table, array('class'=>'content_col')) );
    echo "\n";
  }

  // Sub-table header for each post.
  function GeneratePostHeader($session_id, $post)
  {
    $post_header = tableRow( tableCol("", array('class'=>'content_left'))
                             . tableCol("", array('class'=>'content_center'))
                             . tableCol("", array('class'=>'content_right'))
                             );
    return $post_header;
  }

  // Sub-table footer for each post.
  function GeneratePostFooter($session_id, $post)
  {
    // Post edit time.
    $edit_time = "";
    if (isset($post['edit']))
      $edit_time = fontsize("</br>edited " . GetTime(TIME_FULL, $post['edit']), 1);
    $edit_time = HTMLTag("label", $edit_time, array('id'=>"edittime{$post['pid']}"));

    // Post time.
    $post_time = fontsize("posted " . GetTime(TIME_FULL, $post['time']), 1);

    // Allow users to edit their own posts.
    $post_controls = "";
    if ($session_id == $post['uid'])
      $post_controls .= makeButton("edit", "editPost({$post['pid']}, \"edit_edit\")");
    // If user hasn't modified karma of this post yet, display karma buttons.
    else if ($this->db->PostKarmaChangeAllowed($post['pid'], $session_id))
      $post_controls .= makeButton(Karma::PLUS, "karma(\"karma_plus\", {$post['pid']}, {$post['uid']})")
        . " " . makeButton(Karma::MINUS, "karma(\"karma_minus\", {$post['pid']}, {$post['uid']})");
    $post_controls .= " " . makeButton("quote", "quotePost({$post['pid']})");

    $post_footer = tableRow ( tableCol($post_time . $edit_time, array('class'=>'content_footer_left'))
                              . tableCol("")
                              . tableCol($post_controls, array('id'=>"post{$post['pid']}controls",
                                                               'class'=>'content_footer_right'))
                              , array('class'=>"test")
                              );
    return $post_footer;
  }

  // Get link to a particular post.
  function GetPostLink($pid, $link_text, $session = "")
  {
    $post = $this->db->GetPostMeta($pid);
    $page = GetPageCount($post['tpid'], DEFAULT_ITEMS_PER_PAGE);
    $link = makeLink("thread.php?tid={$post['tid']}&page=$page#post$pid", $link_text);
    return $link;
  }

  /*******************************\
   *                             *
   *      Sidebar Functions      *
   *                             *
  \*******************************/
  function DisplaySidebar($session = "")
  {
    /* If user is logged in, display some things. */
    $welcome_col = "";
    $logout_col = "";
    $welcome = "welcome";
    if ($session->CheckLogin())
      $welcome .= " {$this->db->GetUserName($session->GetUID())}!";
    else
      $welcome .= "!";

    $this->cur_users = $this->GetOnlineUsers(15);
    $this->day_users = $this->GetOnlineUsers(1440);
    $cur_user_links = array();
    foreach ($this->cur_users as $user)
      array_push ($cur_user_links, makeUserLink($user['uid'], $user['name']));
    $online_usr_str = "online users</br>";
    $online_usr_str .= (count($cur_user_links) == 0)? "none" : implode(", ", $cur_user_links);

    $day_user_links = array();
    foreach ($this->day_users as $user)
      array_push ($day_user_links, makeUserLink($user['uid'], $user['name']));
    $day_usr_str = "users in past day</br>";
    $day_usr_str .= (count($day_user_links) == 0)? "none" : implode(", ", $day_user_links);

    $sidebar_welcome = tableRow(HTMLTag("th", $welcome));
    $sidebar_bookmarks = tableRow(tableCol("bookmarks"));
    $sidebar_pm = tableRow(tableCol("private messages"));
    $sidebar_chat = tableRow(tableCol($this->DisplayChat($session, FALSE)));
    $sidebar_cur_usr = tableRow(tableCol($online_usr_str));
    $sidebar_day_usr = tableRow(tableCol($day_usr_str));
    $sidebar_logout = tableRow(tableCol(makeLink('logout.php', 'logout')));
    $sidebar_version = tableRow(tableCol("LOLBros beta " . makeLink("changelog.txt", "v{$this->version}")));

    $sidebar_table = table($sidebar_welcome
                           . $sidebar_bookmarks
                           . $sidebar_pm
                           . $sidebar_chat
                           . $sidebar_cur_usr
                           . $sidebar_day_usr
                           . $sidebar_logout
                           . $sidebar_version,
                            array('id'=>'sidebar_table'));

    echo HTMLTag("div",
                 $sidebar_table,
                 array('id'=>'sidebar'));
  }

  function DisplayChat($session, $show = FALSE)
  {
    $chat_msgs = $session->GetChatText();
    $chat = "chat</br>";
    $chat .= table( tableRow( tableCol(HTMLTag("div", $chat_msgs,
                                               array('id'=>'chat_msgs_div')),
                                       array('id'=>'chat_msgs'))),
                    array('id'=>'chat_area'));

    $chat .= HTMLTag("form",
                     HTMLTag("textarea", "", array('rows'=>'2', 'name'=>'chat_post', 'id'=>'chat_post',  'onkeyup'=>'sendKey(event)'))
                     . "</br>"
                     . HTMLTag("input", "", array('type'=>'button', 'value'=>'send', 'class'=>'button', 'onClick'=>'sendChat(this.form)')),
                     array('name'=>'chat_input', 'id'=>'chat_input'));
    if ($show)
      echo $chat;
    else
      return $chat;
  }

  /*******************************\
   *                             *
   *        User Functions       *
   *                             *
  \*******************************/

  /* Display user profile page. */
  function DisplayUserPage($uid, $session_user)
  {
    /* Get full user profile and increment view count. */
    $user_info = $this->db->GetUserProfile($uid, TRUE, TRUE);

    $user_profile = $this->GenerateUserProfile($user_info);
    $user_details = $this->GenerateUserDetails($user_info);

    echo "<div id='common'>";
    echo "<table id='user_profile'>";
    echo tableRow( tableCol ($user_profile, array('class'=>'profile')) . tableCol($user_details) );
    echo $this->DisplayUserRecentPosts($user_info);
    echo $this->DisplayUserRecentKarma($user_info);
    echo "</table></br>\n";

    if ($uid == $session_user)
      {
        echo "<div id='profile_control'>";
        echo makeButton("edit profile", "editProfile(\"edit\")");
        echo "</div>";
      }

    echo "</br></br></div>";
  }

  /* Generate quick profile of user. */
  function GenerateUserProfile($user_info)
  {
    $user_profile = "<table id='subprofile' class='noshow'>"
      . tableRow( tableCol(makeUserLink($user_info['uid'], $user_info['name']) . "</br>"
                           . fontSize($user_info['posts'], 2) . fontSize(" posts", 1) ) )
      . tableRow( tableCol(showImg($user_info['avatar'], "profile_img", "profile_img") ) )
      . tableRow( tableCol( fontSize($user_info['plus'], 2) . fontSize(" " . Karma::PLUSpl, 1) . "</br>"
                            . fontSize($user_info['minus'], 2) . fontSize(" " . Karma::MINUSpl, 1) ) )
      . "</table>";
    return $user_profile;
  }

  // Generate table of user details.
  function GenerateUserDetails($user_info)
  {
    $user_details = "<label id='msg'></label>";
    $user_details .= "<table class='noshow' id='prof_details'>"
      . tableRow( tableCol("email") . tableCol("<div id='email_field'>" . $user_info['email'] . "</div>") )
      . tableRow( tableCol("profile views") . tableCol($user_info['views']) )
      . tableRow( tableCol("birthday") . tableCol($user_info['birth']) )
      . tableRow( tableCol("joined on") . tableCol($user_info['join']) )
      . tableRow( tableCol("last login") . tableCol($user_info['t_online']) )
      . "</table>";

    return $user_details;
  }

  // Display a user's recent posts.
  function DisplayUserRecentPosts($user_info)
  {
    $posts = $this->db->GetUserRecentPosts($user_info['uid']);
    $post_links = "";
    foreach ($posts as $post)
    {
      $thread = $this->db->GetThread($post['tid']);
      $post_links .= $this->GetPostLink($post['pid'], $thread['title'])
        . " at " . GetTime(TIME_FULL, $post['time']) . ":   "
        . substr($post['content'], 0, 50);
      if (strlen($post['content']) > 50)
        $post_links .= "...";
      $post_links .= "</br>";
    }

    $recent_posts = tableRow( HTMLTag("th", "Recent Posts", array('colspan'=>2) ) )
      . tableRow( tableCol($post_links, array('colspan'=>2, 'class'=>'recent_posts')) );
    return $recent_posts;
  }

  // Display a user's recent karma activities.
  function DisplayUserRecentKarma($user_info)
  {
    // Karma given.
    $karma_actions = $this->db->GetUserRecentKarmaGiven($user_info['uid']);
    $karma_list = "";
    foreach ($karma_actions as $karma_action)
    {
      $post_meta = $this->db->GetPostMeta($karma_action['pid']);
      $thread = $this->db->GetThread($post_meta['tid']);
      $karma_list .= ($karma_action['type'] === "plus")? Karma::PLUSact : Karma::MINUSact;
      $recipient = $this->GetCachedUser($karma_action['puid']);
      $karma_list .= " " . makeLink("user.php?uid={$recipient['uid']}", $recipient['name']) . " in " . $this->GetPostLink($karma_action['pid'], $thread['title'])
        . " at " . GetTime(TIME_FULL, $karma_action['time']) . "</br>";
    }
    $recent_karma = tableRow( HTMLTag("th", "Recent Karma Given", array('colspan'=>2) ) )
      . tableRow( tableCol($karma_list, array('colspan'=>2, 'class'=>'recent_karma')) );

    // Karma received.
    $karma_actions = $this->db->GetUserRecentKarmaReceived($user_info['uid']);
    $karma_list = "";
    foreach ($karma_actions as $karma_action)
    {
      $post_meta = $this->db->GetPostMeta($karma_action['pid']);
      $thread = $this->db->GetThread($post_meta['tid']);
      $karma_list .= ($karma_action['type'] === "plus")? Karma::PLUSact : Karma::MINUSact;
      $giver = $this->GetCachedUser($karma_action['uid']);
      $karma_list .= " by " . makeLink("user.php?uid={$giver['uid']}", $giver['name']) . " in " . $this->GetPostLink($karma_action['pid'], $thread['title'])
        . " at " . GetTime(TIME_FULL, $karma_action['time']) . "</br>";
    }
    $recent_karma .= tableRow( HTMLTag("th", "Recent Karma Received", array('colspan'=>2) ) )
      . tableRow( tableCol($karma_list, array('colspan'=>2, 'class'=>'recent_karma')) );
    return $recent_karma;
  }

  // Cache user lookup from database.
  function GetCachedUser($uid)
  {
    $user_info = array();
    if (array_key_exists($uid, $this->user_cache))
      $user_info = $this->user_cache[$uid];
    else
      {
        $user_info = $this->db->GetUserProfile($uid);
        $this->user_cache[$uid] = $user_info;
      }
    return $user_info;
  }

  /*******************************\
   *                             *
   *      Create Functions       *
   *                             *
  \*******************************/

  /* Make a post. */
  function MakePost($content, $thread_id, $user_id)
  {
    if ($user_id < 0)
      return FALSE;
    return $this->db->NewPost($content, $thread_id, $user_id);
  }

  /* Make a thread. */
  function MakeThread($title, $content, $user_id)
  {
    if ($user_id < 0)
      return FALSE;
    return $this->db->NewThread($title, $content, $user_id);
  }

  /* Make a user. */
  function MakeUser($username, $email, $password, $birth)
  {

  }
}
?>