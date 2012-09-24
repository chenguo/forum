function userProfView(view, uid)
{
    var func = function(result) {
        $(".usrp_subp_box").html(result);
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
        url: "user.php",
        type: "POST",
        data: data,
        success: func
    });
}

// Save user profile settings
function userProfSave(uid)
{
    $("div.usrp_edit_msg").html("");

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
        url: "user.php",
        type: "POST",
        data: data,
        success: func
    });
}

// Cancel user profile settings (restore default
function userProfCancel(uid)
{
    $("div.usrp_edit_msg").html("");

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
        url: "user.php",
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

    $("div.usrp_pw_msg").html("");

    if (new_pass.localeCompare(cnf_pass) != 0)
    {
        $("div.usrp_pw_msg").html("password update failed: new password not confirmed");
    }
    else
    {
        var func = function(result) {
            if (!isUndef(result) && result == '0')
                $("div.usrp_pw_msg").html("password successfully updated");
            else
                $("div.usrp_pw_msg").html("password update failed: authentication failure");
        }

        var data = "uid="+uid+"&action=usrp_pw_change&cur_pw="+cur_pass+"&new_pw="+new_pass;

        $.ajax({
            url: "user.php",
            type: "POST",
            data: data,
            success: func
        });
    }
}
