console.log("Injected2", window);

function FindReact(dom) {
    for (var key in dom) {
        if (key.startsWith("__reactInternalInstance$")) {
            var compInternals = dom[key]._currentElement;
            return(dom[key]);

            if (compInternals) {
                var compWrapper = compInternals._owner;
                var comp = compWrapper._instance;
                return comp;
            }
        }
    }

    return null;
}

function FindReactHandles(dom) {
    for (var key in dom) {
        if (key.startsWith("__reactEventHandlers$")) {
            return(dom[key]);
        }
    }

    return null;
}

// We want to find the React which governs the status composer.
var el = document.getElementById('composer_text_input_box');
console.log("Find react on", el);
var r = FindReact(el);
if (r) {
    console.log("Returned injected", r);
    var span = el.getElementsByTagName('span');
    console.log("Span", span);
    var r2 = FindReact(span[0]);
    r2.stateNode.innerText = 'testing';

    var event = new Event('input', {
        'bubbles': true,
        'cancelable': true
    });

    r2.stateNode.dispatchEvent(event);
}

var hnd = FindReactHandles(document.getElementById('composer_text_input_box'));
console.log("Found handlers", hnd);
var keycmd = hnd.children.props.handlePastedText;
console.log("Key cmd", keycmd);

document.execCommand('Paste');


//
// var currentNode,
//     ni = document.createNodeIterator(document.documentElement, NodeFilter.SHOW_ELEMENT);
//
// console.log("Check nodes");
// while(currentNode = ni.nextNode()) {
//     var r = FindReact(currentNode);
//     if (r) {
//         console.log("Returned injected", r, currentNode);
//     }
// }
//
