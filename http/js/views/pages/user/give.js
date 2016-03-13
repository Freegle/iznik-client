Iznik.Views.User.Pages.Give.WhereAmI = Iznik.Views.User.Pages.WhereAmI.extend({
    template: "user_give_whereami"
});

Iznik.Views.User.Pages.Give.WhatIsIt = Iznik.Views.Page.extend({
    template: "user_give_whatisit",

    render: function() {
        var self = this;

        Iznik.Views.Page.prototype.render.call(this);
        console.log("Resize", window.navigator.userAgent, /Android(?!.*Chrome)|Opera/
            .test(window.navigator.userAgent));

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
            done: function (e, data) {
                self.$('.js-uploading').addClass('hidden');
                self.$('.js-uploaded').removeClass('hidden');

                console.log("Done", data);
                _.each(data.result.files, function(file) {
                    // Add thumbnail.
                    var v = new Iznik.Views.User.Pages.Give.Thumbnail({
                        model: new IznikModel({
                            src: file.thumbnailUrl
                        })
                    });

                    self.$('.js-thumbnails').append(v.render().el);

                    // Create attachment object and try to identify this as an object
                    $.ajax({
                        type: 'PUT',
                        url: API + 'image',
                        data: {
                            identify: true,
                            filename: file.name
                        }, success: function(ret) {
                            console.log("Attachment returned", ret);
                            if (ret.ret === 0) {
                                var faded = false;
                                _.each(ret.items, function(item) {
                                    console.log("Add item", item)
                                    if (!faded) {
                                        self.$('.js-items').closest('.alert').fadeIn('slow');
                                        faded = true;
                                    }
                                    var v = new Iznik.Views.User.Pages.Give.Item({
                                        model: new IznikModel(item)
                                    });

                                    self.$('.js-items').append(v.render().el);
                                });
                            }
                        }
                    })
                });
            },
            progressall: function (e, data) {
                self.$('.js-addprompt').addClass('hidden');
                self.$('.js-uploaded').addClass('hidden');
                self.$('.js-uploading').removeClass('hidden');
                var progress = parseInt(data.loaded / data.total * 100, 10);

                self.$('.js-progress .progress-bar').css(
                    'width',
                    progress + '%'
                );
            }
        }).on('fileuploadfail', function (e, data) {
            self.$('.js-uploaded').addClass('hidden');
            self.$('.js-uploading').addClass('hidden');
            self.$('.js-uploadfailed').removeClass('hidden');
        });

        return(this);
    }
});

Iznik.Views.User.Pages.Give.Thumbnail = IznikView.extend({
    tagName: 'li',

    template: "user_give_thumbnail",

    events: {
    }
});


Iznik.Views.User.Pages.Give.Item = IznikView.extend({
    tagName: 'li',

    template: "user_give_item",

    events: {
        'click .js-remove': 'remove'
    }
});

