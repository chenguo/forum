<?php
require_once("./include/common_cfg.php");

if (isset($_POST['username']) && isset($_POST['password']))
  {
    if ($session->Login($_POST['username'], $_POST['password']) == TRUE)
      {
        header ("LOCATION: threads.php");
      }
  }
?>

<!DOCTYPE html>
<html>
<head>
<?php $display->DisplayTitle(); ?>
<style>
#login {
  table-layout:auto;
  width:18em;
  margin-left:auto;
  margin-right:auto;
}

table{text-align:center}

input.form{
  width:14em;
  background-color:#FFFFFF;
}
</style>
</head>

<body>
<?php $display->DisplayBanner(FALSE /* no sidebar */) ?>
<div id='common'>
<form class='login' name='login' action='index.php' method='post'>
<table id='login'><tr>
<td><label>username</label></td>
<td><input type='text' size='20' name='username' class='form'></td>
<tr>
<td><label>password</label></td>
<td><input type='password' size='20' name='password' class='form'></td>
<tr></table></br>
<input type='submit' value='log in' class='button'>
</form></br>
</div>

</body>
</html>