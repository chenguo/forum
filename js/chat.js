(function() {
    var Chat = {};

    var init = function () {
        // Event handlers.
        $('#chat_input textarea').on('keyup', function(key) {
            if (key.which === 13 && !key.shiftKey) {
                send();
            }
        });

        // State / display
        Chat.blinkState = 0;
        Chat.newMsg = new Array();
        initDisp();

        // Restore saved messages
        retrieveMsg();

        // Socket init and handling
        append("Chat coming soon!");
        Chat.socket = io.connect('http://localhost:8000/');

        Chat.socket.once('welcome', function(data) {
            append(data.msg);
            identify();
        });
        Chat.socket.once('accepted', function(data) {
            userCount(data.online);
        });
        Chat.socket.on('message', function(data) {
            data.type = 'message';
            rcv(data);
        });
        Chat.socket.on('echo', function(data) {
            data.type = 'message';
            rcv(data);
        });
        Chat.socket.on('enter', function(data) {
            // Update online users.
            userCount(data.online);

            data.type = 'enter';
            rcv(data);
        });
        Chat.socket.on('disconnect', function(data) {
            // Update online users.
            userCount(data.online);

            data.type = 'exit';
            rcv(data);
        });
    }

    // Send server identity message.
    var identify = function () {
        var user = $.cookie('user');
        var uid = $.cookie('uid');
        Chat.socket.emit('id', { user: user, uid: uid });
    };

    // Send message to chat server.
    var send = function (msg) {
        var input = $('#chat_input textarea');
        var msg = $(input).val();
        msg = msg.replace(/\s*$/, '');

        var user = $.cookie('user');
        var uid = $.cookie('uid');
        Chat.socket.emit('message', { msg: msg, user: user, uid: uid });

        $(input).val('');
    };

    // Receive message from chat server.
    var rcv = function (data) {
        // If chat is hidden, cache message.
        if (Chat.hide) { cacheMsg(data); }
        // If chat is showing, display message.
        else { displayMsg(data); }
    };

    // Cache incoming message.
    var cacheMsg = function (data) {
        Chat.newMsg.push(data);;
        $('#chat_box .tbox_title_text').html('Chat (' + Chat.newMsg.length + ')');
        if (!Chat.blink)
        {
            Chat.blink = setInterval(blink, 1000);
        }
    };

    // Display message.
    var displayMsg = function (data) {
        var msg = '';
        if (data.type == 'message')
        {
            msg = decodeMsg(data);
        }
        else if (data.type == 'enter'
                || data.type == 'exit')
        {
            msg = decodeStatus(data);
        }
        append(msg);
    };

    // Decode message.
    var decodeMsg = function (data) {
        var uclass = 'other';
        if (data.uid === $.cookie('uid'))
        {
            uclass = 'self';
        }
        var msg = '<span class='+uclass+'>' + data.user + '</span>: '
            + '<span class=msg>' + data.msg + '</span>';
        return msg;
    };

    // Decode status.
    var decodeStatus = function (data) {
        var msg = '<span class=other>' + data.user + '</span>';
        if (data.type == 'enter')
        {
            msg += ' is now online';
        }
        else if (data.type == 'exit')
        {
            msg += ' is now offline';
        }
        return msg;
    };

    // Append message to chat box.
    var append = function (str) {
        var chatMsg = $('#chat_msg');
        $(chatMsg).html($(chatMsg).html() + str + '</br>');
        $(chatMsg).scrollTop($(chatMsg)[0].scrollHeight);
    };

    // Set online users count.
    var userCount = function (users) {
        var chatCount = $('#chat_count');
        if (users == 1)
        {
            $(chatCount).html('1 user online');
        }
        else
        {
            $(chatCount).html(users + ' users online');
        }
    };

    // Initialze show/hide state.
    var initDisp = function () {
        var cookie = $.cookie("chatHide");
        var title = $('#chat_box .tbox_title');
        if (cookie == 'true')
        {
            var boxHeight = $('#chat_box').height();
            var titleHeight = $('#chat_box .tbox_title').outerHeight();
            var delta = titleHeight - boxHeight;
            $('#chat_box').css('bottom', delta.toString() + 'px');

            // Set event handlers.
            $(title).off('mouseup');
            $(title).on('mouseup', function() {show()});
            Chat.hide = true;
        }
        else
        {
            // Set event handlers.
            $(title).off('mouseup');
            $(title).on('mouseup', function() {hide()});
            Chat.hide = false;
        }
        $('#chat_box').css('visibility', 'visible');
    };

    // Hide chat box.
    var hide = function () {
        if (!Chat.hide)
        {
            Chat.hide = true;

            var boxHeight = $('#chat_box').height();
            var titleHeight = $('#chat_box .tbox_title').outerHeight();
            var delta = titleHeight - boxHeight;
            $('#chat_box').animate({bottom: delta.toString() + 'px'});
            $('#chat_box .tbox_title').off('mouseup');
            $('#chat_box .tbox_title').on('mouseup', function() {show()});
        }
    };

    // Show chat box.
    var show = function () {
        if (Chat.hide)
        {
            // State tracking.
            Chat.hide = false;

            // Unhide and update the title.
            $('#chat_box').animate({bottom: '0'});
            $('#chat_box .tbox_title_text').html('Chat');
            var title = $('#chat_box .tbox_title');

            // Stop blink
            if (Chat.blink)
            {
                $('#chat_box .tbox_title').css('background-color', 'blue');
                clearInterval(Chat.blink);
                Chat.blink = undefined;
            }

            // Focus on input.
            $('#chat_box textarea').focus();

            // Update event handlers.
            $(title).off('mouseup');
            $(title).on('mouseup', function() {hide()});

            // Populate messages.
            while (Chat.newMsg.length > 0)
            {
                rcv(Chat.newMsg.shift());
            }
        }
    };

    // Blink chat box.
    var blink = function() {
        var title = $('#chat_box .tbox_title');
        if (Chat.blinkState == 0)
        {
            $(title).css('background-color', 'lightblue');
            Chat.blinkState = 1;
        }
        else
        {
            $(title).css('background-color', 'blue');
            Chat.blinkState = 0;
        }
    };

    // Store unread messages in cookie.
    var storeMsg = function () {
        $.cookie("chatMsg",
                 JSON.stringify(Chat.newMsg));
        $.cookie('chatHide', Chat.hide);
    }

    // Retrieve stored messages from cookie.
    var retrieveMsg = function () {
        var msgStr = $.cookie("chatMsg");
        var msgs = JSON.parse($.cookie("chatMsg"));
        for (var i = 0; i < msgs.length; i++)
        {
            rcv(msgs[i]);
        }
    }


    // Bind page load/exit functions.
    $(document).ready(init);
    $(window).unload(storeMsg);

}) ();