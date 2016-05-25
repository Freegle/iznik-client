define([
    'jquery',
    'underscore',
    'backform' // ...which needs to call _ before we mess with template format
], function($, _) {
    window.template = function (id) {
        // We should already have required the template in fetchTemplate.
        try {
            if (id in loadedTemplates) {
                var html = loadedTemplates[id];

                // Make templates less likely to bomb out with an exception if a variable is undefined, by
                // using the internal obj.
                html = html ? html.replace(/\{\{/g, '{{obj.') : null;
                html = html.replace(/\{\{obj.obj./g, '{{obj.');
                //console.log("Updated HTML", html);

                // Use a closure to wrap the underscore template so that if we get an error we can log it
                // rather than bomb out of the whole javascript.
                function getClosure(id, tpl) {
                    return (function (html) {
                        try {
                            html = tpl(html);

                            // Sanitise to remove script tags
                            html = html.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');
                            return html;
                        } catch (e) {
                            console.error("Template " + id + " expansion failed with " + e.message + ", ");
                            console.log(this);
                            console.log(html);
                            return ('');
                        }
                    });
                }

                return getClosure(id, _.template(html, {
                    interpolate: /\{\{(.+?)\}\}/g,
                    evaluate: /<%(.+?)%>/g,
                    escape: /\{\{-(.+?)\}\}/g
                }));
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
