// This is a plugin to allow cross-site access to Yahoo from ModTools.
//
// We intercept requests and find ones originating from modtools.org, and set appropriate cookies.
// Then we intercept the responses to those requests and set an Access-Control-Allow-Origin to *.
// This fools Firefox into allowing our requests, which means our JS code on modtools.org can 
// make requests to Yahoo as though it was Yahoo's own client code.
function endsWith(haystack, str) {
    if (haystack.length == 0) {
        return false;
    }
    else {
        return haystack.lastIndexOf(str) == haystack.length - str.length;
    }
}

function log(msg) {
    Application.console.log("ModTools: " + msg);
}

var httpRequestObserver =
{
    observe: function (subject, topic, data) {
            if (topic == "http-on-modify-request") {
            var httpChannel = subject.QueryInterface(Ci.nsIHttpChannel);
            var origin = '';

            try {
                origin = httpChannel.getRequestHeader("Origin");
                //log("Origin " + origin);
            } catch (e) {
            }

            var referer = '';
            try {
                referer = httpChannel.getRequestHeader("Referer");
                //log("Referer " + referer);
            } catch (e) {
            }

            var cookie = '';

            var cookieMgr = Components.classes["@mozilla.org/cookiemanager;1"]
                .getService(Components.interfaces.nsICookieManager);

            var added = {};

            for (var e = cookieMgr.enumerator; e.hasMoreElements();) {
                var cookieval = e.getNext().QueryInterface(Components.interfaces.nsICookie);

                if (((cookieval.host == '.yahoo.com') || (cookieval.host == 'groups.yahoo.com')) &&
                    (cookieval.host.indexOf("analytics") === -1) &&
                    (cookieval.host.indexOf("help") === -1) &&
                    (cookieval.host.indexOf("mail") === -1) &&
                    (cookieval.name.indexOf("ywadp") === -1) &&
                    (cookieval.name.indexOf("fpc100") === -1) &&
                    (cookieval.name.indexOf("__utm") === -1) &&
                    (!added[cookieval.name])) {
                    //log(cookieval.host);
                    cookie += cookieval.name + "=" + cookieval.value + "; ";
                    added[cookieval.name] = true;
                }
            }

            log("Cookies " + cookie);

            if (((subject.originalURI.spec.indexOf("groups.yahoo.com/") !== -1) ||
                (subject.originalURI.spec.indexOf("direct.ilovefreegle.org/") !== -1)) &&
                ((endsWith(origin, "modtools.org")) ||
                (endsWith(referer, "modtools.org/")))) {
                if ((httpChannel.requestMethod == 'OPTIONS')) {
                    // This is a call where CORS has resulted in Firefox doing a preflight
                    // OPTIONS to Yahoo.  Yahoo will reject this, resulting in the
                    // subsequent actual operation not happening.
                    //
                    // We have passed the request data via a DIV, so we can make the call
                    // here.
                    //
                    // Get the data.
                    log("PUT/DELETE");
                    var wd = window.content.document;
                    var args = wd.getElementById('modtoolsreq').textContent;
                    log("Args " + args);

                    if (args.length > 0) {
                        args = JSON.parse(args);
                        log("Parsed" + args);

                        // Suspend the original request to make sure it doesn't complete
                        // until we're done.
                        subject.suspend();
                        log("suspended");

                        args.success = function (ret) {
                            // We succeed.  Store the response in the document.
                            log("Success");
                            log(ret);
                            var rsp = JSON.stringify(ret);
                            log("Response " + rsp);
                            wd.getElementById('modtoolsrsp').textContent = rsp;

                            // Now make the original request complete.
                            log("cancel");
                            subject.cancel(0x804b0002);
                            log('resume');
                            subject.resume();
                        }

                        args.error = function (request, status, error) {
                            // We failed.  Just cancel the request
                            log("Failed " + status + " " + error);
                            log("cancel");
                            subject.cancel(0x804b0002);
                            log('resume');
                            subject.resume();
                        }

                        log("Call ajax");
                        ajaxRequest(args.type, args.url, args.data, cookie, args.success, args.error)
                    } else {
                        log("No request passed");
                    }
                } else {
                    httpChannel.setRequestHeader("Cookie", cookie, false);
                    httpChannel.setRequestHeader("Origin", null, false);
                    httpChannel.setRequestHeader("Referer", null, false);

                    log("Save request for " + subject.originalURI.spec);
                    this.requests.push(httpChannel);
                }
            }
        }
        else if (topic == "http-on-examine-response") {
            var httpChannel = subject.QueryInterface(Ci.nsIHttpChannel);

            for (var i = 0; i < this.requests.length; i++) {
                if (this.requests[i] === httpChannel) {
                    log("Found corresponding request");
                    // Set ACAO to allow us in.
                    httpChannel.setResponseHeader("Access-Control-Allow-Origin", "*", false);
                    httpChannel.setResponseHeader("Access-Control-Allow-Methods", "POST, GET, OPTIONS, PUT, DELETE", false);
                    this.requests.splice(i, 1);
                }
            }
        }
    },

    get observerService() {
        return Cc["@mozilla.org/observer-service;1"].getService(Ci.nsIObserverService);
    },

    QueryInterface: function (aIID) {
        if (aIID.equals(Ci.nsIObserver) ||
            aIID.equals(Ci.nsISupports)) {
            return this;
        }

        throw Components.results.NS_NOINTERFACE;
    },

    register: function () {
        this.observerService.addObserver(this, "http-on-modify-request", false);
        this.observerService.addObserver(this, "http-on-examine-response", false);
        this.requests = new Array();
    },

    unregister: function () {
        this.observerService.removeObserver(this, "http-on-modify-request");
        this.observerService.removeObserver(this, "http-on-examine-response");
    }
};

log("Register");
httpRequestObserver.register();

function onLoad() {
    log("onLoad");
    var appcontent = window.document.getElementById("appcontent");

    if (appcontent && !appcontent.modtools) {
        appcontent.modtools = true;
        appcontent.addEventListener("DOMContentLoaded", contentLoaded, false);
    }
}

function contentLoaded(event) {
    log("contentLoaded");
    var wd = window.content.document;

    if (wd.getElementById('modtoolsfirefox') == null) {
        // Get version
        Components.utils.import("resource://gre/modules/AddonManager.jsm");

        AddonManager.getAddonByID("ModToolsUnlisted@edwardhibbert", function (addon) {
            var wd = window.content.document;
            var version = addon.version;
            var div = wd.createElement('div');
            div.style.display = 'none';
            div.id = 'modtoolsfirefox';
            div.innerHTML = version;
            wd.body.appendChild(div);
        });
    }
}

function ajaxRequest(verb, url, data, cookies, success, error) {
    var xhr = new XMLHttpRequest();
    xhr.open(verb, url, true);
    //xhr.setRequestHeader('Cookie', cookies);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
    var encoded = encodeURIComponent(JSON.stringify(data));
    xhr.setRequestHeader('Content-Length', encoded.length);
    xhr.onload = function (e) {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                success(xhr.responseText);
            } else {
                error(xhr, xhr.statusText, null);
            }
        }
    };
    xhr.onerror = function (e) {
        error(xhr, xhr.statusText, null);
    };
    xhr.send(data);
}

function contentLoad(event) {
    var wd = window.content.document;

    // Use custom event handler for PUT requests, which we can't manage to fool CORS into allowing.
    log("Add comms");
    var div = wd.createElement('div');
    div.style.display = 'none';
    div.id = 'modtools';
    div.innerHTML = version;
    div.addEventListener('put', function (event, param1) {
        log("Put called");
        var wd = window.content.document;
        var mt = wd.getElementById('modtools');
        var url = mt.data.url;
        log("Url is " + url);
        log("Param1");
        log(param1);
    });
    wd.body.appendChild(div);

    log("Added comms");
}

log("Register load");
window.addEventListener('load', onLoad, true);