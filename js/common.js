// Multiple callbacks at window.onload. Based off of code from simonwillison.net.
function loadAction(func)
{
    var onload = window.onload;
    if (!onload || typeof onload != 'function')
    {
        window.onload = func;
    }
    else
    {
        window.onload = function()
        {
            onload();
            func();
        }
    }
}

// Check if var has value
function isUndef(v)
{
    if (typeof(v) == 'undefined' || typeof(v) == 'null')
        return true;
    else
        return false;
}

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

// Show overlay, with content
function overlayShow (result, id)
{
    var json_result = $.parseJSON(result);
    if (json_result.title)
        $('.overlay .tbox_title_text').html(json_result.title);
    if (json_result.content)
        $('.overlay .titled_box .tbox_content').html(json_result.content);
    $('.overlay#'+id).css('visibility', 'visible');
}
window.overlayShow = overlayShow;

function overlayHide (id)
{
    $('.overlay#'+id).css('visibility', 'hidden');
}