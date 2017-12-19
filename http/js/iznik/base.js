// This sets up the basic structure we need before we can do anything.
//
// If you add a standard jQuery plugin in here which is not AMD-compatible, then it also needs to go in
// requirejs-setup as a shim.

define([
    'jquery',
    'backbone',
    'underscore',
    'moment',
    'backbone.collectionView',
    'waypoints',
    'dateshim',
    'bootstrap',
    'bootstrap-select',
    'bootstrap-switch',
    'es6-promise',
    'text',
    'twemoji.min',
    'iznik/diff',
    'iznik/events',
    'iznik/timeago',
    'iznik/majax'
], function ($, Backbone, _, moment) {
    // Promise polyfill for older browsers or IE11 which has less excuse.
    if (typeof window.Promise !== 'function') {
        require('es6-promise').polyfill();
    }

    var Iznik = {
        Models: {
            Activity: {},
            ModTools: {},
            Yahoo: {},
            Plugin: {},
            Message: {},
            Chat: {},
            User: {}
        },
        Views: {
            Activity: {},
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
                Pages: {
                    Find: {},
                    Give: {},
                    Landing: {}
                },
                Home: {},
                Message: {},
                Settings: {}
            },
            Group: {},
            Chat: {},
            Help: {}
        },
        Collections: {
            Activity: {},
            Messages: {},
            Members: {},
            ModTools: {},
            Chat: {},
            Yahoo: {},
            User: {}
        }
    };

    // Add utility functions are then shimmed by webpack config to global or window functions.
    const iznikUtilityShims = {
        globalFunctions: [
            'haversineDistance',
            'resolvedPromise',
            'getURLParam',
            'strip_tags',
            'ABTestShown',
            'ABTestAction',
            'ABTestGetVariant',
            'nullFn',
            'twem',
            'setTitleCounts',
            'ellipsical',
            'formatDuration',
            'getBoundsZoomLevel'
        ],
        windowFunctions: [
            'innerWidth',
            'innerHeight',
            'isVeryShort',
            'innerHeight',
            'canonSubj',
            'setURLParam',
            'removeURLParam',
            'getDistanceFromLatLonInKm',
            'deg2rad',
            'decodeEntities',
            'encodeHTMLEntities',
            'orderedMessages',
            'csvWriter',
            'presdef',
            'chunkArray',
            'base64url',
            'isValidEmailAddress',
            'wbr'
        ]
    }

    // Various utility functions.
    // TODO Poor to have these at the top level of the Iznik object - would be better to put them in a module,
    // but lots of code changes.
    Iznik.resolvedPromise = function(self) {
        // Return a resolved promise as there is nothing to do.
        var p = new Promise(function(resolve, reject) {
            resolve(self);
        });
        return(p);
    }

    Iznik.console = {
        log: function (str) {},
        trace: function() {},
        error: function (str) {}
    }

    Iznik.isXS = function() {
        return window.innerWidth < 320;
    };

    Iznik.isSM = function() {
        return window.innerWidth < 768;
    };

    Iznik.isShort = function() {
        return window.innerHeight < 900;
    };

    Iznik.isVeryShort = function() {
        return window.innerHeight <= 300;
    };

    Iznik.canonSubj = function(subj) {
        subj = subj.toLocaleLowerCase();

        // Remove any group tag
        subj = subj.replace(/^\[.*?\](.*)/, "$1");

        // Remove duplicate spaces
        subj = subj.replace(/\s+/g, ' ');

        subj = subj.trim();

        return (subj);
    };

    Iznik.setURLParam = function(uri, key, value) {
        console.log("Set url param", uri, key, value);
        var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
        var separator = uri.indexOf('?') !== -1 ? "&" : "?";
        if (uri.match(re)) {
            return uri.replace(re, '$1' + key + "=" + value + '$2');
        }
        else {
            return uri + separator + key + "=" + value;
        }
    };

    Iznik.removeURLParam = function(uri, key) {
        var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
        if (uri.match(re)) {
            console.log("Found param");
            return uri.replace(re, '$1$2');
        }
        else {
            return uri;
        }
    };

    Iznik.getDistanceFromLatLonInKm = function(lat1, lon1, lat2, lon2) {
        var R = 6371; // Radius of the earth in km
        var dLat = deg2rad(lat2 - lat1);  // deg2rad below
        var dLon = deg2rad(lon2 - lon1);
        var a =
            Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) *
            Math.sin(dLon / 2) * Math.sin(dLon / 2)
        ;
        var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return (R * c);
    };

    Iznik.deg2rad = function(deg) {
        return deg * (Math.PI / 180)
    };

    Iznik.decodeEntities = (function () {
        // this prevents any overhead from creating the object each time
        var element = document.createElement('div');

        function decodeHTMLEntities(str) {
            if (str && typeof str === 'string') {
                // strip script/html tags
                str = str.replace(/<script[^>]*>([\S\s]*?)<\/script>/gmi, '');
                str = str.replace(/<\/?\w(?:[^"'>]|"[^"]*"|'[^']*')*>/gmi, '');
                element.innerHTML = str;
                str = element.textContent;
                element.textContent = '';
            }

            return str;
        }

        return decodeHTMLEntities;
    })();

    Iznik.encodeHTMLEntities = function(str) {
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    };

// Apply a custom order to a set of messages
    Iznik.orderedMessages = function(stdmsgs, order) {
        var sortmsgs = [];
        if (!_.isUndefined(order)) {
            order = JSON.parse(order);
            _.each(order, function (id) {
                var stdmsg = null;
                _.each(stdmsgs, function (thisone) {
                    if (thisone.id == id) {
                        stdmsg = thisone;
                    }
                });

                if (stdmsg) {
                    sortmsgs.push(stdmsg);
                    stdmsgs = _.without(stdmsgs, stdmsg);
                }
            });
        }

        sortmsgs = $.merge(sortmsgs, stdmsgs);
        return (sortmsgs);
    };

    /**
     * Class for creating csv strings
     * Handles multiple data types
     * Objects are cast to Strings
     **/

    Iznik.csvWriter = function(del, enc) {
        this.del = del || ','; // CSV Delimiter
        this.enc = enc || '"'; // CSV Enclosure

        // Convert Object to CSV column
        this.escapeCol = function (col) {
            if (isNaN(col)) {
                // is not boolean or numeric
                if (!col) {
                    // is null or undefined
                    col = '';
                } else {
                    // is string or object
                    col = String(col);
                    if (col.length > 0) {
                        // use regex to test for del, enc, \r or \n
                        // if(new RegExp( '[' + this.del + this.enc + '\r\n]' ).test(col)) {

                        // escape inline enclosure
                        col = col.split(this.enc).join(this.enc + this.enc);

                        // wrap with enclosure
                        col = this.enc + col + this.enc;
                    }
                }
            }
            return col;
        };

        // Convert an Array of columns into an escaped CSV row
        this.arrayToRow = function (arr) {
            var arr2 = arr.slice(0);

            var i, ii = arr2.length;
            for (i = 0; i < ii; i++) {
                arr2[i] = this.escapeCol(arr2[i]);
            }
            return arr2.join(this.del);
        };

        // Convert a two-dimensional Array into an escaped multi-row CSV
        this.arrayToCSV = function (arr) {
            var arr2 = arr.slice(0);

            var i, ii = arr2.length;
            for (i = 0; i < ii; i++) {
                arr2[i] = this.arrayToRow(arr2[i]);
            }
            return arr2.join("\r\n");
        };
    };

    Iznik.presdef = function (key, obj, def) {
        var ret = obj && obj.hasOwnProperty(key) ? obj[key] : def;
        return (ret);
    };

    Iznik.chunkArray = function(array, size) {
        var start = array.byteOffset || 0;
        array = array.buffer || array;
        var index = 0;
        var result = [];
        while (index + size <= array.byteLength) {
            result.push(new Uint8Array(array, start + index, size));
            index += size;
        }
        if (index <= array.byteLength) {
            result.push(new Uint8Array(array, start + index));
        }
        return result;
    };

    Iznik.base64url = {
        _strmap: 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_',
        encode: function encode(data) {
            data = new Uint8Array(data);
            var len = Math.ceil(data.length * 4 / 3);
            return chunkArray(data, 3).map(function (chunk) {
                return [chunk[0] >>> 2, (chunk[0] & 0x3) << 4 | chunk[1] >>> 4, (chunk[1] & 0xf) << 2 | chunk[2] >>> 6, chunk[2] & 0x3f].map(function (v) {
                    return base64url._strmap[v];
                }).join('');
            }).join('').slice(0, len);
        },
        _lookup: function _lookup(s, i) {
            return base64url._strmap.indexOf(s.charAt(i));
        },
        decode: function decode(str) {
            var v = new Uint8Array(Math.floor(str.length * 3 / 4));
            var vi = 0;
            for (var si = 0; si < str.length;) {
                var w = base64url._lookup(str, si++);
                var x = base64url._lookup(str, si++);
                var y = base64url._lookup(str, si++);
                var z = base64url._lookup(str, si++);
                v[vi++] = w << 2 | x >>> 4;
                v[vi++] = x << 4 | y >>> 2;
                v[vi++] = y << 6 | z;
            }
            return v;
        }
    };

    Iznik.isValidEmailAddress = function(emailAddress) {
        var pattern = /^([a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+(\.[a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+)*|"((([ \t]*\r\n)?[ \t]+)?([\x01-\x08\x0b\x0c\x0e-\x1f\x7f\x21\x23-\x5b\x5d-\x7e\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|\\[\x01-\x09\x0b\x0c\x0d-\x7f\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))*(([ \t]*\r\n)?[ \t]+)?")@(([a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.)+([a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.?$/i;
        return pattern.test(emailAddress);
    };

    Iznik.wbr = function(str, num) {
        var re = RegExp("([^\\s]{" + num + "})(\\w)", "g");
        return str.replace(re, function(all,text,char){
            return text + "<wbr>" + char;
        });
    };

    $.fn.isOnScreen = function(){

        var win = $(window);

        var viewport = {
            top : win.scrollTop(),
            left : win.scrollLeft()
        };
        viewport.right = viewport.left + win.width();
        viewport.bottom = viewport.top + win.height();

        var bounds = this.offset();
        bounds.right = bounds.left + this.outerWidth();
        bounds.bottom = bounds.top + this.outerHeight();

        return (!(viewport.right < bounds.left || viewport.left > bounds.right || viewport.bottom < bounds.top || viewport.top > bounds.bottom));
    };

    Iznik.haversineDistance = function(coords1, coords2, isMiles) {
        function toRad(x) {
            return x * Math.PI / 180;
        }

        var lon1 = coords1[0];
        var lat1 = coords1[1];

        var lon2 = coords2[0];
        var lat2 = coords2[1];

        var R = 6371; // km

        var x1 = lat2 - lat1;
        var dLat = toRad(x1);
        var x2 = lon2 - lon1;
        var dLon = toRad(x2)
        var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
            Math.sin(dLon / 2) * Math.sin(dLon / 2);
        var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        var d = R * c;

        if(isMiles) d /= 1.60934;

        return d;
    }

    Iznik.getURLParam = function(name) {
        var url = location.search.replace(/\&amp;/g, '&');
        name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
        var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
            results = regex.exec(url);
        return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
    }

    Iznik.strip_tags = function(input, allowed) { // eslint-disable-line camelcase
        //  discuss at: http://locutus.io/php/strip_tags/
        // original by: Kevin van Zonneveld (http://kvz.io)
        // improved by: Luke Godfrey
        // improved by: Kevin van Zonneveld (http://kvz.io)
        //    input by: Pul
        //    input by: Alex
        //    input by: Marc Palau
        //    input by: Brett Zamir (http://brett-zamir.me)
        //    input by: Bobby Drake
        //    input by: Evertjan Garretsen
        // bugfixed by: Kevin van Zonneveld (http://kvz.io)
        // bugfixed by: Onno Marsman (https://twitter.com/onnomarsman)
        // bugfixed by: Kevin van Zonneveld (http://kvz.io)
        // bugfixed by: Kevin van Zonneveld (http://kvz.io)
        // bugfixed by: Eric Nagel
        // bugfixed by: Kevin van Zonneveld (http://kvz.io)
        // bugfixed by: Tomasz Wesolowski
        //  revised by: Rafał Kukawski (http://blog.kukawski.pl)
        //   example 1: strip_tags('<p>Kevin</p> <br /><b>van</b> <i>Zonneveld</i>', '<i><b>')
        //   returns 1: 'Kevin <b>van</b> <i>Zonneveld</i>'
        //   example 2: strip_tags('<p>Kevin <img src="someimage.png" onmouseover="someFunction()">van <i>Zonneveld</i></p>', '<p>')
        //   returns 2: '<p>Kevin van Zonneveld</p>'
        //   example 3: strip_tags("<a href='http://kvz.io'>Kevin van Zonneveld</a>", "<a>")
        //   returns 3: "<a href='http://kvz.io'>Kevin van Zonneveld</a>"
        //   example 4: strip_tags('1 < 5 5 > 1')
        //   returns 4: '1 < 5 5 > 1'
        //   example 5: strip_tags('1 <br/> 1')
        //   returns 5: '1  1'
        //   example 6: strip_tags('1 <br/> 1', '<br>')
        //   returns 6: '1 <br/> 1'
        //   example 7: strip_tags('1 <br/> 1', '<br><br/>')
        //   returns 7: '1 <br/> 1'

        // making sure the allowed arg is a string containing only tags in lowercase (<a><b><c>)
        allowed = (((allowed || '') + '').toLowerCase().match(/<[a-z][a-z0-9]*>/g) || []).join('')

        var tags = /<\/?([a-z][a-z0-9]*)\b[^>]*>/gi
        var commentsAndPhpTags = /<!--[\s\S]*?-->|<\?(?:php)?[\s\S]*?\?>/gi

        return input.replace(commentsAndPhpTags, '').replace(tags, function ($0, $1) {
            return allowed.indexOf('<' + $1.toLowerCase() + '>') > -1 ? $0 : ''
        })
    }

    Iznik.ABTestShown = function(uid, variant) {
        $.ajax({
            url: API + 'abtest',
            type: 'POST',
            data: {
                uid: uid,
                variant: variant,
                shown: true
            }
        });
    }

    Iznik.ABTestAction = function(uid, variant) {
        $.ajax({
            url: API + 'abtest',
            type: 'POST',
            data: {
                uid: uid,
                variant: variant,
                action: true
            }
        });
    }

    Iznik.ABTestGetVariant = function(uid, cb) {
        var p = $.ajax({
            url: API + 'abtest',
            type: 'GET',
            data: {
                uid: uid,
            }, success: function(ret) {
                if (ret.ret === 0) {
                    cb(ret.variant);
                }
            }
        });

        return(p);
    }

    Iznik.nullFn = function() {}

    Iznik.twem = function(msg) {
        if (msg) {
            msg = msg.replace(/\\\\u(.*?)\\\\u/g, function(match, contents, offset, s) {
                var s = contents.split('-');
                var ret = '';
                _.each(s, function(t) {
                    ret += twemoji.convert.fromCodePoint(t);
                });

                return(ret);
            });
        }

        return(msg);
    }

    var chatTitleCount = 0;
    var newsfeedTitleCount = 0;

    Iznik.setTitleCounts = function(chat, newsfeed) {
        if (chat !== null) {
            chatTitleCount = chat;
        }

        if (newsfeed !== null) {
            newsfeedTitleCount = newsfeed;
        }

        var unseen = chatTitleCount + newsfeedTitleCount;

        // We'll adjust the count in the window title.
        var title = document.title;
        var match = /\(.*\) (.*)/.exec(title);
        title = match ? match[1] : title;

        if (unseen) {
            document.title = '(' + unseen + ') ' + title;
        } else {
            document.title = title;
        }
    }

    Iznik.ellipsical = function(str, len) {
        if (str.length - 3 > len) {
            str = str.substring(0, len - 3) + '...';
        }

        return(str);
    }

    Iznik.formatDuration = function(secs) {
        var ret;

        if (secs < 60) {
            ret = Math.round(secs) + ' second';
        } else if (secs < 60 * 60) {
            ret = Math.round(secs / 60) + ' minute';
        } else if (secs < 24 * 60 * 60) {
            ret = Math.round(secs / 60 / 60) + ' hour';
        } else {
            ret = Math.round(secs / 60 / 60 / 24) + ' day';
        }

        if (ret.indexOf('1 ') != 0) {
            ret += 's';
        }

        return(ret);
    }

    /**
     * Return an object with the selection range or cursor position (if both have the same value)
     * @param {DOMElement} el A dom element of a textarea or input text.
     * @return {Object} reference Object with 2 properties (start and end) with the identifier of the location of the cursor and selected text.
     **/
    Iznik.getInputSelection = function(el) {
        var start = 0, end = 0, normalizedValue, range, textInputRange, len, endRange;

        if (typeof el.selectionStart == "number" && typeof el.selectionEnd == "number") {
            start = el.selectionStart;
            end = el.selectionEnd;
        } else {
            range = document.selection.createRange();

            if (range && range.parentElement() == el) {
                len = el.value.length;
                normalizedValue = el.value.replace(/\r\n/g, "\n");

                // Create a working TextRange that lives only in the input
                textInputRange = el.createTextRange();
                textInputRange.moveToBookmark(range.getBookmark());

                // Check if the start and end of the selection are at the very end
                // of the input, since moveStart/moveEnd doesn't return what we want
                // in those cases
                endRange = el.createTextRange();
                endRange.collapse(false);

                if (textInputRange.compareEndPoints("StartToEnd", endRange) > -1) {
                    start = end = len;
                } else {
                    start = -textInputRange.moveStart("character", -len);
                    start += normalizedValue.slice(0, start).split("\n").length - 1;

                    if (textInputRange.compareEndPoints("EndToEnd", endRange) > -1) {
                        end = len;
                    } else {
                        end = -textInputRange.moveEnd("character", -len);
                        end += normalizedValue.slice(0, end).split("\n").length - 1;
                    }
                }
            }
        }

        return {
            start: start,
            end: end
        };
    }

    Iznik.getBoundsZoomLevel = function(bounds, mapDim) {
        var WORLD_DIM = { height: 256, width: 256 };
        var ZOOM_MAX = 21;

        function latRad(lat) {
            var sin = Math.sin(lat * Math.PI / 180);
            var radX2 = Math.log((1 + sin) / (1 - sin)) / 2;
            return Math.max(Math.min(radX2, Math.PI), -Math.PI) / 2;
        }

        function zoom(mapPx, worldPx, fraction) {
            return Math.floor(Math.log(mapPx / worldPx / fraction) / Math.LN2);
        }

        var ne = bounds.getNorthEast();
        var sw = bounds.getSouthWest();

        var latFraction = (latRad(ne.lat()) - latRad(sw.lat())) / Math.PI;

        var lngDiff = ne.lng() - sw.lng();
        var lngFraction = ((lngDiff < 0) ? (lngDiff + 360) : lngDiff) / 360;

        var latZoom = zoom(mapDim.height, WORLD_DIM.height, latFraction);
        var lngZoom = zoom(mapDim.width, WORLD_DIM.width, lngFraction);

        return Math.min(latZoom, lngZoom, ZOOM_MAX);
    }

    Iznik.setMeta = function(title, description, image) {
        // We set meta tags for social preview.  You might think that this will have no effect
        // since we're running on the client, but it will be picked up by the cron prerender
        // and hence served up to crawlers.
        //
        // First remove the old ones.
        $('meta[itemprop=title]').remove();
        $('meta[name=description]').remove();
        $('meta[property="og:title"]').remove();
        $('meta[property="og:description"]').remove();
        $('meta[property="og:image"]').remove();

        title = title ? title : SITE_NAME;
        description = description ? description : SITE_DESCRIPTION;
        image = image ? image : (USER_SITE + '/images/user_logo.png');

        $('head').append('<meta itemprop="title" />');
        $('meta[itemprop=title]').attr('content', title);
        $('head').append('<meta property="og:title" />');
        $('meta[itemprop="og:title"]').attr('content, title');

        $('head').append('<meta name="description" />');
        $('meta[name=description]').attr('content', description);
        $('head').append('<meta property="og:description" />');
        $('meta[property="og:description"]').attr('content', description);

        $('head').append('<meta property="og:image" />');
        $('meta[property="og:image"]').attr('content', image);
    }

    function cacheKey(url, data) {
        // Get a unique key for this URL and data.  The data is important because it is passed to the AJAX call and
        // can therefore return different data.
        return("cache." + encodeURIComponent(url) + "." + encodeURIComponent(JSON.stringify(data)));
    }

    Iznik.Model = Backbone.Model.extend({
        toJSON2: function () {
            var json;

            if (this.toJSON) {
                json = this.toJSON.call(this);
            } else {
                var str = JSON.stringify(this.attributes);
                json = JSON.parse(str);
            }

            return (json);
        }
        // , fetch: function (options) {
        //     var self = this;
        //     console.log("Fetch model ", self.get('id')); console.trace();
        //     return Backbone.Model.prototype.fetch.call(self);
        // }
    });

    // We have the ability to cache in storage.  This is controlled by several optional parameters.
    //
    // For now we only cache collection fetches because model fetches are unlikely to be on page load, which is
    // what we're interested in optimising.
    //
    // cached is a callback which will be invoked if we can satisfy a request from cache.  Default to no caching.
    // cacheExpiry is the lifetime in seconds of the cache entry corresponding to this fetch.  Default 48 hours.  We
    //   always call cached with expired data because it looks better for the user to see the screen populate and then
    //   update than it does to see a blank screen.
    // cacheOnly indicates whether to bother doing a fetch at all if we managed to use a cached version.  Default false.
    // cacheFetchAfter is a delay in seconds before issuing any fetch after successfully finding it in the cache.
    //   This can be useful for page load - if we manage to populate the page with cached data then we can refresh
    //   it later when things have quietened down, which makes the page feel more responsive to users while keeping
    //   the data roughly up to date.  Default to 3-10 seconds with some randomness, which means it will usually
    //   happen after the page has rendered.
    Iznik.Collection = Backbone.Collection.extend({
        model: Iznik.Model,

        promise: null,

        constructor: function (options) {
            this.options = options || {};
            Backbone.Collection.apply(this, arguments);
        }, fetch: function(options) {
            var self = this;
            var issueFetch = true;
            var fetchDelay = 0;
            var url = typeof self.url == 'string' ? self.url : self.url();

            if (options && options.cached) {
                // We would like a cached fetch.
                var key = cacheKey(url, options.data);
                // console.log("Fetch key", url, key);

                try {
                    var cached = Storage.get(key);
                    // console.log("Cache get returned", cached ? cached.length : null);
                    var expires = Storage.get(key + '.time');
                    // console.log("Expires", expires);

                    if (cached && expires) {
                        // We have some cached data.  Put it into the collection.
                        // console.log("Got cached data");
                        var data = JSON.parse(cached);
                        self.set(data);
                        // console.log("Collection after set", self);

                        // Now invoke our callback to show we've completed.
                        options.cached();

                        var now = (new Date()).getTime();
                        var age = now - expires;
                        var expiry = options.hasOwnProperty('cacheExpiry') ? options.cacheExpiry : 60 * 60 * 48;
                        // console.log("Compare expire", age, expiry);

                        // We want to fetch if our cache has expired, or if it is valid but we don't just want the
                        // cached value.
                        issueFetch = age >= expiry || !options.cacheOnly;

                        if (issueFetch && age >= expiry) {
                            // Our entry has expired and we are going to get a new one.  It's possible that this
                            // might fail due to quota issues.  Zap our old one to avoid always showing data
                            // that is too old.
                            try {
                                Storage.remove(key);
                                Storage.remove(key + '.time');
                            } catch (e) {}
                        }

                        // We might want to delay it.
                        fetchDelay = options.hasOwnProperty('cacheFetchAfter') ? (options.cacheFetchAfter * 1000) :
                            (3000 + Math.floor(Math.random() * 7000));
                    }
                } catch (e) {console.error(e.message);}

                // console.log("Cached collection fetch", url, issueFetch, fetchDelay); console.trace();
            }

            if (issueFetch) {
                // Use our own promise so that we can get the data if we need to.
                self.promise = new Promise(function(resolve, reject) {
                    // We don't have a cached value.  Fetch it.
                    function issueFetch() {
                        // console.log("Issue fetch", options);
                        Backbone.Collection.prototype.fetch.call(self, options).then(function() {
                            // TODO Error handling?
                            if (options && options.cached) {
                                // We have fetched it - save it in our cache (before the caller can mess with it).
                                // console.log("Fetched, save it", url);
                                try {
                                    var key = cacheKey(url, options.data);
                                    var data = JSON.stringify(self.toJSON());

                                    // CC   if (Persist.size == -1 || data.length < Persist.size) { // CC
                                        // Don't cache stuff that's too big.
                                        try {
                                            Storage.set(key, data);
                                            Storage.set(key + '.time', (new Date()).getTime());
                                            // console.log("Stored length", key, Storage.get(key).length);
                                        } catch (e) {
                                            // Failed.  Most likely quota - tidy some stuff up, including
                                            // this value so that it doesn't stay out of date.
                                            Storage.remove(key);
                                            Storage.remove(key + '.time');

                                            console.log("Failed to set", e.message);
                                            Storage.iterate(function(k,v) {
                                                console.log("Consider prune ", k);
                                                if (k.indexOf('cache.') === 0 ||
                                                    (k.indexOf('chat-') !== -1 &&
                                                    (k.indexOf('-width') !== -1 || k.indexOf('-height') !== -1 || k.indexOf("-lp") !== -1))) {
                                                    console.log("Remove", k, v.length);
                                                    Storage.remove(k);
                                                }
                                            });
                                        }
                                    /* CC } else {
                                        // We can't cache this, as it's too big.  Remove any previously cached data
                                        // which might be below this limit, as otherwise it will persist forever and
                                        // become increasingly misleading.
                                        console.log("Don't cache too long", key, data.length);
                                        Storage.remove(key);
                                        Storage.remove(key + '.time');
                                    } */
                                } catch (e) {console.log("Exception", e); console.error(e.message);}
                            }

                            // Now tell the caller the fetch has completed.
                            // console.log("Resolve fetch");
                            resolve();
                        });
                    }

                    // Now fetch - immediately or after a delay.
                    if (fetchDelay > 0) {
                        // console.log("Delay fetch for", fetchDelay);
                        window.setTimeout(issueFetch, fetchDelay);
                    } else {
                        // console.log("Immediate fetch");
                        issueFetch();
                    }
                });
            } else {
                self.promise = resolvedPromise();
            }

            return(self.promise);
        }
    });

    Iznik.View = (function (View) {

        var ourview = View.extend({
            globalClick: function (e) {
                // When a click occurs, we block further clicks for a few seconds, to stop double click damage.
                $(e.target).addClass('blockclick');
                window.setTimeout(function () {
                    $(e.target).removeClass('blockclick');
                }, 5000);

                // We also want to spot if an AJAX call has been made; since this goes to the server, it may take a
                // while before it completes and the user sees some action.  We add a class to pulse the element to
                // provide visual comfort.
                //
                // Note that we expect to have one outstanding request (our long poll) at all times.
                window.setTimeout(function () {
                    if ($.active > 1) {
                        // An AJAX call was started in another click handler.  Start pulsing.
                        $(e.target).addClass('showclicked');

                        window.setTimeout(function () {
                            // The pulse should be removed in the ajaxStop handler, but have a fallback just in
                            // case.
                            $(e.target).removeClass('showclicked');
                        }, 5000);
                    }
                }, 0);
            },

            constructor: function (options) {
                this.options = options || {};
                View.apply(this, arguments);
            },

            checkDOM: function(self) {
                console.log("CheckDOM", this);
                if (!self) {
                    self = this;
                }

                if (self.$el.closest('body').length > 0) {
                    console.log("Now in DOM", self);
                    self.inDOMProm.resolve(self);
                } else {
                    console.log("Not in DOM yet", self);
                    window.setTimeout(self.checkDOM, 50, self);
                }
            },

            ourRender: function() {
                var html;

                if (this.model) {
                    html = window.template(this.template)(this.model.toJSON2());
                } else {
                    html = window.template(this.template)();
                }

                this.$el.html(html);

                return this;
            },

            render: function () {
                // A key difference from normal Backbone is that our render method is async and returns a Promise,
                // rather than synchronous.  The reason for this is to allow us to fetch templates on demand from
                // the server, rather than in a big blob.  This reduces page load time.
                //
                // This means that where we override render in a view, and call the prototype render, we have to both 
                // issue a then() on the returned promise and return it from our own render.  So you'll see code
                // along the lines of:
                //
                // var p = Iznik.View.prototype.render.call(this);
                // p.then(function() {...}
                // return(p);
                //
                // You'll get used to it.
                var self = this;
                var promise = new Promise(function(resolve, reject) {
                    if (!self.template) {
                        // We don't have a template.  We can render.
                        resolve(self.ourRender.call(self));

                        if (self.hasOwnProperty('triggerRender')) {
                            // We don't often need this, so it's controlled by a flag.
                            self.trigger('rendered');
                        }
                    } else {
                        // We have a template.  We need to fetch it.
                        templateFetch(self.template).then(function() {
                            resolve(self.ourRender.call(self));
                            if (self.hasOwnProperty('triggerRender')) {
                                self.trigger('rendered');
                            }
                        })
                    }
                });

                return(promise);
            },

            inDOM: function () {
                return (this.$el.closest('body').length > 0);
            },

            waitDOM: function(self, cb) {
                // Sometimes, we need to wait until our rendering has completed and an element is in the DOM.  We
                // do this in a rather clunky polling way, as it's not idiomatic with promises.
                if (self.$el.closest('body').length > 0) {
                    cb.call(self, self);
                } else {
                    window.setTimeout(self.waitDOM, 50, self, cb);
                }
            },

            destroyIt: function () {
                this.undelegateEvents();
                this.$el.removeData().unbind();
                this.remove();
                Backbone.View.prototype.remove.call(this);
            }
        });

        ourview.extend = function (child) {
            // We want to inherit events when we extend a view.  This is useful in cases such as a modal which has
            // its own events but wants the modal events too.
            //
            // We do this by overriding extend itself, so that we merge in the events from the child.  Using
            // _.extend to do this makes weird bad things happen, so we do it ourselves in JS.
            //
            // We don't have to worry about the case where the events property is a function because we don't use that.
            var view = View.extend.apply(this, arguments);

            if (view.prototype.events) {
                if (child.hasOwnProperty('events')) {
                    var ourevents = typeof this.prototype.events !== 'undefined' ? jQuery.extend({}, this.prototype.events) : {
                        // 'click .btn': 'globalClick'
                    };
                    for (var i in child.events) {
                        ourevents[i] = child.events[i];
                    }

                    view.prototype.events = ourevents;
                }
            }

            return view;
        }

        return (ourview);

    })(Backbone.View);

    Iznik.View.Timeago = Iznik.View.extend({
        timeagoRunning: false,

        render: function() {
            var self = this;

            // Expand the template via the parent then set the times.
            var p = Iznik.View.prototype.render.call(this);
            p.then(function(self) {
                if (!self.timeagoRunning) {
                    self.timeagoRunning = true;

                    // We want to ensure this gets updated, and also update the title to be human readable on
                    // mouseover.  But we don't need to do this immediately, so delay it, to avoid extra
                    // expensive DOM manipulation during page load.
                    _.delay(_.bind(function() {
                        var self = this;

                        self.$('.timeago').each(function() {
                            var $el = $(this);
                            var d = $el.prop('title');

                            if (d) {
                                // Ensure that we will keep this up to date.
                                $el.timeago(new moment(d));

                                // Prettify the title so that it looks readable on mouseover.
                                var s = (new moment(d)).format('LLLL');
                                $el.prop('title', s);
                            }
                        });
                    }, self), 30000);
                }
            });

            return(p);
        }
    });

    // Save as global as it's useful for debugging.
    window.Iznik = Iznik;

    return (Iznik);
});
