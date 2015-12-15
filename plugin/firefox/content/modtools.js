// This is a plugin to allow cross-site access to Yahoo from ModTools.
//
// We intercept requests and find ones originating from modtools.org, and set appropriate cookies.
// Then we intercept the responses to those requests and set an Access-Control-Allow-Origin to *.
// This fools Firefox into allowing our requests, which means our JS code on modtools.org can 
// make requests to Yahoo as though it was Yahoo's own client code.

String.prototype.between = function(a,b)
{
    var p = this.indexOf(a);
    var q = this.indexOf(b, p+a.length);
    var ret = "";
    //LOG("p = " + p + " q = " + q + "\n");

    if ((p != -1) && (q != -1) && (q > p))
    {
        ret = this.substring(p+a.length, q)
    }

    return(ret);
}

function endsWith(haystack, str)
{
  if (haystack.length == 0)
  {
      return false;
  }
  else
  {
      return haystack.lastIndexOf(str) == haystack.length-str.length;
  }
}

function log(msg) 
{
  Application.console.log("ModTools: " + msg);
}

/**
*
*  Base64 encode / decode
*  http://www.webtoolkit.info/
*
**/
var Base64 = {

// private property
_keyStr : "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",

// public method for encoding
encode : function (input) {
    var output = "";
    var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
    var i = 0;

    input = Base64._utf8_encode(input);

    while (i < input.length) {

        chr1 = input.charCodeAt(i++);
        chr2 = input.charCodeAt(i++);
        chr3 = input.charCodeAt(i++);

        enc1 = chr1 >> 2;
        enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
        enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
        enc4 = chr3 & 63;

        if (isNaN(chr2)) {
            enc3 = enc4 = 64;
        } else if (isNaN(chr3)) {
            enc4 = 64;
        }

        output = output +
        this._keyStr.charAt(enc1) + this._keyStr.charAt(enc2) +
        this._keyStr.charAt(enc3) + this._keyStr.charAt(enc4);

    }

    return output;
},

// public method for decoding
decode : function (input) {
    var output = "";
    var chr1, chr2, chr3;
    var enc1, enc2, enc3, enc4;
    var i = 0;

    input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");

    while (i < input.length) {

        enc1 = this._keyStr.indexOf(input.charAt(i++));
        enc2 = this._keyStr.indexOf(input.charAt(i++));
        enc3 = this._keyStr.indexOf(input.charAt(i++));
        enc4 = this._keyStr.indexOf(input.charAt(i++));

        chr1 = (enc1 << 2) | (enc2 >> 4);
        chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
        chr3 = ((enc3 & 3) << 6) | enc4;

        output = output + String.fromCharCode(chr1);

        if (enc3 != 64) {
            output = output + String.fromCharCode(chr2);
        }
        if (enc4 != 64) {
            output = output + String.fromCharCode(chr3);
        }

    }

    output = Base64._utf8_decode(output);

    return output;

},

// private method for UTF-8 encoding
_utf8_encode : function (string) {
    string = string.replace(/\r\n/g,"\n");
    var utftext = "";

    for (var n = 0; n < string.length; n++) {

        var c = string.charCodeAt(n);

        if (c < 128) {
            utftext += String.fromCharCode(c);
        }
        else if((c > 127) && (c < 2048)) {
            utftext += String.fromCharCode((c >> 6) | 192);
            utftext += String.fromCharCode((c & 63) | 128);
        }
        else {
            utftext += String.fromCharCode((c >> 12) | 224);
            utftext += String.fromCharCode(((c >> 6) & 63) | 128);
            utftext += String.fromCharCode((c & 63) | 128);
        }

    }

    return utftext;
},

// private method for UTF-8 decoding
_utf8_decode : function (utftext) {
    var string = "";
    var i = 0;
    var c = c1 = c2 = 0;

    while ( i < utftext.length ) {

        c = utftext.charCodeAt(i);

        if (c < 128) {
            string += String.fromCharCode(c);
            i++;
        }
        else if((c > 191) && (c < 224)) {
            c2 = utftext.charCodeAt(i+1);
            string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
            i += 2;
        }
        else {
            c2 = utftext.charCodeAt(i+1);
            c3 = utftext.charCodeAt(i+2);
            string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
            i += 3;
        }

    }

    return string;
}

}

var httpRequestObserver =
{
  observe: function(subject, topic, data)
  {
    if (topic == "http-on-modify-request") {
      var httpChannel = subject.QueryInterface(Ci.nsIHttpChannel);
      var origin = '';

      try {
        origin = httpChannel.getRequestHeader("Origin");
        //log("Origin " + origin);
      } catch (e) {};

      var referer = '';
      try {
        referer = httpChannel.getRequestHeader("Referer");
        //log("Referer " + referer);
      } catch (e) {};

      var cookie = '';
      try {
        cookie = httpChannel.getRequestHeader("Cookie");
      } catch (e) {};

      //log("Request method " + httpChannel.requestMethod);

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
          $jq = jQuery.noConflict();
          wd = window.content.document;
          var args = $jq('#modtoolsreq', wd).text();
          log(args);

          if (args.length > 0) {
            args = JSON.parse(args);
            log(args);

            // Suspend the original request to make sure it doesn't complete
            // until we're done.
            subject.suspend();
            log("suspended");

            args.success = function(ret) {
              // We succeed.  Store the response in the document.
              log("Success");
              log(ret);
              $jq = jQuery.noConflict();
              wd = window.content.document;
              var rsp = JSON.stringify(ret);
              log("Response " + rsp);
              $jq('#modtoolsrsp', wd).text(rsp);

              // Now make the original request complete.
              log("cancel");
              subject.cancel(0x804b0002);
              log('resume');
              subject.resume();
            }

            args.error = function (request, status, error) {
              // We failed.  Just cancel the request
              log("Failed");
              log("cancel");
              subject.cancel(0x804b0002);
              log('resume');
              subject.resume();
            }

            log("Call ajax");
            $jq.ajax(args, wd);
          } else {
            log("No request passed");
          }
        } else {
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

      for (var i = 0; i < this.requests.length; i++) 
      {
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

  QueryInterface : function (aIID)
  {
      if (aIID.equals(Ci.nsIObserver) ||
          aIID.equals(Ci.nsISupports))
      {
          return this;
      }

      throw Components.results.NS_NOINTERFACE;

  },

  register: function()
  {
    this.observerService.addObserver(this, "http-on-modify-request", false);
    this.observerService.addObserver(this, "http-on-examine-response", false);
    this.requests = new Array();
  },

  unregister: function()
  {
    this.observerService.removeObserver(this, "http-on-modify-request");
    this.observerService.removeObserver(this, "http-on-examine-response");
  }
};

log("Register");
httpRequestObserver.register();

function onLoad() 
{
  var appcontent=window.document.getElementById("appcontent");

  if (appcontent && !appcontent.modtools) 
  {
    appcontent.modtools=true;
    appcontent.addEventListener("DOMContentLoaded", contentLoaded, false);
  }
}

function contentLoaded(event)
{
  $jq = jQuery.noConflict();
  wd = window.content.document;

  // Get version
  Components.utils.import("resource://gre/modules/AddonManager.jsm"); 
    
  AddonManager.getAddonByID("ModTools@edwardhibbert", function(addon) {  
    var version = addon.version;
    if ($jq('#modtoolsfirefox').length == 0) {
	$jq('body', wd).append('<div style="display:none" id="modtoolsfirefox">' + version + '</div>');
    }
  });  

  // Allow config migration
  if (window.content.document.URL.indexOf("modtools.org/index.php?action=settings") !== -1) {
    log("contentLoad");

    try {
      var data = {};

      var ls = window.content.localStorage;
      var children = toppref.getChildList("", {});

      for (i = 0; i < children.length; i++)
      {
        if (children[i].indexOf("cache.") === -1) {
          var val = getValue(children[i]);
          log(children[i] + " " + val);
          data[children[i]] = val;
        }
      }

      var jsonned = JSON.stringify(data);
      log("Finished, set " + jsonned.length);
      $jq('body', wd).append('<div id="modplugin" style="display: none;">' + Base64.encode(jsonned) + '</div>');
      log("Finished, set ok");
    } catch (ex) { log(ex.message); }
  }

  // Don't fire for frames.
  if ((!event.originalTarget.defaultView.frameElement) &&
      (window.content.document.URL.indexOf("modtools.org/index.php?action=settings") !== -1))
  {
    contentLoad(event);
  }
}

var startPoint="extensions.modplugin.";
var toppref=Components.classes["@mozilla.org/preferences-service;1"].
  getService(Components.interfaces.nsIPrefService).
  getBranch(startPoint);

function getValue(prefName, defaultValue) {
  try
  {
    var prefType=toppref.getPrefType(prefName);

    // underlying preferences object throws an exception if pref doesn't exist
    if (prefType==pref.PREF_INVALID) {
      return defaultValue;
    }

    switch (prefType) {
      case pref.PREF_STRING: return toppref.getCharPref(prefName);
      case pref.PREF_BOOL: return toppref.getBoolPref(prefName);
      case pref.PREF_INT: return toppref.getIntPref(prefName);
    }
  }
  catch (ex)
  {
    return(defaultValue);
  }
}

/**
*
*  Base64 encode / decode
*  http://www.webtoolkit.info/
*
**/
var Base64 = {

  // private property
  _keyStr : "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",

  // public method for encoding
  encode : function (input) {
      var output = "";
      var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
      var i = 0;

      input = Base64._utf8_encode(input);

      while (i < input.length) {

          chr1 = input.charCodeAt(i++);
          chr2 = input.charCodeAt(i++);
          chr3 = input.charCodeAt(i++);

          enc1 = chr1 >> 2;
          enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
          enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
          enc4 = chr3 & 63;

          if (isNaN(chr2)) {
              enc3 = enc4 = 64;
          } else if (isNaN(chr3)) {
              enc4 = 64;
          }

          output = output +
          this._keyStr.charAt(enc1) + this._keyStr.charAt(enc2) +
          this._keyStr.charAt(enc3) + this._keyStr.charAt(enc4);

      }

      return output;
  },

  // public method for decoding
  decode : function (input) {
      var output = "";
      var chr1, chr2, chr3;
      var enc1, enc2, enc3, enc4;
      var i = 0;

      input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");

      while (i < input.length) {

          enc1 = this._keyStr.indexOf(input.charAt(i++));
          enc2 = this._keyStr.indexOf(input.charAt(i++));
          enc3 = this._keyStr.indexOf(input.charAt(i++));
          enc4 = this._keyStr.indexOf(input.charAt(i++));

          chr1 = (enc1 << 2) | (enc2 >> 4);
          chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
          chr3 = ((enc3 & 3) << 6) | enc4;

          output = output + String.fromCharCode(chr1);

          if (enc3 != 64) {
              output = output + String.fromCharCode(chr2);
          }
          if (enc4 != 64) {
              output = output + String.fromCharCode(chr3);
          }

      }

      output = Base64._utf8_decode(output);

      return output;

  },

  // private method for UTF-8 encoding
  _utf8_encode : function (string) {
      string = string.replace(/\r\n/g,"\n");
      var utftext = "";

      for (var n = 0; n < string.length; n++) {

          var c = string.charCodeAt(n);

          if (c < 128) {
              utftext += String.fromCharCode(c);
          }
          else if((c > 127) && (c < 2048)) {
              utftext += String.fromCharCode((c >> 6) | 192);
              utftext += String.fromCharCode((c & 63) | 128);
          }
          else {
              utftext += String.fromCharCode((c >> 12) | 224);
              utftext += String.fromCharCode(((c >> 6) & 63) | 128);
              utftext += String.fromCharCode((c & 63) | 128);
          }

      }

      return utftext;
  },

  // private method for UTF-8 decoding
  _utf8_decode : function (utftext) {
      var string = "";
      var i = 0;
      var c = c1 = c2 = 0;

      while ( i < utftext.length ) {

          c = utftext.charCodeAt(i);

          if (c < 128) {
              string += String.fromCharCode(c);
              i++;
          }
          else if((c > 191) && (c < 224)) {
              c2 = utftext.charCodeAt(i+1);
              string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
              i += 2;
          }
          else {
              c2 = utftext.charCodeAt(i+1);
              c3 = utftext.charCodeAt(i+2);
              string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
              i += 3;
          }

      }

      return string;
  }

}

function ajaxRequest(verb, url, success, error) {
  var xhr = new XMLHttpRequest();
  xhr.open(verb, url, true);
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
  xhr.send(null);
}

function contentLoad(event) {
  $jq = jQuery.noConflict();
  wd = window.content.document;

  // Use custom event handler for PUT requests, which we can't manage to fool CORS into allowing.
  log("Add comms");
  $jq('body', wd).append('<div id="modtools"></div>');
  $jq('#modtools', wd).bind('put', function(event, param1) {
    log("Put called");
    var url = $jq('#modtools', wd).data('url');
    log("Url is " + url);
    log("Param1");
    log(param1);
  });
  log("Added comms");
}

log("Register load");
window.addEventListener('load', onLoad, true);

