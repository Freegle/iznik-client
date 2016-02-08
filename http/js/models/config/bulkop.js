Iznik.Models.ModConfig.BulkOp = IznikModel.extend({
    urlRoot: API + 'bulkop',

    parse: function(ret) {
        return(ret.bulkop);
    }
});
