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
   *       Board Information     *
   *                             *
  \*******************************/

  // Get board information for display.

  // Display threads in the forum.
  function GetBoardDisplayInfo($threads_per_page, $posts_per_page, $page=1)
  {
    $board_info = array();
    $formatted_threads = array();
    $uid = $this->session->GetUID();

    // Make a table for links to other pages and make thread link.
    $board_info['pages'] = $this->MakePageLinks ($page, $threads_per_page,
                                                 $this->db->GetNumThreads(),
                                                 Pages::BOARD."?");
    $board_info['new_thr'] = makeLink(Pages::MAKETHR, "new thread");

    $threads = $this->db->GetThreads($page, $threads_per_page);
    foreach ($threads as $thread)
      {
        array_push($formatted_threads, $this->GetThreadInfo($thread));
      }

    $board_info['threads'] = $formatted_threads;

    return $board_info;
  }

  // Make links to pages in a thread (for use below thread title)
  function MakeThreadPageLinks ($items_per_page, $max_items, $link)
  {
    $max_page = GetPageCount($max_items, $items_per_page);
    $page_links = "";
    if ($max_page > 1)
      $page_links .= makeLink("$link", "1", array('class'=>'thr_page_link')). " "
                              . makeLink("$link&page=2", "2", array('class'=>'thr_page_link'));
    if ($max_page > 2)
      $page_links .= " " . makeLink("$link&page=3", "3", array('class'=>'thr_page_link'));
    if ($max_page > 3)
      $page_links .= " " . makeLink("$link&page=4", "4", array('class'=>'thr_page_link'));
    if ($max_page > 4)
      $page_links .= " " . makeLink("$link&page=5", "5", array('class'=>'thr_page_link'));
    if ($max_page > 5)
      $page_links .= " " . makeLink("$link&page=$max_page", "last", array('class'=>'thr_page_link'));
    return $page_links;
  }

  /*******************************\
   *                             *
   *      Thread Information     *
   *                             *
  \*******************************/

  // For a particular user and thread, get notifications for user pertaining to that thread.
  function GetThreadFlags ($uid, $thread)
  {
    $flags = "";

    // Check the last post the user has viewed
    $user_post_view = $this->db->GetUserPostView($uid, $thread['tid']);
    $num_viewed = $user_post_view['tpid'];
    $posts_per_page = $this->session->posts_per_page;
    $page = floor($num_viewed / $posts_per_page) + 1;
    if ($num_viewed < $thread['posts'])
      {
        $flags .= makeLink(Pages::THREAD . "?tid={$thread['tid']}&page=$page#post{$user_post_view['pid']}", "new");
      }
    return $flags;
  }

  // Get thread title.
  function GetThreadTitle($tid)
  {
    $thread = $this->db->GetThread($tid, FALSE /* Only title */);
    return $thread['title'];
  }

  // Get basic thread information for display of list of threads
  function GetThreadInfo($thread)
  {
    $thread_info = array();
    $posts_per_page = $this->session->posts_per_page;

    $thread_info['link'] = makeLink(Pages::THREAD."?tid={$thread['tid']}", $thread['title'], array('class'=>'thread'));
    $thread_info['pages'] = $this->MakeThreadPageLinks($posts_per_page, $thread['posts'], Pages::THREAD."?tid={$thread['tid']}");
    $thread_info['flags'] = $this->GetThreadFlags($this->session->GetUID(), $thread);
    $thread_info['create_time'] = GetTime(TIME_FULL, $thread['create_time']);
    $thread_info['post_time'] = GetTime(TIME_FULL, $thread['post_time']);
    $thread_info['posts'] = $thread['posts'];
    $thread_info['views'] = $thread['views'];
    $thread_info['creator'] = $this->db->GetUserName($thread['uid']);
    $thread_info['last_poster'] = $this->db->GetUserName($thread['last_uid']);

    return $thread_info;
  }

  // Get thread information for display.
  function GetThreadDisplayInfo($tid, $posts_per_page=DEFAULT_ITEMS_PER_PAGE, $page=1)
  {
    $thread_info = array();
    $formatted_posts = array();

    // Get thread and list of post infos
    $thread = $this->db->GetThread($tid, TRUE /* update viewcount */);
    $posts = $this->db->GetPosts($tid, $page, $posts_per_page);
    $uid = $this->session->GetUID();

    // Format post info for output.
    foreach ($posts as $post)
      {
        array_push($formatted_posts, $this->FormatPost($post));
      }
    // Mark user as at least having read the last post on current page.
    $last_post = end($posts);
    $this->db->UpdateUserPostView($uid, $tid, $last_post['pid'], $last_post['tpid']);

    // Favorite status.
    $fav = "";
    if ($this->db->GetThreadUserFav($tid, $uid))
      {
        $fav = makeLink("javascript:void(0)",
                        showImg("/imgs/site/star_filled.png", array('class'=>'favicon', 'onclick'=>"threadMarkFav(0,$uid,$tid)")));
      }
    else
      {
        $fav = makeLink("javascript:void(0)",
                        showImg("/imgs/site/star_empty.png", array('class'=>'favicon', 'onclick'=>"threadMarkFav(1,$uid,$tid)")));
      }

    // Populate thread info for display
    $thread_info['title'] = $thread['title'];
    $thread_info['board'] = makeLink(Pages::BOARD, "board");
    $thread_info['pages'] = $this->MakePageLinks($page, $posts_per_page, $thread['posts'], Pages::THREAD."?tid=$tid");
    $thread_info['posts'] = $formatted_posts;
    $thread_info['fav'] = $fav;
    return $thread_info;
  }

  // Format post from database to display format
  function FormatPost($post)
  {
    $formatted_post = array();
    $formatted_post['pid'] = $post['pid'];
    $formatted_post['uid'] = $post['uid'];
    $formatted_post['content'] = prepContent($post['content'], TRUE);
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
          $page_links .= makeLink("$link&page=1", "<<", array('class'=>'page_link')) . "  ";
        if ($page > 2)
          $page_links .= makeLink("$link&page=" . ($page-2), $page - 2, array('class'=>'page_link')) . " ";
        if ($page > 1)
          $page_links .= makeLink("$link&page=" . ($page-1), $page - 1, array('class'=>'page_link')) . " ";
        $page_links .= "$page";
        if ($page < $max_page)
          $page_links .= " " . makeLink("$link&page=" . ($page+1), $page + 1, array('class'=>'page_link'));
        if ($page < $max_page - 1)
          $page_links .= " " . makeLink("$link&page=" . ($page+2), $page + 2, array('class'=>'page_link'));
        if ($page < $max_page - 2)
          $page_links .= "  " . makeLink("$link&page=" . $max_page, ">>", array('class'=>'page_link'));
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
          array_push($plus_names, makeLink(Pages::USER."?uid={$user_info['uid']}", $user_info['name']));
        else
          array_push($minus_names, makeLink(Pages::USER."?uid={$user_info['uid']}", $user_info['name']));
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
  function GetPostLink($pid, $link_text)
  {
    $post = $this->db->GetPostMeta($pid);
    $page = GetPageCount($post['tpid'], $this->session->posts_per_page);
    $link = makeLink(Pages::THREAD."?tid={$post['tid']}&page=$page#post$pid", $link_text);
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
    $sidebar_info['board'] = makeLink(Pages::BOARD, "board");
    $sidebar_info['bookmark'] = makeLink(Pages::BOARD, "bookmarks");
    $sidebar_info['privmsg'] = makeLink(Pages::BOARD, "messages");
    $sidebar_info['cur_users'] = $cur_usr_str;
    $sidebar_info['day_users'] = $day_usr_str;
    $sidebar_info['logout'] = makeLink(Pages::ACTION."?action=logout", "logout");
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

  // Display a user's recent posts.
  function GenerateUserRecentPosts($uid)
  {
    $posts = $this->db->GetUserRecentPosts($uid);
    $recent_posts = array();
    foreach ($posts as $post)
    {
      $post_array = array();
      $thread = $this->db->GetThread($post['tid']);
      $post_array['post'] = $this->GetPostLink($post['pid'], $thread['title']);
      $post_array['time'] = GetTime(TIME_FULL, $post['time']);
      $post_array['content'] = substr($post['content'], 0, 50);
      if (strlen($post['content']) > 50)
        $post_array['content'] .= "...";
      array_push($recent_posts, $post_array);
    }
    return $recent_posts;
  }

  // Generate user's recent karma given history
  function GenerateUserRecentKarmaGiven($uid)
  {
    $karma_actions = $this->db->GetUserRecentKarmaGiven($uid);
    $recent_karma_given = array();

    foreach ($karma_actions as $karma_action)
      {
        $karma_action_array = array();

        $post_meta = $this->db->GetPostMeta($karma_action['pid']);
        $thread = $this->db->GetThread($post_meta['tid']);
        $recip = $this->GetCachedUser($karma_action['puid']);

        $karma_action_array['action'] = ($karma_action['type'] === "plus")? Karma::PLUSact : Karma::MINUSact;
        $karma_action_array['recip'] = makeLink(Pages::USER."?uid={$recip['uid']}", $recip['name']);
        $karma_action_array['thread'] = $this->GetPostLink($karma_action['pid'], $thread['title']);
        $karma_action_array['time'] = GetTime(TIME_FULL, $karma_action['time']);

        array_push($recent_karma_given, $karma_action_array);
      }

    return $recent_karma_given;
  }

  // Generate user's recent karma given history
  function GenerateUserRecentKarmaRecvd($uid)
  {
    $karma_actions = $this->db->GetUserRecentKarmaReceived($uid);
    $recent_karma_recvd = array();

    foreach ($karma_actions as $karma_action)
      {
        $karma_action_array = array();

        $post_meta = $this->db->GetPostMeta($karma_action['pid']);
        $thread = $this->db->GetThread($post_meta['tid']);
        $recip = $this->GetCachedUser($karma_action['puid']);

        $karma_action_array['action'] = ($karma_action['type'] === "plus")? Karma::PLUSact : Karma::MINUSact;
        $karma_action_array['recip'] = makeLink(Pages::USER."?uid={$recip['uid']}", $recip['name']);
        $karma_action_array['thread'] = $this->GetPostLink($karma_action['pid'], $thread['title']);
        $karma_action_array['time'] = GetTime(TIME_FULL, $karma_action['time']);

        array_push($recent_karma_recvd, $karma_action_array);
      }

    return $recent_karma_recvd;
  }

  // Construct a list of the user's favorite threads.
  function GenerateUserFavorites($uid)
  {
    $threads_per_page = $this->session->threads_per_page;
    $tid_list = $this->db->GetUserFavThreads($uid);
    $formatted_threads = array();

    foreach($tid_list as $tid)
      {
        $thread_info = $this->db->GetThread($tid);
        array_push($formatted_threads, $this->GetThreadInfo($thread_info));
      }

    return $formatted_threads;
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