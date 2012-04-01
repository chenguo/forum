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

$uid = $_GET['uid'];

?>

<!DOCTYPE html>
<html>
<head>
<?php $forum->DisplayTitle(Title::USER, $uid); ?>

<script src='include/jsfunc.js'></script>
<script type='text/javascript'>
window.addEventListener('DOMContentLoaded', loadAction, false);

function editProfile(action)
{
  var table = document.getElementById("prof_details");
  var email = document.getElementById("email_field");
  var avatar = document.getElementById("profile_img");

  if (action == "edit")
    {
      // Make the email field an input box.
      email.innerHTML = "<input type='text' size='20' value='"+email.innerHTML+"' class='form' id='email_form'>";

      // Add empty row.
      row = table.insertRow(-1);

      // Add input for avatar
      row = table.insertRow(-1);
      row.insertCell(0).innerHTML = "avatar";
      row.insertCell(1).innerHTML = "<input type='text' size='20' class='form' name='avatar' id='avatar_form' value='"
        + avatar.src + "'></input>";

      // Add inputs for password change.
      row = table.insertRow(-1);
      row.insertCell(0).innerHTML = "old password";
      row.insertCell(1).innerHTML = "<input type='password' size='20' class='form' name='old_pw' id='old_pw_form'>";
      row = table.insertRow(-1);
      row.insertCell(0).innerHTML = "new password";
      row.insertCell(1).innerHTML = "<input type='password' size='20' class='form' name='newpw' id='new_pw_form'>";
      row = table.insertRow(-1);
      row.insertCell(0).innerHTML = "confirm new password";
      row.insertCell(1).innerHTML = "<input type='password' size='20' class='form' name='new_pw_cnf' id='new_pw_cnf_form'>";

      // Change the edit button to a save button.
      document.getElementById("profile_control").innerHTML =
        "<input type='button' value='save' class='button' onclick='editProfile(\"save\")'> "
        + "<input type='button' value='cancel' class='button' onclick='editProfile(\"cancel\")'>";
    }
  else if (action == "save" || action == "cancel")
    {
      var msg = document.getElementById("msg");

      var req = new XMLHttpRequest();
      req.onreadystatechange=function()
      {
        if (req.readyState == 4 && req.status == 200)
          {
            //alert(req.responseText);
            // Separate out the fields.
            var pattern = /\[msg:([^\]]*)\]\[email:([^\]]*)\]\[avatar:([^\]]*)\]/;
            var fields = req.responseText.match(pattern);

            // Update fields.
            msg.innerHTML = fields[1];
            email.innerHTML = fields[2];
            avatar.src = fields[3];

            // Delete input rows that were added to table.
            for (i = 0; i < 5; i++)
              table.deleteRow(-1);

            document.getElementById("profile_control").innerHTML =
            "<input type='button' value='edit profile' class='button' onclick='editProfile(\"edit\")'>";
          }
      }

      req.open("POST", "profile_update.php?", true);
      req.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

      // If saving, collect values and post them.
      if (action == "save")
        {
          var email_form = document.getElementById("email_form");
          var avatar_form = document.getElementById("avatar_form");
          var old_pw_form = document.getElementById("old_pw_form");
          var new_pw_form = document.getElementById("new_pw_form");
          var new_pw_cnf_form = document.getElementById("new_pw_cnf_form");
          var post_content = "email="+email_form.value
            + "&avatar="+encodeURIComponent(avatar_form.value);

          if (old_pw_form.value != "")
            {
              if (new_pw_form.value == "" || new_pw_cnf_form.value == "")
                alert ("Password update not submitted: new password/confirm new password empty");
              else if (new_pw_form.value != new_pw_cnf_form.value)
                alert("Password udpate not submitted: new password is different from confirm new password");
              else
                post_content += "&oldpw="+old_pw_form.value+"&newpw="+new_pw_form.value;
            }

          req.send(post_content);
        }
      else
        {
          req.send("cancel=1");
        }
    }
}
</script>
</head>

<body>
<?php $forum->DisplayBanner(); ?>
<a href="threads.php">Back to Board</a></br></br>
<?php
$forum->DisplaySidebar($session);
$forum->DisplayUserPage($uid, $session->GetUID());
 ?>
</body>
</html>
