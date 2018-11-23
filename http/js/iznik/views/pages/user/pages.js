define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/facebook',
    'clipboard',
    'iznik/views/pages/pages',
    'typeahead',
    'jquery.scrollTo'
], function($, _, Backbone, Iznik, FBLoad, Clipboard) {
    Iznik.Views.User.Pages.WhereAmI = Iznik.Views.Page.extend({
        firstMatch: null,

        pctooltip: false,

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
            var self = this;

            if (this.pctooltip) {
                var field = self.$('.js-postcode');
                if (field.data && field.data('bs.tooltip')) {
                    self.$('.js-postcode').tooltip('destroy');
                }
                this.pctooltip = false;
            }
        },

        showButt: function() {
            var self = this;

            self.$('.js-hidepcpc').removeClass('col-lg-offset-3');
            self.$('.js-hidepcpc').addClass('col-lg-offset-7 col-lg-5');
            _.delay(function() {
                self.$('.js-hidepcpc').removeClass('margtrans');
                self.$('.js-hidepcpc').removeClass('col-lg-offset-7 col-lg-6');
                self.$('.js-hidepcbutt').removeClass('hidden');
                self.$('.js-hidepcor').removeClass('hidden');
            }, 1100);
        },

        hideButt: function() {
            var self = this;
            self.$('.js-hidepcbutt').addClass('hidden');
            self.$('.js-hidepcor').addClass('hidden');
            self.$('.js-hidepcpc').addClass('col-lg-offset-7');
            _.defer(function() {
                self.$('.js-hidepcpc').removeClass('col-lg-offset-7 col-lg-5');
                self.$('.js-hidepcpc').addClass('col-lg-offset-3 col-lg-6 margtrans');
            });
        },

        showHideButt: function() {
            var self = this;
            var val = self.$('.js-postcode').typeahead('val');

            if (val.length == 0) {
                self.showButt();
            } else {
                self.hideButt();
            }
        },

        keyUp: function(e) {
            var self = this;

            self.showHideButt();

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
            var self = this;
            self.showHideButt();

            // Make sure they can see the typeahead by scrolling.  Delay because an on-screen keyboard might open.
            _.delay(function() {
                var top = self.$('.tt-input').offset().top ;
                $('body').scrollTo(top - $('.navbar').height(), 'slow');
            }, 2000);
        },

        getLocation: function () {
            var self = this;
            window.showHeaderWait();
            self.$('.js-getloc').tooltip('destroy');
            self.$('.js-getloc').tooltip({
                'placement': 'bottom',
                'title': "Finding location..."
            });
            self.$('.js-getloc').tooltip('show');
            navigator.geolocation.getCurrentPosition(_.bind(this.gotLocation, this), _.bind(this.errorLocation, this), { timeout: 10000 });
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

                // We want to check if the group we have selected is on this platform - if not, then we want to choose the
                // first (closest) group.
                //
                // The selected group might either be one we are a member of, or one of the nearby suggested ones.
                var member = Iznik.Session.getGroup(val);
                var onhere = false;

                if (member) {
                    onhere = member.get('onhere');
                    console.log("Already member", onhere);
                }  else {
                    // Check nearby.
                    _.each(self.groupsnear, function(groupnear) {
                        if (groupnear.id == val) {
                            onhere = groupnear.onhere;
                            console.log("Found nearby", val, onhere);
                        }
                    });
                }

                if (!onhere) {
                    // We need to use the first nearby group which is on here.
                    var first = null;

                    _.each(self.groupsnear, function(groupnear) {
                        if (!first && groupnear.onhere) {
                            console.log("First is", groupnear);
                            first = groupnear;
                        }
                    });

                    if (first) {
                        val = first.id;
                        console.log("Selected not on here, use first", val);
                    }
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
                Storage.set('myhomegroup', val);
                Storage.set('myhomegrouptime', (new Date()).getTime());
            } catch (e) {}
        },

        locChange: function(showok) {
            var self = this;
            var showok = showok === null ? true : showok;

            var loc = this.$('.js-postcode').typeahead('val');

            $.ajax({
                type: 'GET',
                url: API + 'locations',
                data: {
                    typeahead: loc
                }, success: function(ret) {
                    if (ret.ret == 0 && ret.locations.length > 0) {
                        self.recordLocation(ret.locations[0]);

                        // Update our map if we have one.
                        var map = self.$('.js-locmap');
                        if (map.length > 0) {
                            var width = Math.floor(self.$('.js-postcode').width());
                            map.css('width', width);
                            map.css('height', width);
                            var mapicon = 'https://www.ilovefreegle.org/images/mapmarker.gif';	// CC
                            map.html('<img class="img-thumbnail" src="https://maps.google.com/maps/api/staticmap?size=' + width + 'x' + width + '&zoom=12&center=' + ret.locations[0].lat + ','  + ret.locations[0].lng + '&maptype=roadmap&markers=icon:' + mapicon + '|' + ret.locations[0].lat + ','  + ret.locations[0].lng + '&sensor=true" />');
                        }

                        if (showok) {
                            self.$('.js-savelocok').removeClass('hidden');
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
                        self.$('.js-homegroup').fadeIn('slow');
                    } else if (first.onyahoo && first.showonyahoo) {
                        // But Yahoo does and we want to show it.
                        self.$('.js-toyahoo').attr('href', 'https://groups.yahoo.com/neo/groups/' + first.nameshort);
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
                        self.$('.js-yahootoo a').attr('href', 'https://groups.yahoo.com/neo/groups/' + first.nameshort);
                    }
                }
            }
        },

        recordLocation: function(location) {
            var self = this;

            if (!_.isUndefined(location)) {
                // console.log("Record location ", this.$('.js-postcode').typeahead('val'), location.name); console.trace();
                this.$('.js-postcode').typeahead('val', location.name);
                self.$('.js-next').fadeIn('slow');
                self.$('.js-ok').fadeIn('slow');

                // console.log("Record location", location);
                if (!_.isUndefined(location.groupsnear)) {
                    self.groupsnear = location.groupsnear;
                }

                try {
                    var l = location;

                    // Save space.
                    delete l.groupsnear;

                    Iznik.Session.setSetting('mylocation', l);
                    Storage.set('mylocation', JSON.stringify(l))
                } catch (e) {
                    console.log("Exception", e.message);
                };

                var groups = self.$('.js-groups');

                if (groups.length > 0) {
                    // We have a group select dropdown on the page.
                    if (self.groupsnear) {
                        // We have some groups near their chosen location.
                        var homegroup = null;
                        var homegrouptime = null;
                        var homegroupfound = false;
                        var firstonhere = null;

                        try {
                            homegroup = Storage.get('myhomegroup');
                            homegrouptime = Storage.get('myhomegrouptime');
                        } catch (e) {};

                        // If the first group has been founded since the home group was set up, then we want to
                        // use that rather than a previous preference.  Otherwise new groups don't get existing
                        // members from their area.
                        if (self.groupsnear.length > 0) {
                            var g = self.groupsnear[0];
                            if (g) {
                                var founded = (new Date(g.founded)).getTime();
                                if (!homegrouptime || homegrouptime < founded) {
                                    homegroup = g.id;
                                    try {
                                        Storage.set('myhomegroup', homegroup);
                                        Storage.set('myhomegrouptime', (new Date()).getTime());
                                    } catch (e) {};
                                }
                            }
                        }

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

                        Iznik.Session.testLoggedIn([
                            'me',
                            'groups'
                        ]);

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

                self.trigger('gotlocation', location);
            }
        },
        
        errorLocation: function (position) { // CC
            console.log("errorLocation");
            window.hideHeaderWait();
            var self = this;
            self.$('.js-getloc').tooltip('destroy');
            _.delay(function () {
                self.$('.js-getloc').tooltip({
                    'placement': 'bottom',
                    'title': "No location available. Check your Settings for Location access/services."
                });
                self.$('.js-getloc').tooltip('show');
                _.delay(function () {
                    self.$('.js-getloc').tooltip('destroy');
                }, 20000);
            }, 500);
        },

        gotLocation: function(position) {
            console.log("gotLocation");
            window.hideHeaderWait();
            var self = this;
            self.$('.js-getloc').tooltip('destroy');

            $.ajax({
                type: 'GET',
                url: API + 'locations',
                data: {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                }, success: function(ret) {
                    if (ret.ret == 0 && ret.location) {
                        self.recordLocation(ret.location, true);

                        // Add some eye candy to make them spot the location.
                        self.ttHide();
                        self.$('.js-postcode').tooltip({
                            'placement': 'bottom',
                            'title': "Your device thinks you're here.  If it's wrong, please change it."});
                        self.$('.js-postcode').tooltip('show');
                        self.pctooltip = true;
                        _.delay(function() {
                            self.ttHide();
                        }, 20000);
                    }
                }, complete: function() {
                    if (self.wait) {
                        self.wait.close();
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
                    if (ret == '') return;  // CC
                    var matches = [];
                    _.each(ret.locations, function(location) {
                        matches.push(location.name);
                    });

                    asyncResults(matches);

                    _.delay(function() {
                        self.ttHide();
                    }, 10000);

                    if (matches.length == 0) {
                        self.$('.js-postcode').tooltip({'trigger':'focus', 'title': 'Please use a valid UK postcode (including the space)'});
                        self.$('.js-postcode').tooltip('show');
                        self.pctooltip = true;
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
                    minLength: 3,
                    hint: false,
                    highlight: true
                }, {
                    name: 'postcodes',
                    source: _.bind(self.postcodeSource, self)
                });

                try {
                    var id = Storage.get('draft');
                    var q = null;
                    var msg = null;

                    if (id) {
                        // We have a draft we were in the middle of.
                        msg = new Iznik.Models.Message({
                            id: id
                        });

                        q = msg.fetch();
                    } else {
                        q = Iznik.resolvedPromise(self);
                    }

                    q.then(function() {
                        if (id && msg.get('id') == id && !_.isUndefined(msg.get('location'))) {
                            // We want to use the location from the message we are in the middle of.
                            Storage.set('mylocation', JSON.stringify(msg.get('location')));
                        }

                        // See if we know where we are from last time.
                        var mylocation = Storage.get('mylocation');

                        if (!mylocation) {
                            mylocation = Iznik.Session.getSetting('mylocation', null);
                        } else {
                            mylocation = JSON.parse(mylocation);
                        }

                        if (mylocation) {
                            var postcode = mylocation.name;
                            self.$('.js-postcode').typeahead('val', postcode);
                            self.locChange.call(self, false);
                        }
                    });
                } catch (e) {};
            });

            return(p);
        }
    });

    Iznik.Views.User.Pages.WhatNext = Iznik.Views.Page.extend({
        id: null,
        image: null,

        render: function() {
            var self = this;

            var p = Iznik.Views.Page.prototype.render.call(this);
            p.then(function() {
                try {
                    var homegroup = Storage.get('myhomegroup');

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
                                self.id = Storage.get('lastpost');
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

    Iznik.Views.User.Pages.WhatNext.Share = Iznik.Views.Modal.extend({
        events: {
            'click .js-sharefb': 'sharefb',
            'click .js-close': 'clickclose',
            'click .js-whatsapp': 'whatsapp',
            'click .js-copy': 'copy'
        },

        clickclose: function() {
            Iznik.ABTestAction('sharepost', 'close');
            this.close();
        },

        sharefb: function() {
            var self = this;

            // Can get the image but sharing both image and link on FB means that only image shown and we want link - so image won't be available to other share types
            // var image = null;
            // var atts = self.model.get('attachments');
            // if (atts && atts.length > 0) {
            //     image = atts[0].path;
            // }

            var href = 'https://www.ilovefreegle.org/message/' + self.model.get('id') + '?src=mobileshare';
            var subject = self.model.get('subject');

            Iznik.ABTestAction('sharepost', 'Mobile Share');
            // https://github.com/EddyVerbruggen/SocialSharing-PhoneGap-Plugin
            var options = {
                message: "I've just posted this on Freegle - interested?\n\n", // not supported on some apps (Facebook, Instagram)
                subject: 'Freegle post: ' + subject, // for email
                //files: ['', ''], // an array of filenames either locally or remotely
                url: href,
                //chooserTitle: 'Pick an app' // Android only, you can override the default share sheet title
            }
            // if( image){
            //     options.files = [image];
            // }

            var onSuccess = function (result) {
                console.log("Share completed? " + result.completed); // On Android apps mostly return false even while it's true
                console.log("Shared to app: " + result.app); // On Android result.app is currently empty. On iOS it's empty when sharing is cancelled (result.completed=false)
                self.close();
            }

            var onError = function (msg) {
                console.log("Sharing failed with message: " + msg);
            }
            // Iznik.ABTestAction('sharepost', 'facebook');

            window.plugins.socialsharing.shareWithOptions(options, onSuccess, onError);
            /*FB.ui(params, function (response) {
                self.close();
            });*/
        },

        whatsapp: function() {
            var self = this;

            Iznik.ABTestAction('sharepost', 'whatsapp');
            var url = 'whatsapp://send?text=' + encodeURI(self.model.get('subject') + " - see more at " + self.url);
            window.open(url);
        },

        render: function() {
            var self = this;

            var p = Iznik.Views.Modal.prototype.render.call(self);

            self.url = window.location.protocol + '//' + window.location.host + '/message/' + self.model.get('id') + '?src=fbpost';

            p.then(function() {
                self.listenToOnce(FBLoad(), 'fbloaded', function () {
                    if (!FBLoad().isDisabled()) {
                        self.$('.js-sharefb').show();
                    }
                });

                FBLoad().render();

                Iznik.ABTestShown('sharepost', 'facebook');
                Iznik.ABTestShown('sharepost', 'clipboard');
                Iznik.ABTestShown('sharepost', 'close');

                if (Iznik.isSM()) {
                    Iznik.ABTestShown('sharepost', 'whatsapp');
                }

                self.clipboard = new Clipboard('.js-clip', {
                    text: function() {
                        console.log("Get text", self.url);
                        return self.url;
                    }
                });

                self.clipboard.on('success', function(e) {
                    Iznik.ABTestAction('sharepost', 'clipboard');
                    self.close();
                });

                self.clipboard.on('error', function(e) {
                    console.error('Clipboard error', e);
                    self.close();
                });
            });

            return(p);
        }
    });
});