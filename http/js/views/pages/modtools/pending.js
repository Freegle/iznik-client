Iznik.Views.ModTools.Pages.Pending = Iznik.Views.Page.extend({
    modtools: true,

    template: "modtools_pending_main",

    fetch: function() {
        var self = this;
        self.$('.js-none').hide();
        this.msgs.fetch({
            data: {
                collection: 'Pending'
            }
        }).then(function() {
            if (self.msgs.length == 0) {
                self.$('.js-none').fadeIn('slow');
            } else {
                // CollectionView handles adding/removing/sorting for us.
                self.collectionView = new Backbone.CollectionView( {
                    el : self.$('.js-list'),
                    modelView : Iznik.Views.ModTools.Message.Pending,
                    collection : self.msgs
                } );

                self.collectionView.render();
            }
        });
    },

    render: function() {
        Iznik.Views.Page.prototype.render.call(this);

        this.msgs = new Iznik.Collections.Message();

        // If we detect that the pending counts have changed on the server, refetch the messages so that we add/remove
        // appropriately.
        this.listenTo(Iznik.Session, 'pendingcountschanged', this.fetch);
        this.fetch();
    }
});

Iznik.Views.ModTools.Message.Pending = IznikView.extend({
    template: 'modtools_pending_message',

    events: {
        'click .js-viewsource': 'viewSource',
        'click .js-rarelyused': 'rarelyUsed'
    },

    rarelyUsed: function() {
        this.$('.js-rarelyused').fadeOut('slow');
        this.$('.js-stdmsgs li').fadeIn('slow');
    },

    viewSource: function(e) {
        e.preventDefault();
        e.stopPropagation();

        var v = new Iznik.Views.ModTools.Message.ViewSource({
            model: this.model
        });
        v.render();
    },

    render: function() {
        var self = this;

        self.$el.html(window.template(self.template)(self.model.toJSON2()));
        _.each(self.model.get('groups'), function(group, index, list) {
            var mod = new IznikModel(group);

            // Add in the message, because we need some values from that
            mod.set('message', self.model.toJSON());

            var v = new Iznik.Views.ModTools.Message.Pending.Group({
                model: mod
            });
            self.$('.js-grouplist').append(v.render().el);

            var mod = new Iznik.Models.ModTools.User(self.model.get('fromuser'));
            var v = new Iznik.Views.ModTools.User({
                model: mod
            });

            self.$('.js-user').html(v.render().el);

            // The Yahoo part of the user
            var mod = IznikYahooUsers.findUser({
                email: self.model.get('envelopefrom') ? self.model.get('envelopefrom') : self.model.get('fromaddr'),
                group: group.nameshort,
                groupid: group.id
            });

            mod.fetch().then(function() {
                var v = new Iznik.Views.ModTools.Yahoo.User({
                    model: mod
                });
                self.$('.js-yahoo').append(v.render().el);
            });

            // Add any attachments.
            _.each(self.model.get('attachments'), function(att) {
                console.log("Attachment", att);
                var v = new Iznik.Views.ModTools.Message.Photo({
                    model: new IznikModel(att)
                });

                self.$('.js-attlist').append(v.render().el);
            });

            // Add the default standard actions.
            var configs = Iznik.Session.get('configs');
            var sessgroup = Iznik.Session.get('groups').get(group.id);
            var config = configs.get(sessgroup.get('configid'));

            self.$('.js-stdmsgs').append(new Iznik.Views.ModTools.StdMessage.Button({
                model: new IznikModel({
                    title: 'Approve',
                    action: 'Approve',
                    message: self.model,
                    messageView: self,
                    config: config
                })
            }).render().el);

            self.$('.js-stdmsgs').append(new Iznik.Views.ModTools.StdMessage.Button({
                model: new IznikModel({
                    title: 'Reject',
                    action: 'Reject',
                    message: self.model,
                    messageView: self,
                    config: config
                })
            }).render().el);

            self.$('.js-stdmsgs').append(new Iznik.Views.ModTools.StdMessage.Button({
                model: new IznikModel({
                    title: 'Delete',
                    action: 'Delete',
                    message: self.model,
                    messageView: self,
                    config: config
                })
            }).render().el);

            // Add the other standard messages, in the order requested.
            var stdmsgs = config.get('stdmsgs');
            var order = JSON.parse(config.get('messageorder'));
            var sortmsgs = [];
            _.each(order, function(id) {
                var stdmsg =  null;
                _.each(stdmsgs, function(thisone) {
                    if (thisone.id == id) {
                        stdmsg = thisone;
                    }
                });

                if (stdmsg) {
                    sortmsgs.push(stdmsg);
                    stdmsgs = _.without(stdmsgs, stdmsg);
                }
            });

            sortmsgs.push(stdmsgs);

            _.each(sortmsgs, function(stdmsg) {
                if (_.contains(['Approve', 'Reject', 'Delete', 'Leave', 'Edit'], stdmsg.action)) {
                    stdmsg.message = self.model;
                    stdmsg.messageView = self;
                    var v = new Iznik.Views.ModTools.StdMessage.Button({
                        model: new IznikModel(stdmsg),
                        config: config
                    });

                    var el = v.render().el;
                    self.$('.js-stdmsgs').append(el);

                    if (stdmsg.rarelyused) {
                        $(el).hide();
                    }
                }
            });
        });

        this.$('.timeago').timeago();
        this.$el.fadeIn('slow');

        return(this);
    }
});

Iznik.Views.ModTools.Message.Pending.Group = IznikView.extend({
    template: 'modtools_pending_group',

    render: function() {
        var self = this;
        self.$el.html(window.template(self.template)(self.model.toJSON2()));

        return(this);
    }
});

Iznik.Views.ModTools.StdMessage.Modal = Iznik.Views.Modal.extend({
    expand: function() {
        this.$el.html(window.template(this.template)(this.model.toJSON2()));

        // Apply standard message settings
        var stdmsg = this.options.stdmsg.attributes;
        var config = this.options.config.attributes;

        var subj = this.model.get('subject');
        this.$('.js-subject').val((stdmsg.subjpref ? stdmsg.subjpref : 'Re') +
        ': ' + subj +
        (stdmsg.subjsuff ? stdmsg.subjsuff : ''));
        this.$('.js-myname').html(Iznik.Session.get('me').displayname);

        // Quote original message.
        var msg = this.model.get('textbody');
        msg = '> ' + msg.replace(/((\r\n)|\r|\n)/gm, '\n> ');

        // Add text
        msg = (stdmsg.body ? (stdmsg.body + '\n\n') : '') + msg;

        // Expand substitution strings
        msg = this.substitutionStrings(msg, this.model.attributes, config, this.model.get('groups')[0]);
        
        // Put it in
        this.$('.js-text').val(msg);

        this.open(null);
        $('.modal').on('shown.bs.modal', function () {
            $('.modal .js-text').focus();
        });
    },

    substitutionStrings: function(text, message, config, group) {
        console.log("Substitute", text, message, config, group);
        text = text.replace(/\$groupname/g, group.nameshort);
        text = text.replace(/\$networkname/g, config.network);
        text = text.replace(/\$groupnonetwork/g, group.nameshort.replace(config.network, ''));

        text = text.replace(/\$owneremail/g, group.nameshort + "-owner@yahoogroups.com");
        text = text.replace(/\$groupemail/g, group.nameshort + "@yahoogroups.com");
        text = text.replace(/\$groupurl/g, "https://groups.yahoo.com/neo/groups/" + group.nameshort + "/info");
        text = text.replace(/\$myname/g, Iznik.Session.get('me').displayname);
        text = text.replace(/\$nummembers/g, group.membercount);
        text = text.replace(/\$origsubj/g, message.subject);

        //if (message.hasOwnProperty('comment')) {
        //    text = text.replace(/\$memberreason/g, message['comment'].trim());
        // TODO }

        // TODO $otherapplied

        // TODO var from = message['realemail'] ? message['realemail'] : (message['from'] ? message['from'] : message['email']);
        text = text.replace(/\$membermail/g, message.fromaddr);
        // TODO var fromid = from.substring(0, from.indexOf('@'));
        //text = text.replace(/\$memberid/g, fromid);

        var messagehistory = message.fromuser.messagehistory;

        //for (var i in keywordlist) {
        //    var keyword = keywordlist[i];
        //    var msgs = counts[keyword];
        //    var summ = '';
        //
        //    if (msgs) {
        //        var regex = new RegExp("\\$numrecent" + keyword.toLowerCase(), "gim");
        //        text = text.replace(regex, msgs['count']);
        //
        //        for (var m in msgs['messages']) {
        //            var cmsg = msgs['messages'][m];
        //            summ += "#" + cmsg['id'] + ": " + formatDate(cmsg['date'], false, false) + " - " + cmsg['subject'] + "\n";
        //        }
        //    }
        //
        //    var regex = new RegExp("\\$recent" + keyword.toLowerCase(), "gim");
        //    text = text.replace(regex, summ);
        //}
        //
        //var msgs = counts['All'];
        //
        //if (msgs) {
        //    var regex = new RegExp("\\$numrecentmsg", "gim");
        //    text = text.replace(regex, msgs['count']);
        //
        //    var summ = '';
        //    for (var m in msgs['messages']) {
        //        cmsg = msgs['messages'][m];
        //        summ += "#" + cmsg['id'] + ": " + formatDate(cmsg['date'], false, false) + " - " + cmsg['subject'] + "\n";
        //    }
        //}
        //
        //var regex = new RegExp("\\$recentmsg", "gim");
        //text = text.replace(regex, summ);

        //if (message['headerdate']) {
        //    text = text.replace(/\$membersubdate/g, formatDate(message['headerdate'], false, false));
        //}
        //
        //if (message['duplicates']) {
        //    var summ = '';
        //
        //    for (var m in message['duplicates']) {
        //        var cmsg = message['duplicates'][m]['msg'];
        //        summ += "#" + cmsg['id'] + ": " + formatDate(cmsg['date'], false, false) + " - " + cmsg['subject'] + "\n";
        //    }
        //
        //    var regex = new RegExp("\\$duplicatemessages", "gim");
        //    text = text.replace(regex, summ);
        //}

        return(text);
    }
});

Iznik.Views.ModTools.StdMessage.Pending.Reject = Iznik.Views.ModTools.StdMessage.Modal.extend({
    template: 'modtools_pending_reject',

    events: {
        'click .js-send': 'send'
    },

    send: function() {
        // We reject the message on all groups.  Future enhancement?
        var self= this;
        _.each(self.model.get('groups'), function(group, index, list) {
            $.ajax({
                type: 'POST',
                url: API + 'message',
                data: {
                    id: self.model.get('id'),
                    groupid: group.id,
                    action: 'Reject',
                    subject: 'Re: ' + self.model.get('subject'),
                    body: self.$('.js-text').val()
                }, success: function(ret) {
                    self.trigger('rejected');
                    self.close();
                }
            })
        });
    },

    render: function() {
        this.expand();
        return(this);
    }
});

Iznik.Views.ModTools.Message.ViewSource = Iznik.Views.Modal.extend({
    template: 'modtools_pending_viewsource',

    render: function() {
        var self = this;
        this.open(this.template);

        // Fetch the individual message, which gives us access to the full message (which isn't returned
        // in the normal messages call to save bandwidth.
        var m = new Iznik.Models.Message({
            id: this.model.get('id')
        });

        m.fetch().then(function() {
            self.$('.js-source').text(m.get('message'));
        });
        return(this);
    }
});

Iznik.Views.ModTools.StdMessage.Button = IznikView.extend({
    template: 'modtools_pending_stdmsg',

    tagName: 'li',

    events: {
        'click .js-approve': 'approve',
        'click .js-reject': 'reject',
        'click .js-delete': 'deleteMe'
    },

    approve: function() {
        var self = this;
        var message = self.model.get('message');

        // We approve the message on all groups.  Future enhancement?
        _.each(message.get('groups'), function(group, index, list) {
            $.ajax({
                type: 'POST',
                url: API + 'message',
                data: {
                    id: message.get('id'),
                    groupid: group.id,
                    action: 'Approve'
                }, success: function(ret) {
                    self.model.get('messageView').$el.fadeOut('slow');
                }
            })
        });
    },

    reject: function() {
        var self = this;
        var message = self.model.get('message');

        var v = new Iznik.Views.ModTools.StdMessage.Pending.Reject({
            model: message,
            stdmsg: this.model,
            config: this.options.config
        });

        this.listenToOnce(v, 'rejected', function() {
            self.model.get('messageView').$el.fadeOut('slow', function() {
                self.remove();
            });
        });

        v.render();
    },

    deleteMe: function() {
        var self = this;
        var message = self.model.get('message');

        // We delete the message on all groups.  Future enhancement?
        _.each(message.get('groups'), function(group, index, list) {
            $.ajax({
                type: 'POST',
                url: API + 'message',
                data: {
                    id: message.get('id'),
                    groupid: group.id,
                    action: 'Delete'
                }, success: function(ret) {
                    self.model.get('messageView').$el.fadeOut('slow');
                }
            })
        });
    }
});