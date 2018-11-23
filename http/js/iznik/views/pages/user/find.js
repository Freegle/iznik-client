var parser = require('rss-parser');

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

        showChoose: false,

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
            var recognition = new SpeechRecognition();
            recognition.onresult = function (event) {
                if (event.results.length > 0) {
                    self.$('.js-search').val(event.results[0][0].transcript);
                    self.doSearch();
                }
            };
            self.$('.js-search').focus();
            recognition.start();

            /*require(['iznik/speech'], function () {
                self.$('.js-search').on('result', function(e, str) {
                    self.$('.js-search').val(str);
                    self.doSearch();
                });

                self.$('.js-search').speech();
            })*/
        },

        keyup: function (e) {
            // Search on enter.
            if (e.which == 13) {
                this.$('#searchbutton').click();
            }

            if (!this.showChoose) {
                this.showChoose = true;
                this.$('.js-searchchoose').slideDown('slow');
            }
        },

        saveSearchType: function() {
            var self = this;
            try {
                var val = self.$('.js-searchoffers').prop('checked') ? 'Offer' : 'Wanted';
                Storage.set('searchtype', val);
            } catch (e) {}
        },

        restoreSearchType: function() {
            var self = this;
            console.log("Restore search type");
            try {
                var t = Storage.get('searchtype');
                console.log("Got", t);
                if (t) {
                    this.$('.js-searchchoose').show();
                    self.$(".js-searchoffers").boostrapSwitch('state', t == 'Offer' );
                }
            } catch (e) {}
        },

        changeSearchType: function() {
            var self = this;

            self.saveSearchType();

            if (self.options.search) {
                // If we change the type of the search when there is something in the search box, do the search again (for
                // the new type).  This means they don't need to figure out to hit the Search button again, which wouldn't
                // work anyway because we are already on the correct URL.
                self.fetchData.messagetype = Storage.get('searchtype');
                self.collection.reset();
                self.context = null;
                self.fetch(self.fetchData);
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
                self.restoreSearchType();
                self.collection = null;

                if (typeof SpeechRecognition === 'function') {    // CC
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

                    // Add eBay search results.
                    //
                    // Turns out this yields peanuts.
                    //
                    // if (mylocation) {
                    //     var v = new Iznik.Views.User.Pages.Find.eBayAds({
                    //         term: self.options.search,
                    //         postcode: mylocation.name
                    //     });
                    //
                    //     v.render().then(function() {
                    //         $('#js-rightsidebar').html(v.$el);
                    //     });
                    // }

                    if (!self.noGoogleAds) {
                        var ad = new Iznik.View.GoogleAd();
                        ad.render();
                        $('#js-rightsidebar').html(ad.el);
                    }
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
                            // TODO Maps
                            var zoom = 8;
                            var groups = msg.get('groups');
                            if (groups.length > 0 &&
                                groups[0].hasOwnProperty('settings') &&
                                groups[0].settings.hasOwnProperty('map')) {
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

    Iznik.Views.User.Pages.Find.eBayAds = Iznik.View.extend({
        template: 'user_find_ebay',

        render: function() {
            var self = this;

            var p = Iznik.View.prototype.render.call(this);

            p.then(function() {
                var url = "/ebay.php?keyword=" + encodeURIComponent(self.options.term) + "&sortOrder=PricePlusShippingLowest&programid=" + EBAY_PROGRAMID + "&campaignid=" + EBAY_CAMPAIGNID + "&toolid=" + EBAY_TOOLID + "&buyerPostalCode=" + encodeURIComponent(self.options.postcode) + "&maxDistance=25&listingType1=All&feedType=rss&lgeo=1";
                parser.parseURL(url, function(err, parsed) {
                    if (parsed && parsed.feed && parsed.feed.entries && parsed.feed.entries.length) {
                        self.collection = new Iznik.Collection(parsed.feed.entries);

                        // Get the images from the description.
                        self.collection.each(function(m) {
                            var desc = m.get('content');
                            var re = /img src='(.*?)'/;
                            var match = re.exec(desc);

                            if (match && match.length > 1) {
                                m.set('image', match[1]);
                            }

                            var cont = m.get('content');

                            if (cont) {
                                m.set('content', cont.replace(/http\:\/\//g, 'https://'));
                            }
                        });

                        self.collectionView = new Backbone.CollectionView({
                            el: self.$('.js-list'),
                            modelView: Iznik.Views.User.Pages.Find.eBayAd,
                            collection: self.collection,
                            processKeyEvents: false
                        });

                        self.collectionView.render();

                        self.$('.js-ebay').css('height', window.innerHeight - $('#botleft').height() - $('nav').height() - 50);
                        self.$('.js-ebay').css('overflow-y', 'scroll');
                        self.$('.js-ebay').css('overflow-x', 'hidden');
                        self.$('.js-ebay').fadeIn('slow');
                    }
                })
            });

            return(p);
        }
    });

    Iznik.Views.User.Pages.Find.eBayAd = Iznik.View.Timeago.extend({
        template: 'user_find_ebayone',

        tagName: 'li',

        className: 'completefull',

        render: function() {
            var self = this;

            Iznik.ABTestShown('eBayAd', 'Find');
            var p = Iznik.View.Timeago.prototype.render.call(this);

            p.then(function() {
                // Make image prettier.
                self.$('img').addClass('img-rounded img-thumbnail margright');

                // Make links clickable outside backbone.
                self.$('a').attr('data-realurl', true);

                // Pad info
                self.$('td:eq(1)').addClass('padleft');
            });

            return(p);
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
                var now = (new Date()).getTime();
                var last = Storage.get('lastaskschedule');

                if (!Storage.get('dontaskschedule') && (!last || (now - last > 24 * 60 * 60 * 1000))) {
                    Storage.set('lastaskschedule', now);
                    self.listenToOnce(Iznik.Session, 'isLoggedIn', function () {
                        try {
                            var v = new Iznik.Views.User.Schedule.Modal({
                                mine: true,
                                help: true
                            });

                            self.listenToOnce(v, 'modalClosed modalCancelled', function () {
                                var w = new Iznik.Views.User.Pages.Find.Share({
                                    model: new Iznik.Models.Message({
                                        id: Storage.get('lastpost')
                                    })
                                });

                                w.model.fetch().then(function () {
                                    w.render();
                                });
                            })
                        } catch (e) {
                        }
                    });

                    Iznik.Session.testLoggedIn([
                        'me',
                        'groups'
                    ]);
                }
            });

            return (p);
        }
    });

    Iznik.Views.User.Pages.Find.Share = Iznik.Views.User.Pages.WhatNext.Share.extend({
        template: "user_find_share"
    });
});