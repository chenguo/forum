<?php
/* DEBUG: Error reporting */
ini_set('display_errors', 1); error_reporting(E_ALL | E_STRICT);
require_once ('src/defines.php');
require_once ('src/forum.php');
require_once ('src/session.php');
require_once ('src/html.php');
require_once ('src/util.php');

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

?>
