<?php
require_once("include/defines.php");
require_once("include/common_cfg.php");

$update_fields = array();
$msg = "";
if (!isset($_POST['cancel']))
  {
    $msg = "profile updated";

    if (isset($_POST['email']))
      $update_fields['email'] = "\"" . $_POST['email'] . "\"" ;
    if (isset($_POST['avatar']))
      $update_fields['avatar'] = "\"" . $_POST['avatar'] . "\"";

    if (isset($_POST['oldpw']))
      {
        if ($session->CheckPassword($_POST['oldpw']) != TRUE)
          $msg = "password not updated: old password incorrect";
        else if (!isset($_POST['newpw']))
          $msg = "password not updated: no new password entered";
        else
          {
            // Update password.
            $update_fields['password'] = "\"" . md5($_POST['newpw']) . "\"";
          }
      }
  }

$profile = $db->UpdateUserProfile($session->GetUID(), $update_fields);
echo "[msg:$msg][email:{$profile['email']}][avatar:{$profile['avatar']}]";

?>