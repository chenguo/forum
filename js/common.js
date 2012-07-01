// Multiple callbacks at window.onload. Taken from simonwillison.net.
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