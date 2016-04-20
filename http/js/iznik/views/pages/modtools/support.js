define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/views/pages/pages'
], function($, _, Backbone, Iznik) {
    Iznik.Views.ModTools.Pages.Support = Iznik.Views.Page.extend({
        modtools: true,

        template: "modtools_support_main",

        events: function () {
            return _.extend({}, Iznik.Views.Page.prototype.events, {
                'click .js-searchuser': 'searchUser',
                'keyup .js-searchuserinp': 'keyup',
                'click .js-mailgroup': 'mailGroup'
            });
        },

        keyup: function (e) {
            // Search on enter.
            if (e.which == 13) {
                this.$('.js-searchuser').click();
            }
        },

        searchUser: function () {
            var self = this;

            self.$('.js-loading').addClass('hidden');

            self.collection = new Iznik.Collections.Members.Search(null, {
                collection: 'Approved',
                search: this.$('.js-searchuserinp').val().trim()
            });

            self.collectionView = new Backbone.CollectionView({
                el: self.$('.js-searchuserres'),
                modelView: Iznik.Views.ModTools.Member.SupportSearch,
                collection: self.collection
            });

            var v = new Iznik.Views.PleaseWait({
                timeout: 1
            });
            v.render();

            self.collectionView.render();
            this.collection.fetch({
                remove: true,
                data: {
                    limit: 100
                },
                success: function (collection, response, options) {
                    v.close();

                    if (collection.length == 0) {
                        self.$('.js-none').fadeIn('slow');
                    }
                }
            });
        },

        mailGroup: function () {
            var self = this;
            var subject = self.$('.js-mailsubj').val();
            var body = self.$('.js-mailbody').val();

            console.log("Subject, body", subject, body);
            if (subject.length > 0 && body.length > 0) {
                $.ajax({
                    type: 'POST',
                    url: API + 'group',
                    data: {
                        action: 'Contact',
                        id: self.$('.js-grouplist').val(),
                        from: self.$('.js-mailfrom').val(),
                        subject: subject,
                        body: body
                    }, success: function (ret) {
                        if (ret.ret == 0) {
                            self.$('.js-mailsuccess').fadeIn('slow');
                        } else {
                            self.$('.js-mailerror').fadeIn('slow');
                        }
                    }, error: function () {
                        self.$('.js-mailerror').fadeIn('slow');
                    }
                });
            }
        },

        render: function () {
            var self = this;
            Iznik.Views.Page.prototype.render.call(this);

            // TODO This should be more generic, but it's really part of hosting multiple networks on the same
            // server, which we don't do.
            var type = Iznik.Session.isAdmin() ? null : 'Freegle';
            type = 'Freegle';
            $.ajax({
                url: API + 'groups',
                data: {
                    'grouptype': type
                }, success: function (ret) {
                    console.log("Got grouplist", ret);
                    _.each(ret.groups, function (group) {
                        self.$('.js-grouplist').append('<option value="' + group.id + '"></option>');
                        self.$('.js-grouplist option:last').html(group.namedisplay);
                    })
                }
            })
        }
    });

    // TODO This feels like an abuse of the memberships API just to use the search mechanism.  Should there be a user
    // search instead?
    Iznik.Views.ModTools.Member.SupportSearch = Iznik.View.extend({
        template: 'modtools_support_member',

        events: {},

        render: function () {
            var self = this;

            self.$el.html(window.template(self.template)(self.model.toJSON2()));

            // Our user
            var v = new Iznik.Views.ModTools.User({
                model: self.model
            });

            self.$('.js-user').html(v.render().el);

            // We are not in the context of a specific group here, so the general remove/ban buttons don't make sense.
            self.$('.js-ban, .js-remove').closest('li').remove();

            // Add any emails
            self.$('.js-otheremails').empty();
            _.each(self.model.get('otheremails'), function (email) {
                if (email.preferred) {
                    self.$('.js-email').append(email.email);
                } else {
                    var mod = new Iznik.Model(email);
                    var v = new Iznik.Views.ModTools.Message.OtherEmail({
                        model: mod
                    });
                    self.$('.js-otheremails').append(v.render().el);
                }
            });

            // Add any group memberships.
            self.$('.js-memberof').empty();
            _.each(self.model.get('memberof'), function (group) {
                var mod = new Iznik.Model(group);
                var v = new Iznik.Views.ModTools.Member.Of({
                    model: mod,
                    user: self.model
                });
                self.$('.js-memberof').append(v.render().el);
            });

            self.$('.js-applied').empty();
            _.each(self.model.get('applied'), function (group) {
                var mod = new Iznik.Model(group);
                var v = new Iznik.Views.ModTools.Member.Applied({
                    model: mod
                });
                self.$('.js-applied').append(v.render().el);
            });

            // Add the default standard actions.
            self.model.set('fromname', self.model.get('displayname'));
            self.model.set('fromaddr', self.model.get('email'));
            self.model.set('fromuser', self.model);

            self.$('.js-stdmsgs').append(new Iznik.Views.ModTools.StdMessage.Button({
                model: new Iznik.Model({
                    title: 'Mail',
                    action: 'Leave Approved Member',
                    member: self.model
                })
            }).render().el);

            this.$('.timeago').timeago();

            return (this);
        }
    });
});