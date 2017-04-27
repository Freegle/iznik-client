function getVersion() {
    var version = 'NaN';
    var xhr = new XMLHttpRequest();
    xhr.open('GET', chrome.extension.getURL('manifest.json'), false);
    xhr.send(null);
    var manifest = JSON.parse(xhr.responseText);
    return manifest.version;
}

//var version = chrome.app.getDetails().version;
var version = getVersion();

var d = document.createElement('div');
d.id = 'modtoolschrome';
d.style.display = "none";
d.innerHTML = version;
document.body.appendChild(d);

chrome.runtime.onMessage.addListener(
    function (request, sender, sendResponse) {
        //console.log("Content script message received");
        //console.log(request);

        if (request.action == 'getreq') {
            var data = $('#modtoolsreq').text();
            //console.log("Got majax data " + data);
            sendResponse({request: data});
        } else if (request.action == 'storersp') {
            var data = $('#modtoolsrsp').text();
            //console.log("Got majax data " + data);
            var rsp = JSON.stringify(request.data);
            //console.log("Store response len " + rsp.length);
            $('#modtoolsrsp').text(rsp);
            sendResponse({});
        }
    }
);

console.log("ModTools Content Script Loaded");

function keyText(text) {
    for (var i = 0; i < text.length; i++) {
        var e = new Event("keypress");
        var char = text.substring(i, i+1);
        console.log("Key", char);
        e.key=char;
        e.keyCode=e.key.charCodeAt(0);
        e.which=e.keyCode;
        e.altKey=false;
        e.ctrlKey=false;
        e.shiftKey=false;
        e.metaKey=false;
        // e.bubbles=true;
        // e.isTrusted = true;
        document.dispatchEvent(e);
    }
}

(function( $ ) {
    $.fn.execInsertText = function(text) {
        var activeElement = document.activeElement;
        var result = this.each(function() {
            this.focus();
            document.execCommand('selectAll');
            document.execCommand('insertText', false, text);
        });
        if (activeElement) {
            activeElement.focus();
        }
        return result;
    };
}( jQuery ));

function waitFor(check, parm) {
    var p = new Promise(function(resolve, reject) {
        function checkIt() {
            if (check()) {
                resolve(parm);
            } else {
                window.setTimeout(checkIt, 100);
            }
        }

        checkIt();
    });

    return(p);
}

function shuffle(array) {
    var currentIndex = array.length, temporaryValue, randomIndex;

    // While there remain elements to shuffle...
    while (0 !== currentIndex) {

        // Pick a remaining element...
        randomIndex = Math.floor(Math.random() * currentIndex);
        currentIndex -= 1;

        // And swap it with the current element.
        temporaryValue = array[currentIndex];
        array[currentIndex] = array[randomIndex];
        array[randomIndex] = temporaryValue;
    }

    return array;
}

function status(str) {
    $('#mtstatus').html(str);
}

function statusHide() {
    $('#mtholder').hide();
}

function injectScript(file, node) {
    var th = document.getElementsByTagName(node)[0];
    var s = document.createElement('script');
    s.setAttribute('type', 'text/javascript');
    s.setAttribute('src', file);
    th.appendChild(s);
}

function FindReact(dom) {
    for (var key in dom) {
        if (key.startsWith("__reactInternalInstance$")) {
            var compInternals = dom[key]._currentElement;
            var compWrapper = compInternals._owner;
            var comp = compWrapper._instance;
            return comp;
        }
    }
    return null;
}

var topost = [];
var msgcount = 0;
var postmsgs = [];

function checkMessages(group, facebook) {
    // We have a closure to hold the group and facebook info.
    return(function() {
        // Return a promise which we resolve when we've completed the check.
        var p = new Promise(function(resolve, reject) {
            // Put our status div in the bottom left
            $('body').append('<div style="position: fixed; bottom: 0; left: 0; background: #e8fefb" id="mtholder"><table><tbody><tr><td><img src="https://iznik.modtools.org/images/modtools_logo.png" width="40px" /></td><td id="mtstatus"></td></tr></tbody></table>')
            status('Checking ' + group.nameshort + '...');

            // Ask the server which messages need posing.
            $.ajax({
                url: 'https://iznik.modtools.org/api/messages?uid=' + facebook.uid + '&facebook_postable=true',
                type: 'GET',
                success:function(ret) {
                    console.log("Got unposted", ret);

                    if (ret.messages.length > 0) {
                        topost.push({
                            group: group,
                            facebook: facebook,
                            messages: ret.messages
                        });

                        msgcount += ret.messages.length;
                    }

                    resolve();
                }
            });
        });

        return(p);
    })
}

function postMessage(details) {
    status('Posting ' + details.facebook.name + ', left ' + postmsgs.length + '...');
    var message = postmsgs.shift();
    console.log("Post", message);
    //var url = "https://www.ilovefreegle.org/message/" + message.id + "?src=fbgroup2";
    var url = "https://ilovefreegle.org/m/" + message.id + '?src=g';
    var subj = message.subject;
    subj = subj.length > 50 ? subj.substring(0, 50) : subj;

    waitFor(function () {
        var ret = false;
        $('input').each(function () {
            var placeholder = $(this).prop('placeholder');
            if (placeholder == 'What are you selling?') {
                ret = true;
            }
        });

        return (ret);
    }).then(function () {
        $('input').each(function () {
            var placeholder = $(this).prop('placeholder');
            if (placeholder == 'What are you selling?') {
                var inp = this;
                $(inp).click();
                $(inp).focus();

                waitFor(function () {
                    var ret = false;
                    $('input').each(function () {
                        var placeholder = $(this).prop('placeholder');
                        if (placeholder == 'Add price') {
                            ret = true;
                        }
                    });

                    return (ret);
                }).then(function () {
                    $('input').each(function () {
                        var placeholder = $(this).prop('placeholder');
                        if (placeholder == 'What are you selling?') {
                            buyandsell = true;
                            $(this).execInsertText(message.subject + "...reply at " + url);
                        }

                        if (placeholder == 'Add price') {
                            $(this).execInsertText(0);
                        }

                        if (placeholder == 'Add location (optional)') {
                            try {
                                var next = $(this).closest('div').find('button');
                                if (next.prop('title') == 'Remove') {
                                    // Click to remove default location.
                                    next.click();
                                }

                                var loc = this;
                                window.setTimeout(function () {
                                    if (message.hasOwnProperty('area')) {
                                        $(loc).execInsertText(message.area.name);
                                        // $(loc).blur();
                                    }

                                    // var url = "https://modtools.org/message/" + message.id + "?src=fbgroup2";
                                    // var inp = $('#composer_text_input_box').find('div[contenteditable=true]');
                                    // console.log("Desc in", inp);
                                    // inp.focus();
                                    //
                                    // window.setTimeout(function () {
                                    //     // console.log("Do inject");
                                    //     // injectScript( chrome.extension.getURL('/modtoolsinject.js'), 'body');
                                    // }, 5000);

                                    window.setTimeout(function () {
                                        $.ajax({
                                            url: 'https://iznik.modtools.org/api/messages',
                                            type: 'POST',
                                            data: {
                                                'action': 'UpdateFacebookPostable',
                                                'uid': details.facebook.uid,
                                                'id': message.id,
                                                'arrival': message.arrival
                                            },
                                            success: function (ret) {
                                                $('#pagelet_group_composer button').click();
                                                if (postmsgs.length > 0) {
                                                    window.setTimeout(postMessage, 30000, details);
                                                } else {
                                                    postMessages();
                                                }
                                            }
                                        });
                                    }, 1000);
                                }, 1000);
                            } catch (e) {
                                console.log("Failed on location", e.message);
                            }

                        }

                        // $('#pagelet_group_composer button').each(function() {
                        //     if ($(this).prop('disabled')) {
                        //         $(this).prop('disabled', null);
                        //     }
                        // });
                    });
                });
            }
        });
    });

    waitFor(function () {
        var ret = false;
        $('em').each(function () {
            if ($(this).html() == 'Write Post') {
                ret = true;
            }
        });

        return (ret);
    }).then(function () {
        console.log("Not a buy and sell");
        postMessages();
    });
}

function postMessages() {
    var details = topost.pop();
    console.log("Posting details", details);

    if (details) {
        status('Posting ' + details.facebook.name + '...');
        var url = document.URL;

        var fbgroup = "https://www.facebook.com/groups/" + details.facebook.id + '/';
        console.log("Compare urls", document.URL, fbgroup);

        if (document.URL === fbgroup) {
            // We're already there - post them.
            postmsgs = details.messages;
            console.log("Already there, post", postmsgs);

            // If this doesn't work out, we don't want to launch right into it again if they reload the page.
            try {
                localStorage.removeItem('mtconfirmed');
            } catch (e) {}

            postMessage(details);
        } else {
            // We need to navigate.
            console.log("Need to navigate");
            document.location = fbgroup;
        }
    } else {
        status('Up to date');
    }
}

$(document).ready(function() {
    console.log("MT Document ready", document.URL);

    if (document.URL.indexOf('modtools.org') != -1) {
        // We are loading on a ModTools page.  Find out who we are; when we load on Facebook we use this info.
        $.ajax({
            url: '/api/session',
            type: 'GET',
            success: function (ret) {
                console.log("Logged in", ret);
                if (ret.ret === 0) {
                    var groupstopost = [];

                    if (ret.hasOwnProperty('groups')) {
                        for (var i = 0; i < ret.groups.length; i++) {
                            var group = ret.groups[i];
                            if (group.facebook) {
                                groupstopost.push({
                                    id: group.id,
                                    nameshort: group.nameshort,
                                    facebook: group.facebook
                                });
                            }
                        }

                        chrome.storage.sync.set({
                            'myid': ret.me.id,
                            'groups': groupstopost
                        });
                    }
                }
            }
        });
    } else if (document.URL.indexOf('https://www.facebook.com') === 0) {
        // We are loading on Facebook.  Find out who we are.
        chrome.storage.sync.get(null, function(obj) {
            console.log("MT ID", obj);
            var myid = obj.myid;
            var groups = obj.groups;

            if (myid && groups.length > 0) {
                // We know who we are (or were when we last logged in).  Get the groups we need to share posts on.
                var promises = [];

                for (var i = 0; i < groups.length; i++) {
                    var group = groups[i];
                    var facebooks = group.facebook;

                    for (var j = 0; j < facebooks.length; j++) {
                        var facebook = facebooks[j];

                        if (facebook.type == 'Group') {
                            var p = checkMessages(group, facebook)();
                            console.log("Check for", group,p);
                            promises.push(p);
                        }
                    }
                }

                console.log("Waiting for", promises.length);

                Promise.all(promises).then(function() {
                    console.log("Completed all checks", topost);

                    var confirmed = false;

                    try {
                        confirmed = localStorage.getItem('mtconfirmed');
                    } catch (e) {
                        console.log("LS exception", e.message);
                    }

                    if (!confirmed) {
                        status('Asking...');
                        confirmed = window.confirm("May ModTools post " + msgcount + " post" + ((msgcount != 1) ? 's' : '') + '?');
                        try {
                            localStorage.setItem('mtconfirmed', confirmed);
                        } catch (e) {
                            console.log("LS exception", e.message);
                        }
                    }

                    if (confirmed) {
                        // Shuffle so that if we keep interrupting, we will give different groups a chance.
                        // topost = shuffle(topost);
                        postMessages();
                    } else {
                        statusHide();
                    }
                });
            } else {
                // We don't know who we are yet - we mustn't have yet loaded MT with this plugin.
                status('<font color="red">Please log in to ModTools</font>');
            }
        });
    }
});