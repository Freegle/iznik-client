define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'fileinput',
    'iznik/models/user/message',
    'iznik/views/group/select',
    'iznik/models/user/message',
    'iznik/views/user/message'
], function ($, _, Backbone, Iznik) {
    Iznik.Views.User.Pages.WhatIsIt = Iznik.Views.Page.extend({
        pleaseWait: null,

        suggestions: [],
        uploading: 0,

        events: {
            'click .js-next': 'next',
            'change .js-item': 'checkNext',
            'change .tt-hint': 'checkNext',
            'keyup .js-description': 'checkNext',
            'change .bootstrap-tagsinput .tt-input': 'checkNext'
        },

        getItem: function () {
            var val = this.$('.js-item').typeahead('val');
            if (!val) {
                val = this.$('.js-item').val();
            }
            return(val);
        },

        checkNext: function () {
            var self = this;

            if (this.$el.closest('body').length > 0) {
                var item = this.getItem();
                self.$('.js-item').removeClass('error-border');
                self.$('.js-description').removeClass('error-border');

                // We accept either a photo or a description.
                if (self.uploading || item.length == 0) {
                    self.$('.js-item').addClass('error-border');
                    self.$('.js-next').fadeOut('slow');
                    self.$('.js-ok').fadeOut('slow');
                } else if (self.$('.js-description').val().length > 0 || self.photos.length > 0) {
                    self.$('.js-next').fadeIn('slow');
                    self.$('.js-ok').fadeIn('slow');
                } else if (self.$('.js-description').val().length == 0) {
                    self.$('.js-description').addClass('error-border');
                }

                _.delay(_.bind(self.checkNext, self), 300);
            }
        },

        save: function () {
            // Save the current message as a draft.
            var self = this;

            var item = this.getItem();
            if (item.length == 0) {
                self.$('.js-item').focus();
                self.$('.js-item').addClass('error-border');
            }

            var locationid = null;
            var groupid = null;
            try {
                var loc = localStorage.getItem('mylocation');
                locationid = loc ? JSON.parse(loc).id : null;
                groupid = localStorage.getItem('myhomegroup');
            } catch (e) {};

            var d = jQuery.Deferred();
            var attids = [];
            this.photos.each(function (photo) {
                attids.push(photo.get('id'))
            });

            var data = {
                collection: 'Draft',
                locationid: locationid,
                messagetype: self.msgType,
                item: item,
                textbody: self.$('.js-description').val(),
                attachments: attids,
                groupid: groupid
            };

            $.ajax({
                type: 'PUT',
                url: API + 'message',
                data: data,
                success: function (ret) {
                    if (ret.ret == 0) {
                        d.resolve();
                        try {
                            localStorage.setItem('draft', ret.id);
                        } catch (e) {
                        }
                    } else {
                        d.reject();
                    }
                }, error: function () {
                    d.reject();
                }
            });

            return (d.promise());
        },

        next: function () {
            var self = this;
            this.save().done(function () {
                Router.navigate(self.whoami, true);
            }).fail(function () {
                self.$('.js-saveerror').fadeIn('slow');
            });
        },

        itemSource: function (query, syncResults, asyncResults) {
            var self = this;

            if (query.length >= 2) {
                $.ajax({
                    type: 'GET',
                    url: API + 'item',
                    data: {
                        typeahead: query
                    }, success: function (ret) {
                        var matches = [];
                        _.each(ret.items, function (item) {
                            if (item.hasOwnProperty('item')) {
                                matches.push(item.item.name);
                            }
                        })

                        asyncResults(matches);
                    }
                })
            }
        },

        allUploaded: function() {
            var self = this;

            if (self.suggestions.length > 0) {
                var v = new Iznik.Views.Help.Box();
                v.template = 'user_give_suggestions';
                v.render().then(function(v) {
                    self.$('.js-sugghelp').html(v.el);
                    _.each(self.suggestions, function(suggestion) {
                        var html = '<li class="btn btn-white js-suggestion">' + suggestion.name + '</li>';
                        self.$('.js-suggestions').append(html);
                        self.$('.js-suggestion:last').on('click', function(e) {
                            self.$('.js-item').typeahead('val', e.target.innerHTML);
                        })
                    })
                });
            }
        },

        render: function () {
            var self = this;
            self.photos = new Iznik.Collection();

            var p = Iznik.Views.Page.prototype.render.call(this).then(function () {
                _.delay(_.bind(self.checkNext, self), 300);

                self.typeahead = self.$('.js-item').typeahead({
                    minLength: 2,
                    hint: false,
                    highlight: true,
                    autoselect: false
                }, {
                    name: 'items',
                    source: self.itemSource
                });

                if (self.options.item) {
                    self.$('.js-item').typeahead('val', self.options.item);
                }

                // Close the suggestions after 30 seconds in case people are confused.
                self.$('.js-item').bind('typeahead:open', function() {
                    _.delay(function() {
                        self.$('.js-item').typeahead('close');
                    }, 30000);
                });

                // File upload
                self.$('#fileupload').fileinput({
                    uploadExtraData: {
                        type: 'Message',
                        identify: true
                    },
                    showUpload: false,
                    allowedFileExtensions: ['jpg', 'jpeg', 'gif', 'png'],
                    uploadUrl: API + 'image',
                    resizeImage: true,
                    maxImageWidth: 800,
                    browseIcon: '<span class="glyphicon glyphicon-plus" />&nbsp;',
                    browseLabel: 'Add photos',
                    browseClass: 'btn btn-primary nowrap',
                    showCaption: false,
                    showRemove: false,
                    showUploadedThumbs: false,
                    dropZoneEnabled: false,
                    buttonLabelClass: '',
                    fileActionSettings: {
                        showZoom: false,
                        showRemove: false,
                        showUpload: false
                    },
                    layoutTemplates: {
                        footer: '<div class="file-thumbnail-footer">\n' +
                        '    {actions}\n' +
                        '</div>'
                    }
                });

                // Count how many we will upload.
                self.$('#fileupload').on('fileloaded', function (event) {
                    self.uploading++;
                });

                // Upload as soon as photos have been resized.
                self.$('#fileupload').on('fileimageresized', function (event) {
                    self.$('#fileupload').fileinput('upload');

                    // We don't seem to be able to hide this control using the options.
                    self.$('.fileinput-remove').hide();
                });

                // Watch for all uploaded
                self.$('#fileupload').on('fileuploaded', function (event, data) {
                    // Add the photo to our list
                    var mod = new Iznik.Models.Message.Attachment({
                        id: data.response.id,
                        src: data.response.paththumb,
                        path: data.response.paththumb,
                        mine: true
                    });

                    self.photos.add(mod);

                    // Show the uploaded thumbnail and hackily remove the one provided for us.
                    self.draftPhotos.render();
                    _.delay(function() {
                        self.$('.file-preview-frame').remove();
                    }, 500);

                    // Add any hints about the item
                    self.$('.js-suggestions').empty();
                    self.suggestions = [];

                    _.each(data.response.items, function (item) {
                        self.suggestions.push(item);
                    });

                    self.uploading--;

                    if (self.uploading == 0) {
                        self.allUploaded();
                    }
                });

                try {
                    var id = localStorage.getItem('draft');

                    if (id) {
                        // We have a draft we were in the middle of.
                        var msg = new Iznik.Models.Message({
                            id: id
                        });

                        msg.fetch().then(function () {
                            if (self.msgType == msg.get('type')) {
                                // Parse out item from subject.
                                var matches = /(.*?)\:([^)].*)\((.*)\)/.exec(msg.get('subject'));
                                if (matches && matches.length > 2 && matches[2].length > 0) {
                                    self.$('.js-item').val(matches[2]);
                                } else {
                                    self.$('.js-item').val(msg.get('subject'));
                                }

                                msg.stripGumf('textbody');
                                self.$('.js-description').val(msg.get('textbody'));

                                // Add the thumbnails.
                                self.photos = new Iznik.Collection(msg.get('attachments'));
                                msg.set('mine', true);

                                self.draftPhotos = new Iznik.Views.User.Message.Photos({
                                    collection: self.photos,
                                    message: msg,
                                    showAll: true
                                });

                                self.draftPhotos.render().then(function() {
                                    self.$('.js-draftphotos').html(self.draftPhotos.el);
                                });
                            }
                        });
                    } else {
                        // Just set up an empty collection of photos.
                        // Add the thumbnails.
                        self.photos = new Iznik.Collection();

                        self.draftPhotos = new Iznik.Views.User.Message.Photos({
                            collection: self.photos,
                            message: null,
                            showAll: true
                        });

                        self.draftPhotos.render().then(function() {
                            self.$('.js-draftphotos').html(self.draftPhotos.el);
                        });
                    }
                } catch (e) {
                }
            });

            return (p);
        }
    });

    Iznik.Views.User.Pages.WhoAmI = Iznik.Views.Page.extend({
        events: {
            'change .js-email': 'changeEmail',
            'keyup .js-email': 'changeEmail',
            'click .js-next': 'doit'
        },

        doit: function () {
            var self = this;
            var email = this.$('.js-email').val();
            var id = null;

            try {
                id = localStorage.getItem('draft');
            } catch (e) {
            }

            if (id) {
                self.pleaseWait = new Iznik.Views.PleaseWait();
                self.pleaseWait.render();

                $.ajax({
                    type: 'POST',
                    url: API + 'message',
                    data: {
                        action: 'JoinAndPost',
                        email: email,
                        id: id
                    }, success: function (ret) {
                        self.pleaseWait.close();

                        if (ret.ret == 0) {
                            try {
                                // The draft has now been sent.
                                localStorage.setItem('lastpost', id);
                                localStorage.removeItem('draft');
                            } catch (e) {}

                            if (ret.newuser) {
                                // We didn't know this email and have created a user for them.  Show them an invented
                                // password, and allow them to change it.
                                Iznik.Session.set('inventedpassword', ret.newpassword);
                                Iznik.Session.set('newuser', ret.newuser);
                                Router.navigate('/newuser', true);
                            } else {
                                // Known user.  Just display the confirm page.
                                Router.navigate(self.whatnext, true)
                            }
                        }
                    }, error: self.fail
                });
            }
        },

        changeEmail: function () {
            var email = this.$('.js-email').val();
            try {
                localStorage.setItem('myemail', email);
            } catch (e) {
            }

            if (isValidEmailAddress(email)) {
                this.$('.js-email').removeClass('error-border');
                this.$('.js-next').fadeIn('slow');
                this.$('.js-ok').fadeIn('slow');
            } else {
                this.$('.js-email').addClass('error-border');
                this.$('.js-next').fadeOut('slow');
                this.$('.js-ok').hide();
            }
        },

        render: function () {
            var p = Iznik.Views.Page.prototype.render.call(this);
            p.then(function(self) {
                self.listenToOnce(Iznik.Session, 'isLoggedIn', function (loggedIn) {
                    if (loggedIn) {
                        // We know our email address from the session
                        self.$('.js-email').val(Iznik.Session.get('me').email);
                        self.changeEmail();
                    } else {
                        // We're not logged in - but we might have remembered one.
                        try {
                            var email = localStorage.getItem('myemail');
                            if (email) {
                                self.$('.js-email').val(email);
                                self.changeEmail();
                            }
                        } catch (e) {
                        }
                    }
                });

                Iznik.Session.testLoggedIn();
            });

            return(p);
        }
    });
});
