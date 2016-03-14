Iznik.Views.User.Pages.Give.WhereAmI = Iznik.Views.User.Pages.WhereAmI.extend({
    template: "user_give_whereami"
});

Iznik.Views.User.Pages.Give.WhatIsIt = Iznik.Views.Page.extend({
    template: "user_give_whatisit",

    events: function(){
        return _.extend({}, Iznik.Views.Page.prototype.events,{
            'click .js-next': 'next',
            'change .js-items': 'changedItems'
        });
    },

    changedItems: function() {
        var self = this;
        if (self.$('.js-items').length == 0) {
            self.$('.bootstrap-tagsinput').addClass('error-border');
        } else {
            self.$('.bootstrap-tagsinput').removeClass('error-border');
        }
    },

    next: function() {
        var items = this.$('.js-items').tagsinput('items');
        console.log("Items", this.$('.js-items').val(), items);
        if (items.length == 0) {
            self.$('.tt-input').focus();
            self.$('.bootstrap-tagsinput').addClass('error-border');
        }
    },

    itemSource: function(query, syncResults, asyncResults) {
        var self = this;

        if (query.length >= 2) {
            $.ajax({
                type: 'GET',
                url: API + 'item',
                data: {
                    typeahead: query
                }, success: function(ret) {
                    var matches = [];
                    _.each(ret.items, function(item) {
                        matches.push(item.item.name);
                    })

                    asyncResults(matches);
                }
            })
        }
    },

    render: function() {
        var self = this;

        Iznik.Views.Page.prototype.render.call(this);
        console.log("Resize", window.navigator.userAgent, /Android(?!.*Chrome)|Opera/
            .test(window.navigator.userAgent));

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
            add: function(e, data) {
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
                self.$('.js-uploaded').removeClass('hidden');
                var promises = [];
                self.tagcount = 0;

                _.each(data.result.files, function(file) {
                    // Add thumbnail.
                    var v = new Iznik.Views.User.Pages.Give.Thumbnail({
                        model: new IznikModel({
                            src: file.thumbnailUrl
                        })
                    });

                    self.$('.js-thumbnails').append(v.render().el);

                    // Create attachment object and try to identify this as an object
                    promises.push($.ajax({
                        type: 'PUT',
                        url: API + 'image',
                        data: {
                            identify: true,
                            filename: file.name
                        }, success: function(ret) {
                            if (ret.ret === 0) {
                                _.each(ret.items, function(item) {
                                    self.$('.js-items').tagsinput('add', item.name);
                                    self.tagcount++;
                                });
                            }
                            console.log("Completed PUT");
                        }
                    }));
                });

                $.when.apply($, promises).done(function() {
                    self.pleaseWait.close();
                });
            },
            progressall: function (e, data) {
                self.$('.js-addprompt').addClass('hidden');
                self.$('.js-uploaded').addClass('hidden');
                self.$('.js-uploading').removeClass('hidden');
                var progress = parseInt(data.loaded / data.total * 100, 10);

                console.log("Progress", progress,  self.pleaseWait.$('.js-progress .progress-bar'));
                self.pleaseWait.$('.js-progress .progress-bar').css(
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
