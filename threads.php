<?php
require_once("./include/common_cfg.php");

if (!$session->CheckLogin(TRUE))
  header("LOCATION: index.php");

$page = 1;
if (isset($_REQUEST['page']))
  {
    $page = intval($_REQUEST['page']);
    if ($page < 1)
      $page = 1;
  }
?>

<!DOCTYPE html>
<html>
<head>
<?php $forum->DisplayTitle(); ?>
<script src='include/jsfunc.js'></script>
<script>
window.addEventListener('DOMContentLoaded', loadAction, false);
</script>
</head>

<body>
<?php
$forum->DisplayBanner();
$forum->DisplaySidebar($session);
$forum->DisplayThreads($session, $page);
?>
</body>
</html>
