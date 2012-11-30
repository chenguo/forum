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
  const USRTHR = "usrthr";
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
  const PROFILE = "prof";
  const EDIT_PROF = "edit";
  const RECENT = "recent";
  const FAV = "fav";
  const MSG = "msg";
}

final class CSS
{
  const COMMON = 'css/common.css';
  const INDEX = 'css/index.css';
  const BOARD = 'css/board.css';
  const THREAD = 'css/thread.css';
  const SIDEBAR = 'css/sidebar.css';
  const USER = 'css/user.css';
}

final class JS
{
  const JQUERY = 'http://ajax.googleapis.com/ajax/libs/jquery/1.7.0/jquery.min.js';
  const COMMON = 'js/common.js';
  const INDEX = 'js/index.js';
  const BOARD = 'js/board.js';
  const THREAD = 'js/thread.js';
  const SIDEBAR = 'js/sidebar.js';
  const USER = 'js/user.js';
}

final class SUBP
{
  const DETAILS = 0;
  const SETTINGS = 1;
  const PW = 2;
  const RECENT = 3;
  const FAV = 4;
  const PRIVMSG = 5;
}

define("CHAT_SEQ_MAX", 1024);
define("BOARD_NAME", "LOL Bros, LOL");
define("VERSION", 0.80);
define("COMMON_CSS", "<link href='/css/common.css' type='text/css' rel='stylesheet'>\n");
define("COMMON_JQUERY", "<script type='text/javascript' src='http://ajax.googleapis.com/ajax/libs/jquery/1.7.0/jquery.min.js'></script>");
define("TIME_FULL", "g:i a M/j/Y");
define("TIME_MYSQL", "Y-m-d H:i:s");
define("TIME_CHAT", "g:i:s");
define("DEFAULT_ITEMS_PER_PAGE", 20);