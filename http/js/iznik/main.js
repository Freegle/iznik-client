var API = 'https://modtools.org/api/'; // CC
//var API = 'https://iznik.ilovefreegle.org/api/'; // CC
var YAHOOAPI = 'https://groups.yahoo.com/api/v1/';
var YAHOOAPIv2 = 'https://groups.yahoo.com/api/v2/';

var isiOS = false; // CC
var useSwipeRefresh = false;
var initialURL = false;
var hammer = false;
var mobilePushId = false;
var mobilePush = false;

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
        var refreshbutton = $('#refreshbutton span');
        refreshbutton.addClass("no-before");
        var spinner = $("<img src='" + iznikroot + "images/loadermodal.gif' style='height:14px;' />");
        $(refreshbutton).html(spinner);
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
        var refreshbutton = $('#refreshbutton span');
        refreshbutton.removeClass("no-before");
        $(refreshbutton).html('');
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
        $('#nonetwork').hide();
    } else {
        $('#nonetwork').show();
    }
}

// Called when app starts - and when it restarts when Router.mobileReload() called

var alllog = "";    // TODOCC
var logtog = false;

function mainOnAppStart() { // CC
console.log("main boot");	// CC
isiOS = (window.device.platform === 'iOS'); // CC
if (!initialURL) {
    initialURL = window.location.href;
}

if (!isiOS) {   // vertical swipe on iOS stops scrolling
    var androidVersion = parseFloat(device.version);    // Not using Crosswalk so only enable swipe refresh for Android 4.4+
    if (androidVersion >= 4.4) {
        useSwipeRefresh = true;
    }
}

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

	  var oldconsolelog = console.log;  // TODOCC http://stackoverflow.com/questions/1215392/how-to-quickly-and-conveniently-disable-all-console-log-statements-in-my-code
	  console.log = function () {
	      var msg = '';
	      for (var i = 0; i < arguments.length; i++) {
	          var arg = arguments[i];
	          if (typeof arg !== "string") {
	              arg = JSON.stringify(arg);
	          }
	          msg += arg+' ';
	      }
	      if (logtog) {
	          msg = "<div style='background-color:#aaa;'>" + msg + "</div>";
	      } else {
	          msg = "<div>" + msg + "</div>";
	      }
	      logtog = !logtog;
	      alllog = msg + alllog;
	      $('#js-mobilelog').html(alllog);
	      //oldconsolelog(msg);
	  }

    // http://hammerjs.github.io/getting-started/

    if (useSwipeRefresh) {
        //hammer.get('swipe').set({ direction: Hammer.DIRECTION_ALL });
        hammer = new Hammer(window);
        hammer.get('swipe').set({ direction: Hammer.DIRECTION_VERTICAL });
        hammer.on('swipedown', function (ev) {
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
        console.log("retryIt", thedelay, this, arguments);
        // CC setTimeout(function () {
        // CC    $.ajax(self);
        // CC }, thedelay);
    }

    function extendIt(args, options) {
        _.extend(args[0], options && typeof options === 'object' ? options : {}, {
            error: function () { retryIt.apply(this, arguments); }
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
        alert("No PN");
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
            //$("#registrationId").val(data.registrationId);
            if (isiOS) {
                alert("registration: " + mobilePushId);
            }
        });

        mobilePush.on('notification', function (data) {
            //alert("push notification");
            mobilePush.clearAllNotifications();   // no success and error fns given
            if (data.count) {
                mobilePush.setApplicationIconBadgeNumber(function () { }, function () { }, data.count);
            }
            if (data.count > 0) {
                alert(JSON.stringify(data));
                console.log("push notification");
                console.log(data);
                var chatids = data.additionalData.chatids;
                chatids = _.uniq(chatids);

                require(['iznik/views/chat/chat'], function (ChatHolder) {
                    //_.each(chatids, function (chatid) {
                    //    ChatHolder().fetchAndRestore(chatid);
                    //});
                    // Just open first chat
                    if (chatids.length > 0) {
                        ChatHolder().fetchAndRestore(chatids[0]);
                    };
                });
            }

            mobilePush.finish(function () {
                console.log("push finished");
                //alert("finished");
            });
        });

        mobilePush.on('error', function (e) {
            alert("error: " + e.message);
        });
    }

});

}; // CC
