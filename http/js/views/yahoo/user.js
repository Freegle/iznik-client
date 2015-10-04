Iznik.Views.ModTools.Yahoo.User = IznikView.extend({
    template: 'modtools_yahoo_user',

    render: function() {
        var self = this;

        self.$el.html(window.template(self.template)(self.model.toJSON2()));

        self.$('.js-posting').val(self.model.get('postingStatus'));
        self.$('.js-delivery').val(self.model.get('deliveryType'));
        var mom = new moment(self.model.get('date') * 1000);
        self.$('.js-joined').html(mom.format('ll'));

        return(this);
    }
});