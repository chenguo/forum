/*******************************\
 *                             *
 *      Common Functions       *
 *                             *
\*******************************/

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
