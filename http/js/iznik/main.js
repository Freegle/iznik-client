window.Iznik = {
    Models     : {
        ModTools: {},
        Yahoo: {}
    },
    Views      : {
        ModTools: {
            Pages: {},
            Message: {},
            Member: {},
            StdMessage: {
                Pending: {},
                Approved: {},
                PendingMember: {},
                ApprovedMember: {}
            },
            Settings: {},
            User: {},
            Yahoo: {}
        },
        Plugin: {
            Yahoo: {}
        },
        User: {
            Pages: {}
        },
        Group: {},
        Help: {}
    },
    Collections: {
        Messages: {},
        Members: {},
        ModTools: {},
        Yahoo: {}
    }
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

var IznikCollection = Backbone.Collection.extend({
    model: IznikModel
});

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
        },

        destroyIt: function() {
            this.undelegateEvents();
            this.$el.removeData().unbind();
            this.remove();
            Backbone.View.prototype.remove.call(this);
        }
    });
})(Backbone.View);

//Backbone.emulateHTTP = true;
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
var YAHOOAPI= 'https://groups.yahoo.com/api/v1/';