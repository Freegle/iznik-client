console.log("Injected", window);
console.log("Get debug tools", __REACT_DEVTOOLS_GLOBAL_HOOK__);

var desc = null;

window.setTimeout(function() {
    // Wait for dev tools
    var elementData = window.__REACT_DEVTOOLS_GLOBAL_HOOK__.reactDevtoolsAgent.elementData.values();
    var elts = []; var done = false;
    while (!done) {
        var iter = elementData.next();
        done = iter.done;
        elts.push(iter.value);

        if (iter.value &&
            iter.value.hasOwnProperty('props') &&
            iter.value.props) {
            if (iter.value.props.hasOwnProperty('text')) {
                console.log("Text", iter.value, iter.value.props.text);
                if (iter.value.key) {
                    if (!desc) {
                        desc = iter.value;
                        console.log("Found desc", desc);
                    }
                }
            }

            if (iter.value.props.hasOwnProperty('placeholder')) {
                console.log("Placeholder", iter.value.props.placeholder);

                if (iter.value.props.placeholder.hasOwnProperty('props')) {
                    console.log("Placeholder props", iter.value, iter.value.props.placeholder.props);
                    console.log("Placeholder translation", iter.value, iter.value.props.placeholder.props.translation);

                    // temp1.publicInstance.updater.enqueueForceUpdate(temp1.publicInstance)
                    // undefined
                    // temp1.publicInstance.props.text = 'testin2g';
                    // "testin2g"
                    // temp1.publicInstance.updater.enqueueForceUpdate(temp1.publicInstance)
                } else {
                    console.log("No props");
                }
            }
        }
    }

    if (desc) {
        console.log("Set desc", desc);
        desc.publicInstance.props.text = 'Hello there';
        desc.publicInstance.updater.enqueueForceUpdate(desc.publicInstance);
    }
}, 10000);

// function recurCheck(el, depth) {
//     console.log("Check", el, depth);
//     if (el) {
//         if (el.hasOwnProperty('props') && el.props.hasOwnProperty('value')) {
//             console.log("Value", el.props.value, el);
//         }
//
//         if (el.hasOwnProperty('props') && el.props.hasOwnProperty('children') && el.props.children) {
//             console.log("Has children", typeof el.props.children);
//             for (var i = 0; i < el.props.children.length; i++) {
//                 recurCheck(el.props.children[i], depth + 1);
//                 console.log("Back from children");
//             }
//         }
//     }
// }
// for (var i in roots) {
//     console.log(i);
//     var set = roots[i];
//     set.forEach(function(val) {
//         var el = val.current.memoizedState.element;
//         recurCheck(el, 0);
//     });
// }