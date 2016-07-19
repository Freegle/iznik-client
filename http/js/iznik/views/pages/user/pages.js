define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/views/pages/pages',
    'typeahead',
    'jquery.scrollTo'
], function($, _, Backbone, Iznik) {
    Iznik.Views.User.Pages.WhereAmI = Iznik.Views.Page.extend({
        firstMatch: null,

        events: {
            'focus .tt-input': 'scrollTo',
            'click .js-getloc': 'getLocation',
            'change .js-homegroup': 'changeHomeGroup',
            'typeahead:change .js-postcode': 'locChange',
            'keyup .js-postcode': 'keyUp',
            'click .tt-suggestion': 'locChange'
        },

        keyUp: function(e) {
            var self = this;
            if (e.which === 13) {
                if (self.firstMatch) {
                    // We choose the first match on enter.
                    this.$('.js-postcode').typeahead('val', self.firstMatch);

                    $.ajax({
                        type: 'GET',
                        url: API + 'locations',
                        data: {
                            typeahead: self.firstMatch
                        }, success: function(ret) {
                            if (ret.ret == 0) {
                                self.recordLocation(ret.locations[0], true);
                                self.$('.js-next').click();
                            }
                        }
                    });
                }

                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
            }
        },
            
        scrollTo: function() {
            // Make sure they can see the typeahead by scrolling.  Delay because an on-screen keyboard might open.
            var self = this;
            _.delay(function() {
                var top = self.$('.tt-input').offset().top ;
                $('body').scrollTo(top - $('.navbar').height(), 'slow');
            }, 2000);
        },

        getLocation: function() {
            navigator.geolocation.getCurrentPosition(_.bind(this.gotLocation, this));
        },

        changeHomeGroup: function() {
            try {
                localStorage.setItem('myhomegroup', this.$('.js-homegroup select').val());
            } catch (e) {}
        },

        locChange: function() {
            var loc = this.$('.js-postcode').typeahead('val');

            var self = this;

            $.ajax({
                type: 'GET',
                url: API + 'locations',
                data: {
                    typeahead: loc
                }, success: function(ret) {
                    if (ret.ret == 0) {
                        self.recordLocation(ret.locations[0], true);
                    }
                }
            });
        },
        
        changeGroup: function() {
            var self = this;

            // See if the group is on Yahoo rather than on here.
            var current;
            if (self.$('.js-groups').length > 0) {
                current = self.$('.js-groups').val();
            } else {
                current = self.groupsnear[0].id;
            }

            var first = null;

            _.each(self.groupsnear, function(group) {
                if (group.id == current) {
                    first = group;
                }
            })

            self.$('.js-closestgroupname').html(first.namedisplay);

            if (!first.onhere) {
                // We don't host this group.
                if (first.onyahoo && first.showonyahoo) {
                    // But Yahoo does and we want to show it.
                    self.$('.js-onyahoo').fadeIn('slow');
                    self.$('.js-next').hide();
                    self.$('.js-toyahoo').show();
                    self.$('.js-toyahoo').attr('href', 'https://groups.yahoo.com/group/' + first.nameshort);
                } else {
                    // Who knows where it is?
                }
            } else {
                // We host this group.
                self.$('.js-onyahoo').hide();
                self.$('.js-next').show();
                self.$('.js-toyahoo').hide();

                if (first.onyahoo && first.showonyahoo) {
                    // But it's also on Yahoo, and some people might want to go there.
                    self.$('.js-yahootoo').show();
                    self.$('.js-yahootoo a').attr('href', 'https://groups.yahoo.com/group/' + first.nameshort);
                }
            }
        },

        recordLocation: function(location, changegroup) {
            var self = this;
            // console.log("Record location ", this.$('.js-postcode').typeahead('val'), location.name, changegroup);

            if (this.$('.js-postcode').typeahead('val') != location.name && changegroup) {
                // We've changed location.  We might need to change group too.
                try {
                    localStorage.removeItem('myhomegroup');
                } catch (e) {}
            }

            this.$('.js-postcode').typeahead('val', location.name);
            self.$('.js-next').fadeIn('slow');
            self.$('.js-ok').fadeIn('slow');

            console.log("Record location", location);
            self.groupsnear = location.groupsnear;
            console.log("Groupsnear length", self.groupsnear.length);

            try {
                var l = location;

                // Save space.
                delete l.groupsnear;

                Iznik.Session.setSetting('mylocation', l);
                localStorage.setItem('mylocation', JSON.stringify(l))
            } catch (e) {
                console.log("Exception", e.message);
            };

            var groups = self.$('.js-groups');
            console.log("Groupsnear length", self.groupsnear.length);

            if (groups.length > 0) {
                // We have a group select dropdown on the page.
                if (self.groupsnear) {
                    // We have some groups near their chosen location.
                    var homegroup = null;
                    var homegroupfound = false;

                    try {
                        homegroup = localStorage.getItem('myhomegroup');
                    } catch (e) {};

                    // Show home group if it's present.
                    groups.empty();
                    _.each(self.groupsnear, function(groupnear) {
                        if (homegroup == groupnear.id) {
                            homegroupfound = true;
                        }
                        groups.append('<option value="' + groupnear.id + '" />');
                        groups.find('option:last').text(groupnear.namedisplay);
                    });

                    if (homegroupfound) {
                        groups.val(homegroup);
                    } else {
                        self.changeHomeGroup();
                    }

                    self.changeGroup();
                    groups.on('change', _.bind(self.changeGroup, self));
                    self.$('.js-homegroup').fadeIn('slow');
                }
            } else {
                // We don't have a groups drop down.  Hide that section, but still check for whether we need to
                // redirect to Yahoo.
                self.$('.js-homegroup').hide();
                if (self.groupsnear) {
                    self.changeGroup();
                }
            }
        },

        gotLocation: function(position) {
            var self = this;

            $.ajax({
                type: 'GET',
                url: API + 'locations',
                data: {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                }, success: function(ret) {
                    if (ret.ret == 0 && ret.location) {
                        self.recordLocation(ret.location, true);
                    }
                }
            });
        },

        postcodeSource: function(query, syncResults, asyncResults) {
            var self = this;

            $.ajax({
                type: 'GET',
                url: API + 'locations',
                data: {
                    typeahead: query
                }, success: function(ret) {
                    var matches = [];
                    _.each(ret.locations, function(location) {
                        matches.push(location.name);
                    });

                    asyncResults(matches);

                    if (matches.length == 0) {
                        self.$('.js-postcode').tooltip({'trigger':'focus', 'title': 'Please use a valid UK postcode'});
                        self.$('.js-postcode').tooltip('show');
                    } else {
                        self.firstMatch = matches[0];
                        self.$('.js-postcode').tooltip('hide');
                    }
                }
            })
        },

        render: function() {
            var p = Iznik.Views.Page.prototype.render.call(this);
            p.then(function(self) {
                if (!navigator.geolocation) {
                    self.$('.js-geoloconly').hide();
                }

                self.$('.js-postcode').typeahead({
                    minLength: 2,
                    hint: false,
                    highlight: true
                }, {
                    name: 'postcodes',
                    source: _.bind(self.postcodeSource, self)
                });

                try {
                    // See if we know where we are from last time.
                    var mylocation = localStorage.getItem('mylocation');
                    
                    if (!mylocation) {
                        mylocation = Iznik.Session.getSetting('mylocation', null);
                    }
                    
                    var postcode = JSON.parse(mylocation).name;

                    if (mylocation) {
                        self.$('.js-postcode').typeahead('val', postcode);
                        self.locChange.call(self);
                    }
                } catch (e) {};

                var groupoverride = $('meta[name=iznikusergroupoverride]').attr("content");
                if (groupoverride) {
                    self.$('.js-groupoverridename').html(groupoverride);
                    self.$('.js-groupoverride').fadeIn('slow');
                }
            });

            return(p);
        }
    });
});