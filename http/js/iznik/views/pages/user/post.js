define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'fileupload'
], function ($, _, Backbone, Iznik) {
    Iznik.Views.User.Pages.WhatIsIt = Iznik.Views.Page.extend({
        events: {
            'click .js-next': 'next',
            'change .js-items': 'checkNext',
            'change .bootstrap-tagsinput .tt-input': 'checkNext'
        },

        getItem: function () {
            // We might have some tags input, and also some freeform text.  We are interested in having both.
            return (this.$('.js-items').val().join(' ') + ' ' + this.$('.tt-input').val());
        },

        checkNext: function () {
            var item = this.getItem();
            if (item.length > 0) {
                this.$('.js-next').fadeIn('slow');
            } else {
                this.$('.js-next').fadeOut('slow');
            }
        },

        save: function () {
            // Save the current message as a draft.
            var self = this;

            var item = this.getItem();
            if (item.length == 0) {
                self.$('.tt-input').focus();
                self.$('.bootstrap-tagsinput').addClass('error-border');
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
                            matches.push(item.item.name);
                        })

                        asyncResults(matches);
                    }
                })
            }
        },

        render: function () {
            var self = this;

            Iznik.Views.Page.prototype.render.call(this);

            if (self.options.item) {
                self.$('.js-items').val(self.options.item);
            }

            self.$('.js-items').tagsinput({
                freeInput: true,
                trimValue: true,
                tagClass: 'label-primary',
                confirmKeys: [9, 13],
                typeaheadjs: {
                    name: 'items',
                    source: this.itemSource
                }
            });

            // CollectionView handles adding/removing for us.
            self.photos = new Iznik.Collection();
            self.collectionView = new Backbone.CollectionView({
                el: self.$('.js-thumbnails'),
                modelView: Iznik.Views.User.Thumbnail,
                modelViewOptions: {
                    collection: self.photos,
                    page: self
                },
                collection: self.photos
            });

            self.collectionView.render();

            self.$('.js-upload').fileupload({
                url: API + 'upload',
                // Enable image resizing, except for Android and Opera,
                // which actually support image resizing, but fail to
                // send Blob objects via XHR requests:
                disableImageResize: /Android(?!.*Chrome)|Opera/
                    .test(window.navigator.userAgent),
                imageMaxWidth: 800,
                imageMaxHeight: 800,
                acceptFileTypes: /(\.|\/)(gif|jpe?g|png)$/i,
                dataType: 'json',
                add: function (e, data) {
                    self.pleaseWait = new Iznik.Views.PleaseWait({
                        timeout: 1
                    });
                    self.pleaseWait.template = 'user_give_uploadwait';
                    self.pleaseWait.render();

                    if (data.autoUpload || (data.autoUpload !== false &&
                        $(this).fileupload('option', 'autoUpload'))) {
                        data.process().done(function () {
                            data.submit();
                        });
                    }
                },
                done: function (e, data) {
                    self.$('.js-uploading').addClass('hidden');
                    var promises = [];
                    self.tagcount = 0;

                    _.each(data.result.files, function (file) {
                        // Create attachment object and try to identify this as an object
                        promises.push($.ajax({
                            type: 'PUT',
                            url: API + 'image',
                            data: {
                                identify: true,
                                filename: file.name
                            }, success: function (ret) {
                                if (ret.ret === 0) {
                                    // Add thumbnail.
                                    var mod = new Iznik.Models.Message.Attachment({
                                        id: ret.id,
                                        src: file.thumbnailUrl
                                    });

                                    self.photos.add(mod);

                                    // Add any hints about the item
                                    _.each(ret.items, function (item) {
                                        self.$('.js-items').tagsinput('add', item.name);
                                        self.tagcount++;
                                    });

                                    self.checkNext();
                                }
                            }
                        }));
                    });

                    $.when.apply($, promises).done(function () {
                        self.pleaseWait.close();

                        if (self.tagcount > 0) {
                            var v = new Iznik.Views.Help.Box();
                            v.template = 'user_give_suggestions';
                            self.$('.js-sugghelp').html(v.render().el);
                        }
                    });
                },
                progressall: function (e, data) {
                    self.$('.js-addprompt').addClass('hidden');
                    self.$('.js-uploading').removeClass('hidden');
                    var progress = parseInt(data.loaded / data.total * 100, 10);

                    self.pleaseWait.$('.js-progress .progress-bar').css(
                        'width',
                        progress + '%'
                    );
                }
            }).on('fileuploadfail', function (e, data) {
                self.$('.js-uploading').addClass('hidden');
                self.$('.js-uploadfailed').removeClass('hidden');
            });

            return (this);
        }
    });

    Iznik.Views.User.Thumbnail = Iznik.View.extend({
        tagName: 'li',

        template: "user_thumbnail",

        events: {
            'click .js-remove': 'removeMe'
        },

        removeMe: function () {
            this.model.destroy();
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
                $.ajax({
                    type: 'POST',
                    url: API + 'message',
                    data: {
                        action: 'JoinAndPost',
                        email: email,
                        id: id
                    }, success: function (ret) {
                        if (ret.ret == 0) {
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
            var self = this;

            Iznik.Views.Page.prototype.render.call(this);

            this.listenToOnce(Iznik.Session, 'isLoggedIn', function (loggedIn) {
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
        }
    });
});
