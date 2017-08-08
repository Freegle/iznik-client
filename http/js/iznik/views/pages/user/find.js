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
        template: "user_find_whereami",
        title: "Find something"
    });

    Iznik.Views.User.Pages.Find.Search = Iznik.Views.Infinite.extend({
        template: "user_find_search",

        title: "Find something",

        retField: 'messages',

        events: {
            'click #searchbutton': 'doSearch',
            'typeahead:select .js-search': 'doSearch',
            'keyup .js-search': 'keyup',
            'click .js-speech': 'speech'
        },

        speech: function() {
            var self = this;
            require([ 'iznik/speech' ], function() {
                self.$('.js-search').on('result', function(e, str) {
                    self.$('.js-search').val(str);
                    self.doSearch();
                });

                self.$('.js-search').speech();
            })
        },

        keyup: function (e) {
            // Search on enter.
            if (e.which == 13) {
                this.$('#searchbutton').click();
            }
        },

        saveSearchType: function() {
            var self = this;
            try {
                var val = self.$('.js-searchoffers').prop('checked') ? 'Offer' : 'Wanted';
                console.log("Save search type", val);
                Storage.set('searchtype', val);
            } catch (e) {}
        },

        restoreSearchType: function() {
            var self = this;
            try {
                var t = Storage.get('searchtype');
                console.log("Restore search type", t);
                if (t) {
                    self.$(".js-searchoffers").boostrapSwitch('state', t == 'Offer' );
                }
            } catch (e) {}
        },

        changeSearchType: function() {
            // If we change the type of the search when there is something in the search box, do the search again (for
            // the new type).  This means they don't need to figure out to hit the Search button again, which wouldn't
            // work anyway because we are already on the correct URL.
            this.saveSearchType();
            var term = this.$('.js-search').val();
            if (term.length > 0) {
                this.render();
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
                        if (!_.isUndefined(item.item)) {
                            matches.push(item.item.name);
                        }
                    })

                    asyncResults(matches);
                }
            })
        },

        showOfferWanted: function(){
            var self = this;
            console.log("Show offer wanted ", self.$(".js-searchoffers").bootstrapSwitch('state'));

            if (self.$(".js-searchoffers").bootstrapSwitch('state')) {
                self.$('.js-offeronly').show();
                self.$('.js-wantedonly').hide();
            } else {
                self.$('.js-offeronly').hide();
                self.$('.js-wantedonly').show();
            }
        },

        render: function () {
            this.context = null;

            var p = Iznik.Views.Infinite.prototype.render.call(this, {
                model: new Iznik.Model({
                    item: this.options.item
                })
            });

            p.then(function(self) {
                self.collection = null;

                if (window.hasOwnProperty('webkitSpeechRecognition')) {
                    self.$('.js-speech').show();
                }

                var data;

                self.searchtype = 'Offer';

                try {
                    var stored = Storage.get('searchtype');

                    if (stored) {
                        self.searchtype = stored;
                    }
                } catch (e) {}

                self.$(".js-searchoffers").bootstrapSwitch({
                    onText: 'Only&nbsp;OFFERs',
                    offText: 'Only&nbsp;WANTEDs',
                    onColor: 'default',
                    offColor: 'default',
                    state: self.searchtype == 'Offer'
                });

                self.$(".js-searchoffers").on('switchChange.bootstrapSwitch', _.bind(self.changeSearchType, self));

                if (self.options.search) {
                    // We've searched for something - we're showing the results.
                    self.restoreSearchType();
                    self.$('h1').hide();
                    self.$('.js-search').val(self.options.search);

                    var mylocation = null;
                    try {
                        mylocation = Storage.get('mylocation');

                        if (mylocation) {
                            mylocation = JSON.parse(mylocation);
                        }
                    } catch (e) {}

                    self.collection = new Iznik.Collections.Messages.MatchedOn(null, {
                        modtools: false,
                        searchmess: self.options.search,
                        nearlocation: mylocation ? mylocation : null,
                        collection: 'Approved'
                    });

                    data = {
                        messagetype: self.searchtype,
                        nearlocation: mylocation ? mylocation.id : null,
                        search: self.options.search,
                        subaction: 'searchmess'
                    };
                } else {
                    // We've not searched yet.
                    var mygroups = Iznik.Session.get('groups');
                    var myhomegroup = Storage.get('myhomegroup');

                    if (mygroups && mygroups.length > 0) {
                        self.collection = new Iznik.Collections.Message(null, {
                            modtools: false,
                            collection: 'Approved'
                        });

                        self.$('.js-browse').show();
                    } else if (myhomegroup) {
                        self.collection = new Iznik.Collections.Message(null, {
                            modtools: false,
                            collection: 'Approved',
                            groupid: myhomegroup
                        });

                        self.$('.js-browse').show();
                    }

                    data = {
                        messagetype: self.searchtype
                    };
                }

                self.showOfferWanted();

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
                            // Only show a search result for active posts.
                            return (model.get('outcomes').length == 0);
                        }
                    },
                    collection: self.collection,
                    processKeyEvents: false
                });

                if (self.collection) {
                    // We might have been trying to reply to a message.
                    //
                    // Listening to the collectionView means that we'll find this, eventually, if we are infinite
                    // scrolling.
                    self.listenTo(self.collectionView, 'add', function(modelView) {
                        try {
                            var replyto = Storage.get('replyto');
                            var replytext = Storage.get('replytext');
                            var thisid = modelView.model.get('id');

                            if (replyto == thisid) {
                                // This event happens before the view has been rendered.  Wait for that.
                                self.listenToOnce(modelView, 'rendered', function() {
                                    modelView.expand.call(modelView);
                                    modelView.continueReply.call(modelView, replytext);
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
                            if (groups.length > 0 && groups[0].settings.hasOwnProperty('map')) {
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
                        self.$('.js-postwantedpresearch').show().addClass('fadein');
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
        title: "Find something",

        render: function() {
            // We want to start the wanted with the last search term.
            try {
                this.options.item = Storage.get('lastsearch');
            } catch (e) {}

            return(Iznik.Views.User.Pages.WhatIsIt.prototype.render.call(this));
        }
    });

    Iznik.Views.User.Pages.Find.WhoAmI = Iznik.Views.User.Pages.WhoAmI.extend({
        whatnext: '/find/whatnext',
        template: "user_find_whoami",
        title: "Find something"
    });

    Iznik.Views.User.Pages.Find.WhatNext = Iznik.Views.User.Pages.WhatNext.extend({
        template: "user_find_whatnext",

        title: "Find something",

        render: function () {
            var self = this;

            var p = Iznik.Views.User.Pages.WhatNext.prototype.render.call(this);
            p.then(function () {
                try {
                    var v = new Iznik.Views.User.Pages.Find.Share({
                        model: new Iznik.Models.Message({
                            id: Storage.get('lastpost')
                        })
                    });

                    v.model.fetch().then(function() {
                        v.render();
                    });

                } catch (e) {
                }
            });

            return (p);
        }
    });

    Iznik.Views.User.Pages.Find.Share = Iznik.Views.User.Pages.WhatNext.Share.extend({
        template: "user_find_share"
    });
});