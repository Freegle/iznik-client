define([
    'jquery',
    'underscore',
    'backform' // ...which needs to call _ before we mess with template format
], function($, _) {
    window.template = function (id) {
        // We should already have required the template in fetchTemplate.
        try {
            if (id in loadedTemplates) {
                return(loadedTemplates[id]);
            } else {
                console.error("Template not loaded", id);
            }
        } catch (e) {
            console.error("Template " + id + " failed with " + e.message);
            return null;
        }
    };

    return(_);
});
