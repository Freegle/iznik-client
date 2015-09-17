Iznik.Views.Pages.ModTools.Landing = Iznik.Views.Page.extend({
    modtools: true,

    template: "modtools_landing_main",

    render: function() {
        var self = this;
        var messagetitle, spamtitle, domaintitle;

        Iznik.Views.Page.prototype.render.call(this);

        $.ajax({
            url: API + 'dashboard',
            success: function(ret) {
                switch (Iznik.Session.get('me').systemrole){
                    case 'Admin':
                        messagetitle = 'Message History (System-wide)';
                        spamtitle = 'Spam Detection (System-wide)';
                        domaintitle = 'Email domains people use (System-wide)';
                        sourcetitle = 'How people send messages (System-wide)';
                        break;
                    default:
                        messagetitle = 'Message History (Your groups)';
                        spamtitle = 'Spam Detection (Your groups)';
                        domaintitle = 'Email domains people use (Your groups)';
                        sourcetitle = 'How people send messages (Your groups)';
                        break;
                }

                var coll = new Iznik.Collections.DateCounts(ret.dashboard.messagehistory);
                var graph = new Iznik.Views.MessageGraph({
                    target: self.$('.js-messagegraph').get()[0],
                    data: coll,
                    title: messagetitle
                });

                graph.render();

                coll = new Iznik.Collections.DateCounts(ret.dashboard.spamhistory);
                graph = new Iznik.Views.MessageGraph({
                    target: self.$('.js-spamgraph').get()[0],
                    data: coll,
                    title: spamtitle
                });

                graph.render();

                coll = new Iznik.Collections.DateCounts(ret.dashboard.domainhistory);
                graph = new Iznik.Views.DomainChart({
                    target: self.$('.js-domainchart').get()[0],
                    data: coll,
                    title: domaintitle
                });

                graph.render();

                coll = new Iznik.Collections.DateCounts(ret.dashboard.sourcehistory);
                graph = new Iznik.Views.SourceChart({
                    target: self.$('.js-sourcechart').get()[0],
                    data: coll,
                    title: sourcetitle
                });

                graph.render();
            }
        })
    }
});