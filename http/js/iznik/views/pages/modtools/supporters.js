define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/views/pages/pages',
    'iznik/views/pages/user/pages'
], function ($, _, Backbone, Iznik) { // CC
        Iznik.Views.ModTools.Pages.Supporters = Iznik.Views.Page.extend({
        modtools: true,
    
        template: "layout_supporters",

        render: function () {
            console.log("supporters render");
            return Iznik.Views.Page.prototype.render.call(this);
        }
    });
    

});