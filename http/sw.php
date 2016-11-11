<?php
define('IZNIK_BASE', dirname(__FILE__) . '/..');
require_once('/etc/iznik.conf');
require_once(IZNIK_BASE . '/include/config.php');
require_once(IZNIK_BASE . '/include/misc/scripts.php');

header("Cache-Control: max-age=0, no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: Wed, 11 Jan 1984 05:00:00 GMT");
header('Content-type: text/javascript'); 

?>
// Our ServiceWorker is used for two purposes:
// - To hold the push subscription.  Unfortunately localStorage isn't allowed in service workers and
//   so we need to use indexedDB.  This allows us to receive push notifications from the server, and display them to
//   the client (most useful on mobile).
// - To cache our application to speed up page loads and (in time) work offline.  The use of service workers is a
//   better approach than using appcache, which is initially appealing but turns into a nightmare.  The great thing
///  about service workers is that a fetch occurs whenever the browser wants to retrieve something.  This is fantastic,
//   because it allows us to intercept them and return cached versions.  There's a nice tutorial about this
//   at https://www.smashingmagazine.com/2016/02/making-a-service-worker/.

var currentVersion = <?php echo getVersion(); ?>;
var lastVersion = null;

// Get our initial data for version and pushsub.
const request = indexedDB.open( 'iznikDB', 1 );
var db;
var pushsub = null;
var cacheConfig = {
    staticCacheItems: [],
    offlineImage: '<svg role="img" aria-labelledby="offline-title"' + ' viewBox="0 0 400 300" xmlns="http://www.w3.org/2000/svg">' + '<title id="offline-title">Offline</title>' + '<g fill="none" fill-rule="evenodd"><path fill="#D8D8D8" d="M0 0h400v300H0z"/>' + '<text fill="#9B9B9B" font-family="Times New Roman,Times,serif" font-size="72" font-weight="bold">' + '<tspan x="93" y="172">offline</tspan></text></g></svg>',
    offlinePage: '/offline/'
};

request.onsuccess = function() {
    db = this.result;

    // Now get our push subscription, if present.
    // Get our subscription from indexDB
    var transaction = db.transaction(['swdata']);
    var objectStore = transaction.objectStore('swdata');
    var request1 =  objectStore.get('pushsubscription');
    request1.onsuccess = function(event) {
        if (request1.result) {
            pushsub = request1.result.value;
            console.log("Retrieved pushsub", pushsub);
        }
    }

    var request2 =  objectStore.get('version');
    request2.onsuccess = function(event) {
        function preCache(opts) {
            var cacheKey = cacheName('static', opts);
            return caches.open(cacheKey)
                .then(cache => cache.addAll(opts.staticCacheItems));
        }

        lastVersion = request2.result ? request2.result.value : null;
        console.log("Retrieved version", lastVersion);

        if (currentVersion != lastVersion) {
            console.log("Version changed");
            caches.keys().then(function (cacheKeys) {
                    // We want to delete everything.
                    console.log("Cache to delete", cacheKeys);
                    cacheKeys.map(function (oldKey) {
                    console.log("Delete", oldKey);
                    caches.delete(oldKey);

                    var request = db.transaction(['swdata'], 'readwrite')
                        .objectStore('swdata')
                        .put({id: 'version', value: currentVersion});

                    request.onsuccess = function (e) {
                        console.log("Saved new version", currentVersion);
                    };

                    request.onerror = function (e) {
                        console.error("Failed to save new version", e);
                        e.preventDefault();
                    };

                    // Now precache anything we want.
                    preCache(cacheConfig);
                });
            });
        } else {
            // The version is the same.  Make sure we have anything we need in the cache.
            console.log("Version unchanged");
            preCache(cacheConfig);
        }

        request2.onerror = function(event) {
            console.log("Get version error", event);
        }
    }
};

request.onupgradeneeded = function(event) {
    var db = event.target.result;
    db.createObjectStore("swdata", {keyPath: "id"});
}

request.onerror = function(event) {
    console.log("SW IndexedDB error", event);
}

self.addEventListener('install', function(event) {
    console.log("SW installed");
    function onInstall(event, opts) {
        var cacheKey = cacheName('static', opts);
        return caches.open(cacheKey).then(function (cache) {
            return cache.addAll(opts.staticCacheItems);
        });
    }

    event.waitUntil(onInstall(event, cacheConfig).then(function () {
        return self.skipWaiting();
    }));
});

self.addEventListener('activate', function(event) {
    console.log("SW activated");
    return self.clients.claim();
});

self.addEventListener('message', function(event) {
    console.log("SW got message", event.data, event.data.type);
    
    switch(event.data.type) {
        case 'clearcache': {
            console.log("SW Clear cache");
            caches.keys().then(function (cacheKeys) {
                // We want to delete everything.
                console.log("SW Cache to delete", cacheKeys);
                cacheKeys.map(function (oldKey) {
                    caches.delete(oldKey);
                });
            });
            break;
        }

        case 'subscription': {
            // We have been passed our push notification subscription, which we may use to authenticate ourselves
            // to the server when processing notifications.
            console.log("SW Save subscription ", event.data.subscription);
            var request = db.transaction(['swdata'], 'readwrite')
                .objectStore('swdata')
                .put({id: 'pushsubscription', value: event.data.subscription});

            request.onsuccess = function (e) {
                console.log("SW Saved subscription");
            };

            request.onerror = function (e) {
                console.error("SW Failed to save subscription", e);
                e.preventDefault();
            };
            break;
        }
    }
});

self.addEventListener('push', function(event) {
    // At present there is no payload in the notification, so we need to query the server to get the information
    // we need to display to the user.  This is why we need our pushsub stored, so that we can authenticate to
    // the server.
    console.log('SW Push message received', event, pushsub);
    var url = new URL(self.registration.scope + 'api/session');

    if (pushsub) {
        // We add our push subscription as a way of authenticating ourselves to the server, in case we're
        // not already logged in.  A by product of this will be that it will log us in - but for the user
        // this is nice behaviour, as it means that if they click on a notification they won't be prompted
        // to log in.
        if (url.searchParams) {
            url.searchParams.append('pushcreds', pushsub);
            console.log("SW Add pushcreds", pushsub);
        } else {
            // Chrome mobile doesn't seem to support searchParams
            url = url + '?pushcreds=' + encodeURIComponent(pushsub);
            console.log("SW Add pushcreds into url", url);
        }
    }

    function closeAll() {
        registration.getNotifications({tag: 'work'}).then(function (notifications) {
            for (var i = 0; i < notifications.length; i++) {
                notifications[i].close();
            }
        });
    }

    event.waitUntil(
        fetch(url, {
            credentials: 'include'
        }).then(function(response) {
            return response.json().then(function(ret) {
                console.log("SW got session during push", ret);
                var workstr = '';
                var url = '/';

                if (ret.ret == 0) {
                    try {
                        if (ret.hasOwnProperty('work')) {
                            // We are a mod.
                            url = '/modtools';
                            // Now we can decide what notification to show.
                            var work = ret.work;

                            if (typeof(work) != 'undefined') {
                                // The order of these is intentional, because it controls what the value of url will be and therefore
                                // where we go when we click the notification.
                                var spam = work.spam + work.spammembers + ((ret.systemrole == 'Admin' || ret.systemrole == 'Support') ? (work.spammerpendingadd + work.spammerpendingremove) : 0);

                                if (spam > 0) {
                                    workstr += spam + ' spam ' + " \n";
                                    url = '/modtools/messages/spam';
                                }

                                if (work.pendingmembers > 0) {
                                    workstr += work.pendingmembers + ' pending member' + ((work.pendingmembers != 1) ? 's' : '') + " \n";
                                    url = '/modtools/members/pending';
                                }

                                if (work.pending > 0) {
                                    workstr += work.pending + ' pending message' + ((work.pending != 1) ? 's' : '') + " \n";
                                    url = '/modtools/messages/pending';
                                }

                                // Clear any we have shown so far.
                                closeAll();

                                if (workstr == '') {
                                    // We have to show a popup, otherwise we'll get the "updated in the background" message.  But
                                    // we can start a timer to clear the notifications later.
                                    setTimeout(closeAll, 2000);
                                }

                                workstr = workstr == '' ? "No tasks outstanding" : workstr;
                            } else {
                                workstr = "No tasks outstanding";
                            }
                        } else {
                            // TODO User notifications.  Also change image and text below.
                            setTimeout(closeAll, 2000);
                        }
                    } catch (e) {
                        workstr = "Exception " + e.message;
                    }
                }

                // Show a notification.  Don't vibrate - that would be too annoying.
                console.log("SW Return notification", workstr, url);
                return self.registration.showNotification("ModTools", {
                    body: workstr,
                    icon: '/images/favicon/modtools/favicon-96x96.png',
                    tag: 'work',
                    data: {
                        'url': url
                    }
                });
            }).catch(function(err) {
                // TODO retry?
                workstr = "Network error " + err;
                return self.registration.showNotification("ModTools", {
                    body: workstr,
                    icon: '/images/favicon/modtools/favicon-96x96.png',
                    tag: 'work',
                    data: {
                    'url': url
                    }
                });
                setTimeout(closeAll, 2000);
            });
        })
    );
});

self.addEventListener('notificationclick', function(event) {
    // We've clicked on a notification.  We want to try to open the site in the appropriate place to show the work.
    var data = event.notification.data;
    var url = data.url ? data.url : '/';

    // Close the notification now we've clicked on it.
    event.notification.close();

    // This looks to see if the site is already open and focuses if it is
    event.waitUntil(clients.matchAll({
        type: "window"
    }).then(function(clientList) {
        // Attempt to focus on existing client.  This probably doesn't work, though; see
        // https://github.com/slightlyoff/ServiceWorker/issues/758
        // TODO
        for (var i = 0; i < clientList.length; i++) {
            var client = clientList[i];
            if ('focus' in client)
                return client.focus();
        }
        if (clients.openWindow)
            return clients.openWindow(url + '?src=pushnotif');
    }));
});

// Now we're into our fetch cache.
//

function cacheName(key, opts) {
    // We just use the key; when our version number changes we will wipe out the cache, so we don't need
    // versioning in here.
    return key;
}

function addToCache(cacheKey, request, response) {
    if (response.ok) {
        var copy = response.clone();
        caches.open(cacheKey).then(function (cache) {
            try {
                cache.put(request, copy);
            } catch (e) {}
        });
    }
    return response;
}

function fetchFromCache(event) {
    return caches.match(event.request).then(function (response) {
        if (!response) {
            // console.log("SW not in cache", event.request.url);
            throw Error(event.request.url + ' not found in cache');
        }

        //console.log("SW found in cache", event.request.url, response);
        response.fromCache = true;

        return response;
    });
}

function offlineResponse(resourceType, opts) {
    if (resourceType === 'image') {
        return new Response(opts.offlineImage, { headers: { 'Content-Type': 'image/svg+xml' } });
    } else if (resourceType === 'content') {
        return caches.match(opts.offlinePage);
    }
    return undefined;
}

self.addEventListener('fetch', function(event) {

try {

    function shouldHandleFetch(event, opts) {
        // We want to cache:
        // - GET requests only
        // - Not the SW itself.
        // - Any file on our own domain which is not an API call, or an image (because
        //   we generate a lot of images and might fill our cache).
        var request = event.request;
        var url = new URL(request.url);
        var p = url.pathname.lastIndexOf('/');
        var dot = url.pathname.indexOf('.', p+1);
        var ret = request.method === 'GET' &&
            (url.origin === self.location.origin &&
                dot != -1 &&
                url.pathname.indexOf('sw.js') === -1 &&
                url.pathname.indexOf('img_') === -1 &&
                url.pathname.indexOf('/api') === -1 &&
                url.pathname.indexOf('/maintenance') === -1 &&
                url.pathname.indexOf('/subscribe') === -1)
        ;
        // console.log("Should handle", url, ret);

        return(ret);
    }

    function onFetch(event, opts) {
        var request = event.request;
        var acceptHeader = request.headers.get('Accept');
        var resourceType = 'static';
        var cacheKey;

        if (acceptHeader.indexOf('text/html') !== -1) {
            resourceType = 'content';
        } else if (acceptHeader.indexOf('image') !== -1) {
            resourceType = 'image';
        }

        cacheKey = cacheName(resourceType, opts);

        // Whatever the resourceType, we want to return a cached version first if we have it.
        event.respondWith(fetchFromCache(event).catch(function () {
            return fetch(request);
        }).then(function (response) {
            if (!response.fromCache) {
                // Only add to the cache if we didn't get it from there.
                return addToCache(cacheKey, request, response);
            } else {
                return response;
            }
        }).catch(function () {
            return offlineResponse(resourceType, opts);
        }));
    }

    if (shouldHandleFetch(event, cacheConfig)) {
        onFetch(event, cacheConfig);
    }
} catch (e) {
    console.log("Fetch exception", e);
}
});