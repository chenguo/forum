<?php
ini_set('display_errors', 1); error_reporting(E_ALL | E_STRICT);
require_once("./include/defines.php");
include_once("./include/common_cfg.php");

if (!isset($_REQUEST['action']))
  {
    exit();
  }

$action = $_REQUEST['action'];

// First check for logout
if ($action === "logout")
  {
    $session->Logout();
    header("Location: index.php");
    exit();
  }

// This page requires login access. We need both pid and uid, and they must be equal.
if ($session->CheckLogin())
  {
    if (0 > ($uid = $session->GetUID()))
      {
        echo 0;
        exit();
      }

    /* Handle post submission. */
    if ($action === "post" && isset($_POST['content']) && strlen($_POST['content']) > 0
        && isset($_POST['tid']) && $_POST['tid'] > 0)
      {
        $forum->MakePost($_POST['tid'], $_POST['content'], $uid);
        $posts_per_page = DEFAULT_ITEMS_PER_PAGE;
        $num_posts = $db->GetThreadNumPosts($_POST['tid']);
        $last_page = GetPageCount($num_posts, $posts_per_page);
        header("LOCATION: thread.php?tid={$_POST['tid']}&page=$last_page");
        exit();
      }
    // Preview new post
    else if ($action === "new_post_preview" && isset($_POST['content']) && isset($_POST['tid']))
      {
        $post_info = array('pid'=>'0',
                           'uid'=>"$uid",
                           'content'=>prepContent($_POST['content'], $_POST['tid']),
                           'controls'=>"",
                           'time'=>"",
                           'edit'=>"",
                           'karma'=>array('plus_karma'=>'', 'minus_karma'=>''));
        echo $display->GeneratePost($post_info);
        exit();
      }
    // Get chat messages
    else if ($action === "chatGet" && isset($_REQUEST['seq']))
      {
        $seq = $_REQUEST['seq'];
        $msg_info = $db->GetChatText($seq);
        $new_seq = $msg_info[0];
        $messages = $msg_info[1];
        $text_seq = "[seq:$new_seq]";
        $text_str = "";
        foreach ($messages as $message)
          {
            $user_font_class = ($message['uid'] == $uid) ?
              "chatmsg_user" : "chatmsg_name";

            $time_str = GetTime(TIME_CHAT, $message['time']);
            $text_str .= HTMLTag("font", $message['name'], array('class'=>$user_font_class))
              . HTMLTag("font", " ($time_str):", array('class'=>'chatmsg_time'))
              . HTMLTag("font", " " . $message['text'], array('class'=>'chatmsg_text'))
              . "</br>";
          }
        $session->AppendChatText($text_str);
        echo $text_seq . $text_str;
        exit();
      }
    // Send chat message
    else if ($action === "chatSend" && isset($_REQUEST['text']))
      {
        $new_seq = $db->SendChat($uid, $_REQUEST['text']);
        //echo "Chat send new req: $new_seq\n";
        exit();
      }

    // All other actions require a pid.
    $pid;
    if (isset($_REQUEST['pid']))
      {
        $pid = $_REQUEST['pid'];
        $post_uid = $db->GetPostUID($pid);
        if ($post_uid < 0)
          exit();
      }
    else
      exit();

    // Apply karma
    if ($action === "karma_plus" || $action === "karma_minus")
      {
        // Ensure poster and user are different.
        if ($_GET['puid'] == $uid)
          {
            echo "0";
          }
        else
          {
            $type = ($action === "karma_plus")? "plus" : "minus";
            if (TRUE == $db->AddPostKarma($type, $_GET['pid'], $_GET['puid'], $uid))
              echo "1";
            else
              echo "0";
          }
      }
    // Quoting
    else if ($action === "quote")
      {
        $post = $db->GetPost($pid);
        $author = $db->GetUserName($post['uid'], FALSE);
        echo "[quote author=$author pid={$post['pid']} tpid={$post['tpid']}]{$post['content']}[/quote]\n";
      }
    // Editing post
    else if (($action === "edit_edit" || $action === "edit_preview" || $action === "edit_cancel"
              || $action === "edit_submit")
             && ($post_uid == $uid))
      {
        // Some actions are only available if the user is the author of the post being
        // operated on.
        $post = $db->GetPost($pid);
        $tid = $post['tid'];

        // If this was an edit submission, update in DB.
        if ($action === "edit_cancel")
          {
            echo prepContent($post['content'], $tid);
          }
        else if ($action === "edit_submit" && isset($_POST['content']))
          {
            $post = $db->UpdatePost($_POST['content'], $_POST['pid']);
            $edit_time = fontsize("edited " . GetTime(TIME_FULL, $post['edit']), 1);
            echo "[edit:$edit_time]";
            echo prepContent($_POST['content'], $tid);
          }
        else
          {
            $content;

            // Display post content. If preview, display submitted content instead.
            if ($action === "edit_edit")
              $content = $post['content'];
            else if ($action === "edit_preview")
              $content = $_POST['content'];
            else
              return;

            echo prepContent($content, $tid);
            echo "</br></br><form name='edit'>"
              . "<textarea class='edit_text' rows='10' cols='80' name='content' id='edit$pid'>$content</textarea>"
              . "</br></form>"
              . "<center>"
              . makeButton("submit", array('onclick'=>"editPost($pid, \"edit_submit\")"))
              . " " . makeButton("preview", array('onclick'=>"editPost($pid, \"edit_preview\")"))
              . " " . makeButton("cancel", array('onclick'=>"editPost($pid, \"edit_cancel\")"))
              . "</center>";
          }
      }
  }

?>