(function() {
    var Chat = {};

    var init = function () {
        // Socket init and handling
        Chat.socket = io.connect('http://localhost:8000/');
        Chat.socket.once('welcome', function(data) {
            append(data.msg + '</br>');
        });
        Chat.socket.on('message', function(data) {
            rcv(data);
        });
        Chat.socket.on('echo', function(data) {
            rcv(data);
        });

        // Event handlers.
        $('#chat_box .chat_input textarea').on('keyup', function(key) {
            if (key.which === 13 && !key.shiftKey) {
                send();
            }
        });

        Chat.hide = false;
        Chat.newMsg = 0;

        hide();
    }

    var append = function (str) {
        var chatLog = $('#chat_box .chat_msg').html();
        var chatMsg = $('#chat_box .chat_msg');
        $(chatMsg).html(chatLog + str);
        $(chatMsg).scrollTop($(chatMsg)[0].scrollHeight);
    };

    // Send message to chat server.
    var send = function (msg) {
        var input = $('.chat_input textarea');
        var msg = $(input).val();
        msg = msg.replace(/\s*$/, '');

        var user = $.cookie('user');
        var uid = $.cookie('uid');
        Chat.socket.emit('message', { msg: msg, user: user, uid: uid });

        $(input).val('');
    }

    // Receive message from chat server.
    var rcv = function (data) {
        var uclass = 'other';
        if (data.uid === $.cookie('uid'))
        {
            uclass = 'self';
        }
        var msg = '<span class='+uclass+'>' + data.user + '</span>: '
            + '<span class=msg>' + data.msg + '</span></br>';
        append(msg);

        if (Chat.hide)
        {
            Chat.newMsg++;
            $('#chat_box .tbox_title_text').html('Chat (' + Chat.newMsg + ')');
            if (!Chat.blink)
            {
                Chat.blink = setInterval(blink, 1000);
            }
        }
    }

    // Hide chat box.
    var hide = function () {
        if (!Chat.hide)
        {
            var boxHeight = $('#chat_box').height();
            var titleHeight = $('#chat_box .tbox_title').outerHeight();
            var delta = titleHeight - boxHeight;
            $('#chat_box').animate({bottom: delta.toString() + 'px'});
            $('#chat_box .tbox_title').off('mouseup');
            $('#chat_box .tbox_title').on('mouseup', function() {show()});
            Chat.hide = true;
        }
    }

    // Show chat box.
    var show = function () {
        if (Chat.hide)
        {
            // Unhide and update the title.
            $('#chat_box').animate({bottom: '0'});
            $('#chat_box .tbox_title_text').html('Chat');
            var title = $('#chat_box .tbox_title');

            // Focus on input.
            $('#chat_box textarea').focus();

            // Update event handlers.
            $(title).off('mouseup');
            $(title).on('mouseup', function() {hide()});

            // State tracking.
            Chat.hide = false;
            Chat.newMsg = 0;

            // Stop blink
            if (Chat.blink)
            {
                $('#chat_box .tbox_title').css('background-color', 'blue');
                clearInterval(Chat.blink);
                Chat.blink = undefined;
            }
        }
    }

    // Blink chat box.
    var blink = function() {
        var title = $('#chat_box .tbox_title');
        var color = $(title).css('background-color');
        if (color === 'rgb(0, 0, 255)')
        {
            $(title).css('background-color', 'gold');
        }
        else
        {
            $(title).css('background-color', 'blue');
        }
    }

    $(document).ready(init);
}) ();