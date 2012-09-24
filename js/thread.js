// Handle edit post functionality
// Edit post
function editPost(pid, action, title)
{
    var post = $('#post'+pid);
    var ctrls = $(post).find('.post_controls');
    // Edit: clear the controls (maybe keep quote in future?)
    if (action == 'edit_edit')
    {
        $(ctrls).html('');
    }
    // Cance/submit: put buttons back
    else if (action == 'edit_cancel' || action == 'edit_submit')
    {
        var new_buttons =
            "<input type='button' value='edit' class='button' onclick='editPost("+pid+", \"edit_edit\","+title+")'>"
            + " <input type='button' value='quote' class='button' onclick='quotePost("+pid+")'>";
        $(ctrls).html(new_buttons);
    }

    // Encode POST
    var data = 'pid='+pid+'&action='+action;
    if (action == 'edit_preview' || action == 'edit_submit')
    {
        var content = $('#edit'+pid).val();
        if (isUndef(content))
        {
            $(post).find('.edit_msg').html('Edit failed, no content found');
            return;
        }
        content = encodeURIComponent(content);
        data += '&content='+content;

        if (title)
        {
            var title_str = $('#edit_title').val();
            if ( isUndef(title_str) || /^\s*$/.test(title_str) )
            {
                $(post).find('.edit_msg').html('Edit failed, no title found or empty title');
                return;
            }
            title_str = encodeURIComponent(title_str);
            data += '&title='+title_str;
        }
    }

    // Parse result and update HTML
    var func = function(result) {
        var json_result = $.parseJSON(result);
        if (json_result.content)
            $(post).find('.post_text').html(json_result.content);
        if (json_result.title)
            $('div.thr_ttl').html(json_result.title);
        if (json_result.edit)
            $(post).find('.post_edit').html(json_result.edit);
        if (json_result.edit_time)
            $("#edittime"+pid).html(json_result.edit_time);
        if (json_result.debug)
            alert(json_result.debug);
    }

    $.ajax({
        url: "thread.php",
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
        url:"thread.php",
        type:"POST",
        data:"action=quote&pid="+pid,
        success:func
    });
}

// Preview post
function previewNewPost(tid)
{
    var content = $("#newpost_form").val();
    var data = "action=new_post_preview&tid="
        +tid+"&content="+encodeURIComponent(content);
    var func = function(result) {
        $("#new_post_preview").html(result);
    };
    $.ajax({
        url: "thread.php",
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
            var initial = (type == "karma_plus")? "p" : "m";
            $(".user_prof_"+puid+" .usrp_karma .usrp_karma_"+initial)
                .each(function(index){
                    $(this).html( Number($(this).html()) + 1 )
                });

            // Update list of karma givers
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

            // Remove buttons for further karma actions
            $("#post"+pid+" .plus,#post"+pid+" .minus").remove();
        }
    }

    $.ajax({
        url: "thread.php",
        data: data,
        success: func
    });
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
    var img;
    var new_fav;

    if (fav == 0)
    {
        img = "/imgs/site/star_empty.png";
        new_fav = 1;
    }
    else
    {
        img = "/imgs/site/star_filled.png";
        new_fav = 0;
    }

    func = function(result) {
        // Change action next time icon is cleared.
        $(favicon).each(function () {
            $(this).off('click');
            $(this).click(function(event) { threadMarkFav(new_fav,uid,tid); });
            $(this).attr("src", img);
            $(this).removeAttr("onclick");
        });
    }

    var data = "uid="+uid+"&action=thrMarkFav&tid="+tid+"&fav="+fav;

    $.ajax({
        url: "board.php",
        type: "POST",
        data: data,
        success: func
    });
}
