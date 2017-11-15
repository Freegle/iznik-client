function getVersion() {
    var version = 'NaN';
    var xhr = new XMLHttpRequest();
    xhr.open('GET', chrome.extension.getURL('manifest.json'), false);
    xhr.send(null);
    var manifest = JSON.parse(xhr.responseText);
    return manifest.version;
}

chrome.runtime.onMessage.addListener(
    function (request, sender, sendResponse) {
        //console.log("Content script message received");
        //console.log(request);

        if (request.action == 'getreq') {
            var data = $('#modtoolsreq').text();
            //console.log("Got majax data " + data);
            sendResponse({request: data});
        } else if (request.action == 'storersp') {
            var data = $('#modtoolsrsp').text();
            //console.log("Got majax data " + data);
            var rsp = JSON.stringify(request.data);
            //console.log("Store response len " + rsp.length);
            $('#modtoolsrsp').text(rsp);
            sendResponse({});
        }
    }
);

console.log("ModTools Content Script Loaded");

function keyText(text) {
    for (var i = 0; i < text.length; i++) {
        var e = new Event("keypress");
        var char = text.substring(i, i+1);
        console.log("Key", char);
        e.key=char;
        e.keyCode=e.key.charCodeAt(0);
        e.which=e.keyCode;
        e.altKey=false;
        e.ctrlKey=false;
        e.shiftKey=false;
        e.metaKey=false;
        // e.bubbles=true;
        // e.isTrusted = true;
        document.dispatchEvent(e);
    }
}

(function( $ ) {
    $.fn.execInsertText = function(text) {
        var activeElement = document.activeElement;
        var result = this.each(function() {
            this.focus();
            document.execCommand('selectAll');
            document.execCommand('insertText', false, text);
        });
        if (activeElement) {
            activeElement.focus();
        }
        return result;
    };
}( jQuery ));

function waitFor(check, parm) {
    var p = new Promise(function(resolve, reject) {
        function checkIt() {
            if (check()) {
                resolve(parm);
            } else {
                window.setTimeout(checkIt, 100);
            }
        }

        checkIt();
    });

    return(p);
}

function shuffle(array) {
    var currentIndex = array.length, temporaryValue, randomIndex;

    // While there remain elements to shuffle...
    while (0 !== currentIndex) {

        // Pick a remaining element...
        randomIndex = Math.floor(Math.random() * currentIndex);
        currentIndex -= 1;

        // And swap it with the current element.
        temporaryValue = array[currentIndex];
        array[currentIndex] = array[randomIndex];
        array[randomIndex] = temporaryValue;
    }

    return array;
}

function status(str) {
    $('#mtstatus').html(str);
}

function statusHide() {
    $('#mtholder').hide();
}
