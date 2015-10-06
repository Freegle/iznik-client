// This is a plugin to allow cross-site access to Yahoo from ModTools.
//
// We intercept requests and find ones originating from modtools.org, and set appropriate cookies.
// Then we intercept the responses to those requests and set an Access-Control-Allow-Origin to *.
// This fools Firefox into allowing our requests, which means our JS code on modtools.org can
// make requests to Yahoo as though it was Yahoo's own client code.

var lastTabId;

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

var cookie;

function updateCookies() {
    chrome.cookies.getAllCookieStores(function (stores) {
        console.log("Get all cookies");
        console.log(stores);

        for (var s in stores) {
            //console.log("ID " + stores[s].id);

            newcookie = '';

            chrome.cookies.getAll({ url : "http://groups.yahoo.com", storeId: stores[s].id }, function (cookies) {
                //chrome.cookies.getAll({ storeId: stores[s].id }, function (cookies) {
                //console.log("Got cookies");
                //console.log(cookies);

                for (var j in cookies) {
                    //console.log(cookies[j].domain);
                    if ((cookies[j].domain === ".yahoo.com") ||
                        (cookies[j].domain === ".groups.yahoo.com"))
                    {
                        //console.log("Add for " + cookies[j].domain);
                        //console.log(cookies[j]);
                        newcookie += cookies[j].name + '=' + cookies[j].value + '; ';
                    }
                }

                cookie = newcookie;

                console.log("Got cookies for Yahoo Groups " + cookie);
            });
        }
    });
}

var requests = new Array();

chrome.webRequest.onBeforeSendHeaders.addListener(
    function(details) {

        var headers = details.requestHeaders;
        var blockingResponse = {};
        var tweak = false;

        console.log("Before send", details.url);
        if ((details.url.indexOf("groups.yahoo.com") !== -1) ||
            (details.url.indexOf("direct.ilovefreegle.org") !== -1)) {

            for (var i = 0; i < headers.length; ++i) {
                //console.log(headers[i].name + " = " + headers[i].value);
                if ((headers[i].name === 'Origin') ||
                    (headers[i].name === 'Referer')) {

                    if (((details.url.indexOf("groups.yahoo.com/") !== -1) ||
                        ((details.url.indexOf("direct.ilovefreegle.org/") !== -1))) &&
                        ((headers[i].value.indexOf("//dev.modtools.org") !== -1) ||
                        (headers[i].value.indexOf("//modtools.org") !== -1) ||
                        (headers[i].value.indexOf("//iznik.modtools.org") !== -1))) {

                        // This is a request we are interested in.
                        if (details.method != 'OPTIONS') {
                            if (details.tabId !== -1) {
                                // Save off tab id so that if we get any OPTIONS requests triggered
                                // from this tab then we will know the id.
                                console.log("Save tabid " + details.tabId);
                                lastTabId = details.tabId;
                            }

                            // We can make the request, as long as we add the cookies.
                            //headers[i].value = "http://groups.yahoo.com";
                            //console.log("Request to " + details.url + " " + headers[i].name + " rewritten to " + headers[i].value);
                            console.log("Save request " + details.requestId);
                            requests[details.requestId] = details;
                            tweak = true;

                            var header = {
                                name : 'Cookie',
                                value : cookie
                            };

                            headers.push(header);
                            //headers.push({ name: "X-FE-LS", value: "0" });

                            //console.log("Final headers with cookie " + cookie);
                            //console.log(headers);
                        } else {
                            // This is a call where CORS has resulted in Chrome doing a preflight
                            // OPTIONS to Yahoo.  Yahoo will reject this, resulting in the
                            // subsequent actual operation not happening.
                            //
                            // We have passed the request data via a DIV, so we can make the call
                            // here.
                            //
                            // Get the data.  To do this we have to ask the content script to access
                            // the window and get it for us.
                            console.log("OPTIONS");
                            chrome.tabs.sendMessage(lastTabId, {action: "getreq"}, function(ret) {
                                console.log("Content get returned response " + ret.request);
                                var args = JSON.parse(ret.request);
                                console.log(args);

                                args.success = function(ret) {
                                    // We succeed.  Store the response in the document.
                                    console.log("Success");
                                    console.log(ret);
                                    chrome.tabs.sendMessage(lastTabId, {action: "storersp", data: ret}, function(args) {
                                        console.log("Stored response");
                                    });
                                }

                                args.error = function (request, status, error) {
                                    // We failed.  Just cancel the request
                                    console.log("Failed " + status);
                                }

                                // We have to make this call async.  That is so that we can process the result
                                // and (if successful) set it up in the document, so that when the actual
                                // issued call fails due to CORS the response is there.
                                args.async = false;

                                console.log("Call ajax");
                                console.log(args);
                                $.ajax(args);
                                console.log("Called ajax");
                            });
                        }
                    }
                }
            }

            if (tweak) {

                console.log("Tweak");

                for (var i = 0; i < headers.length; ++i) {
                    if (headers[i].name === 'Origin') {
                        console.log("Origin");
                        headers.splice(i, 1);
                    }
                }

                for (var i = 0; i < headers.length; ++i) {
                    if (headers[i].name === 'Referer') {
                        console.log("Referer");
                        headers.splice(i, 1);
                    }
                }
            }
        }

        console.log("Set headers");
        blockingResponse.requestHeaders = headers;
        console.log("Updated headers"); console.log(headers);

        // Update cookies for next time - completes asyn.
        updateCookies();

        return blockingResponse;
    },
    {urls: ["<all_urls>"]},
    ["blocking", "requestHeaders"]);

chrome.webRequest.onHeadersReceived.addListener(
    function(details) {

        var blockingResponse = {
            responseHeaders: details.responseHeaders
        };

        //console.log("Response " + details.requestId);

        if (requests[details.requestId]) {
            console.log("Interested in " + details.requestId + details.url);
            //console.log(details);
            var found = false;

            for (var i = 0; i < blockingResponse.responseHeaders.length; ++i) {
                //console.log(headers[i].name + " = " + headers[i].value);

                if (blockingResponse.responseHeaders[i].name === 'Access-Control-Allow-Origin') {
                    console.log("Found Access-Control-Allow-Origin");
                    blockingResponse.responseHeaders[i].value = "*";
                    found = true;
                }
            }

            if (!found) {
                console.log("ACAO Not found, add");
                var header = {
                    name : 'Access-Control-Allow-Origin',
                    value : '*'
                };

                blockingResponse.responseHeaders.push(header);
            }

            for (var i = 0; i < blockingResponse.responseHeaders.length; ++i) {
                //console.log(headers[i].name + " = " + headers[i].value);

                if (blockingResponse.responseHeaders[i].name === 'Access-Control-Allow-Methods') {
                    console.log("Found Access-Control-Allow-Methods");
                    blockingResponse.responseHeaders[i].value = "POST, GET, OPTIONS, PUT, DELETE";
                    found = true;
                }
            }

            if (!found) {
                console.log("ACAO Not found, add");
                var header = {
                    name : 'Access-Control-Allow-Methods',
                    value : 'POST, GET, OPTIONS, PUT, DELETE'
                };

                blockingResponse.responseHeaders.push(header);
            }

            //console.log("Response headers");
            //console.log(headers);
        }

        return blockingResponse;
    },
    {urls: ["<all_urls>"]},
    ["blocking", "responseHeaders"]);


