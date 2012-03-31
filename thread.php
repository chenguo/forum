<?php
require_once("./include/defines.php");
require_once("./include/common_cfg.php");
ini_set('display_errors', 1); error_reporting(E_ALL | E_STRICT);

// This page requires login access.
if (!$session->CheckLogin(TRUE) && $session->GetUID() < 0)
  header("LOCATION: login.php");

$thread_id;
$page;
// We arrived here without a valid thread id... Invalid, leave page.
if (!isset($_REQUEST['tid']) || $_REQUEST['tid'] < 0)
  header("LOCATION: threads.php");
else
  {
    $thread_id = $_REQUEST['tid'];
    $page = 1;
    if (isset($_REQUEST['page']))
      {
        $page = intval($_REQUEST['page']);
        if ($page < 1)
          $page = 1;
      }
  }
?>

<!DOCTYPE html>
<html>
<head>
<?php $forum->DisplayTitle(Title::THREAD, $thread_id); ?>
<script src='include/jsfunc.js'></script>
<script type='text/javascript'>
window.addEventListener('DOMContentLoaded', loadAction, false);
</script>
</head>

<body>
<?php
$forum->DisplayBanner();
$forum->DisplaySidebar($session);
$forum->DisplayThread($thread_id, $session, $page);
 ?>

<!--Make post form-->
<form name='post' action='action.php' method='post' onsubmit="button.disabled = true; return true;">
<textarea class='post_text' rows='10' cols='80' name='content' id='newpost_form'></textarea></br>
<input type='hidden' name='tid' value='<?php echo $thread_id ?>'>
<input type='hidden' name='action' value='post'>
<input type='submit' value='submit' class='button'>
</form></br>

</body>
</html>
