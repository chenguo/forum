<?php
ini_set('display_errors', 1); error_reporting(E_ALL | E_STRICT);
require_once("./include/defines.php");
require_once("./src/html.php");

/* Database interface class */
class DB
{
  var $db_handle;

  function DB($host, $user, $pwd, $db)
  {
    $connection = mysql_connect("$host", "$user", "$pwd") or die (mysql_error());
    $this->db_handle = mysql_select_db("$db", $connection) or die (mysql_error());
  }

  /*******************************\
   *                             *
   *       User Functions        *
   *                             *
  \*******************************/

  // Verify user against his password in the database.
  function VerifyUser($user, $pw, $update_time = FALSE)
  {
    $result = $this->__SelectFromTable(Tables::USERS, array('uid', 'password'), array("name=\"$user\""), 1);
    if (count($result) > 0 && $result[0]['password'] === md5($pw))
      {
        if ($update_time == TRUE)
          $this->UpdateUserTimestamp($result[0]['uid']);
        return $result[0]['uid'];
      }
    return -1;
  }

  // Check if a user ID is valid.
  function CheckUID($uid)
  {
    $result = $this->__SelectFromTable(Tables::USERS, array('uid'), array("uid=\"$uid\""), 1);
    if (count($result) > 0)
      return TRUE;
    return FALSE;
  }

  // Match a user's cookie to a username
  function CheckCookie($uid, $cookie)
  {
    $result = $this->__SelectFromTable(Tables::USERS, array('uid'), array("uid=\"$uid\"","password=\"$cookie\""), 1);
    if (count($result) > 0)
      return TRUE;
    return FALSE;
  }

  /* Update a user's time stamp. This should be invoked whenever a user loads a page. */
  function UpdateUserTimestamp($uid)
  {
    $time = GetTime(TIME_MYSQL);
    $this->__UpdateTable(Tables::USERS, array('t_online'=>"'$time'"), array("uid=$uid"));
  }

  /* Return user name matching passed in user id. */
  function GetUserName($user_id, $link=TRUE)
  {
    $result = $this->__SelectFromTable(Tables::USERS, array('name'), array("uid=\"$user_id\""), 1);
    if (count($result) > 0)
      {
        $name = $result[0]['name'];
        if ($link == TRUE)
          $name = UsrLink($user_id, $name);
        return $name;
      }
    return "";
  }

  // Get array of online users.
  function GetOnlineUsers($time_diff)
  {
    $users = array();
    $time = GetTime(TIME_MYSQL);
    $result = $this->__SelectFromTable(Tables::USERS, array('uid', 'name'), array("timestampdiff(MINUTE, t_online, '$time')<=$time_diff"));
    foreach ($result as $user)
      array_push($users, $user);
    return $users;
  }

  /* Get a user's profile from the database. */
  function GetUserProfile($uid, $full = FALSE, $incr_view = FALSE)
  {
    if ($incr_view === TRUE)
      $this->__UpdateTable(Tables::USERS, array("views"=>"views+1"), array("uid=$uid"));

    $fields = array('uid', 'name', 'posts', 'avatar', 't_online', 'plus', 'minus', 'signature');
    if ($full == TRUE)
      array_push($fields, 'email', 'birth', '`join`', 'views', 'posts_display', 'threads_display');
    $result = $this->__SelectFromTable(Tables::USERS, $fields, array("uid=$uid"), 1);
    if ($result)
      return $result[0];
    else
      return FALSE;
  }

  // Get the settings specific part of the user profile
  function GetUserSettings($uid)
  {
    $result = $this->__SelectFromTable(Tables::USERS, array('posts_display', 'threads_display'), array("uid=$uid"), 1);
    if ($result)
      return $result[0];
    else
      return FALSE;
  }

  // Update user's profile.
  function UpdateUserProfile($uid, $info_array)
  {
    if (isset($info_array['signature']))
      $info_array['signature'] = '"' . $this->insertPrep($info_array['signature']) . '"';

    if (count($info_array) > 0)
      $this->__UpdateTable(Tables::USERS, $info_array, array("uid=$uid"));
    $user_info = $this->GetUserProfile($uid, TRUE);
    return $user_info;
  }

  // Get the last post a user has viewed in a thread.
  function GetUserPostView($uid, $tid)
  {
    $pview_info = $this->__SelectFromTable(Tables::USRTHR, array("tpid", "pid"), array("uid=$uid", "tid=$tid"), 1);
    if (count($pview_info) == 0)
      return 0;
    else
      return $pview_info[0];
  }

  // Update the last post a user has viewed in a thread.
  function UpdateUserPostView($uid, $tid, $pid, $tpid)
  {
    // Check for given thread, what is the last post the user has viewed.
    $usrthr_info = $this->__SelectFromTable(Tables::USRTHR, array("tpid"), array("uid=$uid", "tid=$tid"), 1);

    // Only update post viewed if thread hasn't been viewed before, or stored last viewed
    // post is older than passed in post.
    if (count($usrthr_info) == 0)
      $this->__InsertIntoTable(Tables::USRTHR, array('uid'=>$uid, 'tid'=>$tid, 'pid'=>$pid, 'tpid'=>$tpid));
    else if ($usrthr_info[0]['tpid'] < $tpid)
      $this->__UpdateTable(Tables::USRTHR, array('pid'=>$pid, 'tpid'=>$tpid), array("uid=$uid", "tid=$tid"));
  }

  // Update user favorite status for a thread
  function UpdateUserThrFav($uid, $tid, $fav)
  {
    // For sanity, check current fav status.
    $set_fav = $this->__SelectFromTable(Tables::USRTHR, array("fav"), array("uid=$uid", "tid=$tid"), 1);

    // Post hasn't been touched by user yet. Add new entry.
    if (count($set_fav) == 0)
      $this->__InsertIntoTable(Tables::USRTHR, array('uid'=>$uid, 'tid'=>$tid, 'fav'=>$fav));
    // If favorite status is different, update.
    else if ($set_fav[0]['fav'] != $fav)
      $this->__UpdateTable(Tables::USRTHR, array('fav'=>$fav), array("uid=$uid", "tid=$tid"));
  }

  // Get user favorite threads.
  function GetUserFavThreads($uid)
  {
    // Get thread IDs of user's favorite threads.
    $threads = $this->__SelectFromTable(Tables::USRTHR, array("tid"), array("uid=$uid", "fav=1"));
    $tid_list = array();
    foreach ($threads as $thread)
      array_push($tid_list, $thread['tid']);

    // Construct query.
    $query = "SELECT * FROM " . Tables::THREADS . " where tid="  . implode ("||tid=", $tid_list)
      . " ORDER BY post_time DESC";
    $result = mysql_query($query) or die (mysql_error());
    $thread_list = array();
    while ($row = mysql_fetch_assoc($result))
      array_push($thread_list, $row);
    return $thread_list;
  }

  // Get a user's most recent posts.
  function GetUserRecentPosts($uid, $num_posts=10, $page=1)
  {
    $first_post = 0;
    $last_post = $first_post + $num_posts - 1;
    $result = mysql_query("SELECT * FROM " . Tables::POSTS . " WHERE uid=$uid ORDER BY time DESC LIMIT $first_post,$last_post") or die (mysql_error());
    $posts = array();
    while ($row = mysql_fetch_assoc($result))
      array_push($posts, $row);
    return $posts;
  }

  // Get a user's most recent karma given.
  function GetUserRecentKarmaGiven($uid, $num_acts=10, $page=1)
  {
    $first_act = 0;
    $last_act = $first_act + $num_acts - 1;
    $result = mysql_query("SELECT * FROM " . Tables::KARMA . " WHERE uid=$uid ORDER BY time DESC LIMIT $first_act,$last_act") or die (mysql_error());
    $karma = array();
    while ($row = mysql_fetch_assoc($result))
      array_push($karma, $row);
    return $karma;
  }

  // Get a user's most recent karma received.
  function GetUserRecentKarmaReceived($puid, $num_acts=10, $page=1)
  {
    $first_act = 0;
    $last_act = $first_act + $num_acts - 1;
    $result = mysql_query("SELECT * FROM " . Tables::KARMA . " WHERE puid=$puid ORDER BY time DESC LIMIT $first_act,$last_act") or die (mysql_error());
    $karma = array();
    while ($row = mysql_fetch_assoc($result))
      array_push($karma, $row);
    return $karma;
  }
  /*******************************\
   *                             *
   *      Thread Functions       *
   *                             *
  \*******************************/

  // Get the number of threads in forum.
  function GetNumThreads()
  {
    $result = $this->__SelectFromTable(Tables::THREADS, array("COUNT(tid)"), array());
    return $result[0]['COUNT(tid)'];
  }

  // Return array representing threads of the forum.
  function GetThreads($page = 1, $num_threads = 25)
  {
    $first_thread = ($page - 1) * $num_threads;

    $result = mysql_query("SELECT * FROM " . Tables::THREADS . " ORDER BY post_time DESC LIMIT $first_thread,$num_threads") or die (mysql_error());
    $threads = array();
    while ($row = mysql_fetch_assoc($result))
      array_push($threads, $row);
    return $threads;
  }

  // Return thread information matching given thread id.
  function GetThread($thread_id, $update_view = FALSE)
  {
    // Update viewcount.
    if ($update_view)
      {
        $data = array();
        $data['views'] = "views+1";
        $this->__UpdateTable(Tables::THREADS, $data, array("tid=$thread_id"));
      }
    $result = $this->__SelectFromTable(Tables::THREADS, array("*"), array("tid=$thread_id"), 1);
    return $result[0];
  }

  // Get thread title.
  function GetThreadTitle($thread_id)
  {
    $result = $this->__SelectFromTable(Tables::THREADS, array("title"), array("tid=$thread_id"), 1);
    return $result[0]['title'];
  }

  // Get number of posts in a thread.
  function GetThreadNumPosts($thread_id)
  {
    $result = $this->__SelectFromTable(Tables::THREADS, array("posts"), array("tid=$thread_id"), 1);
    return $result[0]['posts'];
  }

  // Update title of existing thread
  function UpdateThreadTitle($tid, $title)
  {
    $title = $this->insertPrep($title);
    $data = array('title'=>"\"$title\"");
    $this->__UpdateTable(Tables::THREADS, $data, array("tid=$tid"));
    return $this->GetThreadTitle($tid);
  }

  // Get thread's user favorite status
  function GetThreadUserFav($tid, $uid)
  {
    // Check if a thread is a user's favorite.
    $usrthr_info = $this->__SelectFromTable(Tables::USRTHR, array("fav"), array("uid=$uid", "tid=$tid"), 1);
    if (isset($usrthr_info[0]['fav']))
      return $usrthr_info[0]['fav'];
    else
      return FALSE;
  }

  /* Add a new thread to the database. */
  function NewThread($title, $content, $uid)
  {
    $tid = -1;

    /* Make sure the user ID is valid. */
    if(!$this->CheckUID($uid))
      {
        return -1;
      }

    try
      {
        $this->__BEGIN();

        $tid = $this->__NewThread($title, $content, $uid);

        $this->__COMMIT();
      }
    catch (Exception $e)
      {
        echo "Post error: {$e->getMessage()}</br>\n";
        $this->__ROLLBACK();
        return -1;
      }

    return $tid;
  }

  /*******************************\
   *                             *
   *       Post Functions        *
   *                             *
  \*******************************/

  // Return posts in given thread id. $THREAD being true denotes getting posts from a thread. Otherwise we're
  // getting posts by a user.
  function GetPosts($id, $page = 1, $num_posts = 25, $thread = TRUE)
  {
    $id_type = "tid";
    if ($thread == FALSE)
      $id_type = "uid";

    $first_post = ($page - 1) * $num_posts + 1;
    $last_post = $first_post + $num_posts - 1;
    $key_array = array("$id_type=\"$id\"", "tpid>=$first_post", "tpid<=$last_post");
    return $this->__SelectFromTable(Tables::POSTS, array("*"), $key_array);
  }

  // Get a single post by pid.
  function GetPost($pid)
  {
    $result = $this->__SelectFromTable(Tables::POSTS, array("*"), array("pid=$pid"), 1);
    return $result[0];
  }

  // Get post meta information.
  function GetPostMeta($pid)
  {
    $result = $this->__SelectFromTable(Tables::POSTS, array('tid', 'tpid', 'uid'), array("pid=$pid"), 1);
    return $result[0];
  }

  // Get posts poster uid.
  function GetPostUID($pid)
  {
    $result = $this->__SelectFromTable(Tables::POSTS, array('uid'), array("pid=$pid"), 1);
    return $result[0]['uid'];
  }

  // Get a post's karma stats.
  function GetPostKarma($pid)
  {
    $result = $this->__SelectFromTable(Tables::KARMA, array('*'), array("pid=$pid"));
    return $result;
  }

  // Give a post karma
  function AddPostKarma($type, $pid, $puid, $uid)
  {
    return $this->__AddKarma($type, $pid, $puid, $uid);
  }

  // Check if a user can alter the karma of a particular post
  function PostKarmaChangeAllowed($pid, $uid)
  {
    $result = $this->__SelectFromTable(Tables::KARMA, array('pid'), array("pid=$pid", "uid=$uid"), 1);
    if (count($result) > 0)
      return FALSE;
    return TRUE;
  }

  // Add a new post to the database.
  function NewPost($tid, $content, $uid)
  {
    /* Make the post. */
    $pid;
    try
      {
        $this->__BEGIN();
        $pid = $this->__NewPost($tid, $content, $uid, GetTime(TIME_MYSQL));
        $this->__COMMIT();
      }
    catch (Exception $e)
      {
        echo "Post error: {$e->getMessage()}</br>\n";
        $this->__ROLLBACK();
        return FALSE;
      }
    return $pid;
  }

  // Update post already in the database.
  function UpdatePost($content, $pid)
  {
    $data = array();
    $data['content'] = "\"" . $this->insertPrep($content) . "\"";
    $data['edit'] = "\"" . GetTime(TIME_MYSQL) . "\"";
    $this->__UpdateTable(Tables::POSTS, $data, array("pid=$pid"));
    return $this->GetPost($pid);
  }

  /* Escape characters for insertion into database. */
  function insertPrep($string)
  {
    // Newlines, tabs etc insert fine. The problematic cases are \, ', and "
    $string = preg_replace("/\\\\/", "\\\\\\\\", $string);
    $string = preg_replace("/\"/", "\\\"", $string);
    $string = preg_replace("/'/", "\\\'", $string);
    return $string;
  }

  /***********************\
   *                     *
   *   Chat Functions    *
   *                     *
  \***********************/

  // Get current chat sequence.
  function GetChatSeq()
  {
    $result = mysql_query("SELECT seq FROM " . Tables::CHAT . " ORDER BY time DESC LIMIT 1");
    if ($result)
      {
        $row = mysql_fetch_row($result);
        //echo "Get Chat Seq: {$row[0]}";
        return $row[0];
      }
  }

  // Get chat entries that came after a sequence number
  function GetChatText($seq)
  {
    $new_seq = $this->GetChatSeq();
    $text_str = "[seq:$new_seq]";
    $result;
    if($seq > -1)
      {
        // Get all posts where time is greater than time of the sequence number passed in.
        $result = mysql_query("SELECT * FROM " . Tables::CHAT . " WHERE time > (SELECT time FROM " . Tables::CHAT . " WHERE seq=$seq) ORDER BY time ASC") or throwException("Could not get chat msg: " . mysql_error());
      }
    $messages = array();
    while ($result && $row = mysql_fetch_assoc($result))
      array_push($messages, $row);
    return array($new_seq, $messages);
  }

  // Enter chat message into database.
  function SendChat($uid, $msg)
  {
    $name = $this->GetUserName($uid, FALSE);
    $seq = $this->GetChatSeq();
    $msg = $this->insertPrep($msg);

    if ($seq === -1 || $seq == CHAT_SEQ_MAX)
      $seq = 1;
    else
      $seq++;

    mysql_query("DELETE FROM " . Tables::CHAT . " WHERE seq=$seq");
    mysql_query("INSERT INTO " . Tables::CHAT . " VALUES ($seq, $uid, \"$name\", \"$msg\", NOW())");
    return $seq;
  }

  /***********************\
   *                     *
   * Database Interfaces *
   *                     *
  \***********************/

  /* Begin an atomic transaction. */
  function __BEGIN()
  {
    mysql_query("BEGIN") or die (mysql_error());
  }

  /* Commit an atomic transaction. */
  function __COMMIT()
  {
    mysql_query("COMMIT") or die (mysql_error());
  }

  /* Rollback an atomic transaction. */
  function __ROLLBACK()
  {
    mysql_query("ROLLBACK") or die (mysql_error());
  }

  // Update database for karma change
  function __AddKarma($type, $pid, $puid, $uid)
  {
    $time = GetTime(TIME_MYSQL);
    try
      {
        if ($type !== "plus" && $type !== "minus")
          throwException("Undefined karma type: $type");
        $result = mysql_query("SELECT pid FROM " . Tables::KARMA . " WHERE pid=$pid&&uid=$uid LIMIT 1") or throwException("Could not check karma table: " . mysql_error());
        if (mysql_num_rows($result) > 0)
          throwException("User already applied karma to this post");

        $this->__BEGIN();
        mysql_query("INSERT INTO " . Tables::KARMA . " (pid, uid, puid, type, time) VALUES ($pid, $uid, $puid, \"$type\", \"$time\")") or throwException(mysql_error());
        if ($type === "plus")
          mysql_query("UPDATE " . Tables::USERS . " SET `plus`=plus+1 WHERE uid=$puid") or throwException(mysql_error());
        else
          mysql_query("UPDATE " . Tables::USERS . " SET `minus`=minus+1 WHERE uid=$puid") or throwException(mysql_error());
        $this->__COMMIT();
      }
    catch (Exception $e)
      {
        $this->DBError($e->getMessage());
        $this->__ROLLBACK();
        return FALSE;
      }
    return TRUE;
  }

  /* Make database insertion to make new thread. */
  function __NewThread($title, $content, $uid)
  {
    $tid = $this->GetNumThreads() + 1;
    $time = GetTime(TIME_MYSQL);
    $title = $this->insertPrep($title);

    // Make the thread.
    mysql_query("INSERT INTO " . Tables::THREADS . " VALUES ($tid, \"$title\", $uid, 0, \"$time\", $uid, \"$time\", 0)") or throwException(mysql_error());

    /* Make the first post. */
    $this->__NewPost($tid, $content, $uid, $time);

    return $tid;
  }

  /* Add new post to the database. */
  function __NewPost($tid, $content, $uid, $time)
  {
    /* Get pid. */
    $result = mysql_query("SELECT COUNT(pid) FROM " . Tables::POSTS) or throwException("getting pid: " . mysql_error());
    $row = mysql_fetch_row($result);
    $pid = $row[0] + 1;
    $content = $this->insertPrep($content);

    /* Get post count in thread. */
    $result = mysql_query("SELECT posts FROM " . Tables::THREADS . " WHERE tid=$tid LIMIT 1") or throwException("getting tpid: " . mysql_error());
    $row = mysql_fetch_assoc($result);
    $tpid = $row['posts'] + 1;

    /* Make the post. */
    mysql_query("INSERT INTO " . Tables::POSTS . " VALUES ({$pid},{$tid},{$tpid},{$uid},\"$content\",\"{$time}\", null)") or throwException("making post: " . mysql_error());

    /* Update the thread with new post info. */
    mysql_query("UPDATE " . Tables::THREADS . " SET posts=posts+1, last_uid={$uid}, post_time=\"{$time}\" WHERE tid={$tid}") or throwException("update thread: " . mysql_error());

    /* Update user. */
    mysql_query("UPDATE " . Tables::USERS . " SET posts=posts+1 WHERE uid={$uid}") or throwException("update user: " . mysql_error());

    return $pid;
  }

  // Select entries from TABLE
  function __SelectFromTable($table, $col_array, $key_array, $limit=-1)
  {
    $columns = implode (", ", $col_array);
    $query = "SELECT $columns FROM $table";
    if (count($key_array) > 0)
      $query .= " WHERE " . implode ("&&", $key_array);
    if ($limit > 0)
      $query .= " LIMIT $limit";
    $result = mysql_query($query) or die (mysql_error() . ' ' . $query);
    $ret_array = array();
    while ($row = mysql_fetch_assoc($result))
      {
        array_push($ret_array, $row);
      }
    return $ret_array;
  }

  // Insert into table TABLE a new entry with the keys and values of DATA_ARRAY as the fields and their
  // respective values.
  function __InsertIntoTable($table, $data_array)
  {
    $columns = implode (", ", array_keys($data_array));
    $values = implode (", ", $data_array);

    try
      {
        $this->__BEGIN();
        mysql_query("INSERT INTO $table ($columns) VALUES ($values)") or throwException(mysql_error());
        $this->__COMMIT();
      }
    catch (Exception $e)
      {
        $this->DBError($e->getMessage());
        $this->__ROLLBACK();
        return FALSE;
      }
    return TRUE;
  }

  // Update table TABLE at an entry whose KEY field is VALUE with fields contained in DATA_ARRAY.
  function __UpdateTable($table, $data_array, $key_array)
  {
    $entries = "";
    foreach ($data_array as $entry_key => $entry_value)
      {
        if ($entries != "")
            $entries .= ", ";
        $entries .= "$entry_key=$entry_value";
      }
    $query = "UPDATE $table SET $entries";
    if (count($key_array) > 0)
      $query .= " WHERE " . implode("&&", $key_array);

    //echo "$query</br></br>";
    try
      {
        $this->__BEGIN();
        mysql_query($query) or throwException(mysql_error());
        $this->__COMMIT();
      }
    catch (Exception $e)
      {
        $this->DBError($e->getMessage());
        $this->__ROLLBACK();
        return FALSE;
      }

    return TRUE;
  }

  // Display error message.
  function DBError($msg)
  {
    echo "MySQL ERROR: $msg</br>\n";
  }
}
?>