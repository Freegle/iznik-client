// Our ServiceWorker is used for two purposes:
// - To hold the push subscription.  Unfortunately localStorage isn't allowed in service workers and
//   so we need to use indexedDB.  This allows us to receive push notifications from the server, and display them to
//   the client (most useful on mobile).
// - To cache our application to speed up page loads and (in time) work offline.  The use of ServiceWorkers is a
//   better approach than using appcache, which is initially appealing but turns into a nightmare.
const request = indexedDB.open( 'iznikDB', 1 );
var db;
var pushsub = null;
var version = null;

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
        if (request2.result) {
            version = request2.result.value;
            console.log("Retrieved version", version);
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
    console.log("SW Installed");
    self.skipWaiting();
});

self.addEventListener('activate', function(event) {
    self.clients.matchAll().then(function (clients) {
        for (var i = 0; i < clients.length; i++) {
            clients[i].postMessage({
                type: 'activated'
            });
        }
    });
});

self.addEventListener('message', function(event) {
    console.log("SW got message", event.data);
    console.log("Message type", event.data.type);
    
    switch(event.data.type) {
        case 'subscription':
            // We have been passed our push notification subscription, which we may use to authenticate ourselves
            // to the server when processing notifications.
            console.log("Save subscription ", event.data.subscription);
            var request = db.transaction(['swdata'], 'readwrite')
                .objectStore('swdata')
                .put({id: 'pushsubscription', value: event.data.subscription});

            request.onsuccess = function (e) {
                console.log("Saved subscription");
            };

            request.onerror = function (e) {
                console.error("Failed to save subscription", e);
                e.preventDefault();
            };
            break;

        case 'version': {
            // The normal Javascript client code has told us what our version number is.  This allows us to pre-cache
            console.log("Application version", version, event.data.version);
            if (version != event.data.version) {
                // The version has changed.
                console.log("Version changed");
                var request = db.transaction(['swdata'], 'readwrite')
                    .objectStore('swdata')
                    .put({id: 'version', value: event.data.version});

                request.onsuccess = function (e) {
                    console.log("Saved version", event.data.version);
                    version = event.data.version;
                };

                request.onerror = function (e) {
                    console.error("Failed to save version", e);
                    e.preventDefault();
                };
            } else {
                console.log("Version unchanged");
            }
            break;
        }
    }
});

self.addEventListener('push', function(event) {
    // At present there is no payload in the notification, so we need to query the server to get the information
    // we need to display to the user.  This is why we need our pushsub stored, so that we can authenticate to
    // the server.
    console.log('SW Push message received', event, pushsub);
    var url = new URL(self.registration.scope + '/api/session');

    if (pushsub) {
        // We add our push subscription as a way of authenticating ourselves to the server, in case we're
        // not already logged in.  A by product of this will be that it will log us in - but for the user
        // this is nice behaviour, as it means that if they click on a notification they won't be prompted
        // to log in.
        if (url.searchParams) {
            url.searchParams.append('pushcreds', pushsub);
            console.log("Add pushcreds", pushsub);
        } else {
            // Chrome mobile doesn't seem to support searchParams
            url = url + '?pushcreds=' + encodeURIComponent(pushsub);
            console.log("Add pushcreds into url", url);
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
                                    setTimeout(closeAll, 1000);
                                }

                                workstr = workstr == '' ? "No tasks outstanding" : workstr;
                            } else {
                                workstr = "No tasks outstanding";
                            }
                        } else {
                            // TODO User notifications.  Also change image and text below.
                        }
                    } catch (e) {
                        workstr = "Exception " + e.message;
                    }
                }

                // Show a notification.  Don't vibrate - that would be too annoying.
                return  self.registration.showNotification("ModTools", {
                    body: workstr,
                    icon: '/images/favicon/modtools/favicon-96x96.png',
                    tag: 'work',
                    data: {
                        'url': url
                    }
                });
            }).catch(function(err) {
                workstr = "Network error " + err
                setTimeout(closeAll, 1000);
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