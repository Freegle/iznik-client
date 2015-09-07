// We have a view for everything that is common across all pages, e.g. sidebars.
Iznik.Views.Page = IznikView.extend({
    render: function() {
        var self = this;

        // Set the base page layout
        $('body').html(window.template('layout'));
        $('.bodyContent').html(this.$el);

        // Put this page in
        this.$el.html(window.template(this.template)(Iznik.Session.toJSON2()));
        $('.pageContent').html(this.$el);

        // Show anything which should or shouldn't be visible based on login status.
        this.listenToOnce(Iznik.Session, 'isLoggedIn', function(loggedIn){
            if (loggedIn) {
                console.log("Loggedin only", $('.js-loggedinonly'));
                $('.js-loggedinonly').toggleClass('reallyHide');
                $('.js-loggedinonly').fadeIn('slow');
                $('.js-loggedoutonly').fadeOut('slow');
            } else {
                $('.js-loggedoutonly').toggleClass('reallyHide');
                $('.js-loggedoutonly').fadeIn('slow');
                $('.js-loggedinonly').fadeOut('slow');
            }
        });

        Iznik.Session.testLoggedIn();

        window.scrollTo(0, 0);

        // Let anyone who cares know.
        this.trigger('pageContentAdded');
    }
});

Iznik.Views.Pages.NotFound = Iznik.Views.Page.extend({
    template: "notfound"
});