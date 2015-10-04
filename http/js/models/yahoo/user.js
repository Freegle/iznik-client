Iznik.Models.Yahoo.User = IznikModel.extend({
    url: function() {
        var url = YAHOOAPI + "search/groups/" + this.get('group') +
                "/members?memberType=CONFIRMED&start=1&count=1&sortBy=name&sortOrder=asc&query=" +
                this.get('email') + "&chrome=raw";
        return(url);
    },

    parse: function(ret, options) {
        if (ret.hasOwnProperty('ygData') &&
                ret.ygData.hasOwnProperty('members') &&
                ret.ygData.members.length == 1) {
            return(ret.ygData.members[0]);
        }
    }
});