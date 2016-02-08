var IznikRouter = Backbone.Router.extend({

    initialize: function(){
        var self = this;

        this.bind('route', this.pageView);

        self.listenToOnce(self, 'loadedPage', function() {
            // We start our syncs once - after that they are responsible for restarting themselves if they want to.
            self.listenToOnce(Iznik.Session, 'isLoggedIn', function(loggedIn){
                if (self.modtools) {
                    // This is a ModTools page - start any plugin work.
                    if (loggedIn) {
                        // Delay starting this work slightly to allow main part of page to load.
                        _.delay(function() {
                            IznikPlugin.startSyncs();
                            IznikPlugin.checkWork();
                        }, 500);
                    }
                }
            });

            Iznik.Session.testLoggedIn();
        });
    },

    pageView: function(){
        var url = Backbone.history.getFragment();

        if(!/^\//.test(url) && url != ""){
            url = "/" + url;
        }

        // Make sure we have google analytics for Backbone routes.
        if(!_.isUndefined(_gaq)){
            _gaq.push(['_trackPageview', url]);
        }
    },

    routes: {
        "yahoologin": "yahoologin",
        "modtools": "modtools",
        "modtools/supporters": "supporters",
        "modtools/messages/pending": "pendingMessages",
        "modtools/messages/approved(/:search)": "approvedMessages",
        "modtools/messages/spam": "spamMessages",
        "modtools/members/pending(/:search)": "pendingMembers",
        "modtools/members/approved(/:search)": "approvedMembers",
        "modtools/members/spam": "spamMembers",
        "modtools/spammerlist/pendingadd(/:search)": "spammerListPendingAdd",
        "modtools/spammerlist/confirmed(/:search)": "spammerListConfirmed",
        "modtools/spammerlist/pendingremove(/:search)": "spammerListPendingRemove",
        "modtools/spammerlist/whitelisted(/:search)": "spammerListWhitelisted",
        "modtools/settings": "settings",
        "*path": "home"
    },

    loadRoute: function(routeOptions){
        // Tidy any modal grey.
        $('.modal-backdrop').remove();

        //console.log("loadRoute"); console.log(routeOptions);
        var self = this;
        routeOptions = routeOptions || {};

        self.modtools = routeOptions.modtools;

        function loadPage(){
            firstbeep = true;
            routeOptions.page.render();
            self.trigger('loadedPage');
        }

        // Load the FB API.  If we're in a canvas app, it'll check if we're logged in, and if not try to do so.  Otherwise
        // it'll just complete, at which point we load the page.
        self.listenToOnce(FBLoad, 'fbloaded', function(){
            loadPage();
        });

        FBLoad.render();
    },

    home: function(){
        var self = this;
        this.listenToOnce(Iznik.Session, 'isLoggedIn', function(loggedIn){
            if (loggedIn || location.host.indexOf("modtools") != -1) {
                Router.navigate('/modtools', {
                    trigger: true
                });
            } else {
                var page = new Iznik.Views.User.Pages.Landing();
                self.loadRoute({page: page});
            }
        });

        Iznik.Session.testLoggedIn();
    },

    yahoologin: function(path) {
        var self = this;
        // We have been redirected here after an attempt to sign in with Yahoo.  We now try again to login
        // on the server.  This time we should succeed.
        var returnto = getURLParam('returnto');

        this.listenToOnce(Iznik.Session, 'yahoologincomplete', function(ret) {
            if (ret.ret == 0) {
                if (returnto) {
                    window.location = returnto;
                } else {
                    self.home.call(self);
                }
            } else {
                // TODO
                window.location = '/';
            }
        });

        Iznik.Session.yahooLogin();
    },

    modtools: function() {
        var self = this;
        this.listenToOnce(Iznik.Session, 'loggedIn', function(loggedIn){
            var page = new Iznik.Views.ModTools.Pages.Landing();
            self.loadRoute({page: page, modtools: true});
        });

        Iznik.Session.forceLogin();
    },

    supporters: function() {
        var page = new Iznik.Views.ModTools.Pages.Supporters();
        this.loadRoute({page: page});
    },

    pendingMessages: function() {
        this.listenToOnce(Iznik.Session, 'loggedIn', function(loggedIn){
            var page = new Iznik.Views.ModTools.Pages.PendingMessages();
            this.loadRoute({page: page, modtools: true});
        });

        Iznik.Session.forceLogin();
    },

    spamMessages: function() {
        this.listenToOnce(Iznik.Session, 'loggedIn', function(loggedIn){
            var page = new Iznik.Views.ModTools.Pages.SpamMessages();
            this.loadRoute({page: page, modtools: true});
        });

        Iznik.Session.forceLogin();
    },

    approvedMessages: function(search) {
        this.listenToOnce(Iznik.Session, 'loggedIn', function(loggedIn){
            var page = new Iznik.Views.ModTools.Pages.ApprovedMessages({
                search: search
            });
            this.loadRoute({
                page: page,
                modtools: true
            });
        });

        Iznik.Session.forceLogin();
    },

    pendingMembers: function(search) {
        this.listenToOnce(Iznik.Session, 'loggedIn', function(loggedIn){
            var page = new Iznik.Views.ModTools.Pages.PendingMembers({
                search: search
            });
            this.loadRoute({
                page: page,
                modtools: true
            });
        });

        Iznik.Session.forceLogin();
    },

    approvedMembers: function(search) {
        this.listenToOnce(Iznik.Session, 'loggedIn', function(loggedIn){
            var page = new Iznik.Views.ModTools.Pages.ApprovedMembers({
                search: search
            });
            this.loadRoute({
                page: page,
                modtools: true
            });
        });

        Iznik.Session.forceLogin();
    },

    spamMembers: function() {
        this.listenToOnce(Iznik.Session, 'loggedIn', function(loggedIn){
            var page = new Iznik.Views.ModTools.Pages.SpamMembers();
            this.loadRoute({page: page, modtools: true});
        });

        Iznik.Session.forceLogin();
    },

    spammerListPendingAdd: function(search) {
        this.listenToOnce(Iznik.Session, 'loggedIn', function(loggedIn){
            var page = new Iznik.Views.ModTools.Pages.SpammerList({
                search: search,
                urlfragment: 'pendingadd',
                collection: 'PendingAdd',
                helpTemplate: 'modtools_spammerlist_help_pendingadd'
            });
            this.loadRoute({page: page, modtools: true});
        });

        Iznik.Session.forceLogin();
    },

    spammerListPendingRemove: function(search) {
        this.listenToOnce(Iznik.Session, 'loggedIn', function(loggedIn){
            var page = new Iznik.Views.ModTools.Pages.SpammerList({
                search: search,
                urlfragment: 'pendingremove',
                collection: 'PendingRemove',
                helpTemplate: 'modtools_spammerlist_help_pendingremove'
            });
            this.loadRoute({page: page, modtools: true});
        });

        Iznik.Session.forceLogin();
    },

    spammerListConfirmed: function(search) {
        this.listenToOnce(Iznik.Session, 'loggedIn', function(loggedIn){
            var page = new Iznik.Views.ModTools.Pages.SpammerList({
                search: search,
                urlfragment: 'confirmed',
                collection: 'Spammer',
                helpTemplate: 'modtools_spammerlist_help_confirmed'
            });
            this.loadRoute({page: page, modtools: true});
        });

        Iznik.Session.forceLogin();
    },

    spammerListWhitelisted: function(search) {
        this.listenToOnce(Iznik.Session, 'loggedIn', function(loggedIn){
            var page = new Iznik.Views.ModTools.Pages.SpammerList({
                search: search,
                urlfragment: 'whitelisted',
                collection: 'Whitelisted',
                helpTemplate: 'modtools_spammerlist_help_whitelisted'
            });
            this.loadRoute({page: page, modtools: true});
        });

        Iznik.Session.forceLogin();
    },

    settings: function() {
        this.listenToOnce(Iznik.Session, 'loggedIn', function(loggedIn){
            var page = new Iznik.Views.ModTools.Pages.Settings();
            this.loadRoute({page: page, modtools: true});
        });

        Iznik.Session.forceLogin();
    }
});

var Router;

$(document).ready(function(){
    // We have a busy indicator.
    $(document).ajaxStop(function () {
        $('#spinner').hide();
    });

    $(document).ajaxStart(function () {
        $('#spinner').show();
    });

    // We're ready.  Get backbone up and running.
    Router = new IznikRouter();

    try {
        Backbone.history.start({
            pushState: true
        });
    } catch (e) {
        // We've got an uncaught exception.
        // TODO Log it to the server.
        console.log("Top-level exception", e);
        console.trace();
    }

    // Start the plugin
    IznikPlugin.render();
});

// We can flag anchors as not to be handled via Backbone using data-realurl
$(document).on('click', 'a:not([data-realurl]):not([data-toggle])', function(evt){
    // Don't trigger for anchors within selectpicker.
    if ($(this).parents('.selectpicker').length == 0) {
        evt.preventDefault();
        evt.stopPropagation();
        var href = $(this).attr('href');
        var ret = Router.navigate(href, {trigger: true});

        if (ret === undefined && $link.hasClass('allow-reload')) {
            Backbone.history.loadUrl(href);
        }
    }
});
