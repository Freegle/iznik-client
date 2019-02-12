// We fetch templates over AJAX, then compile them ready for use.
var loadedTemplates = [];
var $ = require('jquery');
var _ = require('underscore');

function templateStore(tpl, html) {
    //console.log("templateStore:"+tpl);
    html = html.replace(/src=\"\//g, 'src="' + iznikroot)	// CC
    html = html.replace(/src=\'\//g, "src='" + iznikroot)	// CC

    // Make templates less likely to bomb out with an exception if a variable is undefined, by
    // using the internal obj.
    html = html ? html.replace(/\{\{/g, '{{obj.') : null
    html = html.replace(/\{\{obj.obj./g, '{{obj.')
    html = html.replace(/\{\{obj.timeago/g, '{{timeago')
    //console.log("Updated HTML", html)

    // Use a closure to wrap the underscore template so that if we get an error we can log it
    // rather than bomb out of the whole javascript.
    function getClosure(tpl, und) {
        return (function (obj) {
            try {
                // We're assuming here that we have already included moment by the time we execute this, so it
                // completes synchronously.  We have - from the mention below in define.
                var moment = require('moment');
                obj = _.extend(obj, {
                    // We call this timeago function from within templates.  This allows us to insert a formatted time
                    // into the HTML before it's added to the DOM, which is more efficient than adding it and then
                    // manipulating it afterwards.
                    timeago: function(d) {
                        var s = '';
                        var m = (new moment(d));
                        var s = m.fromNow();

                        // Don't allow future times.
                        s = s.indexOf('in ') === 0 ? 'just now': s;

                        return(s);
                    },
                    moment: moment
                });

                html = und(obj);

                // Sanitise to remove script tags
                html = html.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');

                // Add template name into the HTML, which is very useful for debugging.
                html = "<!-- " + tpl + " -->\r\n" + html;
                return html;
            } catch (e) {
                console.error("Template " + tpl + " expansion failed with " + e.message + ", ");
                console.log(obj);
                console.log(html);
                return ('');
            }
        });
    }

    loadedTemplates[tpl] = getClosure(tpl, _.template(html, {
        interpolate: /\{\{(.+?)\}\}/g,
        evaluate: /<%(.+?)%>/g,
        escape: /<%-(.+?)%>/g
    }, {
        // This supposedly improves performance - see https://jsperf.com/underscore-template-function-with-variable-setting
        variable: 'obj'
    }));
}

module.exports = {
    template: function (id) {
        // We should already have loaded the template in fetchTemplate.
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
    },

    templateFetch: function(tpl) {
        //console.log("templateFetch:"+tpl);
        var promise = new Promise(function(resolve, reject) {
            var $ = require('jquery');
            var promise;
            if (tpl in loadedTemplates) {
                resolve(tpl);
            } else {
                const templatePath = tpl.replace(/\_/g, '/') + '.html';
                return import(
                    /* webpackMode: "lazy-once" */
                    '/template/' + templatePath
                ).then(function(html) {
                    templateStore(tpl, html.default);
                    resolve(tpl);
                }).catch(function(err) {
                    console.error("Template fetch failed", tpl, err);
                    reject(err);
                })
            }
        });

        return(promise);
    }
};
