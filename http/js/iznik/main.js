window.Iznik = {
    Models     : {},
    Views      : {
        Pages: {
            ModTools: {}
        },
        Map: {},
        Group: {},
        Message: {},
        Post: {},
        Reply: {},
        Settings: {},
        Events: {},
        MyPosts: {},
        Help: {},
        Alerts: {},
        Reuse: {},
        Plugin: {}
    },
    Collections: {}
};

// Define our own base classes.
var IznikModel = Backbone.Model.extend({
    toJSON2: function() {
        var json;

        if (this.toJSON) {
            json = this.toJSON.call(this);
        } else {
            var str = JSON.stringify(this.attributes);
            json = JSON.parse(str);
        }

        return(json);
    }
});

var IznikCollection = Backbone.Collection.extend({});

// Set options into this.options by default.
var IznikView = (function(View) {
    return View.extend({
        constructor: function(options) {
            this.options = options || {};
            View.apply(this, arguments);
        },

        render: function() {
            var self = this;

            if (self.model) {
                self.$el.html(window.template(self.template)(self.model.toJSON2()));
            } else {
                self.$el.html(window.template(self.template));
            }

            return self;
        }
    });
})(Backbone.View);

Backbone.emulateHTTP = true;
Backbone.emulateJSON = true;

// Ensure we can log.
if (!window.console) {var console = {};}
if (!console.log) {
    console.log = function(str) {
    };
}

if (!console.error) {
    console.error = function(str) {
        window.alert(str);
    };
}

function isMobile(){
    return window.innerWidth < 749;
}

var API = '/api/';