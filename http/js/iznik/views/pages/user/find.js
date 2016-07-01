define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/models/group',
    'iznik/views/group/info',
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
            'typeahead:select .js-search': 'doSearch',
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
                    modelView: Iznik.Views.User.Message.Replyable,
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

                    data.remove = true;
                    self.fetch(data).then(function () {
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
        template: "user_find_whatnext",

        render: function() {
            var p = Iznik.Views.Page.prototype.render.call(this);
            p.then(function() {
                try {
                    var homegroup = localStorage.getItem('myhomegroup');

                    if (homegroup) {
                        var g = new Iznik.Models.Group({
                            id: homegroup
                        });

                        g.fetch().then(function() {
                            var v = new Iznik.Views.Group.Info({
                                model: g
                            });
                            v.render().then(function() {
                                self.$('.js-group').html(v.el)
                            });
                        });
                    }
                } catch (e) {};
            });

            return(p)
        }
    });
});