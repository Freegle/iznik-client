Iznik.Views.Pages.ModTools.Landing = Iznik.Views.Page.extend({
    modtools: true,

    template: "modtools_landing_main",

    render: function() {
        var self = this;
        Iznik.Views.Page.prototype.render.call(this);

        $.ajax({
            url: API + 'dashboard',
            success: function(ret) {
                var coll = new Iznik.Collections.DateCounts(ret.dashboard.messagehistory);
                console.log("collection", coll, Iznik.Session);
                switch (Iznik.Session.get('me').systemrole){
                    case 'Admin':
                        title = 'Message History (System-wide)';
                        break;
                    default:
                        title = 'Message History (Your groups)';
                        break;
                }

                var graph = new Iznik.Views.MessageGraph({
                    target: self.$('.js-messagegraph').get()[0],
                    data: coll,
                    title: title
                });

                graph.render();
            }
        })
    }
});