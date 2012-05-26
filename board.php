<?php
require_once("./include/common_cfg.php");

if (!$session->CheckLogin(TRUE))
  header("LOCATION: " . Pages::LOGIN);

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
<?php $display->DisplayTitle(); ?>
<link rel="stylesheet" type="text/css" href="css/board.css"/>
<script src='include/jsfunc.js'></script>
<script>
window.addEventListener('DOMContentLoaded', loadAction, false);
</script>
</head>

<body>
<?php
$display->DisplayBanner();
$display->DisplaySidebar();
$display->DisplayBoard($page);
?>
</body>
</html>
