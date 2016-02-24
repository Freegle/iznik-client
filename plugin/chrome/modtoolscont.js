function getVersion() {
    var version = 'NaN';
    var xhr = new XMLHttpRequest();
    xhr.open('GET', chrome.extension.getURL('manifest.json'), false);
    xhr.send(null);
    var manifest = JSON.parse(xhr.responseText);
    return manifest.version;
}

//var version = chrome.app.getDetails().version;
var version = getVersion();

var d = document.createElement('div');
d.id = 'modtoolschrome';
d.style.display = "none";
d.innerHTML = version;
document.body.appendChild(d);

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
    });
