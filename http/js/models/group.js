Iznik.Models.Group = IznikModel.extend({
    urlRoot: API + 'group',

    parse: function(ret) {
        return(ret.group);
    }
});
