Iznik.Models.ModConfig.StdMessage = IznikModel.extend({
    urlRoot: API + 'stdmsg',

    parse: function(ret) {
        return(ret.stdmsg);
    }
});
