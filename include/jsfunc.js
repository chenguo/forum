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
function editPost(pid, action)
{
  var req;
  if (window.XMLHttpRequest)
    req = new XMLHttpRequest();
  req.onreadystatechange=function()
  {
    if (req.readyState == 4 && req.status == 200)
      {
        //alert(req.responseText);
        var pattern = /^\[edit:([^\]]*)\](.*)/;
        var fields = req.responseText.match(pattern);
        if (fields)
          {
            document.getElementById("edittime"+pid).innerHTML="</br>"+fields[1];
            document.getElementById("post"+pid+"_text").innerHTML=fields[2];
          }
        else if (req.responseText != 0)
          document.getElementById("post"+pid+"_text").innerHTML=req.responseText;
      }
  }
  var post_controls = document.getElementById("post"+pid+"_controls");

  // Update button display.
  if (action == "edit_edit")
    post_controls.innerHTML = "";
  else if (action == "edit_cancel" || action == "edit_submit")
    post_controls.innerHTML =
      "<input type='button' value='quote' class='button' onclick='quotePost("+pid+")'>"
      + " <input type='button' value='edit' class='button' onclick='editPost("+pid+", \"edit_edit\")'>";

  if (action == "edit_edit" || action == "edit_cancel")
    {
      req.open("GET", "action.php?pid="+pid+"&action="+action+"&t="+Math.random(), true);
      req.send();
    }
  else
    {
      var content = document.getElementById("edit"+pid).value;
      req.open("POST", "action.php?", true);
      req.setRequestHeader("Content-type","application/x-www-form-urlencoded");
      req.send("pid="+pid+"&action="+action+"&content="+encodeURIComponent(content));
    }
}

// Generates quoted content for making new post.
function quotePost(pid)
{
  //alert("test");
  var req;
  if (window.XMLHttpRequest)
    req = new XMLHttpRequest();
  req.onreadystatechange=function()
    {
      if (req.readyState == 4 && req.status == 200)
        {
          //alert(req.responseText);
          var newpost = document.getElementById("newpost_form");
          if (req.responseText != 0)
            newpost.value = newpost.value + req.responseText;
          newpost.focus();
        }
    }
  //alert("action quote pid " + pid);
  req.open("POST", "action.php?", true);
  req.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  req.send("action=quote&pid="+pid);
}

// Preview new post.
function previewNewPost(uid, tid)
{
  var req;
  if (window.XMLHttpRequest)
    req = new XMLHttpRequest();
  req.onreadystatechange=function()
    {
      if (req.readyState == 4 && req.status == 200)
        {
          var preview = document.getElementById("new_post_preview");
          preview.innerHTML = req.responseText;
        }
    }
  // Get text of new post
  var content = document.getElementById("newpost_form").value;
  req.open("POST", "action.php?", true);
  req.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  req.send("action=new_post_preview&uid="+uid+"&tid="+tid+"&content="+encodeURIComponent(content));
}

// Apply karma to post.
function karma(type, pid, puid)
{
  var req;
  if (window.XMLHttpRequest)
    req = new XMLHttpRequest();
  req.onreadystatechange=function()
    {
      if (req.readyState == 4 && req.status == 200) //&& req.responseText == 1)
        {
          if (req.responseText == 0)
            alert("action failed");
          else if (type == "karma_plus")
            {
              alert("brofist successful");
            }
          else if (type == "karma_minus")
            {
              alert("bitchslap successful");
            }
        }
    }

  //alert("sending");
  req.open("GET", "action.php?action="+type+"&pid="+pid+"&puid="+puid+"&t="+Math.random(), true);
  req.send();
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