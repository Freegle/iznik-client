(function ( $ ) {
    var id;

    $.fn.speech = function(action) {
        var self = this;

        console.log("Speech");

        if (window.hasOwnProperty('webkitSpeechRecognition')) {
            console.log("Enabled");
            if (!action) {
                console.log("Start");
                var recognition = new webkitSpeechRecognition();

                recognition.continuous = false;
                recognition.interimResults = false;

                recognition.lang = "en-US";
                recognition.start();

                [
                    'onaudiostart',
                    'onaudioend',
                    'onend',
                    'onerror',
                    'onnomatch',
                    'onresult',
                    'onsoundstart',
                    'onsoundend',
                    'onspeechend',
                    'onstart'
                ].forEach(function(eventName) {
                    recognition[eventName] = function(e) {
                        console.log(eventName, e);
                    };
                });

                recognition.onresult = function (e) {
                    console.log("Got result", str);
                    var str = e.results[0][0].transcript;
                    recognition.stop();
                    self.trigger('result', str);
                };

                recognition.onerror = function (e) {
                    recognition.stop();
                }
            } else if (action == 'stop') {
                recognition.stop();
            }
        }

        return this;
    };

}( jQuery ));