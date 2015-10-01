// We handle zombie views as follows:
// - we spot when they are removed from the DOM by overriding jQuery's cleanData method.
// - we track view creation and record the view in the DOM data
// - when a DOM element is removed (which we catch) we check to see whether it is part of a view (which we can)
// - if so we undelegate events
// - we also clearTimeout on any timer attribute in the view - which is our own convention

(function($){
    var clean = jQuery.cleanData;

    $.cleanData = function(els){
        for(var i = 0, e; (e = els[i]) !== undefined; i++){
            var nearestView = $(e).getView();
            if(nearestView){
                nearestView.undelegateEvents();

                if(nearestView.timer){
                    clearTimeout(nearestView.timer);
                }
            }
        }

        clean(els);
    };

})(jQuery);

(function($){

    var origSetElement = Backbone.View.prototype.setElement;

    var saveView = function($el, view){
        $el.data('saveView', view);
    };

    Backbone.View.prototype.setElement = function(element){
        if(this.el != element){
            $(this.el).getView('zap');
        }
        $(element).getView(this);

        return origSetElement.apply(this, arguments);
    };

    $.expr[':'].findView = function(element, ix, prop, stack){
        return $(element).data('saveView') !== undefined;
    };

    var getTheView = function($el){
        return($el.length ? $el.data('saveView') : null);
    };

    var methods = {

        zap: function($el){
            $el.removeData('saveView');
        }

    };

    $.fn.getView = function(){
        var ret = this;
        var args = Array.prototype.slice.call(arguments, 0);

        if($.isFunction(methods[args[0]])){
            methods[args[0]](this);
        }else if(args[0] && args[0] instanceof Backbone.View){
            saveView(this.first(), args[0]);
        }else{
            ret = getTheView(this.first(), args[0]);
        }

        return ret;
    };

})(jQuery);