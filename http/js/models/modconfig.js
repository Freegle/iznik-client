Iznik.Models.ModConfig = IznikModel.extend({
    urlRoot: API + 'modconfig',

    parse: function(ret) {
        return(ret.config);
    }
});
