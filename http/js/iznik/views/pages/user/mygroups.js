define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/selectpersist',
    'iznik/views/pages/pages',
    'iznik/views/pages/user/pages',
    'iznik/views/pages/user/post',
    'iznik/views/pages/user/group',
    'iznik/views/group/select',
    'iznik/views/user/message'
], function($, _, Backbone, Iznik) {
    Iznik.Views.User.Pages.MyGroups = Iznik.Views.User.Pages.Group.extend({
        template: "user_mygroups_main",
        
        render: function () {
            var p = Iznik.Views.User.Pages.Group.prototype.render.call(this);

            p.then(function(self) {
                var mygroups = Iznik.Session.get('groups');

                if (mygroups && mygroups.length > 0) {
                    self.$('.js-browse').show();

                    self.collection = new Iznik.Collections.Message(null, {
                        modtools: false,
                        collection: 'Approved'
                    });

                    self.collectionView = new Backbone.CollectionView({
                        el: self.$('.js-list'),
                        modelView: Iznik.Views.User.Message.Replyable,
                        modelViewOptions: {
                            collection: self.collection,
                            page: self
                        },
                        collection: self.collection,
                        visibleModelsFilter: _.bind(self.filter, self)
                    });

                    self.collectionView.render();

                    // Add a group selector and re-render if we change it.  The selected function is called during
                    // render which will trigger the initial view.
                    var v = new Iznik.Views.Group.Select({
                        systemWide: false,
                        all: true,
                        mod: false,
                        id: 'myGroupsSelect'
                    });

                    self.listenTo(v, 'selected', function(selected) {
                        self.selected = selected;
                        self.refetch();
                    });

                    // Render after the listen to as that are called during render.
                    v.render().then(function(v) {
                        self.$('.js-groupselect').html(v.el);
                    });

                    // Add a type selector.  The parent class has an event and method to re-render if we change that.
                    self.$('.js-type').selectpicker();
                    self.$('.js-type').selectPersist();
                } else {
                    self.$('.js-somegroups').hide();
                    self.$('.js-nogroups').fadeIn('slow');
                }
            });

            return (p);
        }
    });
});