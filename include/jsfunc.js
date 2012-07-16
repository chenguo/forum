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