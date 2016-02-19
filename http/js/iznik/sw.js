// Based on tutorial at https://developers.google.com/web/fundamentals/getting-started/push-notifications

self.addEventListener('install', function(event) {
    self.skipWaiting();
});
self.addEventListener('activate', function(event) {
});
self.addEventListener('push', function(event) {
    // We don't actually need to do anything with the notification - the purpose of it is to make the browser
    // alert the user to come back to us.
    //
    // At present there is no payload in the notification.
    console.log('Push message received', event);
    event.waitUntil(
        fetch('/api/session', {
            credentials: 'include'
        }).then(function(response) {
            return response.json().then(function(ret) {
                // Now we can decide what notification to show.
                var work = ret.work;
                var workstr = '';
                var url = '/modtools';

                // The order of these is intentional, because it controls what the value of url will be and therefore
                // where we go when we click the notification.
                var spam = work.spam + work.spammembers + work.spammerpendingadd + work.spammerpendingremove;

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

                workstr = workstr == '' ? "No tasks outstanding" : workstr;

                return  self.registration.showNotification("Moderation Tasks", {
                    body: workstr,
                    icon: '/images/favicon/favicon-96x96.png',
                    tag: 'work',
                    vibrate: [300, 100, 400],
                    data: {
                        'url': url
                    }
                })
            });
        })
    );
});
self.addEventListener('notificationclick', function(event) {
    var data = event.notification.data;
    var url = data.url ? data.url : '/';

    // Close the notification now we've clicked on it.
    event.notification.close();

    // This looks to see if the current is already open and
    // focuses if it is
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