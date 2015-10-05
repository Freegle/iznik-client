Iznik.Views.ModTools.Yahoo.User = IznikView.extend({
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
        var newVal = this.$('.js-delivery').val();
        this.model.changeDelivery(newVal);
    },

    render: function() {
        var self = this;
        self.$el.html(window.template(self.template)(self.model.toJSON2()));

        self.$('.js-posting').val(self.model.get('postingStatus'));
        self.$('.js-delivery').val(self.model.get('deliveryType'));
        var mom = new moment(self.model.get('date') * 1000);
        self.$('.js-joined').html(mom.format('ll'));

        self.listenToOnce(self.model, 'change:postingStatus change:deliveryType', self.render);

        return(this);
    }
});