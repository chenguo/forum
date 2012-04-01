<?php
/* DEBUG: Error reporting */
ini_set('display_errors', 1); error_reporting(E_ALL | E_STRICT);

require_once("./include/defines.php");
require_once("./include/forum.php");
require_once("./include/session.php");
require_once("./include/display.php");

/* Log in to database. */
$db = new DB('localhost',     // host
             'lolbro6_user',  // mysql user
             '123abc',        // mysql user pw
             'lolbro6_board'   // db name
             );

/* Instantiate session with database handle. */
$session = new Session($db);

/* Instantiate forum object. */
$forum = new Forum($db, $session);

/* Instantiate display module */
$display = new Display($forum, $session, BOARD_NAME);

?>
