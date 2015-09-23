Iznik.Views.Pages.ModTools.Landing = Iznik.Views.Page.extend({
    modtools: true,

    template: "modtools_landing_main",

    selected: null,

    updateGraphs: function() {
        var data = {};

        console.log("Currently selexted", this.selected);
        if (this.selected == -2) {
            console.log("System wide");
            data.systemwide = true;
        } else if (this.selected == -1) {
            console.log("All groups");
            data.allgroups = true;
        } else {
            console.log("Specific group");
            data.group = this.selected
        }

        $.ajax({
            url: API + 'dashboard',
            data: data,
            success: function(ret) {
                messagetitle = 'Message History';
                spamtitle = 'Spam Detection';
                domaintitle = 'Email domains people use';
                sourcetitle = 'How people send messages';

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
    },

    render: function() {
        var self = this;
        var messagetitle, spamtitle, domaintitle;

        Iznik.Views.Page.prototype.render.call(this);

        var v = new Iznik.Views.Group.Select({
            systemWide: true,
            all: true,
            id: 'statsGroupDropdown'
        });

        self.listenTo(v, 'selected', function(selected) {
            console.log("Selected group", selected);
            self.selected = selected;
            self.updateGraphs();
        });

        // Render after the listen to as they are called during render.
        self.$('.js-groupselect').html(v.render().el);
    }
});