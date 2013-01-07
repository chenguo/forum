(function() {
    var clearField = function () {
        this.value = '';
    };

    $(document).ready(function() {
        // If browser hasn't populated the fields, replace them
        var user = $("input[name='username']");
        var pw = $("input[name='password']");
        if ($(user).attr('value') == '')
            $(user).attr('value', 'username');
        if ($(pw).attr('value') == '')
            $(pw).attr('value', 'password');

        // Set up handlers to clear the field
        $(user).click(clearField);
        $(pw).click(clearField);
    });
}) ();
