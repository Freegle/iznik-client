Iznik.Views.ModTools.User = IznikView.extend({
    template: 'modtools_user_user',

    render: function() {
        this.$el.html(window.template(this.template)(this.model.toJSON2()));

        var coll = new Iznik.Collections.ModTools.MessageHistory();
        _.each(this.model.get('messagehistory'), function(message, index, list) {
            coll.add(new Iznik.Models.ModTools.User.MessageHistoryEntry(message));
        });

        var v = new Iznik.Views.ModTools.User.History({
            collection: coll
        });
        this.$('.js-messagehistory').html(v.render().el);

        return (this);
    }
});

Iznik.Views.ModTools.User.History = IznikView.extend({
    template: 'modtools_user_history',

    render: function() {
        console.log("Render history");
        this.$el.html(window.template(this.template)());
        this.$('.js-msgcount').html(this.collection.length);

        return(this);
    }
});
