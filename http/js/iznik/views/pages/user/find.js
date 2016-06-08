define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/views/pages/pages',
    'iznik/views/pages/user/pages',
    'iznik/views/pages/user/post',
    'iznik/views/user/message'
], function($, _, Backbone, Iznik) {
    Iznik.Views.User.Pages.Find.WhereAmI = Iznik.Views.User.Pages.WhereAmI.extend({
        template: "user_find_whereami"
    });

    Iznik.Views.User.Pages.Find.Search = Iznik.Views.Infinite.extend({
        template: "user_find_search",

        retField: 'messages',

        events: {
            'click #searchbutton': 'doSearch',
            'keyup .js-search': 'keyup'
        },

        keyup: function (e) {
            // Search on enter.
            if (e.which == 13) {
                this.$('#searchbutton').click();
            }
        },

        doSearch: function () {
            this.$('h1').slideUp('slow');

            var term = this.$('.js-search').val();

            if (term != '') {
                Router.navigate('/find/search/' + encodeURIComponent(term), true);
            } else {
                Router.navigate('/find/search', true);
            }
        },

        itemSource: function (query, syncResults, asyncResults) {
            var self = this;

            $.ajax({
                type: 'GET',
                url: API + 'item',
                data: {
                    typeahead: query
                }, success: function (ret) {
                    var matches = [];
                    _.each(ret.items, function (item) {
                        matches.push(item.item.name);
                    })

                    asyncResults(matches);
                }
            })
        },

        render: function () {
            var p = Iznik.Views.Infinite.prototype.render.call(this, {
                model: new Iznik.Model({
                    item: this.options.item
                })
            });

            p.then(function(self) {
                self.collection = null;
                
                var data;

                if (self.options.search) {
                    // We've searched for something - we're showing the results.
                    self.$('h1').hide();
                    self.$('.js-search').val(self.options.search);

                    var mylocation = null;
                    try {
                        mylocation = localStorage.getItem('mylocation');

                        if (mylocation) {
                            mylocation = JSON.parse(mylocation);
                        }
                    } catch (e) {}

                    self.collection = new Iznik.Collections.Messages.GeoSearch(null, {
                        modtools: false,
                        searchmess: self.options.search,
                        nearlocation: mylocation ? mylocation : null,
                        collection: 'Approved'
                    });

                    data = {
                        messagetype: 'Offer',
                        nearlocation: mylocation ? mylocation.id : null,
                        search: self.options.search,
                        subaction: 'searchmess'
                    };
                } else {
                    // We've not searched yet.
                    var mygroups = Iznik.Session.get('groups');

                    if (mygroups && mygroups.length > 0) {
                        self.$('.js-browse').show();

                        self.collection = new Iznik.Collections.Message(null, {
                            modtools: false,
                            collection: 'Approved'
                        });
                    }
                    
                    data = {};
                }

                self.collectionView = new Backbone.CollectionView({
                    el: self.$('.js-list'),
                    modelView: Iznik.Views.User.SearchResult,
                    modelViewOptions: {
                        collection: self.collection,
                        page: self
                    },
                    visibleModelsFilter: function(model) {
                        var thetype = model.get('type');

                        if (thetype != 'Offer' && thetype != 'Wanted') {
                            // Not interested in this type of message.
                            return(false);
                        } else {
                            // Only show a search result for an offer which has not been taken or wanted not received.
                            var paired = _.where(model.get('related'), {
                                type: thetype == 'Offer' ? 'Taken' : 'Received'
                            });

                            return (paired.length == 0);
                        }
                    },
                    collection: self.collection
                });

                if (self.collection) {
                    // We might have been trying to reply to a message.
                    //
                    // Listening to the collectionView means that we'll find this, eventually, if we are infinite
                    // scrolling.
                    self.listenTo(self.collectionView, 'add', function(modelView) {
                        try {
                            var replyto = localStorage.getItem('replyto');
                            var replytext = localStorage.getItem('replytext');
                            var thisid = modelView.model.get('id');

                            if (replyto == thisid) {
                                // This event happens before the view has been rendered.  Wait for that.
                                self.listenToOnce(modelView, 'rendered', function() {
                                    modelView.expand.call(modelView);
                                    modelView.setReply.call(modelView, replytext);
                                });
                            }
                        } catch (e) {console.log("Failed", e)}
                    })

                    self.collectionView.render();

                    self.fetch({
                        remove: true,
                        data: data
                    }).then(function () {
                        var some = false;

                        self.collection.each(function(msg) {
                            // Get the zoom level for maps and put it somewhere easier.
                            var zoom = 8;
                            var groups = msg.get('groups');
                            if (groups.length > 0) {
                                zoom = groups[0].settings.map.zoom;
                            }
                            msg.set('zoom', zoom);
                            var related = msg.get('related');

                            var taken = _.where(related, {
                                type: 'Taken'
                            });

                            if (taken.length == 0) {
                                some = true;
                            }
                        });

                        if (!some) {
                            self.$('.js-none').fadeIn('slow');
                        } else {
                            self.$('.js-none').hide();
                        }

                        if (self.options.search) {
                            self.$('.js-postwanted').show().addClass('fadein');
                        }
                    });
                }

                self.typeahead = self.$('.js-search').typeahead({
                    minLength: 2,
                    hint: false,
                    highlight: true
                }, {
                    name: 'items',
                    source: self.itemSource
                });

                self.waitDOM(self, function() {
                    // TODO This doesn't work for Firefox - not sure why.
                    if (!self.options.search) {
                        self.typeahead.focus();
                    }
                });
            });

            return (p);
        }
    });

    Iznik.Views.User.SearchResult = Iznik.Views.User.Message.extend({
        template: 'user_find_message',

        events: {
            'click .js-send': 'send'
        },

        initialize: function(){
            this.events = _.extend(this.events, Iznik.Views.User.Message.prototype.events);
        },

        wordify: function (str) {
            str = str.replace(/\b(\w*)/g, "<span>$1</span>");
            return (str);
        },

        startChat: function() {
            // We start a conversation with the sender.
            var self = this;

            self.wait = new Iznik.Views.PleaseWait();
            self.wait.render();

            $.ajax({
                type: 'PUT',
                url: API + 'chat/rooms',
                data: {
                    userid: self.model.get('fromuser').id
                }, success: function(ret) {
                    if (ret.ret == 0) {
                        var chatid = ret.id;
                        var msg = self.$('.js-replytext').val();

                        $.ajax({
                            type: 'POST',
                            url: API + 'chat/rooms/' + chatid + '/messages',
                            data: {
                                message: msg,
                                refmsgid: self.model.get('id')
                            }, complete: function() {
                                // Ensure the chat is opened, which shows the user what will happen next.
                                Iznik.Session.chats.fetch().then(function() {
                                    self.$('.js-replybox').slideUp();
                                    var chatmodel = Iznik.Session.chats.get(chatid);
                                    var chatView = Iznik.activeChats.viewManager.findByModel(chatmodel);
                                    chatView.restore();
                                    self.wait.close();
                                });
                            }
                        });
                    }
                }
            })
        },
        
        send: function() {
            var self = this;
            var replytext = self.$('.js-replytext').val();

            if (replytext.length == 0) {
                self.$('.js-replytext').addClass('error-border').focus();
            } else {
                self.$('.js-replytext').removeClass('error-border');

                try {
                    // Save off details of our reply.  This is so that when we do a force login and may have to sign up or
                    // log in, which can cause a page refresh, we will repopulate this data during the render.
                    localStorage.setItem('replyto', self.model.get('id'));
                    localStorage.setItem('replytext', replytext);
                } catch (e) {}

                // If we're not already logged in, we want to be.
                self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                    // Now we're logged in we no longer need the local storage memory, because we've put it back into
                    // the DOM.
                    try {
                        // Clear the local storage, so that we don't get stuck here.
                        localStorage.removeItem('replyto');
                        localStorage.removeItem('replytext');
                    } catch (e) {}

                    // When we reply to a message on a group, we join the group if we're not already a member.
                    var memberofs = Iznik.Session.get('groups');
                    var member = false;
                    var tojoin = null;
                    if (memberofs) {
                        console.log("Member ofs", memberofs);
                        memberofs.each(function(memberof) {
                            console.log("Check member", memberof);
                            var msggroups = self.model.get('groups');
                            _.each(msggroups, function(msggroup) {
                                console.log("Check msg", msggroup);
                                if (memberof.id = msggroup.groupid) {
                                    member = true;
                                }
                            });
                        });
                    }

                    if (!member) {
                        // We're not a member of any groups on which this message appears.  Join one.  Doesn't much
                        // matter which.
                        console.log("Not a member yet, need to join");
                        var tojoin = self.model.get('groups')[0].id;
                        $.ajax({
                            url: API + 'memberships',
                            type: 'PUT',
                            data: {
                                groupid : tojoin
                            }, success: function(ret) {
                                if (ret.ret == 0) {
                                    // We're now a member of the group.  Fetch the message back, because we'll see more
                                    // info about it now.
                                    self.model.fetch().then(function() {
                                        self.startChat();
                                    })
                                } else {
                                    // TODO
                                }
                            }, error: function() {
                                // TODO
                            }
                        })
                    } else {
                        self.startChat();
                    }
                });

                Iznik.Session.forceLogin({
                    modtools: false
                });
            }
        },

        render: function() {
            var self = this;
            var p;

            if (self.rendered) {
                p = resolvedPromise(self);
            } else {
                self.rendered = true;
                var mylocation = null;
                try {
                    mylocation = localStorage.getItem('mylocation');

                    if (mylocation) {
                        mylocation = JSON.parse(mylocation);
                    }
                } catch (e) {
                }

                this.model.set('mylocation', mylocation);

                // Static map custom markers don't support SSL.
                this.model.set('mapicon', 'http://' + window.location.hostname + '/images/mapmarker.gif');

                // Hide until we've got a bit into the render otherwise the border shows.
                this.$el.css('visibility', 'hidden');
                p = Iznik.Views.User.Message.prototype.render.call(this);

                p.then(function() {
                    // We handle the subject as a special case rather than a template expansion.  We might be doing a search, in
                    // which case we want to highlight the matched words.  So we split out the subject string into a sequence of
                    // spans, which then allows us to highlight any matched ones.
                    self.$('.js-subject').html(self.wordify(self.model.get('subject')));
                    var matched = self.model.get('matchedon');
                    if (matched) {
                        self.$('.js-subject span').each(function () {
                            if ($(this).html().toLowerCase().indexOf(matched.word) != -1) {
                                $(this).addClass('searchmatch');
                            }
                        });
                    }
                    self.$el.css('visibility', 'visible');
                })
            }

            return(p);
        }
    });

    Iznik.Views.User.Pages.Find.WhatIsIt = Iznik.Views.User.Pages.WhatIsIt.extend({
        msgType: 'Wanted',
        template: "user_find_whatisit",
        whoami: '/find/whoami',

        render: function() {
            // We want to start the wanted with the last search term.
            try {
                this.options.item = localStorage.getItem('lastsearch');
            } catch (e) {}

            return(Iznik.Views.User.Pages.WhatIsIt.prototype.render.call(this));
        }
    });

    Iznik.Views.User.Pages.Find.WhoAmI = Iznik.Views.User.Pages.WhoAmI.extend({
        whatnext: '/find/whatnext',
        template: "user_find_whoami"
    });

    Iznik.Views.User.Pages.Find.WhatNext = Iznik.Views.Page.extend({
        template: "user_find_whatnext"
    });
});