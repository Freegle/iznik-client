Iznik.Views.ModTools.Pages.Landing = Iznik.Views.Page.extend({
    modtools: true,

    template: "modtools_landing_main",

    selected: null,

    updateGraphs: function() {
        var data = {};

        if (this.selected == -2) {
            data.systemwide = true;
        } else if (this.selected == -1) {
            data.allgroups = true;
        } else {
            data.group = this.selected
        }

        var statsGroupType = $('#statsGroupType');
        if (statsGroupType.val() != 'null') {
            data.grouptype = statsGroupType.val();
        }

        $.ajax({
            url: API + 'dashboard',
            data: data,
            success: function(ret) {
                var coll = new Iznik.Collections.DateCounts(ret.dashboard.messagehistory);
                var graph = new Iznik.Views.MessageGraph({
                    target: self.$('.js-messagegraph').get()[0],
                    data: coll,
                    title: 'Message History'
                });

                graph.render();

                coll = new Iznik.Collections.DateCounts(ret.dashboard.spamhistory);
                graph = new Iznik.Views.MessageGraph({
                    target: self.$('.js-spamgraph').get()[0],
                    data: coll,
                    title: 'Spam Detection'
                });

                graph.render();

                coll = new Iznik.Collections.DateCounts(ret.dashboard.typehistory);
                graph = new Iznik.Views.TypeChart({
                    target: self.$('.js-typechart').get()[0],
                    data: coll,
                    title: 'Message Balance'
                });

                graph.render();

                coll = new Iznik.Collections.DateCounts(ret.dashboard.deliveryhistory);
                graph = new Iznik.Views.DeliveryChart({
                    target: self.$('.js-deliverychart').get()[0],
                    data: coll,
                    title: 'How Yahoo users get mail (excludes FD/TN)'
                });

                graph.render();

                coll = new Iznik.Collections.DateCounts(ret.dashboard.domainhistory);
                graph = new Iznik.Views.DomainChart({
                    target: self.$('.js-domainchart').get()[0],
                    data: coll,
                    title: 'Email domains people use'
                });

                graph.render();

                coll = new Iznik.Collections.DateCounts(ret.dashboard.sourcehistory);
                graph = new Iznik.Views.SourceChart({
                    target: self.$('.js-sourcechart').get()[0],
                    data: coll,
                    title: 'How people send messages'
                });

                graph.render();
            }
        })
    },

    render: function() {
        var self = this;
        var messagetitle, spamtitle, domaintitle;

        Iznik.Views.Page.prototype.render.call(this);

        self.$('.js-grouptype').selectpicker();
        self.$('.js-grouptype').selectPersist();
        self.$('.js-grouptype').change(function() {
            self.updateGraphs.apply(self);
        });

        var v = new Iznik.Views.Group.Select({
            systemWide: true,
            all: true,
            mod: true,
            id: 'landingGroupSelect'
        });

        self.listenTo(v, 'selected', function(selected) {
            self.selected = selected;
            self.updateGraphs();
        });


        // Render after the listen to as they are called during render.
        self.$('.js-groupselect').html(v.render().el);
    }
});