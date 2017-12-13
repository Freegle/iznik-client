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
            var p = Iznik.Views.Page.prototype.render.call(this);
            p.then(function (self) {
                $.ajax({
                    url: API + 'supporters',
                    success: function (ret) {
                        var html = '';
                        _.each(ret.supporters.Wowzer, function (el, index, list) {
                            if (index == ret.supporters.Wowzer.length - 1) {
                                html += ' and '
                            } else if (index > 0) {
                                html += ', '
                            }

                            html += el.name;
                        });
                        self.$('.js-wowzer').html(html);

                        var html = '';
                        _.each(ret.supporters['Front Page'], function (el, index, list) {
                            if (index == ret.supporters['Front Page'].length - 1) {
                                html += ' and '
                            } else if (index > 0) {
                                html += ', '
                            }

                            html += el.name;
                        });
                        self.$('.js-frontpage').html(html);

                        self.$('.js-content').fadeIn('slow');
                    }
                });
            });
            return p;
        }
    });
    

});