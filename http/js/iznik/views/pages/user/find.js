define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/views/pages/pages',
    'iznik/views/pages/user/pages',
    'iznik/views/user/message'
], function($, _, Backbone, Iznik) {
    Iznik.Views.User.Pages.Find.WhereAmI = Iznik.Views.User.Pages.WhereAmI.extend({
        template: "user_find_whereami"
    });

    Iznik.Views.User.Pages.Find.Search = Iznik.Views.Page.extend({
        template: "user_find_search",

        events: function () {
            return _.extend({}, Iznik.Views.Page.prototype.events, {
                'click #searchbutton': 'doSearch',
                'keyup .js-search': 'keyup'
            });
        },

        keyup: function (e) {
            // Search on enter.
            if (e.which == 13) {
                this.$('#searchbutton').click();
            }
        },

        doSearch: function () {
            this.$('h1').slideUp();

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
            var self = this;

            Iznik.Views.Page.prototype.render.call(this);

            if (this.options.search) {
                this.$('h1').hide();
                this.$('.js-search').val(this.options.search);

                self.collection = new Iznik.Collections.Messages.Search(null, {
                    searchmess: self.options.search,
                    collection: 'Approved'
                });

                self.collectionView = new Backbone.CollectionView({
                    el: self.$('.js-list'),
                    modelView: Iznik.Views.User.SearchResult,
                    modelViewOptions: {
                        collection: self.collection,
                        page: self
                    },
                    collection: self.collection
                });

                self.collectionView.render();

                var v = new Iznik.Views.PleaseWait();
                v.render();

                var mylocation = null;
                try {
                    mylocation = localStorage.getItem('mylocation');
                } catch (e) {
                }

                self.collection.fetch({
                    remove: true,
                    data: {
                        messagetype: 'Offer',
                        nearlocation: mylocation,
                        search: this.options.search,
                        subaction: 'searchmess'
                    },
                    success: function (collection, response, options) {
                        v.close();

                        if (collection.length == 0) {
                            self.$('.js-none').fadeIn('slow');
                        } else {
                            self.$('.js-none').hide();
                        }
                    }
                });
            }

            this.$('.js-search').typeahead({
                minLength: 2,
                hint: false,
                highlight: true
            }, {
                name: 'items',
                source: this.itemSource
            });

            return (this);
        }
    });

    Iznik.Views.User.SearchResult = Iznik.Views.User.Message.extend({
        template: 'user_find_result',

        events: {
            'click .js-send': 'send'
        },

        initialize: function(){
            _.extend(this.events, Iznik.Views.User.Message.prototype.events);
        },
        
        send: function() {
            var self = this;

            // We start a conversation with the sender.
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
                                });
                            }
                        });
                    }
                }
            })
        },
        
        render: function() {
            var related = this.model.get('related');

            var taken = _.where(related, {
                type: 'Taken'
            });

            if (taken.length == 0) {
                // Only show a search result for an offer which has not been taken.
                Iznik.Views.User.Message.prototype.render.call(this);
            } else {
                this.$el.hide();
            }
            
            return(this);
        }
    });
});