Iznik.Views.ModTools.Pages.PendingMessages = Iznik.Views.Infinite.extend({
    modtools: true,

    template: "modtools_messages_pending_main",

    render: function() {
        var self = this;

        Iznik.Views.Page.prototype.render.call(this);

        var v = new Iznik.Views.Help.Box();
        v.template = 'modtools_messages_pending_help';
        this.$('.js-help').html(v.render().el);

        this.groupSelect = new Iznik.Views.Group.Select({
            systemWide: false,
            all: true,
            mod: true,
            counts: [ 'pending', 'pendingother' ],
            id: 'pendingGroupSelect'
        });

        self.listenTo(this.groupSelect, 'selected', function(selected) {
            self.selected = selected;
            
            // We haven't fetched anything for this group yet.
            self.lastFetched = null;
            self.context = null;
            
            self.collection = new Iznik.Collections.Message(null, {
                groupid: self.selected,
                group: Iznik.Session.get('groups').get(self.selected),
                collection: 'Pending'
            });
            
            // CollectionView handles adding/removing/sorting for us.
            self.collectionView = new Backbone.CollectionView( {
                el : self.$('.js-list'),
                modelView : Iznik.Views.ModTools.Message.Pending,
                modelViewOptions: {
                    collection: self.collection,
                    page: self
                },
                collection: self.collection
            } );

            self.collectionView.render();
            self.fetch();
        });

        // Render after the listen to as they are called during render.
        self.$('.js-groupselect').html(self.groupSelect.render().el);

        // If we detect that the pending counts have changed on the server, refetch the messages so that we add/remove
        // appropriately.  Re-rendering the select will trigger a selected event which will re-fetch and render.
        this.listenTo(Iznik.Session, 'pendingcountschanged', _.bind(this.groupSelect.render, this.groupSelect));
        this.listenTo(Iznik.Session, 'pendingcountsotherchanged', _.bind(this.groupSelect.render, this.groupSelect));
    }
});

Iznik.Views.ModTools.Message.Pending = Iznik.Views.ModTools.Message.extend({
    template: 'modtools_messages_pending_message',
    collectionType: 'Pending',

    events: {
        'click .js-viewsource': 'viewSource',
        'click .js-excludelocation': 'excludeLocation',
        'click .js-rarelyused': 'rarelyUsed',
        'click .js-savesubj': 'saveSubject',
        'click .js-editnotstd': 'edit'
    },

    edit: function() {
        var self = this;

        var v = new Iznik.Views.ModTools.StdMessage.Edit({
            model: this.model
        });

        this.listenToOnce(self.model, 'editsucceeded', function() {
            self.model.fetch().then(function() {
                self.render();
            });
        });

        v.render();
    },

    render: function() {
        var self = this;

        self.model.set('mapicon', window.location.protocol + '//' + window.location.hostname + '/images/mapmarker.gif');

        // Get a zoom level for the map.
        _.each(self.model.get('groups'), function(group) {
            self.model.set('mapzoom', group.settings.hasOwnProperty('map') ? group.settings.map.zoom : 12);
        });

        self.$el.html(window.template(self.template)(self.model.toJSON2()));

        // Set the suggested subject here to avoid escaping issues.  Highlight it if it's different
        var sugg = self.model.get('suggestedsubject');
        if (sugg && sugg.toLocaleLowerCase() != self.model.get('subject').toLocaleLowerCase()) {
            self.$('.js-subject').closest('.input-group').addClass('subjectdifference');
        } else {
            self.$('.js-subject').closest('.input-group').removeClass('subjectdifference');
        }

        self.$('.js-subject').val(sugg ? sugg : self.model.get('subject'));

        _.each(self.model.get('groups'), function(group) {
            var mod = new IznikModel(group);

            // Add in the message, because we need some values from that
            mod.set('message', self.model.toJSON());

            var v = new Iznik.Views.ModTools.Message.Pending.Group({
                model: mod
            });
            self.$('.js-grouplist').append(v.render().el);

            var mod = new Iznik.Models.ModTools.User(self.model.get('fromuser'));
            mod.set('groupid', group.id);
            var v = new Iznik.Views.ModTools.User({
                model: mod
            });

            self.$('.js-user').html(v.render().el);

            // The Yahoo part of the user
            var fromemail = self.model.get('envelopefrom') ? self.model.get('envelopefrom') : self.model.get('fromaddr');
            var mod = IznikYahooUsers.findUser({
                email: fromemail,
                group: group.nameshort,
                groupid: group.id
            });

            mod.fetch().then(function() {
                var v = new Iznik.Views.ModTools.Yahoo.User({
                    model: mod
                });
                self.$('.js-yahoo').html(v.render().el);
            });

            self.addOtherInfo();

            // Add any attachments.
            _.each(self.model.get('attachments'), function(att) {
                var v = new Iznik.Views.ModTools.Message.Photo({
                    model: new IznikModel(att)
                });

                self.$('.js-attlist').append(v.render().el);
            });

            // Add the default standard actions.
            var configs = Iznik.Session.get('configs');
            var sessgroup = Iznik.Session.get('groups').get(group.id);
            var config = configs.get(sessgroup.get('configid'));

            if (!_.isUndefined(config) &&
                config.get('subjlen') &&
                self.model.get('suggestedsubject') &&
                (self.model.get('suggestedsubject').length > config.get('subjlen'))) {
                // This subject is too long, and we want to flag that.
                self.$('.js-subject').closest('.input-group').addClass('subjectdifference');
            }

            if (self.model.get('heldby')) {
                // Message is held - just show Release button.
                self.$('.js-stdmsgs').append(new Iznik.Views.ModTools.StdMessage.Button({
                    model: new IznikModel({
                        title: 'Release',
                        action: 'Release',
                        message: self.model,
                        config: config
                    })
                }).render().el);
            } else {
                // Message is not held - we see all buttons.
                self.$('.js-stdmsgs').append(new Iznik.Views.ModTools.StdMessage.Button({
                    model: new IznikModel({
                        title: 'Approve',
                        action: 'Approve',
                        message: self.model,
                        config: config
                    })
                }).render().el);

                self.$('.js-stdmsgs').append(new Iznik.Views.ModTools.StdMessage.Button({
                    model: new IznikModel({
                        title: 'Reject',
                        action: 'Reject',
                        message: self.model,
                        config: config
                    })
                }).render().el);

                self.$('.js-stdmsgs').append(new Iznik.Views.ModTools.StdMessage.Button({
                    model: new IznikModel({
                        title: 'Delete',
                        action: 'Delete',
                        message: self.model,
                        config: config
                    })
                }).render().el);

                self.$('.js-stdmsgs').append(new Iznik.Views.ModTools.StdMessage.Button({
                    model: new IznikModel({
                        title: 'Hold',
                        action: 'Hold',
                        message: self.model,
                        config: config
                    })
                }).render().el);

                if (config) {
                    self.checkMessage(config);
                    self.showRelated();

                    // Add the other standard messages, in the order requested.
                    var sortmsgs = orderedMessages(config.get('stdmsgs'), config.get('messageorder'));
                    var anyrare = false;

                    _.each(sortmsgs, function (stdmsg) {
                        if (_.contains(['Approve', 'Reject', 'Delete', 'Leave', 'Edit'], stdmsg.action)) {
                            stdmsg.message = self.model;
                            var v = new Iznik.Views.ModTools.StdMessage.Button({
                                model: new Iznik.Models.ModConfig.StdMessage(stdmsg),
                                config: config
                            });

                            var el = v.render().el;
                            self.$('.js-stdmsgs').append(el);

                            if (stdmsg.rarelyused) {
                                anyrare = true;
                                $(el).hide();
                            }
                        }
                    });

                    if (!anyrare) {
                        self.$('.js-rarelyholder').hide();
                    }
                }
            }

            // If the message is held or released, we re-render, showing the appropriate buttons.
            self.listenToOnce(self.model, 'change:heldby', self.render);
        });

        this.$('.timeago').timeago();
        this.$el.fadeIn('slow');

        this.listenToOnce(self.model, 'approved rejected deleted', function() {
            self.model.destroy();
        });

        return(this);
    }
});

Iznik.Views.ModTools.Message.Pending.Group = IznikView.extend({
    template: 'modtools_messages_pending_group',

    render: function() {
        var self = this;
        self.$el.html(window.template(self.template)(self.model.toJSON2()));

        return(this);
    }
});

Iznik.Views.ModTools.StdMessage.Pending.Approve = Iznik.Views.ModTools.StdMessage.Modal.extend({
    template: 'modtools_messages_pending_approve',

    events: {
        'click .js-send': 'send'
    },

    send: function() {
        this.model.approve(
            this.options.stdmsg.get('subjpref') ? this.$('.js-subject').val() : null,
            this.options.stdmsg.get('subjpref') ? this.$('.js-text').val() : null
        );
    },

    render: function() {
        this.expand();
        this.closeWhenRequired();
        return(this);
    }
});

Iznik.Views.ModTools.StdMessage.Pending.Reject = Iznik.Views.ModTools.StdMessage.Modal.extend({
    template: 'modtools_messages_pending_reject',

    events: {
        'click .js-send': 'send'
    },

    send: function() {
        this.model.reject(
            this.$('.js-subject').val(),
            this.$('.js-text').val(),
            this.options.stdmsg.get('id')
        );
    },

    render: function() {
        this.expand();
        this.closeWhenRequired();
        return(this);
    }
});
