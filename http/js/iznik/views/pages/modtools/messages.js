define([
    'jquery',
    'underscore',
    'backbone',
    'moment',
    'iznik/base',
    'jquery-ui',
    "iznik/modtools",
    'jquery-show-first',
    'iznik/views/pages/pages',
    'iznik/models/message'
], function($, _, Backbone, moment, Iznik) {
    Iznik.Views.ModTools.Message = Iznik.View.extend({
        events: {
            'change .js-fop': 'setFOP'
        },
        
        rarelyUsed: function () {
            this.$('.js-rarelyused').fadeOut('slow');
            this.$('.js-stdmsgs li').fadeIn('slow');
        },

        restoreEditSubject: function () {
            var self = this;
            window.setTimeout(function () {
                self.$('.js-savesubj .glyphicon').removeClass('glyphicon-ok glyphicon-warning-sign error success').addClass('glyphicon-floppy-save');
            }, 30000);
        },

        editFailed: function () {
            this.removeEditors();
            this.$('.js-savesubj .glyphicon').removeClass('glyphicon-refresh rotate').addClass('glyphicon-warning-sign error');
            this.$('.js-saveplatsubj .glyphicon').removeClass('glyphicon-refresh rotate').addClass('glyphicon-warning-sign error');
            this.restoreEditSubject();
        },

        editSucceeded: function () {
            this.removeEditors();
            this.$('.js-savesubj .glyphicon').removeClass('glyphicon-refresh rotate').addClass('glyphicon-ok success');
            this.$('.js-saveplatsubj .glyphicon').removeClass('glyphicon-refresh rotate').addClass('glyphicon-ok success');
            this.restoreEditSubject();
        },

        removeEditors: function () {
            function removeTinyMCEInstance(editor) {
                var oldLength = tinymce.editors.length;
                tinymce.remove(editor);
                if (oldLength == tinymce.editors.length) {
                    tinymce.editors.remove(editor)
                }
            }

            for (var i = tinymce.editors.length - 1; i > -1; i--) {
                removeTinyMCEInstance(tinymce.editors[i]);
            }
            this.$('.js-tinymce').remove();
        },

        postcodeSource: function(query, syncResults, asyncResults) {
            var self = this;

            $.ajax({
                type: 'GET',
                url: API + 'locations',
                data: {
                    typeahead: query.trim()
                }, success: function(ret) {
                    var matches = [];
                    _.each(ret.locations, function(location) {
                        matches.push(location.name);
                    });

                    asyncResults(matches);

                    _.delay(function() {
                        self.$('.js-postcode').tooltip('destroy');
                    }, 10000);

                    if (matches.length == 0) {
                        self.$('.js-postcode').tooltip({'trigger':'focus', 'title': 'Please use a valid UK postcode (including the space)'});
                        self.$('.js-postcode').tooltip('show');
                    } else {
                        self.firstMatch = matches[0];
                    }
                }
            })
        },

        savePlatSubject: function () {
            var self = this;

            // First edit our copy.
            self.listenToOnce(self.model, 'editsucceeded', function() {
                // Now we may need to edit on Yahoo too.
                self.$('.js-subject').val(self.model.get('subject'));
                self.saveSubject();
            });

            self.model.editPlatformSubject(
                self.$('.js-type').val(),
                self.$('.js-item').val(),
                self.$('.js-location').val()
            );
        },

        saveSubject: function () {
            var self = this;

            require(['tinymce'], function() {
                self.removeEditors();
                self.listenToOnce(self.model, 'editfailed', self.editFailed);
                self.listenToOnce(self.model, 'editsucceeded', self.editSucceeded);

                self.$('.js-savesubj .glyphicon').removeClass('glyphicon-floppy-save glyphicon-warning-sign').addClass('glyphicon-refresh rotate');

                self.listenToOnce(self.model, 'editsucceeded', function () {
                    // If we've just edited, we don't want to display a diffferent subject in the edit box, as that's confusing.
                    self.model.set('suggestedsubject', self.model.get('subject'));
                    self.render();
                });

                var html = self.model.get('htmlbody');

                if (html) {
                    // Yahoo is quite picky about the HTML that we pass back, and can fail edits.  Passing it through TinyMCE
                    // to sanitise it works for this.
                    $('#js-tinymce').remove();
                    self.$el.append('<textarea class="hidden js-tinymce" id="js-tinymce" />');
                    self.$('#js-tinymce').val(html);
                    tinyMCE.init({
                        selector: '#js-tinymce'
                    });

                    var html = tinyMCE.get('js-tinymce').getContent({format: 'raw'});
                    self.model.set('htmlbody', html);
                }

                self.model.edit(
                    self.$('.js-subject').val(),
                    self.model.get('textbody'),
                    self.model.get('htmlbody')
                );
            });
        },

        viewSource: function (e) {
            e.preventDefault();
            e.stopPropagation();

            var v = new Iznik.Views.ModTools.Message.ViewSource({
                model: this.model
            });
            v.render();
        },

        setFOP: function(fop) {
            var self = this;
            var fop = self.$('.js-fop').is(':checked') ? 1 : 0;

            $.ajax({
                type: 'PATCH',
                url: API + 'message',
                data: {
                    id: self.model.get('id'),
                    FOP: fop
                }
            });
        },

        excludeLocation: function (e) {
            var self = this;

            e.preventDefault();
            e.stopPropagation();

            if (self.model.get('location')) {
                var v = new Iznik.Views.PleaseWait({
                    timeout: 1
                });
                v.render();

                _.each(self.model.get('groups'), function (group) {
                    var groupid = group.groupid;
                    console.log("Exclude location", self.model.get('location').id);
                    $.ajax({
                        type: 'POST',
                        url: API + 'locations',
                        data: {
                            action: 'Exclude',
                            byname: true,
                            id: self.model.get('location').id,
                            groupid: groupid,
                            messageid: self.model.get('id')
                        }, success: function (ret) {
                            // We should have a new suggestion
                            self.model.fetch({
                                data: {
                                    groupid: groupid,
                                    collection: self.collectionType
                                }
                            }).then(function () {
                                console.log("New location", self.model.get('location').id);
                                self.render();
                            });

                            v.close();
                        }
                    });
                });
            }
        },

        showDuplicates: function () {
            var self = this;

            // Decide if we need to check for duplicates.
            var check = false;
            var groups = Iznik.Session.get('groups');
            var dupage = 31;
            _.each(self.model.get('groups'), function (group) {
                var dupsettings = Iznik.Session.getSettings(group.groupid);
                if (!dupsettings || !dupsettings.hasOwnProperty('duplicates') || dupsettings.duplicates.check) {
                    check = true;
                    var type = self.model.get('type');
                    dupage = Math.min(dupage, dupsettings.hasOwnProperty('duplicates') ? dupsettings.duplicates[type.toLowerCase()] : 31);
                }
            });

            var dups = [];
            var crossposts = [];

            if (check) {
                var id = self.model.get('id');
                var subj = canonSubj(self.model.get('subject'));

                _.each(self.model.get('groups'), function (group) {
                    var groupid = group.groupid;
                    var fromuser = self.model.get('fromuser');

                    if (fromuser) {
                        _.each(fromuser.messagehistory, function (message) {
                            message.dupage = dupage;

                            // console.log("Check message", message.id, id, message.daysago, canonSubj(message.subject), subj);
                            // The id of the message might have been manipulated in user.js to make sure it's unique per
                            // posting.

                            var p = (message.id + '').indexOf('.');
                            var i = p ==  -1 ? message.id : (message.id + '').substring(0, p);
                            if (i != id && message.daysago < 60) {
                                if (canonSubj(message.subject) == subj) {
                                    // No point displaying any group tag in the duplicate.
                                    message.subject = message.subject.replace(/\[.*\](.*)/, "$1");

                                    if (message.groupid == groupid) {
                                        // Same group - so this is a duplicate
                                        var v = new Iznik.Views.ModTools.Message.Duplicate({
                                            model: new Iznik.Model(message)
                                        });
                                        v.render().then(function(v) {
                                            self.$('.js-duplist').append(v.el);
                                        })

                                        dups.push(message);
                                    } else {
                                        // Different group - so this is a crosspost.
                                        //
                                        // Get the group details for the template.
                                        message.group = Iznik.Session.getGroup(message.groupid).attributes;

                                        var v = new Iznik.Views.ModTools.Message.Crosspost({
                                            model: new Iznik.Model(message)
                                        });

                                        v.render().then(function(v) {
                                            self.$('.js-crosspostlist').append(v.el);
                                        });

                                        crossposts.push(message);
                                    }
                                }
                            }
                        });
                    }
                });
            }

            self.model.set('duplicates', dups);
            self.model.set('crossposts', crossposts);
        },

        checkMessage: function (config) {
            var self = this;

            this.showDuplicates();

            // We colour code subjects according to a regular expression in the config.
            this.$('.js-coloursubj').addClass('success');

            if (config.get('coloursubj')) {
                var subjreg = config.get('subjreg');

                if (subjreg) {
                    var re = new RegExp(subjreg);

                    if (!re.test(this.model.get('subject'))) {
                        this.$('.js-coloursubj').removeClass('success').addClass('error');
                    }
                }
            }
        },

        showRelated: function () {
            var self = this;

            _.each(self.model.get('related'), function (related) {
                // No point displaying any group tag in the duplicate.
                related.subject = related.subject.replace(/\[.*\](.*)/, "$1");

                var v = new Iznik.Views.ModTools.Message.Related({
                    model: new Iznik.Model(related)
                });

                v.render().then(function(v) {
                    self.$('.js-relatedlist').append(v.el);
                });
            });
        },

        addOtherInfo: function () {
            var self = this;

            require(['jquery-show-first'], function() {
                var fromemail = self.model.get('envelopefrom') ? self.model.get('envelopefrom') : self.model.get('fromaddr');

                // Add any other emails
                self.$('.js-otheremails').empty();
                var fromuser = self.model.get('fromuser');

                if (fromuser) {
                    var promises = [];
                    _.each(fromuser.emails, function (email) {
                        if (email.email != fromemail) {
                            var mod = new Iznik.Model(email);
                            var v = new Iznik.Views.ModTools.Message.OtherEmail({
                                model: mod
                            });

                            var p = v.render();
                            p.then(function(v) {
                                self.$('.js-otheremails').append(v.el);
                            });
                            promises.push(p);
                        }
                    });

                    Promise.all(promises).then(function() {
                        self.$('.js-otheremails').showFirst({
                            controlTemplate: '<div><span class="badge">+[REST_COUNT] more</span>&nbsp;<a href="#" class="show-first-control">show</a></div>',
                            count: 5
                        });
                    });

                    // Add any other group memberships we need to display.
                    self.$('.js-memberof').empty();
                    var promises2 = [];

                    var groupids = [self.model.get('groupid')];
                    _.each(fromuser.memberof, function (group) {
                        if (groupids.indexOf(group.id) == -1) {
                            var mod = new Iznik.Model(group);
                            // console.log("Consider fromuser", fromuser);
                            var emails = fromuser.emails;
                            var email = _.where({
                                id: group.emailid
                            });
                            // console.log("Got email", email, emails);

                            var v = new Iznik.Views.ModTools.Member.Of({
                                model: mod,
                                user: new Iznik.Model(fromuser)
                            });

                            var p = v.render();
                            p.then(function(v) {
                                self.$('.js-memberof').append(v.el);
                            });
                            promises2.push(p);

                            groupids.push(group.id);
                        }
                    });

                    Promise.all(promises2).then(function() {
                        self.$('.js-memberof').showFirst({
                            controlTemplate: '<div><span class="badge">+[REST_COUNT] more</span>&nbsp;<a href="#" class="show-first-control">show</a></div>',
                            count: 5
                        });
                    });

                    self.$('.js-applied').empty();
                    var promises3 = [];

                    _.each(fromuser.applied, function (group) {
                        if (groupids.indexOf(group.id) == -1) {
                            // Don't both displaying applications to groups we've just listed as them being a member of.
                            var mod = new Iznik.Model(group);
                            var v = new Iznik.Views.ModTools.Member.Applied({
                                model: mod
                            });

                            var p = v.render();
                            p.then(function(v) {
                                self.$('.js-applied').append(v.el);
                            });
                            promises3.push(p);
                        }
                    });

                    Promise.all(promises3).then(function() {
                        self.$('.js-applied').showFirst({
                            controlTemplate: '<div><span class="badge">+[REST_COUNT] more</span>&nbsp;<a href="#" class="show-first-control">show</a></div>',
                            count: 5
                        });
                    });
                }
            });
        },

        wordify: function (str) {
            str = str.replace(/\b(\w*)/g, "<span>$1</span>");
            return (str);
        },

        spam: function () {
            var self = this;

            _.each(self.model.get('groups'), function (group) {
                var groupid = group.groupid;
                $.ajax({
                    type: 'POST',
                    url: API + 'message',
                    data: {
                        action: 'Spam',
                        id: self.model.get('id'),
                        groupid: groupid
                    }
                });
            });

            self.model.collection.remove(self.model);
        }
    });

    Iznik.Views.ModTools.Message.OtherEmail = Iznik.View.extend({
        template: 'modtools_message_otheremail'
    });

    Iznik.Views.ModTools.Message.Photo = Iznik.View.extend({
        tagName: 'li',

        template: 'modtools_message_photo',

        events: {
            'click .js-img': 'click',
            'click .js-rotateright': 'rotateRight',
            'click .js-rotateleft': 'rotateLeft'
        },

        rotateRight: function() {
            this.rotate(-90);
        },

        rotateLeft: function() {
            this.rotate(90);
        },

        rotate: function(deg) {
            var self = this;

            $.ajax({
                url: API + 'image',
                type: 'POST',
                data: {
                    id: self.model.get('id'),
                    rotate: deg,
                    bust: (new Date()).getTime()
                },
                success: function(ret) {
                    var t = (new Date()).getTime();

                    if (ret.ret === 0) {
                        // Force the image to reload.
                        var url = self.$('img').attr('src');
                        var p = url.indexOf('?');
                        url =  p === -1 ? (url + '?t=' + t) : (url + '&t' + t + '=' + t);
                        self.$('img').attr('src', url);
                    }
                }
            })
        },

        click: function (e) {
            e.preventDefault();
            e.stopPropagation();

            var v = new Iznik.Views.Modal({
                model: this.model
            });

            v.open('modtools_message_photozoom');
        }
    });

    Iznik.Views.ModTools.StdMessage.Modal = Iznik.Views.Modal.extend({
        recentDays: 31,

        keywordList: ['Offer', 'Taken', 'Wanted', 'Received', 'Other'],

        expand: function () {
            var self = this;
            var p = Iznik.Views.Modal.prototype.render.call(this);
            p.then(function() {
                // Apply standard message settings.  Need to refetch as the textbody is not returned in the session.
                if (self.options.stdmsg && self.options.stdmsg.get('id')) {
                    self.options.stdmsg.fetch().then(function () {
                        var stdmsg = self.options.stdmsg.attributes;
                        var config = self.options.config ? self.options.config.attributes : null;

                        var subj = self.model.get('subject');

                        if (subj) {
                            // We have a pre-existing subject to include
                            subj = (stdmsg.subjpref ? stdmsg.subjpref : 'Re') + ': ' + subj +
                                (stdmsg.subjsuff ? stdmsg.subjsuff : '')
                            subj = self.substitutionStrings(subj, self.model.attributes, config, self.model.get('groups')[0]);
                            focuson = 'js-text';
                        } else {
                            // Just expand substitutions in the stdmsg.
                            subj = (stdmsg.subjpref ? stdmsg.subjpref : '') + (stdmsg.subjsuff ? stdmsg.subjsuff : '');
                            subj = self.substitutionStrings(subj, self.model.attributes, config, self.model.get('groups')[0]);
                            focuson = 'js-subject';
                        }

                        self.$('.js-subject').val(subj);

                        // Decide who the mail will look as though it comes from.
                        var name = Iznik.Session.get('me').displayname;
                        if (config && config.fromname == 'Groupname Moderator') {
                            name = self.model.get('groups')[0].nameshort + " Moderator";
                        }

                        self.$('.js-myname').html(name);

                        // Quote original message.
                        var msg = self.model.get('textbody');

                        if (msg) {
                            // We have an existing body to include.
                            msg = '> ' + msg.replace(/((\r\n)|\r|\n)/gm, '\n> ');

                            // Add text
                            msg = (stdmsg.body ? (stdmsg.body + '\n\n') : '') + msg;

                            // Expand substitution strings in body
                            msg = self.substitutionStrings(msg, self.model.attributes, config, self.model.get('groups')[0]);
                        } else if (stdmsg) {
                            // Just expand substitutions in the stdmsg.
                            msg = self.substitutionStrings(stdmsg.body, self.model.attributes, config, self.model.get('groups')[0]);
                        }

                        // Put it in
                        self.$('.js-text').val(msg);

                        self.open(null);
                        $('.modal').on('shown.bs.modal', function () {
                            $('.modal ' + focuson).focus();
                        });

                        // Now check for some things we want to flag up which might suggest that the message
                        // needs attention.
                        var check = msg.toLowerCase();
                        var autosend = true;

                        if (check.indexOf('message maker') !== -1) {
                            self.$('.modal-body').prepend('<div class="alert alert-warning">The Message Maker has now been retired.  Please just link to http://ilovefreegle.org.</div>');
                            autosend = false;
                        }

                        var source = self.model.get('sourceheader');
                        if ((!source || source == 'Platform' || source == 'FDv2' || source.indexOf('TN-') !== -1) &&
                            ((check.indexOf('groups.yahoo') !== -1) ||
                             (msg.indexOf('Yahoo') !== -1))) {
                            self.$('.modal-body').prepend('<div class="alert alert-warning">This message did not come from Yahoo, but your reply mentions Yahoo, so they may not understand.</div>');
                            autosend = false;
                        }

                        if (autosend && self.options.stdmsg.get('autosend')) {
                            self.$('.js-send').click();
                        }
                    });
                } else {
                    // No standard message; just quote and open
                    var subj = self.model.get('subject');
                    subj = _.isUndefined(subj) ? '' : subj;
                    subj = 'Re: ' + self.substitutionStrings(subj, self.model.attributes, null, self.model.get('groups')[0]);
                    self.$('.js-subject').val(subj);

                    // Decide who the mail will look as though it comes from.
                    var name = Iznik.Session.get('me').displayname;
                    self.$('.js-myname').html(name);

                    // Quote original message.
                    var msg = self.model.get('textbody');

                    if (msg) {
                        // We have an existing body to include.
                        msg = '> ' + msg.replace(/((\r\n)|\r|\n)/gm, '\n> ');

                        // Expand substitution strings in body
                        msg = self.substitutionStrings(msg, self.model.attributes, null, self.model.get('groups')[0]);
                    }

                    // Put it in
                    self.$('.js-text').val(msg);
                }

                $(".modal").draggable({
                    handle: ".modal-header",
                });

                self.closeWhenRequired();
            });

            return(p);
        },

        substitutionStrings: function (text, model, config, group) {
            //console.log("substitutionstrings", text, model, config, group);
            var self = this;

            if (!_.isUndefined(text) && text) {
                if (config) {
                    text = text.replace(/\$networkname/g, config.network);
                    text = text.replace(/\$groupnonetwork/g, group.nameshort.replace(config.network, ''));
                }

                text = text.replace(/\$groupname/g, group.nameshort);
                text = text.replace(/\$owneremail/g, group.modsemail);
                text = text.replace(/\$groupemail/g, group.groupemail);
                text = text.replace(/\$groupurl/g, group.url);
                text = text.replace(/\$myname/g, Iznik.Session.get('me').displayname);
                text = text.replace(/\$nummembers/g, group.membercount);
                text = text.replace(/\$nummods/g, group.modcount);

                text = text.replace(/\$origsubj/g, model.subject);

                if (model.fromuser) {
                    var history = model.fromuser.messagehistory;
                    var recentmsg = '';
                    var count = 0;
                    _.each(history, function (msg) {
                        if (msg.daysago < self.recentDays) {
                            recentmsg += moment(msg.date).format('lll') + ' - ' + msg.subject + "\n";
                            count++;
                        }
                    })
                    text = text.replace(/\$recentmsg/gim, recentmsg);
                    text = text.replace(/\$numrecentmsg/gim, count);
                }

                _.each(this.keywordList, function (keyword) {
                    var recentmsg = '';
                    var count = 0;
                    _.each(history, function (msg) {
                        if (msg.type == keyword && msg.daysago < self.recentDays) {
                            recentmsg += moment(msg.date).format('lll') + ' - ' + msg.subject + "\n";
                            count++;
                        }
                    })

                    text = text.replace(new RegExp('\\$recent' + keyword.toLowerCase(), 'gim'), recentmsg);
                    text = text.replace(new RegExp('\\$numrecent' + keyword.toLowerCase(), 'gim'), count);
                });

                text = text.replace(/\$memberreason/g, model.hasOwnProperty('joincomment') ? model.joincomment : '');

                if (model.hasOwnProperty('joined')) {
                    text = text.replace(/\$membersubdate/g, moment(model.joined).format('lll'));
                }

                // TODO $otherapplied
                text = text.replace(/\$otherapplied/g, '');

                text = text.replace(/\$membermail/g, model.fromaddr);
                var from = model.fromuser.hasOwnProperty('realemail') ? model.fromuser.realemail : model.fromaddr;
                var fromid = from.substring(0, from.indexOf('@'));
                var memberid = presdef('yahooid', model.fromuser, fromid);
                text = text.replace(/\$memberid/g, fromid);

                var summ = '';

                if (model.hasOwnProperty('duplicates')) {
                    _.each(model.duplicates, function (m) {
                        summ += moment(m.date).format('lll') + " - " + m.subject + "\n";
                    });

                    var regex = new RegExp("\\$duplicatemessages", "gim");
                    text = text.replace(regex, summ);
                }
            }

            return (text);
        },

        maybeSettingsChange: function (trigger, stdmsg, message, group) {
            var self = this;

            var dt = stdmsg.get('newdelstatus');
            var ps = stdmsg.get('newmodstatus');

            if (dt != 'UNCHANGED') {
                var data = {
                    groupid: group.groupid,
                    id: message.get('fromuser').id
                };

                if (group.onyahoo) {
                    data.yahooDeliveryType = dt;
                } else {
                    // Map the values on Yahoo to those on the platform as best we can.
                    switch (dt) {
                        case 'DIGEST': data.emailfrequency = 24; break;
                        case 'NONE': data.emailfrequency = 0; break;
                        case 'SINGLE': data.emailfrequency = -1; break;
                        case 'ANNOUNCEMENT': data.emailfrequency = 0; break;
                    }
                }

                $.ajax({
                    type: 'POST',
                    headers: {
                        'X-HTTP-Method-Override': 'PATCH'
                    },
                    url: API + 'user',
                    data: data,
                    success: function (ret) {
                        IznikPlugin.checkPluginStatus();
                    }
                });
            }

            if (ps != 'UNCHANGED') {
                var data = {
                    groupid: group.groupid,
                    id: message.get('fromuser').id
                };

                if (group.onyahoo) {
                    data.yahooPostingStatus = ps;
                } else {
                    data.ourPostingStatus = ps
                }

                $.ajax({
                    type: 'POST',
                    headers: {
                        'X-HTTP-Method-Override': 'PATCH'
                    },
                    url: API + 'user',
                    data: data,
                    success: function (ret) {
                        IznikPlugin.checkPluginStatus();
                    }
                });
            }

            self.trigger(trigger);
            self.close();
        },

        closeWhenRequired: function () {
            var self = this;

            // If the underlying message is approved, rejected or deleted then:
            // - we may have actions to complete
            // - this modal should close.
            //
            // TODO This is a bit messy.  We're opening a modal, which needs to redelegate events after the async
            // render.  This seems to mean that if we do self.listenToOnce we lose them.  So instead we listen on
            // something else, the model.
            self.model.listenToOnce(self.model, 'approved', function () {
                _.each(self.model.get('groups'), function (group, index, list) {
                    self.maybeSettingsChange.call(self, 'approved', self.options.stdmsg, self.model, group);
                });
                self.close();
            });

            self.model.listenToOnce(self.model, 'rejected', function () {
                _.each(self.model.get('groups'), function (group, index, list) {
                    self.maybeSettingsChange.call(self, 'rejected', self.options.stdmsg, self.model, group);
                });
                self.close();
            });

            self.model.listenToOnce(self.model, 'deleted', function () {
                _.each(self.model.get('groups'), function (group, index, list) {
                    self.maybeSettingsChange.call(self, 'deleted', self.options.stdmsg, self.model, group);
                });
                self.close();
            });

            self.model.listenToOnce(self.model, 'replied', function () {
                _.each(self.model.get('groups'), function (group, index, list) {
                    self.maybeSettingsChange.call(self, 'replied', self.options.stdmsg, self.model, group);
                });
                self.close();
            });
        }
    });


    Iznik.Views.ModTools.Message.ViewSource = Iznik.Views.Modal.extend({
        template: 'modtools_messages_pending_viewsource',

        render: function () {
            var self = this;
            this.open(this.template);

            // Fetch the individual message, which gives us access to the full message (which isn't returned
            // in the normal messages call to save bandwidth.
            var m = new Iznik.Models.Message({
                id: this.model.get('id')
            });

            m.fetch().then(function () {
                self.$('.js-source').text(m.get('message'));
            });
            return (this);
        }
    });

    Iznik.Views.ModTools.StdMessage.Edit = Iznik.Views.Modal.extend({
        template: 'modtools_message_edit',

        events: {
            'click .js-save': 'save'
        },

        save: function () {
            var self = this;

            self.$('.js-editfailed').hide();

            self.listenToOnce(self.model, 'editsucceeded', function () {
                console.log("Edit succeeded - close");
                self.close();
            });

            self.listenToOnce(self.model, 'editfailed', function () {
                self.$('.js-editfailed').fadeIn('slow');
            });

            var html = tinyMCE.activeEditor.getContent({format: 'raw'});
            var text = tinyMCE.activeEditor.getContent({format: 'text'});

            self.model.edit(
                self.$('.js-subject').val(),
                text,
                html
            );
        },

        expand: function () {
            var self = this;
            this.open(this.template, this.model).then(function() {
                var body = self.model.get('htmlbody');
                body = body ? body : self.model.get('textbody');

                var subj = self.model.get('subject');

                if (self.options.stdmsg) {
                    var subjpref = self.options.stdmsg.get('subjpref');
                    var subjsuff = self.options.stdmsg.get('subjsuff');
                    var stdbody = self.options.stdmsg.get('body');

                    if (stdbody) {
                        if (self.options.stdmsg.get('insert') == 'Top') {
                            body = stdbody + '<br><br>' + body;
                        } else {
                            body = body + '<br><br>' + stdbody;
                        }
                    }

                    if (self.options.stdmsg && self.options.stdmsg.get('edittext') == 'Correct Case') {
                        // First the subject, if it's easy to parse.
                        var matches = /(.*?)\:([^)].*)\((.*)\)/.exec(subj);
                        if (matches && matches.length > 0 && matches[0].length > 0) {
                            subj = matches[1] + ':' + matches[2].toLowerCase().trim() + '(' + matches[3] + ')';
                        }
                    }

                    if (subjpref) {
                        subj = subjpref + subj;
                    }

                    if (subjsuff) {
                        subj = subj + subjsuff;
                    }
                }

                self.$('.js-subject').val(subj);

                if (self.options.stdmsg && self.options.stdmsg.get('edittext') == 'Correct Case') {
                    // Now the body.
                    body = body.toLowerCase();

                    // Contentious choice of single space
                    body = body.replace(/\.( |(&nbsp;))+/g, ". ");
                    body = body.replace(/\.\n/g, ".[-<br>-]. ");
                    body = body.replace(/\.\s\n/g, ". [-<br>-]. ");
                    var wordSplit = '. ';
                    var wordArray = body.split(wordSplit);
                    var numWords = wordArray.length;

                    for (x = 0; x < numWords; x++) {

                        if (!_.isUndefined(wordArray[x])) {
                            wordArray[x] = wordArray[x].replace(wordArray[x].charAt(0), wordArray[x].charAt(0).toUpperCase());

                            if (x == 0) {
                                body = wordArray[x] + ". ";
                            } else if (x != numWords - 1) {
                                body = body + wordArray[x] + ". ";
                            } else if (x == numWords - 1) {
                                body = body + wordArray[x];
                            }
                        }
                    }

                    body = body.replace(/\[-<br>-\]\.\s/g, "\n");
                    body = body.replace(/\si\s/g, " I ");
                    body = body.replace(/(\<p\>.)/i, function (a, b) {
                        return (b.toUpperCase());
                    });
                }

                self.$('.js-text').val(body);

                tinymce.init({
                    selector: '.js-text',
                    height: 300,
                    plugins: [
                        'advlist autolink lists link charmap print preview anchor',
                        'searchreplace visualblocks code fullscreen',
                        'insertdatetime media table paste code'
                    ],
                    menubar: 'edit insert format tools',
                    statusbar: false,
                    toolbar: 'bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link'
                });
            });
        },

        render: function () {
            var self = this;

            require(['tinymce'], function() {
                if (self.options.stdmsg) {
                    // Need to fetch as the body is excluded from what is returned in session.
                    self.options.stdmsg.fetch().then(function () {
                        self.expand();
                    });
                } else {
                    self.expand();
                }
            })
        }
    });

    Iznik.Views.ModTools.StdMessage.Button = Iznik.View.extend({
        template: 'modtools_message_stdmsg',

        tagName: 'li',

        className: 'js-stdbutton',

        events: {
            'click .js-approve': 'approve',
            'click .js-reject': 'reject',
            'click .js-delete': 'deleteMe',
            'click .js-hold': 'hold',
            'click .js-release': 'release',
            'click .js-leave': 'leave',
            'click .js-edit': 'edit'
        },

        hold: function () {
            var self = this;
            var message = self.model.get('message');
            var member = self.model.get('member');
            message ? message.hold() : member.hold();
        },

        release: function () {
            var self = this;
            var message = self.model.get('message');
            var member = self.model.get('member');
            message ? message.release() : member.release();
        },

        approve: function () {
            var self = this;
            var message = self.model.get('message');
            var member = self.model.get('member');

            if (this.options.config) {
                // This is a configured button; open the modal.
                var v = new Iznik.Views.ModTools.StdMessage.Pending.Approve({
                    model: message ? message : member,
                    stdmsg: this.model,
                    config: this.options.config
                });

                v.render();
            } else {
                // No popup to show.
                message ? message.approve() : member.approve();
            }
        },

        edit: function () {
            var self = this;
            var message = self.model.get('message');

            var v = new Iznik.Views.ModTools.StdMessage.Edit({
                model: message ? message : member,
                stdmsg: this.model,
                config: this.options.config
            });

            v.render();
        },

        reject: function () {
            var self = this;
            var message = self.model.get('message');
            var member = self.model.get('member');

            var v = new Iznik.Views.ModTools.StdMessage.Pending.Reject({
                model: message ? message : member,
                stdmsg: this.model,
                config: this.options.config
            });

            v.render();
        },

        leave: function () {
            var self = this;
            var message = self.model.get('message');
            var member = self.model.get('member');

            var v = new Iznik.Views.ModTools.StdMessage.Leave({
                model: message ? message : member,
                stdmsg: this.model,
                config: this.options.config
            });

            v.render();
        },

        deleteMe: function () {
            var self = this;
            var message = self.model.get('message');
            var member = self.model.get('member');

            if (this.options.config) {
                // This is a configured button; open the modal.
                var v = new Iznik.Views.ModTools.StdMessage.Delete({
                    model: message ? message : member,
                    stdmsg: this.model,
                    config: this.options.config
                });

                v.render();
            } else {
                var v = new Iznik.Views.Confirm({
                    model: message ? message : member
                });
                v.template = message ? 'modtools_message_delconfirm' : 'modtools_member_delconfirm';

                self.listenToOnce(v, 'confirmed', function () {
                    message ? message.delete() : member.delete();
                });

                v.render();
            }
        }
    });

    Iznik.Views.ModTools.Message.Duplicate = Iznik.View.Timeago.extend({
        template: 'modtools_message_duplicate',
    });

    Iznik.Views.ModTools.Message.Crosspost = Iznik.View.Timeago.extend({
        template: 'modtools_message_crosspost',
    });

    Iznik.Views.ModTools.Message.Related = Iznik.View.Timeago.extend({
        template: 'modtools_message_related',
    });

    Iznik.Views.ModTools.StdMessage.Leave = Iznik.Views.ModTools.StdMessage.Modal.extend({
        template: 'modtools_message_leave',

        events: {
            'click .js-send': 'send'
        },

        send: function () {
            var subj = this.$('.js-subject').val();

            if (subj.length > 0) {
                this.model.reply(
                    this.$('.js-subject').val(),
                    this.$('.js-text').val(),
                    this.options.stdmsg.get('id')
                );
            } else {
                this.$('.js-subject').focus();
            }
        },

        render: function () {
            return(this.expand());
        }
    });

    Iznik.Views.ModTools.StdMessage.Delete = Iznik.Views.ModTools.StdMessage.Modal.extend({
        template: 'modtools_message_delete',

        events: {
            'click .js-send': 'send'
        },

        send: function () {
            var self = this;
            this.listenToOnce(this.model, 'replied', function () {
                // We've sent the mail; now remove the message/member.
                // TODO Hacky - should we split stdmessages for message/members?
                if (typeof self.model.delete == 'function') {
                    self.model.delete();
                } else {
                    self.model.destroy();
                }
            });

            var subj = this.$('.js-subject').val();

            if (subj.length > 0) {
                this.model.reply(
                    this.$('.js-subject').val(),
                    this.$('.js-text').val(),
                    this.options.stdmsg.get('id')
                );
            } else {
                this.$('.js-subject').focus();
            }
        },

        render: function () {
            return(this.expand());
        }
    });
});