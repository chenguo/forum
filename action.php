<?php
ini_set('display_errors', 1); error_reporting(E_ALL | E_STRICT);
require_once("./include/defines.php");
include_once("./include/common_cfg.php");

if (!isset($_REQUEST['action']))
  {
    exit();
  }

$action = $_REQUEST['action'];

// First check for logout/login
if ($action === "logout")
  {
    $session->Logout();
    header("Location: ".Pages::LOGIN);
    exit();
  }
else if ($action === "login")
  {
    if (isset($_POST['username']) && isset($_POST['password']))
      {
        $cookie = FALSE;
        if (isset($_POST['cookie']))
          $cookie = TRUE;

        if ($session->Login($_POST['username'], $_POST['password'], $cookie))
          {
            header("Location: ".Pages::LOGIN);
            exit();
          }
      }
    header("Location: ".Pages::LOGIN);
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

    // Get chat messages
    if ($action === "chatGet" && isset($_REQUEST['seq']))
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
    // User profile: view recent activities
    else if ($action === "usrp_prof" && isset($_POST['uid']))
      {
        $user_info = $db->GetUserProfile($_POST['uid'], TRUE, FALSE);
        echo $display->GenerateUserDetails($user_info);
        exit();
      }
    else if ($action === "usrp_edit" && $uid === $session->GetUID())
      {
        $user_info = $db->GetUserProfile($uid, TRUE, FALSE);
        echo $display->GenerateUserSettings($user_info);
        echo $display->GenerateUserPWChange($user_info);
        exit();
      }
    else if ($action === "usrp_recent" && isset($_POST['uid']))
      {
        echo $display->GenerateUserRecentPosts($_POST['uid']);
        echo $display->GenerateUserRecentKarma($_POST['uid'], 0); // 0 for given
        echo $display->GenerateUserRecentKarma($_POST['uid'], 1); // 1 for received
        exit();
      }
    // User profile update
    else if (($action === "usrp_save" || $action === "usrp_cancel")
             && $uid === $session->GetUID())
      {
        // Get set of updated information
        if ($action === "usrp_save")
          {
            $new_user_info = array();
            if (isset($_POST['email']))
              $new_user_info['email'] = "\"" . $_POST['email'] . "\"";
            if (isset($_POST['thr_disp']))
              $new_user_info['threads_display'] = $_POST['thr_disp'];
            if (isset($_POST['post_disp']))
              $new_user_info['posts_display'] = $_POST['post_disp'];
            if (isset($_POST['avatar']))
              $new_user_info['avatar'] = "\"" . $_POST['avatar'] . "\"";
            if (isset($_POST['sig']))
              {
                $new_user_info['signature'] = $_POST['sig'];
              }
            $user_info = $db->UpdateUserProfile($uid, $new_user_info);
          }
        else // user_prof_cancel
          {
            $user_info = $db->GetUserProfile($uid, TRUE, FALSE);
          }

        echo json_encode(array('email'=>$user_info['email'],
                               'avatar'=>$user_info['avatar'],
                               'post_disp'=>$user_info['posts_display'],
                               'thr_disp'=>$user_info['threads_display'],
                               'sig'=>$user_info['signature']));
        exit();
      }
    else if ($action === "usrp_pw_change" && isset($_POST['cur_pw']) && isset($_POST['new_pw']))
      {
        if ($session->CheckPassword($_POST['cur_pw']))
          {
            $update_fields = array('password'=>"\"" . md5($_POST['new_pw']) . "\"");
            $user_info = $db->UpdateUserProfile($uid, $update_fields);
            // 0 for success
            echo "0";
          }
        else
          {
            // 1 for authentication failure
            echo "1";
          }
        exit();
      }
    else if ($action === "usrp_msgs")
      {
        echo $display->GenerateUserPrivateMessages($uid);
        exit();
      }
    else if ($action === "usrp_fav")
      {
        echo $display->GenerateUserFavorites($uid);
        exit();
      }
    else if ($action === "thrMarkFav" && isset($_POST['fav']) && isset($_POST['tid']))
      {
        // Failures will be exceptions, so assume this succeeds.
        $db->UpdateUserThrFav($uid, $_POST['tid'], $_POST['fav']);
        echo "1";
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
              echo makeUserLink($uid, $session->GetUserName());
            else
              echo "0";
          }
      }
  }
?>