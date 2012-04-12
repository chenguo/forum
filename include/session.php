<?php

class Session
{
  var $session_var = "21d6f40cfb511982e4424e0e250a9557";
  var $db;
  var $uid = -1;

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
    else if (isset($_COOKIE['lolbros']) && isset($_COOKIE['lolbros_uid']))
      {
        if ($this->db->CheckCookie($_COOKIE['lolbros_uid'], $_COOKIE['lolbros']))
          {
            $this->uid = $_COOKIE['lolbros_uid'];
            $_SESSION[$this->session_var] = $_COOKIE['lolbros_uid'];
            $ret = TRUE;
          }
      }

    if ($ret && $update)
      $this->db->UpdateUserTimestamp($_SESSION[$this->session_var]);

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
        $_SESSION['chat'] = "";
        // Store for 60 days
        if ($cookie)
          {
            setcookie('lolbros_uid', $uid, time() + 5184000);
            setcookie('lolbros', md5($pw), time() + 5184000);
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
        return $this->db->GetUserName($_SESSION[$this->session_var], FALSE);
      }
    else
      return -1;
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
    setcookie('lolbros_uid', 0, 1);
    setcookie('lolbros', 0, 1);
    session_unset();
    session_destroy();
  }
}

?>