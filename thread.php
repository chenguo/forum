<?php
require_once("./include/defines.php");
require_once("./include/common_cfg.php");
ini_set('display_errors', 1); error_reporting(E_ALL | E_STRICT);

// This page requires login access.
if (!$session->CheckLogin(TRUE) && $session->GetUID() < 0)
  header("LOCATION: index.php");

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
<?php $display->DisplayTitle(Title::THREAD, $thread_id); ?>
<link rel="stylesheet" type="text/css" href="css/thread.css" />
<script src='include/jsfunc.js'></script>
<script type='text/javascript'>
window.addEventListener('DOMContentLoaded', loadAction, false);
</script>
</head>

<body>
<?php
$display->DisplayBanner();
$display->DisplaySidebar();
$display->DisplayThread($thread_id, $page);
 ?>
</body>
</html>
