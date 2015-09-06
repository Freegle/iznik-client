// We have a view for everything that is common across all pages, e.g. sidebars.
Iznik.Views.Page = IznikView.extend({
    render: function() {
        // Set the base page layout
        $('body').html(window.template('layout'));
        $('.bodyContent').html(this.$el);

        // Put this page in
        this.$el.html(window.template(this.template)());
        $('.pageContent').html(this.$el);

        window.scrollTo(0, 0);

        // Let anyone who cares know.
        this.trigger('pageContentAdded');
    }
});

Iznik.Views.Pages.NotFound = Iznik.Views.Page.extend({
    template: "notfound"
});