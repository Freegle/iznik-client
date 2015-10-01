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

        if ($('#statsGroupType').val()) {
            data.grouptype = $('#statsGroupType').val();
        }

        $.ajax({
            url: API + 'dashboard',
            data: data,
            success: function(ret) {
                var messagetitle = 'Message History';
                var spamtitle = 'Spam Detection';
                var domaintitle = 'Email domains people use';
                var sourcetitle = 'How people send messages';

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

        self.$('.js-grouptype').selectpicker();
        self.$('.js-grouptype').selectPersist();
        self.$('.js-grouptype').change(function() {
            self.updateGraphs.apply(self);
        });

        var v = new Iznik.Views.Group.Select({
            systemWide: true,
            all: true,
            id: 'statsGroupDropdown'
        });

        self.listenTo(v, 'selected', function(selected) {
            self.selected = selected;
            self.updateGraphs();
        });


        // Render after the listen to as they are called during render.
        self.$('.js-groupselect').html(v.render().el);
    }
});