define([
    'jquery',
    'underscore'
], function($, _) {
    // TODO Namespace pollution here
    //
    // Ensure we can log.
    if (!window.console) {
        window.console = {};
    }
    if (!console.log) {
        console.log = function (str) {
        };
    }

    if (!console.trace) {
        console.trace = function() {

        }
    }

    if (!console.error) {
        console.error = function (str) {
            window.alert(str);
        };
    }

    window.isXS = function() {
        return window.innerWidth < 320;
    };

    window.isSM = function() {
        return window.innerWidth < 768;
    };

    window.isShort = function() {
        return window.innerHeight < 900;
    };

    window.isVeryShort = function() {
        return window.innerHeight <= 300;
    };

    window.canonSubj = function(subj) {
        subj = subj.toLocaleLowerCase();

        // Remove any group tag
        subj = subj.replace(/^\[.*?\](.*)/, "$1");

        // Remove duplicate spaces
        subj = subj.replace(/\s+/g, ' ');

        subj = subj.trim();

        return (subj);
    };

    window.setURLParam = function(uri, key, value) {
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

    window.removeURLParam = function(uri, key) {
        var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
        if (uri.match(re)) {
            console.log("Found param");
            return uri.replace(re, '$1$2');
        }
        else {
            return uri;
        }
    };

    window.getDistanceFromLatLonInKm = function(lat1, lon1, lat2, lon2) {
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

    window.deg2rad = function(deg) {
        return deg * (Math.PI / 180)
    };

    window.decodeEntities = (function () {
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

    window.encodeHTMLEntities = function(str) {
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    };

    // Apply a custom order to a set of messages
    window.orderedMessages = function(stdmsgs, order) {
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

    window.csvWriter = function(del, enc) {
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

    window.presdef = function (key, obj, def) {
        var ret = obj && obj.hasOwnProperty(key) ? obj[key] : def;
        return (ret);
    };

    window.chunkArray = function(array, size) {
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

    window.base64url = {
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

    window.isValidEmailAddress = function(emailAddress) {
        var pattern = /^([a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+(\.[a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+)*|"((([ \t]*\r\n)?[ \t]+)?([\x01-\x08\x0b\x0c\x0e-\x1f\x7f\x21\x23-\x5b\x5d-\x7e\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|\\[\x01-\x09\x0b\x0c\x0d-\x7f\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))*(([ \t]*\r\n)?[ \t]+)?")@(([a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.)+([a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.?$/i;
        return pattern.test(emailAddress);
    };

    window.wbr = function(str, num) {
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
});

function haversineDistance(coords1, coords2, isMiles) {
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

function getURLParam(name) {
    var url = location.search.replace(/\&amp;/g, '&');
    name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
    var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
        results = regex.exec(url);
    return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
}

function strip_tags (input, allowed) { // eslint-disable-line camelcase
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

function ABTestShown(uid, variant) {
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

function ABTestAction(uid, variant) {
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

function ABTestGetVariant(uid, cb) {
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

function nullFn() {}

function twem(msg) {
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

function setTitleCounts(chat, newsfeed) {
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

function ellipsical(str, len) {
    if (str.length - 3 > len) {
        str = str.substring(0, len - 3) + '...';
    }

    return(str);
}

function formatDuration(secs) {
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

    console.log("Return", ret, secs);

    return(ret);
}

/**
 * Return an object with the selection range or cursor position (if both have the same value)
 * @param {DOMElement} el A dom element of a textarea or input text.
 * @return {Object} reference Object with 2 properties (start and end) with the identifier of the location of the cursor and selected text.
 **/
function getInputSelection(el) {
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

function getBoundsZoomLevel(bounds, mapDim) {
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

