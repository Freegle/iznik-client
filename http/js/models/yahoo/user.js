Iznik.Models.Yahoo.User = IznikModel.extend({
    initialize: function() {
        this.bind('change:deliveryType', this.changeDelivery);
        this.bind('change:postingStatus', this.changePostingStatus);
    },

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

        // We set up our listens for changes now, which avoids them firing during the fetch
    },

    changeAttr: function(attr, model, val) {
        var self = this;
        console.log("change", attr, model.previous(attr), val);
        if (!_.isUndefined(model.previous(attr))) {
            // Not the initial fetch.
            function getCrumb(ret) {
                var match = /GROUPS.YG_CRUMB = "(.*)"/.exec(ret);

                if (match) {
                    // Got a crumb.
                    self.crumb = match[1];

                    var members = [
                        {}
                    ];
                    members[0]["userId"] = self.get('userId');
                    members[0][attr] = val;

                    new majax({
                        type: "PUT",
                        url: YAHOOAPI + 'groups/' + self.get('group') + "/members?gapi_crumb=" + self.crumb,
                        data: {
                            members: JSON.stringify(members)
                        }, complete: function () {
                            // Fetch it.  That's an easy way of checking whether it worked; if not
                            // then the value will be different, triggering a change event and hence a
                            // a re-render of the view.
                            console.log("After before fetch", self.toJSON2());
                            self.fetch().then(function() {
                                console.log("After fetch", self.toJSON2())
                            });
                        }
                    });
                } else {
                    var match = /window.location.href = "(.*)"/.exec(ret);

                    if (match) {
                        var url = match[1];
                        $.ajax({
                            type: "GET",
                            url: url,
                            success: getCrumb,
                            error: function (request, status, error) {
                                // Couldn't get a crumb. Reset to old value.  This will trigger a change event and
                                // hance a re-render of any relevant view.
                                self.set(attr, self.previous(attr));
                            }
                        });
                    }
                }
            }

            $.ajax({
                type: "GET",
                url: "https://groups.yahoo.com/neo/groups/" + self.get('group') + "/management/members",
                success: getCrumb,
                error: function (request, status, error) {
                    // Couldn't get a crumb. Reset to old value.  This will trigger a change event and
                    // hance a re-render of any relevant view.
                    self.set(attr, self.previous(attr));
                }
            });
        }
    },

    changeDelivery: function(model, val) {
        this.changeAttr('deliveryType', model, val);
    },

    changePostingStatus: function(model, val) {
        this.changeAttr('postingStatus', model, val);
    }
});