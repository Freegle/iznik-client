Iznik.Views.ModTools.Message.Photo = IznikView.extend({
    tagName: 'li',

    template: 'modtools_message_photo',

    events: {
        'click .js-img': 'click'
    },

    click: function(e) {
        e.preventDefault();
        e.stopPropagation();

        var v = new Iznik.Views.Modal({
            model: this.model
        });

        v.open('modtools_message_photozoom');
    }
});