Iznik.Views.ModTools.Pages.Settings = Iznik.Views.Page.extend({
    modtools: true,

    template: "modtools_settings_main",

    settingsGroup: function() {
        var self = this;

        // Because we switch the form based on our group select we need to remove old events to avoid saving new
        // changes to the previous group.
        if (self.myGroupForm) {
            self.myGroupForm.undelegateEvents();
        }
        if (self.groupForm) {
            self.groupForm.undelegateEvents();
        }

        if (self.selected > 0) {
            var group = new Iznik.Models.Group({
                id: self.selected
            });

            group.fetch().then(function() {
                var mysettings = group.get('mysettings');
                console.log("mysettings", mysettings);
                var configoptions = [];
                var configs = Iznik.Session.get('configs');
                configs.each(function(config) {
                    configoptions.push({
                        label: config.get('name'),
                        value: config.get('id')
                    });
                });
                self.myGroupModel = new IznikModel(mysettings);
                self.myGroupFields = [
                    {
                        name: 'configid',
                        label: 'Configuration to use for this Group',
                        control: 'select',
                        options: configoptions
                    },
                    {
                        name: 'showmessages',
                        label: 'Show messages in All Groups?',
                        control: 'radio',
                        extraClasses: [ 'row' ],
                        options: [{label: 'Yes', value: 1}, {label: 'No', value:0 }]
                    },
                    {
                        name: 'showmembers',
                        label: 'Show members in All Groups?',
                        control: 'radio',
                        extraClasses: [ 'row' ],
                        options: [{label: 'Yes', value: 1}, {label: 'No', value:0 }]
                    },
                    {
                        control: 'button',
                        label: 'Save',
                        type: 'submit',
                        extraClasses: [ 'btn-success topspace botspace' ]
                    }
                ];

                self.myGroupForm = new Backform.Form({
                    el: $('#mygroupform'),
                    model: self.myGroupModel,
                    fields: self.myGroupFields,
                    events: {
                        'submit': function(e) {
                            // Send a PATCH to the server for mysettings.
                            e.preventDefault();
                            var newdata = self.myGroupModel.toJSON();
                            console.log("Save mysettings", group, group.isNew(), newdata);
                            group.save({
                                'mysettings': newdata
                            }, { patch: true });
                            return(false);
                        }
                    }
                });

                self.myGroupForm.render();

                self.groupModel = new IznikModel(group.get('settings'));

                self.groupFields = [
                    {
                        name: 'autoapprove.members',
                        label: 'Auto-approve pending members?',
                        control: 'radio',
                        options: [{label: 'Yes', value: 1}, {label: 'No', value:0 }]
                    },
                    {
                        name: 'duplicates.check',
                        label: 'Flag duplicate messages?',
                        control: 'radio',
                        options: [{label: 'Yes', value: 1}, {label: 'No', value:0 }]
                    },
                    {
                        name: 'keywords.offer',
                        label: 'OFFER keyword',
                        control: 'input'
                    },
                    {
                        name: 'keywords.taken',
                        label: 'TAKEN keyword',
                        control: 'input'
                    },
                    {
                        name: 'keywords.wanted',
                        label: 'WANTED keyword',
                        control: 'input'
                    },
                    {
                        name: 'keywords.received',
                        label: 'RECEIVED keyword',
                        control: 'input'
                    },
                    {
                        name: 'duplicates.offer',
                        label: 'OFFER duplicate period',
                        control: 'input',
                        type: 'number'
                    },
                    {
                        name: 'duplicates.taken',
                        label: 'TAKEN duplicate period',
                        control: 'input',
                        type: 'number'
                    },
                    {
                        name: 'duplicates.wanted',
                        label: 'WANTED duplicate period',
                        control: 'input',
                        type: 'number'
                    },
                    {
                        name: 'duplicates.received',
                        label: 'RECEIVED duplicate period',
                        control: 'input',
                        type: 'number'
                    },
                    {
                        name: 'map.zoom',
                        label: 'Default zoom for maps',
                        control: 'input',
                        type: 'number'
                    },
                    {
                        control: 'button',
                        label: 'Save',
                        type: 'submit',
                        extraClasses: [ 'btn-success topspace botspace' ]
                    }
                ];

                self.groupForm = new Backform.Form({
                    el: $('#groupform'),
                    model: self.groupModel,
                    fields: self.groupFields,
                    events: {
                        'submit': function(e) {
                            e.preventDefault();
                            var newdata = self.groupModel.toJSON();
                            console.log("Save settings", group, group.isNew(), newdata);
                            group.save({
                                'settings': newdata
                            }, { patch: true });
                            return(false);
                        }
                    }
                });

                self.groupForm.render();

                // Layout messes up a bit for radio buttons.
                self.groupForm.$(':radio').closest('.form-group').addClass('clearfix');
            });
        }
    },

    render: function() {
        var self = this;

        Iznik.Views.Page.prototype.render.call(this);

        self.groupSelect = new Iznik.Views.Group.Select({
            systemWide: false,
            all: false,
            mod: true,
            choose: true,
            id: 'settingsGroupSelect'
        });

        self.listenTo(self.groupSelect, 'selected', function(selected) {
            self.selected = selected;
            self.settingsGroup();
        });

        // Render after the listen to as they are called during render.
        self.$('.js-groupselect').html(self.groupSelect.render().el);

        // Personal settings
        var me = Iznik.Session.get('me');

        var personalModel = new IznikModel({
            id: me.id,
            displayname: me.displayname,
            fullname: me.fullname
        });

        var personalFields = [
            {
                name: 'displayname',
                label: 'Display Name',
                control: 'input',
                helpMessage: 'This is your name as displayed publicly to other users.'
            },
            {
                name: 'fullname',
                label: 'Full Name',
                control: 'input',
                helpMessage: 'This is your name as recorded privately on the system.'
            },
            {
                control: 'button',
                label: 'Save',
                type: 'submit',
                extraClasses: [ 'btn-success' ]
            }
        ];

        var personalForm = new Backform.Form({
            el: $('#personalform'),
            model: personalModel,
            fields: personalFields,
            events: {
                'submit': function(e) {
                    e.preventDefault();
                    console.log("Save");
                    return(false);
                }
            }
        });

        personalForm.render();
    }
});
