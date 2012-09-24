<?php
require_once ('src/common_cfg.php');

/* This page requires login access. */
if (!$session->CheckLogin())
  {
    header("LOCATION: index.php");
  }

/* Handle thread submission. */
if (isset($_POST['title']) && strlen($_POST['title']) > 0
    && isset($_POST['content']) && strlen($_POST['content']) > 0)
  {
    $tid = $forum->MakeThread($_POST['title'], $_POST['content'], $session->GetUID());

    if ($tid > 0)
      {
        header ("LOCATION: thread.php?tid={$tid}");
      }
    else
      {
        header ("LOCATION: threads.php");
      }
  }
?>
<!DOCTYPE html>

<html>
<head>
<?php $display->DisplayTitle(); ?>
<link rel="stylesheet" type="text/css" href="css/makethread.css">
<script src='include/jsfunc.js'></script>
<script type='text/javascript'>
window.addEventListener('DOMContentLoaded', loadAction, false);
</script>
</head>

<body>
<?php
$display->DisplayBanner();
$display->DisplaySidebar();
?>

<form class='newthr_form' name='post' action='makethread.php' method='post'>
<input class='newthr_title' type='text' name='title' maxlength='64'></br>
<textarea class='newthr_body' rows='10' cols='80' name='content'></textarea></br>
<input type='submit' value='create' class='button'>
</form></br>
</body>
</html>