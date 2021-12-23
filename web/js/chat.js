//source: http://dumpsite.com/forum/index.php?topic=4.msg8#msg8
String.prototype.replaceAll = function (str1, str2, ignore) {
    return this.replace(new RegExp(str1.replace(/([\/\,\!\\\^\$\{\}\[\]\(\)\.\*\+\?\|\<\>\-\&])/g, "\\$&"), (ignore ? "gi" : "g")), (typeof(str2) == "string") ? str2.replace(/\$/g, "$$$$") : str2);
};
String.prototype.insert = function (index, string) {
    if (index > 0)
        return this.substring(0, index) + string + this.substring(index, this.length);
    else
        return string + this;
};

$(document).ready(function () {
    Notification.requestPermission();
    $('[data-toggle="tooltip"]').tooltip();

    var active = true;
    var newMessagesCount = 0;
    var title = document.title;

    window.onblur = function () {
        active = false;
    };
    window.onfocus = function () {
        active = true;
        newMessagesCount = 0;
        document.title = title;
        setTimeout(removeLineNewMessages, 8000);
    };

    var channelChanged = 0;
    var emoticonsOpened = 0;
    var usersOnline = [];
    startChat();
    scrollMessages();
    setTimeout(refreshChat, 1500);

    //insert private message text in message-text
    $('#users-box').on('click', '.online-user', function () {
        let value = $(this).attr('data-value');
        $('#message-text').val('/priv ' + value + ' ').focus();
    });

    //sending new message when clicked on button
    $('body').on('click', '#send', function () {
        sendMessage();
    });

    //sending new message when pressed enter
    $('body').on('keypress', '#message-text', function (event) {
        if (event.which == 13 && !event.shiftKey) {
            event.preventDefault();
            sendMessage();
        }
    });

    $('body').on('click', '.channel', function () {
        changeChannel($(this).attr('data-value'));
    });

    $('body').on('click', '.language', function () {
        changeLocale($(this).attr('data-value'));
    });

    $('.emoticon-img').click(function () {
        var value = $('#message-text').val();
        var emoticon = $(this).attr('alt');
        insertText(emoticon, '');
    });

    $('#emoticons').click(function () {
        if (emoticonsOpened % 2) {
            hide('emoticons');
        } else {
            show('emoticons');
        }
        emoticonsOpened++;
    });

    $('body').on('click', '.nick', function () {
        insertNick("@" + $(this).text());
    });

    function insertNick(nick) {
        var value = $('#message-text').val();
        $('#message-text').val(value + nick);
        $('#message-text').focus();
    }

    function insertSentMessage(msg) {
        var d = createDate();
        var del = '';
        if (self.role === 'administrator' || self.role === 'moderator') {
            del = '<span class="pull-right kursor" data-id="' + msg.id + '">&times;</span>';
        }
        var light = checkIfMessageHaveNick(msg.text);
        $('#messages-box').append(
            '<div class="message' + light + '" data-id="' + msg.id + '"><span class="date">('
            + d +
            ')</span> <span class="' + self.role + ' text-bold nick">' + msg.userName + '</span>:<span class="message-text"> '
            + parseMessage(msg.text) + '</span>' + del + '</div>'
        );
    }

    function sendMessage() {
        var text = $('#message-text').val();
        if (text === '') {
            return;
        }
        var params = {
            'text': text
        };
        $('#message-text').val("");
        $('#message-text').focus();
        $.ajax({
            type: "POST",
            dataType: "json",
            url: sendPath,
            data: params
        }).done(function (msg) {
            if (msg.status === "false") {
                $('#messages-box').append('<div class="message-error">An error occurred while sending message.</div>');
            } else {
                insertSentMessage(msg);
            }
            if (msg.messages) {
                $.each(msg.messages, function (key, val) {
                    createNewMessage(val);
                });
            }
            setTimeout(scrollMessages, 100);
        });
    }

    function checkIfMessageHaveNick(text) {
        if (text.search("@" + self.username) !== -1) {
            return ' light';
        } else {
            return '';
        }
    }

    function kickFromChannel() {
        $('.channel').removeClass('active');
        $('.channel[data-value="1"]').addClass('active');
        usersOnline = [];
        clearChat();
        channelChanged = 1;
    }

    function isUserTyping() {
        let value = $('#message-text').val();
        if (value && value.search('/priv') === -1) {
            return 1;
        }
        return 0;
    }

    function addLineNewMessages() {
        if (!active && !newMessagesCount) {
            $('#messages-box').append('<div class="line" data-content="' + chatText.new + '"></div>');
        }
    }

    function refreshUsersOnline(users) {
        if (usersOnline.length) {
            checkIfUserLogout(users);
        }
        addUsersOnline(users);
        refreshUsersOnlineBox(users);
    }

    function refreshUsersOnlineBox(users) {
        users.forEach(function (element) {
            var value = $('.online-user[data-value="' + element.username + '"]').text();
            if (element.typing) {
                if (value.indexOf("(") === -1) {
                    $('.online-user[data-value="' + element.username + '"]').text(element.username + ' (...)');
                }
            } else {
                if (value.indexOf("(") !== -1) {
                    $('.online-user[data-value="' + element.username + '"]').text(element.username);
                }
            }
        });
    }

    function addUsersOnline(users) {
        users.forEach(function (element) {
            if (usersOnline.indexOf(element.username) === -1) {
                $('#users-box').append(
                    '<div class="' + element.user_role + ' text-in-info online-user" data-value="' + element.username + '">' + element.username + '</div>'
                );
                usersOnline.push(element.username);
            }
        });
    }

    function checkIfUserLogout(users) {
        usersOnline.forEach(function (element, index, array) {
            let inArray = 0;
            users.forEach(function (element1) {
                if (element === element1.username) {
                    inArray = 1;
                }
            });
            if (!inArray) {
                $('.online-user[data-value="' + element + '"]').remove();
                array.splice(index, 1);
            }
        });
    }

    function refreshChat() {
        var params = {
            typing: isUserTyping()
        };
        $.ajax({
            method: "POST",
            dataType: "json",
            data: params,
            url: refreshPath
        }).done(function (msg) {
            if (msg.kickFromChannel === 1) {
                kickFromChannel();
            }
            if (msg.messages[0]) {
                addLineNewMessages();
                $.each(msg.messages, function (key, val) {
                    if (val.text == 'delete') {
                        $('div[data-id="' + val.id + '"]').remove();
                    } else {
                        createNewMessage(val);
                        notification(val);
                        if (channelChanged === 0) {
                            var audio = new Audio(newMessageSound);
                            audio.currentTime = 0;
                            audio.play();
                        }
                    }
                });
                setTimeout(scrollMessages, 100);
            }
            if (msg.usersOnline) {
                refreshUsersOnline(msg.usersOnline);
            }
            if (channelChanged === 1) {
                channelChanged = 0;
            }
        });
        setTimeout(refreshChat, 1500);
    }

    function createDate(dateInput) {
        if (dateInput !== undefined) {
            var d = new Date(dateInput);
        } else {
            var d = new Date();
        }
        var date = '';
        if (d.getHours() < 10) {
            date += '0' + d.getHours() + ':';
        } else {
            date += d.getHours() + ':';
        }
        if (d.getMinutes() < 10) {
            date += '0' + d.getMinutes() + ':';
        } else {
            date += d.getMinutes() + ':';
        }
        if (d.getSeconds() < 10) {
            date += '0' + d.getSeconds();
        } else {
            date += d.getSeconds();
        }
        return date;
    }

    function createNewMessage(val) {
        var d = createDate(val.date.date);
        var del = '';
        if (self.role === 'administrator' || self.role === 'moderator') {
            del = '<span class="pull-right kursor" data-id="' + val.id + '">&times;</span>';
        }
        var light = checkIfMessageHaveNick(val.text);
        var pm = val.privateMessage ? ' private-message' : '';
        $('#messages-box').append(
            '<div class="message' + light + pm + '" data-id="' + val.id + '"><span class="date">(' + d +
            ')</span> <span class="' + val.user_role + ' text-bold nick">' + val.username + '</span>:<span class="message-text"> '
            + parseMessage(val.text) + '</span>' + del + '</div>'
        );
        if (!active) {
            newMessagesCount++;
            document.title = '(' + newMessagesCount + ') ' + title;
        }
    }

    function scrollMessages() {
        $('#messages-box').scrollTo('100%')
    }

    function changeChannel(channelId) {
        $.ajax({
            type: "POST",
            dataType: "json",
            url: changeChannelPath,
            data: {'channel': channelId}
        }).done(function (msg) {
            if (msg == true) {
                $('.channel').removeClass('active');
                $('.channel[data-value="' + channelId + '"]').addClass('active');
                usersOnline = [];
                clearChat();
                channelChanged = 1;
            }
        });
    }

    function clearChat() {
        $('#users-box').empty();
        $('#messages-box').empty();
    }

    function startChat() {
        var message = '';
        $('div.message').each(function () {
            message = $(this).children('span.message-text').html();
            $(this).children('span.message-text').html(parseMessage(message));
        });
        for (i = 0; i < emoticonsImg.length; i++) {
            $('div[name="emoticons"]').append(function () {
                if (Array.isArray(emoticons[i])) {
                    alt = emoticons[i][0];
                } else {
                    alt = emoticons[i];
                }
                return '<img src="' + emoticonsImg[i] + '" class="emoticon-img kursor" alt="' + alt + '"/>';
            });
        }
        $('div.online-user').each(function () {
            usersOnline.push($(this).attr('data-value'));
        });
    }

    function parseMessage(message) {
        return parseEmoticons(parseLinks(message));
    }

    function parseEmoticons(message) {
        for (i = 0; i < emoticons.length; i++) {
            if (Array.isArray(emoticons[i])) {
                for (j = 0; j < emoticons[i].length; j++) {
                    if (message.includes(emoticons[i][j])) {
                        message = message.replaceAll(emoticons[i][j], '<img src="' + emoticonsImg[i] + '" alt="' + emoticons[i][j] + '"/>');
                    }
                }
            } else {
                if (message.includes(emoticons[i])) {
                    message = message.replaceAll(emoticons[i], '<img src="' + emoticonsImg[i] + '" alt="' + emoticons[i] + '"/>');
                }
            }
        }
        return message;
    }

    //https://stackoverflow.com/a/3890175/6912075
    function parseLinks(inputText) {
        if (inputText.search("https://phs-phsa.ml/chat2/img/") !== -1) {
            return inputText;
        }
        var replacedText, replacePattern1, replacePattern2;

        //URLs starting with http://, https://, or ftp://
        replacePattern1 = /(\b(https?|ftp):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/gim;
        replacedText = inputText.replace(replacePattern1, '<a href="$1" target="_blank">$1</a>');

        //URLs starting with "www." (without // before it, or it'd re-link the ones done above).
        replacePattern2 = /(^|[^\/])(www\.[\S]+(\b|$))/gim;
        replacedText = replacedText.replace(replacePattern2, '$1<a href="http://$2" target="_blank">$2</a>');

        //Change email addresses to mailto:: links.
        //replacePattern3 = /(([a-zA-Z0-9\-\_\.])+@[a-zA-Z\_]+?(\.[a-zA-Z]{2,6})+)/gim;
        //replacedText = replacedText.replace(replacePattern3, '<a href="mailto:$1">$1</a>');

        return replacedText;
    }

    function changeLocale(locale) {
        window.location = languagePath[locale];
    }

    function notification(text) {
        if (Notification.permission === "granted" && !active) {
            // If it's okay let's create a notification
            var username = text.username;
            var messageText = text.text;

            var notification = new Notification(username, {'body': messageText});
            setTimeout(notification.close.bind(notification), 5000);
        }
    }

    function removeLineNewMessages() {
        if ($('.line').length) {
            $('.line').remove();
        }
    }

    $('#bbcode').on('click', '.btn', function() {
        var bbCode = $(this).attr('data-bbcode');

        insertBBCode(bbCode);
    });

    function insertBBCode(bbCode) {
        var bbCodeFirst = '[' + bbCode + ']';
        var bbCodeSecond = '[/' + bbCode + ']';

        insertText(bbCodeFirst, bbCodeSecond);
    }

    function insertText(textStart, textEnd) {
        var selectionStart = $('#message-text').prop('selectionStart');
        var selectionEnd = $('#message-text').prop('selectionEnd');
        var value = $('#message-text').val();

        var pos;
        if (selectionEnd - selectionStart) {
            value = value.insert(selectionEnd, textEnd);
            value = value.insert(selectionStart, textStart);
            pos = selectionStart + textStart.length + selectionEnd - selectionStart + bbCotextEndtextEndeSecond.length;
        } else {
            value = value.insert(selectionStart, textEnd);
            value = value.insert(selectionStart, textStart);
            pos = selectionStart + textStart.length;
        }

        $('#message-text').focus().val(value).prop('selectionStart', pos).prop('selectionEnd', pos);
    }
});
