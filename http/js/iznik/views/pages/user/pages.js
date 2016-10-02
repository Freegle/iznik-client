define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/facebook',
    'iznik/views/pages/pages',
    'typeahead',
    'jquery.scrollTo'
], function($, _, Backbone, Iznik, FBLoad) {
    Iznik.Views.User.Pages.WhereAmI = Iznik.Views.Page.extend({
        firstMatch: null,

        events: {
            'focus .tt-input': 'scrollTo',
            'click .js-getloc': 'getLocation',
            'click .js-changehomegroup': 'changeHomeGroup',
            'typeahead:change .js-postcode': 'locChange',
            'keyup .js-postcode': 'keyUp',
            'focus .js-postcode': 'ttHide',
            'click.js-postcode': 'ttHide',
            'click .tt-suggestion': 'locChange'
        },

        ttHide: function() {
            this.$('.js-postcode').tooltip('destroy');
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
                                self.recordLocation(ret.locations[0]);
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
            var self = this;

            // This is invoked when we choose to continue on the site.  We save the group we are using in
            // local storage for later pages to use.
            var val;

            if (self.$('.js-homegroup').length > 0) {
                // We have a group dropdown.  Choose the group which it is showing.
                val = self.$('.js-homegroup select').val();
                console.log("Get from dropdown", self.groupsnear.length);

                var first = null;
                var onhere = false;
                _.each(self.groupsnear, function(groupnear) {
                    console.log("Consider", groupnear);
                    if (!first && groupnear.onhere) {
                        console.log("First is", groupnear);
                        first = groupnear;
                    }

                    if (groupnear.id == val) {
                        onhere = groupnear.onhere;
                        console.log("Found ", val, onhere);
                    }
                });

                if (!onhere) {
                    val = first.id;
                    console.log("Selected not on here, use first", val);
                }
            } else {
                // We don't have a dropdown but are proceeding onsite.  Choose the closest group which is on the
                // site.
                console.log("No select, nearby groups", self.groupsnear);
                _.each(self.groupsnear, function(groupnear) {
                    if (!val && groupnear.onhere) {
                        console.log("Got on here", groupnear);
                        val = groupnear.id;
                    }
                });
            }

            try {
                console.log("Save home group", val);
                localStorage.setItem('myhomegroup', val);
            } catch (e) {}
        },

        locChange: function() {
            var self = this;

            var loc = this.$('.js-postcode').typeahead('val');

            $.ajax({
                type: 'GET',
                url: API + 'locations',
                data: {
                    typeahead: loc
                }, success: function(ret) {
                    if (ret.ret == 0) {
                        self.recordLocation(ret.locations[0]);

                        // Update our map if we have one.
                        var map = self.$('.js-locmap');
                        if (map.length > 0) {
                            var width = self.$('.js-postcode').width();
                            map.css('width', width);
                            map.css('height', width);
                            var mapicon = window.location.protocol + '//' + window.location.hostname + '/images/mapmarker.gif';
                            map.html('<img class="img-thumbnail" src="https://maps.google.com/maps/api/staticmap?size=' + width + 'x' + width + '&zoom=12&center=' + ret.locations[0].lat + ','  + ret.locations[0].lng + '&maptype=roadmap&markers=icon:' + mapicon + '|' + ret.locations[0].lat + ','  + ret.locations[0].lng + '&sensor=true" />');
                        }
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
            });

            if (!first) {
                // Might not be nearby, but in our own list.
                var mygroups = Iznik.Session.get('groups');

                if (mygroups) {
                    mygroups.each(function(group) {
                        if (group.get('id') == current) {
                            first = group.attributes;
                        }
                    });
                }
            }

            // Start off with no variant showing.
            self.$('.js-onyahoo').hide();
            self.$('.js-yahootoo').hide();
            self.$('.js-toyahoo').hide();
            self.$('.js-next').hide();
            self.$('.js-external').hide();

            console.log("changeGroup", first);
            if (first) {
                self.$('.js-closestgroupname').html(first.namedisplay);

                if (!first.onhere) {
                    if (first.external) {
                        // Hosted externally on a different site.
                        self.$('.js-toexternal').attr('href', first.external);
                        self.$('.js-external').fadeIn('slow');
                        self.$('.js-homegroup').hide();
                    } else if (first.onyahoo && first.showonyahoo) {
                        // But Yahoo does and we want to show it.
                        self.$('.js-toyahoo').attr('href', 'https://groups.yahoo.com/group/' + first.nameshort);
                        self.$('.js-onyahoo').fadeIn('slow');
                        self.$('.js-toyahoo').show();
                        self.$('.js-homegroup').fadeIn('slow');
                    }
                } else {
                    // We host this group.
                    self.$('.js-homegroup, .js-next').fadeIn('slow');

                    if (first.onyahoo && first.showonyahoo && self.$('.js-groups').length > 0) {
                        // But it's also on Yahoo, and some people might want to go there.
                        self.$('.js-yahootoo').show();
                        self.$('.js-yahootoo a').attr('href', 'https://groups.yahoo.com/group/' + first.nameshort);
                    }
                }
            }
        },

        recordLocation: function(location) {
            var self = this;
            console.log("Record location ", this.$('.js-postcode').typeahead('val'), location.name); console.trace();

            if (!_.isUndefined(location)) {
                this.$('.js-postcode').typeahead('val', location.name);
                self.$('.js-next').fadeIn('slow');
                self.$('.js-ok').fadeIn('slow');

                // console.log("Record location", location);
                if (!_.isUndefined(location.groupsnear)) {
                    self.groupsnear = location.groupsnear;
                    console.log("Groupsnear length", self.groupsnear.length);
                }

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

                if (groups.length > 0) {
                    // We have a group select dropdown on the page.
                    if (self.groupsnear) {
                        // We have some groups near their chosen location.
                        var homegroup = null;
                        var homegroupfound = false;
                        var firstonhere = null;

                        try {
                            homegroup = localStorage.getItem('myhomegroup');
                        } catch (e) {};

                        // Show home group if it's present.
                        var addedGroups = [];
                        groups.empty();
                        _.each(self.groupsnear, function(groupnear) {
                            if (homegroup == groupnear.id) {
                                homegroupfound = true;
                            }

                            if (!firstonhere && groupnear.onhere) {
                                firstonhere = groupnear.id;
                            }
                            groups.append('<option value="' + groupnear.id + '" />');
                            groups.find('option:last').text(groupnear.namedisplay);
                            addedGroups.push(groupnear.id);
                        });

                        // Add remaining Freegle groups we're a member of - maybe we have a reason to post on them.
                        self.listenToOnce(Iznik.Session, 'isLoggedIn', function (loggedIn) {
                            if (loggedIn) {
                                var mygroups = Iznik.Session.get('groups');
                                mygroups.each(function(group) {
                                    if (group.get('type') == 'Freegle' && addedGroups.indexOf(group.get('id'))) {
                                        groups.append('<option value="' + group.get('id') + '" />');
                                        groups.find('option:last').text(group.get('namedisplay'));
                                    }
                                });
                            }
                        });

                        Iznik.Session.testLoggedIn();

                        if (homegroupfound) {
                            groups.val(homegroup);
                        }

                        self.changeGroup();
                        groups.on('change', _.bind(self.changeGroup, self));
                    }
                } else {
                    // We don't have a groups drop down.  Hide that section, but still check for whether we need to
                    // redirect to Yahoo.
                    self.$('.js-homegroup').hide();
                    if (self.groupsnear) {
                        self.changeGroup();
                    }
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

                        // Add some eye candy to make them spot the location.
                        self.$('.js-postcode').tooltip('destroy');
                        self.$('.js-postcode').tooltip({
                            'placement': 'bottom',
                            'title': "Your device thinks you're here.  If it's wrong, please change it."});
                        self.$('.js-postcode').tooltip('show');
                        _.delay(function() {
                            self.$('.js-postcode').tooltip('destroy');
                        }, 20000);
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

                    _.default(function() {
                        self.$('.js-postcode').tooltip('destroy');
                    }, 10000);

                    if (matches.length == 0) {
                        self.$('.js-postcode').tooltip({'trigger':'focus', 'title': 'Please use a valid UK postcode'});
                        self.$('.js-postcode').tooltip('show');
                    } else {
                        self.firstMatch = matches[0];
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

    Iznik.Views.User.Pages.WhatNext = Iznik.Views.Page.extend({
        events: {
            'click .js-sharefb': 'sharefb'
        },

        id: null,
        image: null,

        sharefb: function() {
            var self = this;

            if (self.id) {
                var params = {
                    method: 'share',
                    href: window.location.protocol + '//' + window.location.host + '/message/' + self.id,
                    image: self.image
                };

                FB.ui(params, function (response) {
                    self.$('.js-fbshare').fadeOut('slow');
                });
            }
        },

        render: function() {
            var self = this;

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

                    self.listenToOnce(FBLoad(), 'fbloaded', function () {
                        if (!FBLoad().isDisabled()) {
                            // Get the message so that we can include a picture in the share parameters.  This results in a
                            // better preview in the share dialog.
                            //
                            // We have to do this here, because we can't do async stuff in the click on the FB button
                            // otherwise the browser blocks our popup.
                            try {
                                self.id = localStorage.getItem('lastpost');
                            } catch (e) {}

                            if (self.id) {
                                var message = new Iznik.Models.Message({ id: self.id });
                                message.fetch().then(function() {
                                    var atts = message.get('attachments');
                                    if (atts && atts.length > 0) {
                                        self.image = atts[0].path;
                                    }
                                });

                                self.$('.js-sharefb').fadeIn('slow');
                            }
                        }
                    });

                    FBLoad().render();
                } catch (e) { console.error("Give error", e.message)};
            });

            return(p)
        }
    });
});