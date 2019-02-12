//CCvar API = 'https://www.ilovefreegle.org/api/'; // CC
//CCvar YAHOOAPI = 'https://groups.yahoo.com/api/v1/';
//CCvar YAHOOAPIv2 = 'https://groups.yahoo.com/api/v2/';

//CCvar API = 'https://www.ilovefreegle.org/api/'; // CC
//CCvar CHAT_HOST = 'https://users.ilovefreegle.org';
//CCvar EVENT_HOST = 'iznik.modtools.org';
//CCvar USER_SITE = 'www.ilovefreegle.org';

window.isiOS = false // CC
window.useSwipeRefresh = false
window.initialURL = false
var hammer = false
window.mobilePushId = false
window.mobilePush = false
var lastPushMsgid = false
//var badgeconsole = ''
var divertConsole = false
window.showDebugConsole = false
window.isOnline = true

// CC var Raven = require('raven-js')

function panicReload() {
  // This is used when we fear something has gone wrong with our fetching of the code, and want to bomb out and
  // reload from scratch.
  console.error("Panic and reload")
  /* try { // CC
      // If we have a service worker, tell it to clear its cache in case we have bad scripts.
      navigator.serviceWorker.controller.postMessage({
          type: 'clearcache'
      });
  } catch (e) {}

  window.setTimeout(function() {
      window.location.reload()
  }, 1000); */
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
/*window.onerror = function(message, file, line) {
	console.error(message, file, line);
	$.ajax({
		url: API + 'error',
		type: 'PUT',
		data: {
			'errortype': 'Exception',
			'errortext': message + ' in ' + file + ' line ' + line
		}
	});
};*/

window.showHeaderWait = function () {
  if (window.useSwipeRefresh) {
    var refreshicon = jQuery('#refreshicon');
    refreshicon.show();
  } else {
    jQuery('#refreshbutton span').addClass("rotate");
  }
};

window.hideHeaderWait = function (event) {
  if (event) {    // If called as geolocationError
    console.log(event);
  }
  if (window.useSwipeRefresh) {
    var refreshicon = jQuery('#refreshicon');
    refreshicon.hide();
  } else {
    jQuery('#refreshbutton span').removeClass("rotate");
  }
};

window.mobileRefresh = function () {
  console.log("mobileRefresh");
  window.showHeaderWait();
  Backbone.history.loadUrl();
  return false;
}

window.showNetworkStatus = function () {
  if (window.isOnline) {
    jQuery('#nonetwork').addClass('reallyHide');
    jQuery('#refreshbutton').removeClass('reallyHide');
  } else {
    jQuery('#nonetwork').removeClass('reallyHide');
    jQuery('#refreshbutton').addClass('reallyHide');
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
  var alllog = "Log started: " + (new Date()).toISOString();
}
var logtog = false;

function mainOnAppStart() { // CC
  console.log("main boot")	// CC
  window.isiOS = (window.device.platform === 'iOS') // CC
  if (!window.initialURL) {
    window.initialURL = window.location.href
  }

  console.log(device)

  if (!window.isiOS) {   // vertical swipe on iOS stops scrolling
    var androidVersion = parseFloat(device.version)    // Not using Crosswalk so only enable swipe refresh for Android 4.4+
    if (androidVersion >= 4.4) {
      window.useSwipeRefresh = true
    }
    window.useSwipeRefresh = false    // CC Hammer doesn't work in CLI version on Nexus
  }

  // CC     Raven.context(function () {

  require([
    'jquery',
    'underscore',
    'backbone',
    'iznik/router',
    // CC 'hammer'   // CC
  ], function ($, _, Backbone) {
    console.log("starting Backbone")	// CC
    if (!Backbone) {
      // Something has gone unpleasantly wrong.
      console.error("Backbone failed to fetch")
      panicReload()
    }

    $.ajaxSetup({
      mobileapp: 1
    });

    // Template to add link to /mobiledebug is in template/user/layout/layout.html
    if (divertConsole) {
      var oldconsolelog = console.log;
      console.log = function () {
        if (window.showDebugConsole) {
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
            msg = msg.substring(0, 300) + '...';
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

    /* // CCif (window.useSwipeRefresh) {
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
                window.mobileRefresh();
            }
        });
        //hammer.on('swipeleft swiperight', function (ev) {
        //    console.log(ev);
        //    $('.navbar-title').text("LR " + ev.deltaX + " " + ev.direction);
        //});
    }*/

    // Catch back button and clear chats
    window.addEventListener('popstate', function (e) {    // CC
      try {
        var ChatHolder = new Iznik.Views.Chat.Holder()
        ChatHolder.minimiseall()
      } catch (e) { }
    });

    document.addEventListener("offline", function () { window.isOnline = false; console.log("offline"); window.showNetworkStatus() }, false);
    document.addEventListener("online", function () { window.isOnline = true; console.log("online"); window.showNetworkStatus() }, false);

    Backbone.emulateJSON = true;

    // We have a busy indicator.
    $(document).ajaxStop(function () {
      $('#spinner').hide();
      // We might have added a class to indicate that we were waiting for an AJAX call to complete.
      $('.showclicked').removeClass('showclicked');
      window.hideHeaderWait();
    });

    $(document).ajaxStart(function () {
      $('#spinner').show();
      window.showHeaderWait();
      if ((navigator.connection.type != Connection.NONE) && !window.isOnline) { // Remove red cloud if we are now actually online
        console.log("ajaxStart fire online");
        var event = new Event('online');
        document.dispatchEvent(event);
      }
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
      //console.log("retryIt", thedelay, this.responseURL); // CC
      setTimeout(function () {
        $.ajax(self);
      }, thedelay);
    }

    function extendIt(args, options) {
      _.extend(args[0], options && typeof options === 'object' ? options : {}, {
        error: function (event, xhr) {
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
    if ((typeof PushNotification === 'undefined') || (!PushNotification)) {
      console.log("NO PUSH NOTIFICATION SERVICE");
      //alert("No PN");
    } else if (!window.mobilePushId) {
      window.mobilePush = PushNotification.init({
        android: {
          senderID: "423761283916", // FCM: https://console.firebase.google.com/project/scenic-oxygen-849/settings/general/android:org.ilovefreegle.direct
          //senderID: "845879623324", // Old GCM way
          sound: false,
          iconColor: "#5EcA24",
          icon: "icon",
          //forceShow: true,
        },
        ios: {
          //senderID: "845879623324",
          alert: true,
          badge: true,
          sound: false
        }
      });
      window.mobilePush.on('registration', function (data) {
        window.mobilePushId = data.registrationId;
        console.log("push registration " + window.mobilePushId);
        //window.mobilePushId = false;
        //alert("registration: " + window.mobilePushId);
      });

      // Called to handle a push notification
      //
      // A push shows a notification immediately and sets desktop badge count (on iOS and some Android)
      // Note: badge count also set elsewhere when unseen chats counted (and may disagree!)
      //
      // Android:
      //  In foregound:   foreground: true:   doubleEvent: false
      //  In background:  foreground: false:  doubleEvent: false
      //           then:  foreground: false:  doubleEvent: true
      //  Not running:    as per background
      //
      // iOS:
      //  In foregound:   foreground: true:   doubleEvent: false
      //  In background:  foreground: false:  doubleEvent: false
      //           then:  foreground: false:  doubleEvent: true
      //  Not running:    as per background?

      window.mobilePush.on('notification', function (data) {
        console.log("push notification");
        console.log(data);
        var foreground = data.additionalData.foreground.toString() == 'true';   // Was first called in foreground or background
        var msgid = (new Date()).getTime();
        if ('notId' in data.additionalData) {
          msgid = data.additionalData.notId;
        }
        var doubleEvent = (msgid == lastPushMsgid);
        lastPushMsgid = msgid;
        if (!('count' in data)) { data.count = 0; }
        data.count = parseInt(data.count);
        console.log("foreground " + foreground + " double " + doubleEvent + " msgid: " + msgid + " count: " + data.count);
        if (data.count == 0) {
          window.mobilePush.clearAllNotifications();   // no success and error fns given
          console.log("clearAllNotifications");
        }
        //window.mobilePush.setApplicationIconBadgeNumber(function () { }, function () { }, data.count);
        console.log("push set badge: ", data.count, typeof (data.count));
        window.mobilePush.setApplicationIconBadgeNumber(
          function () { console.log("badge success") },
          function () { console.log("badge error") },
          data.count);
        /*var msg = new Date();
        msg = msg.toLocaleTimeString() + " N " + data.count + " "+foreground+' '+msgid+"<br/>";
        badgeconsole += msg;
        $('#badgeconsole').html(badgeconsole);*/

        // Always try to set in-app counts
        if (('chatcount' in data.additionalData) && ('notifcount' in data.additionalData)) {
          var chatcount = parseInt(data.additionalData.chatcount);
          var notifcount = parseInt(data.additionalData.notifcount);
          console.log("Got chatcount " + chatcount + " notifcount " + notifcount);
          if (!isNaN(chatcount) && !isNaN(notifcount)) {
            Iznik.setHeaderCounts(chatcount, notifcount);
            Iznik.Session.chats.fetch();
          }
        }

        // If in background or now in foreground having been woken from background
        if (('route' in data.additionalData) && !foreground && !doubleEvent && data.count) {
          (function waitUntilLoggedIn(retry) {
            if (Iznik.Session.loggedIn) {
              setTimeout(function () {
                console.log("Push go to: " + data.additionalData.route);
                Router.navigate(data.additionalData.route, true);
              }, 500);
            } else {
              setTimeout(function () { if (--retry) { waitUntilLoggedIn(retry); } }, 1000);
            }
          })(10);
        }

        if (foreground) { // Reload if route matches where we are - or if on any chat screen eg /chat/123456 or /chats
          var frag = '/' + Backbone.history.getFragment();
          if (data.additionalData.route) {
            if (frag == data.additionalData.route) {
              console.log("fg: Reload as route matches");
              Backbone.history.loadUrl();
            }
            else {
              if ((frag.substring(0, 5) == '/chat') && (data.additionalData.route.substring(0, 5) == '/chat')) {
                console.log("fg: Reload as route is on chat");
                Backbone.history.loadUrl(); // refresh rather than go to route
              }
            }
          }
        }

        /*if ((!foreground && doubleEvent) && (data.count > 0)) { // Only show chat if started/awakened ie not if in foreground
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
        }*/
        /*require(['iznik/views/chat/chat'], function (ChatHolder) {
            ChatHolder().fallback();
        });*/

        if (window.isiOS) {
          window.mobilePush.finish(function () {
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

      window.mobilePush.on('error', function (e) {
        //alert("error: " + e.message);
        console.log("mobilePush error " + e.message);
      });
    }

    // Bootstrap adds body padding which we don't want.
    $('body').css('padding-right', '');
  });

  // CC }); // CC
}

var mobileGlobalRoot = false   // CC
var oneOffPathname = false // CC

window.mobile_pathname = function () { // CC
  var pathname = window.location.pathname
  if (oneOffPathname) {
    pathname = oneOffPathname
    oneOffPathname = false
  }
  var initialHome = "index.html" // to remove
  if (pathname.substr(-initialHome.length) == initialHome) {
    pathname = pathname.substr(0, pathname.length - initialHome.length)
  }
  if (!mobileGlobalRoot) {
    mobileGlobalRoot = pathname.substr(0, pathname.length - 1)
  }
  pathname = pathname.substr(mobileGlobalRoot.length)
  if (pathname == "") {
    pathname += "/"
  }
  return pathname
}

document.addEventListener("app.Ready", mainOnAppStart, false)

// Fix up CSS cases with absolute url path
var style = document.createElement('style')
style.type = 'text/css'
var css = '.bodyback { background-image: url("' + iznikroot + 'images/wallpaper.png") !important; } \r'
css += '.dd .ddTitle{color:#000;background:#e2e2e4 url("' + iznikroot + 'images/msdropdown/skin1/title-bg.gif") repeat-x left top !important; } \r'
css += '.dd .ddArrow{width:16px;height:16px; margin-top:-8px; background:url("' + iznikroot + 'images/msdropdown/skin1/dd_arrow.gif") no-repeat !important;} \r'
css += '.splitter { background: url("' + iznikroot + 'images/vsizegrip.png") center center no-repeat !important; } \r'
style.innerHTML = css
//console.log(css)
document.getElementsByTagName('head')[0].appendChild(style)

