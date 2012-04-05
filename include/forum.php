<?php
require_once("./include/defines.php");
require_once("./include/db.php");
require_once("./include/session.php");
ini_set('display_errors', 1); error_reporting(E_ALL | E_STRICT);

class Forum
{
  var $title;
  var $db;
  var $session;
  var $version;
  var $cur_users;
  var $day_users;
  var $user_cache;

  function Forum($db, $session)
  {
    //$this->title = $title;
    //$this->version = $version;
    $this->db = $db;
    $this->session = $session;
    $this->user_cache = array();
  }

  /*******************************\
   *                             *
   *   Common field generators   *
   *                             *
  \*******************************/

  function GetOnlineUsers($time)
  {
    return $this->db->GetOnlineUsers($time);
  }

  /*******************************\
   *                             *
   *      Thread Information     *
   *                             *
  \*******************************/

  // Get thread title
  function GetThreadTitle($tid)
  {
    $thread = $this->db->GetThread($tid, FALSE /* Only title */);
    return $thread['title'];
  }

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

  // Get thread information.
  function GetThreadInfo($tid, $posts_per_page=DEFAULT_ITEMS_PER_PAGE, $page=1)
  {
    $thread_info = array();
    $formatted_posts = array();

    $thread = $this->db->GetThread($tid, TRUE /* update viewcount */);
    $posts = $this->db->GetPosts($tid, $page, $posts_per_page);

    // Format post info for output.
    foreach ($posts as $post)
      {
        array_push($formatted_posts, $this->FormatPost($post));
      }
    // Mark user as at least having read the last post on current page.
    $last_post = end($posts);
    $this->db->UpdateUserPostView($this->session->GetUID(), $tid, $last_post['pid'], $last_post['tpid']);


    $thread_info['title'] = $thread['title'];
    $thread_info['board'] = makeLink("threads.php", "board");
    $thread_info['pages'] = $this->MakePageLinks($page, $posts_per_page, $thread['posts'], "thread.php?tid=$tid");
    $thread_info['posts'] = $formatted_posts;
    return $thread_info;
  }

  // Format post from database to display format
  function FormatPost($post)
  {
    $formatted_post = array();
    $formatted_post['pid'] = $post['pid'];
    $formatted_post['uid'] = $post['uid'];
    $formatted_post['content'] = prepContent($post['content'], $post['tid']);
    $formatted_post['controls'] = $this->GetPostControls($post);
    $formatted_post['time'] = $this->GetPostTime($post);
    $formatted_post['karma'] = $this->GetPostKarma($post);

    return $formatted_post;
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

  // Post karma
  function GetPostKarma($post)
  {
    $post_karma = $this->db->GetPostKarma($post['pid']);
    $karma_info = array('plus_karma'=>'', 'minus_karma'=>'');
    $plus_names = array();
    $minus_names = array();

    // For all the karma applied to the post, find the user and organize into postive and
    // negative karma.
    foreach ($post_karma as $karma)
      {
        $user_info = $this->GetCachedUser($karma['uid']);
        if ($karma['type'] === 'plus')
          array_push($plus_names, makeLink("user.php?uid={$user_info['uid']}", $user_info['name']));
        else
          array_push($minus_names, makeLink("user.php?uid={$user_info['uid']}", $user_info['name']));
      }

    // Assemble positive and negative karma lists.
    if (0 < count($plus_names))
      {
        $karma_info['plus_karma'] = Karma::PLUSact . " by: " . implode(", ", $plus_names);
      }
    if (0 < count($minus_names))
      {
        $karma_info['minus_karma'] = Karma::MINUSact . " by: " . implode(", ", $minus_names);
      }
    return $karma_info;
  }

  // Post times
  function GetPostTime($post)
  {
    // Post times.
    $edit_time = "";
    if (isset($post['edit']) && $post['edit'] != 0)
      {
        $edit_time = "edited " . GetTime(TIME_FULL, $post['edit']);
      }
    // Edit time needs an id, since it can change dynamically.
    $edit_time = HTMLTag("label", $edit_time, array('id'=>"edittime{$post['pid']}"));
    $post_time = "posted " . GetTime(TIME_FULL, $post['time']);

    return $edit_time . "</br>" . $post_time;
  }

  // Post action controls
  function GetPostControls($post)
  {
    $session_id = $this->session->GetUID();

    // Allow users to edit their own posts.
    $post_controls = "";
    if ($session_id == $post['uid'])
      {
        if ($post['tpid'] == 1)
          $post_controls .= makeButton("edit", array('onclick'=>"editPost({$post['pid']}, \"edit_edit\", 1)"));
        else
          $post_controls .= makeButton("edit", array('onclick'=>"editPost({$post['pid']}, \"edit_edit\", 0)"));
      }

    // If user hasn't modified karma of this post yet, display karma buttons.
    else if ($this->db->PostKarmaChangeAllowed($post['pid'], $session_id))
      {
        $post_controls .= makeButton(Karma::PLUS, array('onclick'=>"karma(\"karma_plus\", {$post['pid']}, {$post['uid']})"))
          . " " . makeButton(Karma::MINUS, array('onclick'=>"karma(\"karma_minus\", {$post['pid']}, {$post['uid']})"));
      }

    $post_controls .= " " . makeButton("quote", array('onclick'=>"quotePost({$post['pid']})"));
    return $post_controls;
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

  // Get sidebar contents
  function GetSidebarInfo()
  {
    $sidebar_info = array();

    // Get lists of active users currently and in past day
    $this->cur_users = $this->GetOnlineUsers(15);
    $this->day_users = $this->GetOnlineUsers(1440);
    $cur_user_links = array();
    foreach ($this->cur_users as $user)
      array_push ($cur_user_links, makeUserLink($user['uid'], $user['name']));
    $cur_usr_str = "online users</br>";
    $cur_usr_str .= (count($cur_user_links) == 0)? "none" : implode(", ", $cur_user_links);

    $day_user_links = array();
    foreach ($this->day_users as $user)
      array_push ($day_user_links, makeUserLink($user['uid'], $user['name']));
    $day_usr_str = "users in past day</br>";
    $day_usr_str .= (count($day_user_links) == 0)? "none" : implode(", ", $day_user_links);

    $sidebar_info['welcome'] =
      "Welcome</br>"
      . makeUserLink($this->session->GetUID(), $this->session->GetUserName())
      . "!";
    $sidebar_info['chat'] = $this->GenerateChat();
    $sidebar_info['board'] = makeLink("threads.php", "board");
    $sidebar_info['bookmark'] = makeLink("threads.php", "bookmarks");
    $sidebar_info['privmsg'] = makeLink("threads.php", "private messages");
    $sidebar_info['cur_users'] = $cur_usr_str;
    $sidebar_info['day_users'] = $day_usr_str;
    $sidebar_info['logout'] = makeLink("action.php?action=logout", "logout");
    $sidebar_info['version'] = "LOLBros beta " . makeLink("changelog.txt", "v" . VERSION);

    return $sidebar_info;
  }

  // Generate chat
  function GenerateChat()
  {
    $chat = "chat</br>"
      . HTMLTag("div", $this->session->GetChatText(), array('id'=>'sidebar_chat_msgs'))
      . HTMLTag("form",
                HTMLTag("textarea", "",
                        array('rows'=>'2', 'name'=>'chat_post',
                              'id'=>'chat_post',  'onkeyup'=>'sendKey(event)'))
                ,
                array('name'=>'chat_input', 'id'=>'chat_input'));
    return $chat;
  }

  /*******************************\
   *                             *
   *        User Functions       *
   *                             *
  \*******************************/

  // Get user information
  function GetUserInfo($uid)
  {
    return $this->db->GetUserProfile($uid,
                                     FALSE,  // only get basic info
                                     FALSE); // don't increment profile view count
  }

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
        echo makeButton("edit profile", array('onclick'=>"editProfile(\"edit\")"));
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
      . tableRow( tableCol(showImg($user_info['avatar'], array('class'=>'profile_img', 'id'=>'profile_img') ) ) )
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