<?php
/* DEBUG: Error reporting */
ini_set('display_errors', 1); error_reporting(E_ALL | E_STRICT);

require_once("./include/defines.php");
require_once("./include/forum.php");
require_once("./include/session.php");

/* Log in to database. */
$db = new DB('localhost',     // host
             'lolbro6_user',  // mysql user
             '123abc',        // mysql user pw
             'lolbro6_board'   // db name
             );

/* Instantiate forum object. */
$forum = new Forum($db, BOARD_NAME, VERSION);

/* Instantiate session with database handle. */
$session = new Session($db);

?>
