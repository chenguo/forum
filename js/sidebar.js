// Add sidebar load action to window.onload callbacks
loadAction(sbLoadAction);

function sbLoadAction()
{
    $("#sidebar").mouseleave(hideSidebar);
    $("#sbtrig").mouseover(showSidebar);
}

function showSidebar()
{
    $("#sidebar").css('visibility', 'visible');
    $("#sbtrig").css('visibility', 'hidden');
}

function hideSidebar()
{
    $("#sbtrig").css('visibility', 'visible');
    $("#sidebar").css('visibility', 'hidden');
}
