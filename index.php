<?php
require_once("./include/common_cfg.php");

if ($session->CheckLogin())
  {
    header ("LOCATION: ".Pages::BOARD);
    exit;
  }

if (isset($_POST['username']) && isset($_POST['password']))
  {
    if ($session->Login($_POST['username'], $_POST['password']) == TRUE)
      {
        header ("LOCATION: ".Pages::BOARD);
      }
  }
?>

<!DOCTYPE html>
<html>
<head>
<?php $display->DisplayTitle(); ?>
<script type='text/javascript'>
function clearField(field)
{
  field.value = "";
}
</script>
</head>

<body>
<?php
$display->DisplayBanner();
$display->DisplayLogin();
 ?>
</body>
</html>