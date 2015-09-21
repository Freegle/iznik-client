Iznik.Views.Plugin.Main = IznikView.extend({
    render: function() {
        window.setTimeout(this.checkPluginStatus, 3000);
    },

    checkPluginStatus: function() {
        var self = this;

        // Check if we are connected to Yahoo by issuing an API call.
        new majax({
            type: 'GET',
            url: 'https://groups.yahoo.com/api/v1/user/groups/all',
            success: function(ret) {
                console.log("Check plugin status", ret);
                if (ret.hasOwnProperty('ygData') && ret.ygData.hasOwnProperty('allMyGroups')) {
                    $('#js-plugindisconnected').fadeOut('slow', function() {
                        $('#js-pluginconnected').fadeIn('slow');
                    })
                } else {
                    $('#js-pluginconnected').fadeOut('slow', function() {
                        $('#js-plugindisconnected').fadeIn('slow');
                    })
                }
            },
            complete: function() {
                window.setTimeout(self.checkPluginStatus, 30000);
            }
        })
    }
});

Iznik.Views.Plugin.Info = IznikView.extend({
    className: "panel panel-default js-plugin",

    template: "layout_plugin"
});