define([
    'jquery',
    'underscore',
    'backbone',
    'moment',
    'iznik/base',
    'bootstrap-switch',
    'bootstrap-datepicker'
], function($, _, Backbone, moment, Iznik) {
    Iznik.Views.ModTools.Yahoo.User = Iznik.View.extend({
        template: 'modtools_yahoo_user',

        events: {
            'change .js-posting': 'changePostingStatus',
            'change .js-delivery': 'changeDelivery'
        },

        changePostingStatus: function() {
            var newVal = this.$('.js-posting').val();
            this.model.changePostingStatus(newVal);
        },

        changeDelivery: function() {
            console.log("changeDelivery model", this.model);
            var newVal = this.$('.js-delivery').val();
            this.model.changeDelivery(newVal);
        },

        render: function() {
            var p = Iznik.View.prototype.render.call(this);
            p.then(function(self) {
                self.$('.js-posting').val(self.model.get('postingStatus'));
                self.$('.js-delivery').val(self.model.get('deliveryType'));

                if (self.model.get('date')) {
                    var mom = new moment(self.model.get('date') * 1000);
                    var now = new moment();

                    self.$('.js-joined').html(mom.format('ll'));

                    if (now.diff(mom, 'days') <= 31) {
                        self.$('.js-joined').addClass('error');
                    }
                } else {
                    self.$('.js-joinholder').hide();
                }

                self.listenToOnce(self.model, 'change:postingStatus change:deliveryType', self.render);

                self.$('select').selectpicker();
            });

            return(p);
        }
    });
});