// Display sidebar when mouse is hovering over trigger region
function showSidebar()
{
  document.getElementById("sidebar").style.visibility = "visible";
  document.getElementById("sidebar_trigger").style.visibility = "hidden";
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
  document.getElementById("sidebar_trigger").style.visibility = "visible";
}

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
    else if (type == "karma_plus")
      alert("brofist successful");
    else if (type == "karma_minus")
      alert("bitchslap successful");
  }

  $.ajax({
    url: "action.php",
    data: data,
    success: func
  });
}

function loadAction()
{
  return;
  getChat();
  setInterval("getChat()", 1000);
  var chat_msgs = document.getElementById("chat_msgs_div");
  chat_msgs.scrollTop = chat_msgs.scrollHeight;
}

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

// Get elements by class
function getDivsByClass(class_name)
{
  var divs = document.getElementsByTagName("div");
  var class_divs = new Array();
  for (var i = 0; i < divs.length; i++)
    {
      if (divs[i].getAttribute("class") == class_name)
      {
        class_divs.push(divs[i]);
      }
    }
  return class_divs;
}