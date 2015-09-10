// We have a view for everything that is common across all pages, e.g. sidebars.
Iznik.Views.Page = IznikView.extend({
    modtools: false,

    render: function() {
        var self = this;

        // Set the base page layout
        console.log("Render page", this.template, this.modtools);
        $('body').html(this.modtools ?
            window.template('modtools_layout_layout') :
            window.template('layout_layout'));
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

        // Sort out any menu
        $("#menu-toggle").click(function(e) {
            e.preventDefault();
            $("#wrapper").toggleClass("toggled");
        });

        window.scrollTo(0, 0);

        // Let anyone who cares know.
        this.trigger('pageContentAdded');
    }
});

Iznik.Views.Pages.NotFound = Iznik.Views.Page.extend({
    template: "notfound"
});