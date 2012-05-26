<?php
/* DEBUG: Error reporting */
ini_set('display_errors', 1); error_reporting(E_ALL | E_STRICT);

final class Title
{
  const COMMON = 0;
  const THREAD = 1;
  const USER = 2;
}

final class Karma
{
  const PLUS = "brofist";
  const PLUSpl = "brofists";
  const MINUS = "bitchslap";
  const MINUSpl = "bitchslaps";
  const PLUSact = "brofisted";
  const MINUSact = "bitchslapped";
}

final class FileMode
{
  const PROFILE = 0;
  const ATTACH = 1;
}

final class FileType
{
  const FILE = 0;
  const LINK = 1;
}

final class Tables
{
  const CHAT = "chat";
  const POSTS = "posts";
  const THREADS = "threads";
  const USERS = "users";
  const PVIEW = "post_view";
  const KARMA = "karma";
}

final class Pages
{
  const LOGIN = "index.php";
  const ACTION = "action.php";
  const BOARD = "board.php";
  const THREAD = "thread.php";
  const MAKETHR = "makethread.php";
  const USER = "user.php";
}

final class Profile
{
  const PROFILE = 1;
  const EDIT_PROF = 2;
  const RECENT = 3;
  const MSG = 4;
}

define("CHAT_SEQ_MAX", 1024);
define("BOARD_NAME", "LOL Bros, LOL");
define("VERSION", 0.62);
define("COMMON_CSS", "<link href='/css/common.css' type='text/css' rel='stylesheet'>\n");
define("COMMON_JQUERY", "<script type='text/javascript' src='http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js'></script>");
define("TIME_FULL", "g:i a M/j/Y");
define("TIME_MYSQL", "Y-m-d H:i:s");
define("TIME_CHAT", "g:i:s");
define("DEFAULT_ITEMS_PER_PAGE", 20);