/*******************************\
 *                             *
 *      Common Functions       *
 *                             *
\*******************************/

// Display sidebar when mouse is hovering over trigger region
function showSidebar()
{
  document.getElementById("sidebar").style.visibility = "visible";
  document.getElementById("sbtrig").style.visibility = "hidden";
}
// Hide sidebar when mouse leavse it
function hideSidebar(e)
{
  if (!e) var e = window.event;
  var src = (window.event)? e.SrcElement : e.target;
  var target = (e.relatedTarget)? e.relatedTarget : e.toElement;
  while (target.nodeName != "HTML")
    {
      if (target.className == "sidebar")
        return;
      target = target.parentNode;
    }
  document.getElementById("sidebar").style.visibility = "hidden";
  document.getElementById("sbtrig").style.visibility = "visible";
}

// On loading a page, prepare chat
function loadAction()
{
  return;
  getChat();
  setInterval("getChat()", 1000);
  var chat_msgs = document.getElementById("chat_msgs_div");
  chat_msgs.scrollTop = chat_msgs.scrollHeight;
}

// Get chat messages
chat_seq = -1;
function getChat()
{
  return;
  var req;
  if (window.XMLHttpRequest)
    req = new XMLHttpRequest();

  req.onreadystatechange=function()
    {
      if (req.readyState == 4 && req.status == 200)
        {
          //alert("get chat: " + req.responseText + " current seq " + chat_seq);
          var pattern = /\[seq:(\d+)\]([\s\S]*)/m;
          var fields = req.responseText.match(pattern);
          if (fields)
            {
              //alert("Match: field1 " + fields[1] + " field2 " + fields[2]);
              chat_seq = fields[1];
              if (fields[2] != "")
                {
                  var chat_msgs = document.getElementById("chat_msgs_div");
                  chat_msgs.innerHTML = chat_msgs.innerHTML + fields[2];
                  chat_msgs.scrollTop = chat_msgs.scrollHeight;
                }
            }
         }
    }
  //alert("Get Chat Send Seq: "+chat_seq);
  req.open("GET", "action.php?action=chatGet&seq="+chat_seq+"&t="+Math.random(), true);
  req.send();
}

function sendChat(form)
{
  return;
  if (form.chat_post.value == "")
    return;

  var msg = form.chat_post.value;
  form.chat_post.value = "";
  var req;
  if (window.XMLHttpRequest)
    req = new XMLHttpRequest();
  req.open("POST", "action.php?", true);
  req.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  req.send("action=chatSend&text="+msg);
}

function sendKey(e)
{
  return;
  if (e.keyCode == 13)
    {
      var form = document.getElementById("chat_input");
      sendChat(form);
    }
}

/*******************************\
 *                             *
 *      Thread Functions       *
 *                             *
\*******************************/

// Edit post; includes functionality to submit, preview, and cancel edit.
function editPost(pid, action, title)
{
  if (action == "edit_edit")
    $("#post"+pid+"_controls").val("");
  else if (action == "edit_cancel" || action == "edit_submit")
    {
      var new_buttons =
      " <input type='button' value='edit' class='button' onclick='editPost("+pid+", \"edit_edit\,"+title+")'>"
      + "<input type='button' value='quote' class='button' onclick='quotePost("+pid+")'>";
      $("#post"+pid+"_controls").val(new_buttons);
    }

  var data = "";
  if (action == "edit_edit" || action == "edit_cancel")
    {
      data = "pid="+pid + "&action="+action;
    }
  else if (action == "edit_preview" || action == "edit_submit")
    {
      var content = $("#edit"+pid).val();
      var data = "pid="+pid + "&action="+action + "&content="+encodeURIComponent(content);
      if (title)
        {
          data += "&title="+$("#edit_title").val();
        }
    }
  var func = function(result) {
    var json_result = $.parseJSON(result);
    $("#post"+pid+"_text").html(json_result.content);
    if (json_result.title)
      $("div.thread_title").html(json_result.title);
    if (json_result.edit)
      $("#edittime"+pid).html(json_result.edit);
  };

  $.ajax({
    url: "action.php",
    type: "POST",
    data: data,
    success: func
  });
}

// Generates quoted content for making new post.
function quotePost(pid)
{
  var func = function(result) {
    $("#newpost_form").val($("#newpost_form").val()+result);
    $("#newpost_form").focus();
  }

  $.ajax({
    url:"action.php",
    type:"POST",
    data:"action=quote&pid="+pid,
    success:func
  });
}

// Preview new post.
function previewNewPost(tid)
{
  var content = $("#newpost_form").val();
  var data = "action=new_post_preview&tid="
                  +tid+"&content="+encodeURIComponent(content);
  var func = function(result) {
    $("#new_post_preview").html(result);
  };
  $.ajax({
    url: "action.php",
    type: "POST",
    data: data,
    success: func
  });
}

// Apply karma to post.
function karma(type, pid, puid)
{
  var data = "action="+type + "&pid="+pid + "&puid="+puid+"&t="+Math.random();
  var func = function(result) {
    if (result == 0)
      alert("action failed");
    else
      {
        // Increase count in profile
        $(".user_prof_"+puid+" .user_"+type)
          .each(function(index){
              $(this).html( Number($(this).html()) + 1 )
            });

        // Update list
        var saved = $("#post"+pid+" .post_"+type);
        var list = saved.html();
        if (list == "")
          {
            if (type == "karma_plus")
              list = "brofisted by: ";
            else
              list = "bitchslapped by: ";
          }
        else
          {
            list += ", ";
          }
        list += result;
        saved.html(list);
      }
  }

  $.ajax({
    url: "action.php",
    data: data,
    success: func
  });
}

/*******************************\
 *                             *
 *    User Profile Functions   *
 *                             *
\*******************************/
function userProfView(view, uid)
{
  var func = function(result) {
    $(".usrp_content").html(result);
  }
  var data = "uid="+uid+"&action=";
  if (view == "profile")
    data += "usrp_prof";
  else if (view == "edit")
    data += "usrp_edit";
  else if (view == "pw")
    data += "usrp_pw";
  else if (view == "recent")
    data += "usrp_recent";
  else if (view == "favorite")
    data += "usrp_fav";
  else if (view == "message")
    data += "usrp_msgs";

  $.ajax({
    url: "action.php",
    type: "POST",
    data: data,
    success: func
  });
}

// Save user profile settings
function userProfSave(uid)
{
  var func = function(result) {
    var json_result = $.parseJSON(result);
    $("input#email").val(json_result.email);
    $("input#avatar").val(json_result.avatar);
    $("input#post_disp").val(json_result.post_disp);
    $("input#thr_disp").val(json_result.thr_disp);
    $("textarea.prof_edit_sig").html(json_result.sig);

    $("img.user_prof_avatar").attr("src", json_result.avatar);
    $("div.usrp_edit_msg").html("profile successfully updated");
  }
  var data = "uid="+uid+"&action=usrp_save";
  data += "&email="+encodeURIComponent($("input#email").val());
  data += "&avatar="+encodeURIComponent($("input#avatar").val());
  data += "&post_disp="+$("input#post_disp").val();
  data += "&thr_disp="+$("input#thr_disp").val();
  data += "&sig="+encodeURIComponent($("textarea.prof_edit_sig").val());

  $.ajax({
    url: "action.php",
    type: "POST",
    data: data,
    success: func
  });
}

// Cancel user profile settings (restore default
function userProfCancel(uid)
{
  var func = function(result) {
    var json_result = $.parseJSON(result);
    $("input#email").val(json_result.email);
    $("input#avatar").val(json_result.avatar);
    $("input#post_disp").val(json_result.post_disp);
    $("input#thr_disp").val(json_result.thr_disp);
    $("textarea.prof_edit_sig").val(json_result.sig);

    $("div.usrp_edit_msg").html("profile settings restored");
  }
  var data = "uid="+uid+"&action=usrp_cancel";
  $.ajax({
    url: "action.php",
    type: "POST",
    data: data,
    success: func
  });
}

// Update user password
function userProfPW(uid)
{
  var cur_pass = $("input#cur_pw").val();
  var new_pass = $("input#new_pw").val();
  var cnf_pass = $("input#cnf_pw").val();

  if (new_pass.localeCompare(cnf_pass) != 0)
  {
    $("div.usrp_pw_msg").html("password update failed: new password not confirmed");
  }
  else
  {
    var func = function(result) {
      if (result == 0)
        $("div.usrp_pw_msg").html("password successfully updated");
      else
        $("div.usrp_pw_msg").html("password update failed: authentication failure");
    }

    var data = "uid="+uid+"&action=usrp_pw_change&cur_pw="+cur_pass+"&new_pw="+new_pass;

    $.ajax({
      url: "action.php",
      type: "POST",
      data: data,
      success: func
    });
  }
}

/*******************************\
 *                             *
 *  Expandable Area Functions  *
 *                             *
\*******************************/
// Hide/unhide expandable items
function expUnhide(obj)
{
  if ("+" == $(obj).val())
    {
      // Button object is passed. It's sibling will be the hidden objet.
      $(obj).next().css("display","block");
      $(obj).val("-");
    }
  else
    {
      $(obj).next().css("display","none");
      $(obj).val("+");
    }
}

/*******************************\
 *                             *
 *  Favorite Threads Functions *
 *                             *
\*******************************/
// Mark thread as favorite
function threadMarkFav(fav,uid,tid)
{
  var favicon = $("img.favicon");
  var func;
  // Unset favorite status
  if (fav == 0)
    {
      func = function(result) {
        // Change image to empty.
        $(favicon).each(function () {
          $(this).off('click');
          $(this).click(function(event) { threadMarkFav(1,uid,tid); });
          $(this).attr("src", "/imgs/site/star_empty.png");
          $(this).removeAttr("onclick");
        });
      }
    }
  // Set favorite status
  else
    {
      func = function(result) {
        // Change image to filled.
        $(favicon).each(function () {
          $(this).off('click');
          $(this).click(function(event) { threadMarkFav(0,uid,tid); });
          $(this).attr("src", "/imgs/site/star_filled.png");
          $(this).removeAttr("onclick");
        });
      }
    }

  var data = "uid="+uid+"&action=thrMarkFav&tid="+tid+"&fav="+fav;

  $.ajax({
    url: "action.php",
    type: "POST",
    data: data,
    success: func
  });
}