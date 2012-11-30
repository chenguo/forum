// Bring up new thread overlay
function newThread ()
{
    var data = 'action=newthr';
    var func = function(result) {
        window.overlayShow(result,'newthr');
    }

    $.ajax({
        url: "board.php",
        type: "POST",
        data: data,
        success: func
    });
}