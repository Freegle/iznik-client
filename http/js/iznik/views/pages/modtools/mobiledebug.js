define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/views/pages/pages',
    'iznik/views/pages/user/pages'
], function ($, _, Backbone, Iznik) { // CC
        Iznik.Views.ModTools.Pages.MobileDebug = Iznik.Views.Page.extend({
        modtools: true,
    
        template: "modtools_settings_mobiledebug",

        render: function () {
            var p = Iznik.Views.Page.prototype.render.call(this).then(function () {

                $('#js-mobilelog').val(alllog);

                require(['iznik/views/plugin'], function() {
                    window.IznikPlugin = new Iznik.Views.Plugin.Main();
                    IznikPlugin.render().then(function(v) {
                        $("#mobile_debug_work").html(v.el);
                    })
                });
            });
            return p;
        }
    });
    

});