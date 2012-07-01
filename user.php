<?php
require_once("./include/defines.php");
require_once("./include/common_cfg.php");
ini_set('display_errors', 1); error_reporting(E_ALL | E_STRICT);

/* This page requires login access. */
if (!$session->CheckLogin(TRUE))
{
  header("LOCATION: index.php");
}

if (!isset($_GET['uid']) || $_GET['uid'] < 0)
{
  header("LOCATION: threads.php");
}

$view = Profile::PROFILE;
if (isset($_REQUEST['view']))
{
  $view = $_REQUEST['view'];
}
$uid = $_GET['uid'];

?>

<!DOCTYPE html>
<html>
<head>
<?php $display->DisplayTitle(Title::USER, $uid); ?>
<link rel="stylesheet" text="text/css" href="css/user.css" />
<link rel="stylesheet" text="text/css" href="css/board.css" />
<script src='include/jsfunc.js'></script>
<script type='text/javascript'>
window.addEventListener('DOMContentLoaded', loadAction, false);

function prof_edit_hover(obj)
{
  // Obj type should be a table row.
  if (obj.tagName == "tr" || obj.tagName == "TR")
    {
      var test = $(obj).children(".prof_edit_btn_col");
      test.html("<input type='button' class='button prof_btn' value='edit'>");
    }
}

function prof_edit_leave(obj)
{
  // Obj type should be a table row.
  if (obj.tagName == "tr" || obj.tagName == "TR" )
    {
      //alert($(obj).parent().html());
      var test = $(obj).children(".prof_edit_btn_col");
      test.html("");
    }
}


</script>
</head>

<body>
<?php
$display->DisplayBanner();
$display->DisplaySidebar();
$display->DisplayUserPage($uid, $view);
?>
</body>
</html>
