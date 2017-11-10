//var API = 'https://www.ilovefreegle.org/api/'; // CC
//var API = 'https://dev.ilovefreegle.org/api/'; // CC
var API = 'https://iznik.ilovefreegle.org/api/'; // CC
var YAHOOAPI = 'https://groups.yahoo.com/api/v1/';
var YAHOOAPIv2 = 'https://groups.yahoo.com/api/v2/';

var isiOS = false; // CC
var useSwipeRefresh = false;
var initialURL = false;
var hammer = false;
var mobilePushId = false;
var mobilePush = false;
var lastPushMsgid = false;
var badgeconsole = '';
var divertConsole = false;
var showDebugConsole = false;

function panicReload() {
    // This is used when we fear something has gone wrong with our fetching of the code, and want to bomb out and
    // reload from scratch.
    console.error("Panic and reload");
    /* try { // CC
        // If we have a service worker, tell it to clear its cache in case we have bad scripts.
        navigator.serviceWorker.controller.postMessage({
            type: 'clearcache'
        });
    } catch (e) {}

    window.setTimeout(function() {
        window.location.reload();
    }, 1000);*/
}

requirejs.onError = function (err) {
    console.log("Require Error", err);
    //alert("Require Error " + err);
    var mods = err.requireModules;
    var msg = err.message;
    if (msg && msg.indexOf('showFirst') !== -1) {
        // TODO There's something weird about this plugin which means it sometimes doesn't load.  Ignore this until
        // we replace it.
        console.log("showFirst error - ignore for now");
    } else if (mods && mods.length == 1 && mods[0] === "ga") {
        // Analytics can be blocked by privacy tools.
        console.log("Analytics - ignore");
    } else {
        // Any require errors are most likely either due to flaky networks (so we should retry), bad code (which we'll
        // surely fix very soon now), or Service Worker issues with registering a new one while a fetch is outstanding.
        //
        // In all cases, reloading the page will help.  Delay slightly to avoid hammering the server.
        console.error("One we care about");
        panicReload();
    }
};

// Global error catcher so that we log to the server.
window.onerror = function(message, file, line) {
	console.error(message, file, line);
	/*$.ajax({
		url: API + 'error',
		type: 'PUT',
		data: {
			'errortype': 'Exception',
			'errortext': message + ' in ' + file + ' line ' + line
		}
	});*/
};

function showHeaderWait() {
    if (useSwipeRefresh) {
        var refreshicon = $('#refreshicon');
        refreshicon.show();
    } else {
        $('#refreshbutton span').addClass("rotate");
    }
}

function hideHeaderWait(event) {
    if (event) {    // If called as geolocationError
        console.log(event);
    }
    if (useSwipeRefresh) {
        var refreshicon = $('#refreshicon');
        refreshicon.hide();
    } else {
        $('#refreshbutton span').removeClass("rotate");
    }
}
function mobileRefresh() {
    showHeaderWait();
    Backbone.history.loadUrl();
    return false;
}

var isOnline = true;
function showNetworkStatus() {
    if (isOnline) {
        $('#nonetwork').addClass('reallyHide');
        $('#refreshbutton').removeClass('reallyHide');
    } else {
        $('#nonetwork').removeClass('reallyHide');
        $('#refreshbutton').addClass('reallyHide');
    }
}

window.Storage = {
    set: function (key, value) {
        localStorage.setItem(key, value);
    },
    get: function (key) {
        return localStorage.getItem(key);
    },
    remove: function (key) {
        localStorage.removeItem(key);
    },
    iterate: function (cb) {
        for (var i = 0; i < localStorage.length; i++) {
            var key = localStorage.key(i);
            var value = localStorage.getItem(key);
            cb(key, value);
        }
    },
};

// Called when app starts - and when it restarts when Router.mobileReload() called

if (typeof alllog === 'undefined') { 
    var alllog = "Log started: "+(new Date()).toISOString(); 
} 
var logtog = false; 

function mainOnAppStart() { // CC
console.log("main boot");	// CC
isiOS = (window.device.platform === 'iOS'); // CC
if (!initialURL) {
    initialURL = window.location.href;
}

console.log(device);

if (!isiOS) {   // vertical swipe on iOS stops scrolling
    var androidVersion = parseFloat(device.version);    // Not using Crosswalk so only enable swipe refresh for Android 4.4+
    if (androidVersion >= 4.4) {
        useSwipeRefresh = true;
    }
    useSwipeRefresh = false;    // CC Hammer doesn't work in CLI version on Nexus
}
setTimeout(function(){  // Have small delay at startup to try to avoid cannot load index.html error
require([
    'jquery',
    'underscore',
    'backbone',
    'iznik/router',
    'hammer'   // CC
], function ($, _, Backbone) {
    console.log("starting Backbone");	// CC
    if (!Backbone) {
        // Something has gone unpleasantly wrong.
        console.error("Backbone failed to fetch");
        panicReload();
    }

    $.ajaxSetup({
        mobileapp: 1
    });

    // Template to add link to /mobiledebug is in template/user/layout/layout.html
    if (divertConsole) {
        var oldconsolelog = console.log;
        console.log = function () {
            if (showDebugConsole) {
                var now = new Date();
                var msg = '###' + now.toJSON().substring(11) + ': ';
                for (var i = 0; i < arguments.length; i++) {
                    var arg = arguments[i];
                    if (typeof arg !== "string") {
                        arg = JSON.stringify(arg);
                    }
                    msg += arg + ' ';
                }
                if (msg.length > 300) {
                    msg = msg.substring(0,300)+'...';
                }
                msg += "\r\n";
                logtog = !logtog;
                alllog = msg + alllog;
                $('#js-mobilelog').val(alllog);
                //oldconsolelog(msg); 
            }
        }
    }

    // http://hammerjs.github.io/getting-started/

    if (useSwipeRefresh) {
        //hammer.get('swipe').set({ direction: Hammer.DIRECTION_ALL });
        //alert(typeof Hammer);
        hammer = new Hammer(window);
        //alert("got hammer");
        //alert(typeof hammer);
        //alert(JSON.stringify(hammer));
        hammer.get('swipe').set({ direction: Hammer.DIRECTION_VERTICAL });
        hammer.on('swipedown', function (ev) {
            //alert("hammer down");
            //console.log(ev);
            var posn = $(window).scrollTop();
            //console.log("posn=" + posn);
            //$('.navbar-title').text("D " + ev.deltaY + " " + posn);
            if (posn === 0) {
                mobileRefresh();
            }
        });
        //hammer.on('swipeleft swiperight', function (ev) {
        //    console.log(ev);
        //    $('.navbar-title').text("LR " + ev.deltaX + " " + ev.direction);
        //});
    }

    // Catch back button and clear chats
    window.addEventListener('popstate', function (e) {    // CC
        try {
            var ChatHolder = new Iznik.Views.Chat.Holder();
            ChatHolder.minimiseall();
        } catch (e) { }
    });

    document.addEventListener("offline", function () { isOnline = false; showNetworkStatus() }, false);
    document.addEventListener("online", function () { isOnline = true; showNetworkStatus() }, false);

    Backbone.emulateJSON = true;

    // We have a busy indicator.
    $(document).ajaxStop(function () {
        $('#spinner').hide();
        // We might have added a class to indicate that we were waiting for an AJAX call to complete.
        $('.showclicked').removeClass('showclicked');
        hideHeaderWait();
    });

    $(document).ajaxStart(function () {
        $('#spinner').show();
        showHeaderWait();
    });

    // We want to retry AJAX requests automatically, because we might have a flaky network.  This also covers us for
    // Backbone fetches.
    var _ajax = $.ajax;

    function sliceArgs() {
        return (Array.prototype.slice.call(arguments, 0));
    }

    function delay(errors) {
        // Exponential backoff upto a limit.
        return (Math.min(Math.pow(2, errors) * 1000, 30000));
    }

    function retryIt(jqXHR) {
        var self = this;
        this.errors = this.errors === undefined ? 0 : this.errors + 1;
        var thedelay = delay(this.errors);
        //console.log("retryIt", thedelay, this, arguments);
        console.log("retryIt", thedelay, this.responseURL); // CC
        setTimeout(function () {
            $.ajax(self);
        }, thedelay);
    }

    function extendIt(args, options) {
        _.extend(args[0], options && typeof options === 'object' ? options : {}, {
            error:   function (event, xhr) {
                if (xhr.statusText === 'abort') {
                    console.log("Aborted, don't retry");
                } else {
                    retryIt.apply(this, arguments);
                }
            }
        });
    }

    $.ajax = function (options) {
        var url = options.url;

        // There are some cases we don't want to subject to automatic retrying:
        // - Yahoo can validly return errors as part of its API, and we handle retrying via the plugin work.
        // - Where the context is set to a different object, we'd need to figure out how to implement the retry.
        // - File uploads, because we might have cancelled it.
        if (!options.hasOwnProperty('context') && url && url.indexOf('groups.yahoo.com') == -1 && url != API + 'upload') {
            // We wrap the AJAX call in our own, with our own error handler.
            var args;
            if (typeof options === 'string') {
                arguments[1].url = options;
                args = sliceArgs(arguments[1]);
            } else {
                args = sliceArgs(arguments);
            }

            extendIt(args, options);

            return _ajax.apply($, args);
        } else {
            return (_ajax.apply($, arguments));
        }
    };

    console.log("push init start");
    if (!PushNotification) {
        console.log("no push notification service");
        //alert("No PN");
    } else if( !mobilePushId) {
        mobilePush = PushNotification.init({
            android: {
                senderID: "845879623324",
                sound: false,
                //iconColor: "#5EcA24",
                //icon: "icon",
                //forceShow: true,
            },
            ios: {
                //senderID: "845879623324",
                alert: true,
                badge: true,
                sound: false
            }
        });
        mobilePush.on('registration', function (data) {
            mobilePushId = data.registrationId;
            console.log("push registration " + mobilePushId);
            //alert("registration: " + mobilePushId);
        });

        // Called to handle a push notification
        //
        // A push shows a notification immediately and sets desktop badge count (on iOS and some Android)
        // Note: badge count also set elsewhere when unseen chats counted (and may disagree!)
        //
        // Some of the following description is probably not now right (yet again):
        //
        // On iOS this handler is called immediately if running in foreground;
        //  it is not called if app not started; the handler is called when app started.
        //  if in background then the handler is called once immediately, and again when app shown (to cause a double event)
        //
        // On Android this handler is called immediately if running in foreground;
        //  it is not called if not started; the handler is called twice when app started (double event)
        //  if in background then the handler is called once immediately, and again when app shown (to cause a double event)
        mobilePush.on('notification', function (data) {
            //alert("push notification");
            console.log("push notification");
            console.log(data);
            var foreground = data.additionalData.foreground.toString() == 'true';   // Was first called in foreground or background
            var msgid = data.additionalData['google.message_id'];
            if (isiOS) {
                if (!('notId' in data.additionalData)) { data.additionalData.notId = 0; }
                msgid = data.additionalData.notId;
            }
            var doubleEvent = (msgid == lastPushMsgid);
            lastPushMsgid = msgid;
            console.log("foreground "+foreground+" double " + doubleEvent + " msgid: " + msgid);
            if (!('count' in data)) { data.count = 0; }
            if (data.count == 0) {
                mobilePush.clearAllNotifications();   // no success and error fns given
            }
            mobilePush.setApplicationIconBadgeNumber(function () { }, function () { }, data.count);
            /*var msg = new Date();
            msg = msg.toLocaleTimeString() + " N " + data.count + " "+foreground+' '+msgid+"<br/>";
            badgeconsole += msg;
            $('#badgeconsole').html(badgeconsole);*/
            if ((!foreground && doubleEvent) && (data.count > 0)) { // Only show chat if started/awakened ie not if in foreground
                var chatids = data.additionalData.chatids;
                chatids = _.uniq(chatids);

                if (chatids.length > 0) {

                    var chatid = chatids[0];
                    (function waitUntilLoggedIn(retry) {
                        if (Iznik.Session.loggedIn) {
                            //ChatHolder().fetchAndRestore(chatid);
                            setTimeout(function () { Router.navigate('/chat/' + chatid + '?' + $.now(), true); }, 500); // Add timestamp so chat refreshes
                        } else {
                            setTimeout(function () { if (--retry) { waitUntilLoggedIn(retry); } }, 1000);
                        }
                    })(10);
                }
            }
            /*require(['iznik/views/chat/chat'], function (ChatHolder) {
                ChatHolder().fallback();
            });*/

            if (isiOS) {
                mobilePush.finish(function () {
                        console.log("push finished OK");
                        //alert("finished");
                    }, function () {
                        console.log("push finished error");
                        //alert("finished");
                    },
                    data.additionalData.notId
                );
            }
        });

        mobilePush.on('error', function (e) {
            //alert("error: " + e.message);
            console.log("mobilePush error " + e.message);
        });
    }

    // Bootstrap adds body padding which we don't want.
    $('body').css('padding-right', '');
});
}, 250);

}; // CC
