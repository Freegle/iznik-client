console.log("Injected", window);
console.log("Get debug tools", __REACT_DEVTOOLS_GLOBAL_HOOK__);

function doPost() {
    var desc = null;

    // Wait for dev tools
    var elementData = window.__REACT_DEVTOOLS_GLOBAL_HOOK__.reactDevtoolsAgent.elementData.values();
    var elts = []; var done = false;
    while (!done) {
        var iter = elementData.next();
        done = iter.done;
        elts.push(iter.value);
        // console.log(iter.value);

        if (iter.value &&
            iter.value.hasOwnProperty('props') &&
            iter.value.props) {
            if (iter.value.props.hasOwnProperty('handlePastedText')) {
                console.log("Got pasted", iter.value);
            }

            if (iter.value.props.hasOwnProperty('editorState')) {
                console.log("Got editorState", iter.value);
            }

            if (iter.value.props.hasOwnProperty('text')) {
                console.log("Text", iter.value, iter.value.props.text);
                if (iter.value.key) {
                    if (!desc) {
                        desc = iter.value;
                        console.log("Found desc", desc);
                    }
                }
            }

            // if (iter.value.props.hasOwnProperty('placeholder')) {
            //     console.log("Placeholder", iter.value.props.placeholder);
            //
            //     if (iter.value.props.placeholder && iter.value.props.placeholder.hasOwnProperty('props')) {
            //         console.log("Placeholder props", iter.value, iter.value.props.placeholder.props);
            //         console.log("Placeholder translation", iter.value, iter.value.props.placeholder.props.translation);
            //
            //         // temp1.publicInstance.updater.enqueueForceUpdate(temp1.publicInstance)
            //         // undefined
            //         // temp1.publicInstance.props.text = 'testin2g';
            //         // "testin2g"
            //         // temp1.publicInstance.updater.enqueueForceUpdate(temp1.publicInstance)
            //     } else {
            //         console.log("No props");
            //     }
            // }
        }
    }

    if (desc) {
        console.log("Set desc", desc);
        // desc.publicInstance.props.text = 'Hello there';
        // desc.publicInstance.updater.enqueueForceUpdate(desc.publicInstance);

        var box = document.getElementById('composer_text_input_box');
        var editable = box.getElementsByClassname('nottranslate');
        console.log("Editable", editable);
        var e = new Event("keydown");
        var char = text.substring(i, i+1);
        console.log("Key", char);
        e.key=char;
        e.keyCode="s".charCodeAt(0);
        e.which=e.keyCode;
        e.altKey=false;
        e.ctrlKey=false;
        e.shiftKey=false;
        e.metaKey=false;
        editable[0].dispatchEvent(e);
    }
}

function keyText(text) {
    for (var i = 0; i < text.length; i++) {
        var e = new Event("keydown");
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

window.setTimeout(function() {
    var links = document.getElementsByTagName('a');
    for (var i = 0; i < links.length; i++) {
        var tooltip = links[i].getAttribute('data-tooltip-content');
        if (tooltip == 'Start Discussion') {
            console.log("Switch to discussion");
            links[i].click();
            window.setTimeout(function() {
                // Click into status box.
                console.log("Click status");
                var box = document.getElementById('composer_text_input_box');
                console.log("Got ", box);
                box.click();
                console.log("Clicked");
                window.setTimeout(function() {
                    // var br = box.getElementsByTagName('br');
                    // console.log("Got BR", br);
                    // var cont = br[0].parentNode;
                    // console.log("Parent", cont);
                    // cont.innerHTML = '<span data-text="true">Testing</span>';
                    window.setTimeout(doPost, 5000);
                }, 20000);
            }, 3000);
        }
    }
}, 10000);
