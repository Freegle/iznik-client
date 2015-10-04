Iznik.Views.ModTools.Yahoo.User = IznikView.extend({
    template: 'modtools_yahoo_user',

    events: {
        'change .js-posting': 'changePosting',
        'change .js-deliver': 'changeDelivery'
    },

    changePosting: function() {
        var newVal = this.$('.js-posting').val();
        this.model.set('postingStatus', newVal);
    },

    changeDelivery: function() {
        var newVal = this.$('.js-delivery').val();
        this.model.set('deliveryType', newVal);
    },

    render: function() {
        var self = this;
        console.log("User render");
        self.$el.html(window.template(self.template)(self.model.toJSON2()));

        self.$('.js-posting').val(self.model.get('postingStatus'));
        self.$('.js-delivery').val(self.model.get('deliveryType'));
        var mom = new moment(self.model.get('date') * 1000);
        self.$('.js-joined').html(mom.format('ll'));

        self.listenToOnce(self.model, 'change:postingStatus change:deliveryType', self.render);

        return(this);
    }
});