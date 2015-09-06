var IznikRouter = Backbone.Router.extend({

    initialize: function(){
        this.bind('route', this.pageView);
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
        "modtools": "modtools",
        "*path": "home"
    },

    loadRoute: function(routeOptions){
        // Tidy any modal grey.
        $('.modal-backdrop').remove();

        // Remove any old alerts.
        $('.js-alerts').empty();

        // ...and qtips
        $(".qtip").remove();

        //console.log("loadRoute"); console.log(routeOptions);
        var self = this;
        routeOptions = routeOptions || {};

        function loadPage(options){
            try {
                options = options || {};

                self.listenToOnce(routeOptions.page, 'pageContentAdded', function(){
                    self.listenToOnce(Iznik.Session, 'isLoggedIn', function(loggedIn){
                        if (loggedIn) {
                        }
                    });

                    Iznik.Session.testLoggedIn();

                    // Select the right tab
                    $('.js-navbar a').each(function() {
                        //console.log($(this).prop('href'), $(this).prop('href').indexOf(Backbone.history.fragment));
                        if ($(this).prop('href').indexOf('/' + Backbone.history.fragment) !== -1) {
                            $(this).closest('li').addClass('active');
                        } else {
                            $(this).closest('li').removeClass('active');
                        }
                    });

                    self.pageLoaded = true;
                    self.trigger('pageContentAdded');
                });

                routeOptions.page.render();
            } catch (e) {
                console.log("Page load failed", e);
            }
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

            if(loggedIn){
                Router.navigate('/mygroups', {
                    trigger: true
                });
            }else{
                self.homePage = 'landing';
                var page = new Iznik.Views.Pages.Landing();
                self.loadRoute({page: page});
            }
        });

        console.log("Test logged in");
        Iznik.Session.testLoggedIn();
        console.log("Tested");
    },

    modtools: function() {
        console.log("ModTools");
        // We need to be signed in before we can tell if we're allowed to see the moderator tools.
        var self = this;
        this.listenToOnce(Iznik.Session, 'loggedIn', function(loggedIn){
            console.log("Logged in");
        });

        console.log("Test logged in");
        Iznik.Session.forceLogin();
    }
});

$(document).ready(function(){
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
