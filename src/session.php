<?php

class Session
{
  var $session_var = "21d6f40cfb511982e4424e0e250a9557";
  var $db;
  var $uid = -1;
  var $posts_per_page = DEFAULT_ITEMS_PER_PAGE;
  var $threads_per_page = DEFAULT_ITEMS_PER_PAGE;

  function Session($db_handle)
  {
    $this->db = $db_handle;
    $this->uid = -1;
    session_start();
  }

  // Check if a user is logged in. If not, send him to the login page.
  function CheckLogin($update = FALSE)
  {
    $ret = FALSE;
    if (isset($_SESSION[$this->session_var]))
    {
      $this->uid = $_SESSION[$this->session_var];
      $ret = TRUE;
    }
    else if (isset($_COOKIE['pw']) && isset($_COOKIE['uid']))
    {
      if ($this->db->CheckCookie($_COOKIE['uid'], $_COOKIE['pw']))
      {
        $this->uid = $_COOKIE['uid'];
        $_SESSION[$this->session_var] = $_COOKIE['uid'];
        $ret = TRUE;
      }
    }

    if ($this->uid < 0)
      $ret = FALSE;
    if ($ret)
    {
      // Get user settings
      $settings = $this->db->GetUserSettings($this->uid);
      $this->posts_per_page = $settings['posts_display'];
      $this->threads_per_page = $settings['threads_display'];

      if ($update)
        $this->db->UpdateUserTimestamp($_SESSION[$this->session_var]);
    }

    return $ret;
  }

  /* Log user into the forum. */
  function Login($user, $pw, $cookie = FALSE)
  {
    $uid = $this->db->VerifyUser($user, $pw, TRUE);
    if ($uid >= 0)
    {
      $this->uid = $uid;
      $_SESSION[$this->session_var] = $uid;
      $_SESSION['uid'] = $uid;
      $_SESSION['chat'] = "";
      $_SESSION['user'] = $user;
      // Store for 356 days
      if ($cookie && !isset($__COOKIE['pw']))
      {
        setcookie('uid', $uid, time() + 31536000);
        setcookie('user', $user, time() + 31536000);
        setcookie('pw', md5($pw), time() + 31536000);
      }
      return TRUE;
    }
    else
      $this->Logout();
    return FALSE;
  }

  // Check password of current user.
  function CheckPassword ($pw)
  {
    $name = $this->GetUserName();
    if ($name !== -1)
      {
        $uid = $this->db->VerifyUser($name, $pw);
        if ($uid == $_SESSION[$this->session_var])
          return TRUE;
      }
    return FALSE;
  }

  // Return the session owner's UID.
  function GetUID()
  {
    if (isset($_SESSION[$this->session_var]))
      return $_SESSION[$this->session_var];
    else
      return -1;
  }

  /* Return the session owner's user name */
  function GetUserName()
  {
    if (isset($_SESSION[$this->session_var]))
      {
        return $_SESSION['user'];
        //return $this->db->GetUserName($_SESSION[$this->session_var], FALSE);
      }
    else
      return "";
  }

  // Append chat messages to a session
  function AppendChatText($text)
  {
    $_SESSION['chat'] .= $text;
  }

  // Get saved chat text
  function GetChatText()
  {
    //return $_SESSION['chat'];
    return "Chat currently disabled";
  }

  /* Log user out. */
  function Logout()
  {
    $this->uid = -1;
    setcookie('uid', 0, 1);
    setcookie('user', 0, 1);
    setcookie('pw', 0, 1);
    session_unset();
    session_destroy();
  }
}

?>