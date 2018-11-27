define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/views/pages/pages',
    'iznik/models/membership'
], function($, _, Backbone, Iznik) {
    Iznik.Views.User.Pages.Stories = Iznik.Views.Page.extend({
        template: "user_stories_main",

        events: {
            'click .js-add': 'addStory'
        },

        addStory: function() {
            var self = this;

            self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                var v = new Iznik.Views.User.Pages.Stories.Add();
                v.render();
            });

            Iznik.Session.forceLogin([
                'me',
                'groupis'
            ]);
        },

        render: function () {
            var self = this;

            self.model = new Iznik.Model({
                reviewnewsletter: self.options.reviewnewsletter
            });

            var p = Iznik.Views.Page.prototype.render.call(this);

            p.then(function(self) {
                self.collection = new Iznik.Collections.Members.Stories();

                // CollectionView handles adding/removing/sorting for us.
                self.collectionView = new Backbone.CollectionView({
                    el: self.$('.js-list'),
                    modelView: Iznik.Views.User.Pages.Stories.One,
                    collection: self.collection,
                    processKeyEvents: false,
                });

                self.collectionView.render();

                self.collection.fetch({
                    data: {
                        reviewnewsletter: self.options.reviewnewsletter,
                        groupid: self.options.groupid
                    }
                });
            });

            return(p);
        }
    });

    Iznik.Views.User.Pages.Stories.Single = Iznik.Views.Page.extend({
        template: "user_stories_single",

        render: function () {
            var self = this;

            self.model = new Iznik.Models.Membership.Story({
                id: self.options.id
            });

            var p = self.model.fetch();

            p.then(function() {
                Iznik.Views.Page.prototype.render.call(self);
            });

            return(p);
        }
    });

    Iznik.Views.User.Pages.Stories.Thankyou = Iznik.Views.Modal.extend({
        template: 'user_stories_thankyou'
    });

    Iznik.Views.User.Pages.Stories.Add = Iznik.Views.Modal.extend({
        template: 'user_stories_add',

        events: {
            'click .js-add': 'addStory'
        },

        imageid: null,

        setupPhotoUpload: function() {
            var self = this;

            // Photo upload.
            self.$el.find('.js-addphoto').fileinput({
                uploadExtraData: {
                    imgtype: 'Story',
                    story: 1,
                    ocr: false
                },
                showUpload: false,
                allowedFileExtensions: ['jpg', 'jpeg', 'gif', 'png'],
                uploadUrl: API + 'image',
                resizeImage: true,
                maxImageWidth: 800,
                browseIcon: '<span class="glyphicon glyphicon-camera" />&nbsp;',
                browseLabel: 'Add Photo',
                browseClass: 'btn btn-primary btn-lg nowrap',
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
                },
                elErrorContainer: '#js-uploaderror'
            });

            // Upload as soon as we have it.
            self.$el.find('.js-addphoto').on('fileimagesresized', function (event) {
                self.$('.js-photopreviewwrapper').show();
                self.$('.js-addphotowrapper').hide();
                self.$('.js-addphoto').fileinput('upload');
            });

            self.$el.find('.js-addphoto').on('fileuploaded', function (event, data) {
                self.$('.js-photopreview').attr('src', data.response.paththumb);
                self.imageid = data.response.id;

                _.delay(function() {
                    self.$('.file-preview-frame').remove();
                }, 500);
            });
        },

        addStory: function() {
            var self = this;
            self.$('.error').removeClass('error');

            var headline = self.$('.js-headline').val();
            if (headline.length == 0) {
                self.$('.js-headline').addClass('error');
            } else {
                var story = self.$('.js-story').val();
                if (story.length == 0) {
                    self.$('.js-story').addClass('error');
                } else {
                    var isPublic = self.$('input[name=js-public]:checked').val();

                    $.ajax({
                        url: API + 'stories',
                        type: 'POST',
                        headers: {
                            'X-HTTP-Method-Override': 'PUT'
                        },
                        data: {
                            headline: headline,
                            story: story,
                            public: isPublic,
                            photo: self.imageid
                        }, success: function(ret) {
                            if (ret.ret == 0) {
                                self.close();
                                var v = new Iznik.Views.User.Pages.Stories.Thankyou();
                                v.render();
                            }
                        }
                    })
                }
            }
        },

        render: function() {
            var self = this;

            var p = Iznik.Views.Modal.prototype.render.call(this);
            p.then(function() {
                self.setupPhotoUpload();
            });

            return(p);
        }
    });

    Iznik.Views.User.Pages.Stories.One = Iznik.View.extend({
        tagName: 'li',

        template: 'user_stories_one',

        events: {
            'click .js-like': 'like'
        },

        like: function() {
            var self = this;

            self.listenToOnce(Iznik.Session, 'loggedIn', function () {
                var p;

                var liked = self.model.get('liked');

                if (liked) {
                    p = self.model.unlike();
                } else {
                    p = self.model.like();
                }

                p.then(function() {
                    self.model.fetch().then(function() {
                        self.render();
                    });
                });
            });

            Iznik.Session.forceLogin([
                'me'
            ]);
        }
    });
});