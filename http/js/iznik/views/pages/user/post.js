import 'bootstrap-fileinput/js/plugins/piexif.min.js';
import 'bootstrap-fileinput';

var Bloodhound = require('bloodhound-js');

define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/models/user/message',
    'iznik/views/group/select',
    'iznik/models/user/message',
    'iznik/views/user/schedule',
    'iznik/views/user/message'
], function ($, _, Backbone, Iznik) {
    Iznik.Views.User.Pages.WhatIsIt = Iznik.Views.Page.extend({
        pleaseWait: null,

        suggestions: [],
        uploading: 0,

        events: {
            'click .js-take-photo': 'addPhoto', // CC
            'click .js-next': 'next',
            'change .js-item': 'checkNext',
            'focus .js-item': 'focusItem',
            'change .tt-hint': 'checkNext',
            'keyup .js-description': 'checkNext',
            'change .bootstrap-tagsinput .tt-input': 'checkNext',
            'click .js-speechItem': 'speechItem',
            'click .js-cleardraft': 'clearDraft',
            'click .js-addprompt': 'forceAdd'
        },
      
        //cameraSuccess: function (imageURI, self) {  // CC
        cameraSuccess: function (imageData, self) {  // CC
          console.log("cameraSuccess " + imageData.length);

          // https://stackoverflow.com/questions/36912819/cordova-camera-take-picture-as-blob-object
          var contentType = 'image/jpeg';
          var sliceSize = 512;

          var byteCharacters = atob(imageData);
          var byteArrays = [];

          for (let offset = 0; offset < byteCharacters.length; offset += sliceSize) {
            let slice = byteCharacters.slice(offset, offset + sliceSize);

            let byteNumbers = new Array(slice.length);
            for (let i = 0; i < slice.length; i++) {
              byteNumbers[i] = slice.charCodeAt(i);
            }

            let byteArray = new Uint8Array(byteNumbers);

            byteArrays.push(byteArray);
          }

          self.uploading++;

          var imageBlob = new Blob(byteArrays, { type: contentType });
          self.$('#fileupload').fileinput('addToStack', imageBlob);
          self.$('#fileupload').fileinput('upload');
        },

        cameraError: function (msg, self) {  // CC
          setTimeout(function () {
            if (msg === "No Image Selected") { msg = "No photo taken or chosen"; }
            if (msg === "Camera cancelled") { msg = "No photo taken or chosen"; }
            console.log(msg);
            self.$('.js-photo-msg').text(msg);
            self.$('.js-photo-msg').show();
          }, 0);
        },

        addPhoto: function () {  // CC
          var self = this;
          self.$('.js-photo-msg').hide();

      		var maxDimension = 800; // Connection.UNKNOWN Connection.ETHERNET Connection.WIFI Connection.CELL_4G and Connection.NONE
      		if ((navigator.connection.type === Connection.CELL_2G) ||
            (navigator.connection.type === Connection.CELL_2G) ||
            (navigator.connection.type === Connection.CELL)) {
      		  maxDimension = 400;
      		}

      		navigator.camera.getPicture(function (imageURI) {
      		          self.cameraSuccess(imageURI, self);
      		        }, function (msg) {
      		          self.cameraError(msg, self);
      		        },
                  { quality: 50,
                    destinationType: Camera.DestinationType.DATA_URL,
                    //destinationType: Camera.DestinationType.FILE_URI,
                    sourceType: Camera.PictureSourceType.CAMERA,
                    //allowEdit: true,	// Don't: adds unhelpful crop photo step
                    encodingType: Camera.EncodingType.JPEG,
                    targetWidth: maxDimension,
                    targetHeight: maxDimension,
                    //popoverOptions: CameraPopoverOptions,
                    saveToPhotoAlbum: true,
                    correctOrientation: true
                  }
            );
        },

        focusItem: function() {
            // Scroll to bottom so that any suggestions don't hide the description box.
            $("html, body").animate({ scrollTop: $(document).height() }, "slow");
        },

        forceAdd: function() {
            $('#fileupload').click();
        },

        clearDraft: function() {
            var self = this;
            Storage.remove('draft');
            self.render();
        },

        // Not enabled for iOS as iOS10 has speech recognition built in on any text field.
        // It could be enabled for iOS9 using www.ispeech.org but not done
        speechItem: function() {
            var self = this;
            var recognition = new SpeechRecognition();
            recognition.onresult = function (event) {
              if (event.results.length > 0) {
                self.$('.js-item').val(event.results[0][0].transcript);
                self.speechDescription();
              }
            };
            //self.$('.js-item').focus();
            recognition.start();
            /*require(['iznik/speech'], function () {
              self.$('.js-item').on('result', function (e, str) {
                self.$('.js-item').val(str);
                self.$('.js-description').focus();
                self.speechDescription();
              });

              self.$('.js-item').speech();
            })*/
        },

        speechDescription: function() {
            var self = this;
            var recognition = new SpeechRecognition();
            recognition.onresult = function (event) {
              if (event.results.length > 0) {
                self.$('.js-description').val(event.results[0][0].transcript);
              }
            };
            //self.$('.js-description').focus();
            recognition.start();
            /*require([ 'iznik/speech' ], function() {
                self.$('.js-description').on('result', function(e, str) {
                    self.$('.js-description').val(str);
                });

                self.$('.js-description').speech();
            })*/
        },

        getItem: function () {
            var val = this.$('.js-item').typeahead('val');
            if (!val) {
                val = this.$('.js-item').val();
            }

            // Remove brackets - they'll only confuse us.
            val = val.replace(/\(|\)|\[|\]/g, '');

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
                return(null);
            } else {
                var locationid = null;
                var groupid = null;
                try {
                    var loc = Storage.get('mylocation');
                    locationid = loc ? JSON.parse(loc).id : null;
                    groupid = Storage.get('myhomegroup');
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
                    type: 'POST',
                    headers: {
                        'X-HTTP-Method-Override': 'PUT'
                    },
                    url: API + 'message',
                    data: data,
                    success: function (ret) {
                        if (ret.ret == 0) {
                            d.resolve();
                            try {
                                Storage.set('draft', ret.id);
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
            }
        },

        next: function () {
            var self = this;
            var p = this.save();

            if (p) {
                p.done(function () {
                    Router.navigate(self.whoami, true);
                }).fail(function () {
                    self.$('.js-saveerror').fadeIn('slow');
                });
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
            self.draftPhotos = new Iznik.Views.User.Message.Photos({
                collection: self.photos,
                message: null,
                showAll: true
            });

            var p = Iznik.Views.Page.prototype.render.call(this).then(function () {
                if (typeof SpeechRecognition === 'function') {    // CC
                  self.$('.js-speechItem').show();
                }

                _.delay(_.bind(self.checkNext, self), 300);

                // We use bloodhound for better typeahead function which puts less load on the server.
                self.hound = new Bloodhound({
                    queryTokenizer: Bloodhound.tokenizers.whitespace,
                    datumTokenizer: Bloodhound.tokenizers.whitespace,
                    identify: function(datum) {
                        console.log("Identify", datum);
                    },
                    remote: {
                        url: API + 'item',
                        prepare: function(query, settings) {
                            settings.url = settings.url + '?typeahead=' + encodeURIComponent(query);
                            return(settings);
                        },
                        transform: function(ret) {
                            if (ret.ret === 0) {
                                var trans = [];
                                _.each(ret.items, function(item) {
                                    if (item.hasOwnProperty('item')) {
                                        trans.push(item.item.name);
                                    }
                                })
                                return(trans);
                            }
                        }
                    },
                });

                self.hound.initialize().then(function() {
                    self.typeahead = self.$('.js-item').typeahead({
                        minLength: 2,
                        hint: false,
                        highlight: true,
                        autoselect: false,
                        tabAutocomplete: false,
                    }, {
                        name: 'items',
                        source: self.hound,
                        limit: 3
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
                });

                // File upload
                self.$('#fileupload').fileinput({
                    uploadExtraData: {
                        imgtype: 'Message',
                        identify: true
                    },
                    showUpload: false,
                    allowedFileExtensions: ['jpg', 'jpeg', 'gif', 'png'],
                    uploadUrl: API + 'image',
                    resizeImage: true,
                    maxImageWidth: 800,
                    browseIcon: '<span class="glyphicon glyphicon-plus" />&nbsp;',
                    browseLabel: 'Add photos',
                    browseClass: 'btn btn-success btn-lg nowrap pull-right',
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
                    // Have to defer else break fileinput validation processing.
                    _.defer(function() {
                        self.$('#fileupload').fileinput('upload');

                        // We don't seem to be able to hide this control using the options.
                        self.$('.fileinput-remove').hide();
                    })
                });

                // Watch for all uploaded
              self.$('#fileupload').on('fileuploaded', function (event, data) {
                    console.log("File uploaded",data)
                    // Add the photo to our list
                    var mod = new Iznik.Models.Message.Attachment({
                        id: data.response.id,
                        path: data.response.path,
                        paththumb: data.response.paththumb,
                        mine: true
                    });

                    self.photos.add(mod);

                    // Show the uploaded thumbnail and hackily remove the one provided for us.
                    self.draftPhotos.render().then(function() {
                        self.$('.js-draftphotos').html(self.draftPhotos.el);
                        self.$('.js-draftphotos').show();
                    });

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
                    console.log("File uploaded self.uploading", self.uploading)

                    if (self.uploading == 0) {
                        self.allUploaded();
                    }
                });

                try {
                    var id = Storage.get('draft');

                    if (id) {
                        // We have a draft we were in the middle of.
                        var msg = new Iznik.Models.Message({
                            id: id
                        });

                        msg.fetch().then(function () {
                            // At least, we think it's a draft.  But there's a timing window where we could fail to
                            // clear out our local storage but still have submitted the message.  In that case
                            // we don't want to use it.
                            if (self.msgType == msg.get('type') && msg.get('isdraft')) {
                                self.$('.js-olddraft').fadeIn('slow');

                                // Parse out item from subject.
                                var matches = /(.*?)\:([^)].*)\((.*)\)/.exec(msg.get('subject'));
                                if (matches && matches.length > 2 && matches[2].length > 0) {
                                    self.$('.js-item').typeahead('val', matches[2]);
                                } else {
                                    self.$('.js-item').typeahead('val', msg.get('subject'));
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
                                    if (self.photos.length > 0) {
                                        self.$('.js-draftphotos').html(self.draftPhotos.el);
                                        self.$('.js-draftphotos').show();
                                        self.$('.js-addprompt').hide();
                                    }
                                });
                            }
                        });
                    } else {
                        // Just set up an empty collection of photos.
                        // Add the thumbnails.
                        self.draftPhotos.render().then(function() {
                            self.$('.js-draftphotos').html(self.draftPhotos.el);
                            self.$('.js-draftphotos');
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
                id = Storage.get('draft');
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
                                Storage.set('lastpost', id);
                                Storage.remove('draft');
                            } catch (e) {}

                            if (ret.newuser) {
                                // We didn't know this email and have created a user for them.  Show them an invented
                                // password, and allow them to change it.
                                console.log("Got new password", ret);
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
                Storage.set('myemail', email);
            } catch (e) {
            }

            if (Iznik.isValidEmailAddress(email)) {
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
                            var email = Storage.get('myemail');
                            if (email) {
                                self.$('.js-email').val(email);
                                self.changeEmail();
                            }
                        } catch (e) {
                        }
                    }
                });

                Iznik.Session.testLoggedIn([
                    'me',
                    'emails'
                ]);
            });

            return(p);
        }
    });
});
