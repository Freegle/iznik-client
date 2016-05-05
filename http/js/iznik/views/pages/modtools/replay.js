define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/diff'
], function($, _, Backbone, Iznik, JsDiff) {
    Iznik.Views.ModTools.Pages.Replay = Iznik.View.extend({
        modtools: true,

        template: "modtools_replay_main",

        paused: false,
        pauseAt: null,
        timerRunning: false,
        
        eventIndex: 0,

        jump: function(e) {
            var x = e.pageX - $('#replayBar').offset().left;
            this.eventIndex = Math.floor(this.replayEvents.length * x / $('#replayBar').width());
            this.progress();

            // Find any previous full DOM, and then play forwards until we get to this point, then pause.
            this.pauseAt = this.eventIndex;
            this.paused = false;

            while (this.eventIndex > 0 && this.replayEvents[this.eventIndex].event != 'DOM-f') {
                this.eventIndex--;
            }

            this.playEvent();
        },

        pause: function() {
            $('#js-pause').addClass('reallyHide');
            $('#js-play').removeClass('reallyHide');
            this.paused = true;
        },

        play: function() {
            this.paused = false;
            $('#js-pause').removeClass('reallyHide');
            $('#js-play').addClass('reallyHide');
            this.playEvent();
        },

        finished: function () {
            console.log("Finished");
            $('#js-pause').removeClass('reallyHide');
            $('#js-play').addClass('reallyHide');
            this.eventIndex = 0;
        },

        replaceDOM: function(data) {
            var canvas = $('#replayCanvas').detach();
            var header  = $('#replayHeader').detach();
            $('body')[0].outerHTML = data;
            $('body').prepend(header);
            $('body').append(canvas);
            this.currentDOM = data;
        },

        progress: function() {
            var self = this;
            var percent = 100 * self.eventIndex / self.replayEvents.length;
            $('#js-progress').css('width',  percent + '%').attr('aria-valuenow', percent);
            if (self.eventIndex < self.replayEvents.length) {
                $('#js-time').html(this.replayEvents[self.eventIndex].clienttimestamp + "&nbsp;GMT");
            }
        },

        playEvent: function() {
            var self = this;

            var event = self.replayEvents[self.eventIndex++];

            var currHeight = $(window).height();
            var currWidth = $(window).width();

            // Adjust window size if necessary.
            if (currHeight - self.headerHeight != event.viewy || currWidth != event.viewx) {
                // console.log("Adjust size", currHeight - self.headerHeight, currWidth, event.viewy, event.viewx);
                window.resizeTo(event.viewx, event.viewy + self.headerHeight)
            }

            // Update time and progress
            self.progress();

            switch (event.event) {
                case 'DOM-f':
                {
                    // Full DOM replace.
                    self.replaceDOM(event.data);
                    break;
                }

                case 'DOM-d':
                {
                    // Diff on current DOM.
                    if (self.currentDOM) {
                        var newdom = JsDiff.applyPatch(self.currentDOM, event.data);
                        if (newdom) {
                            self.replaceDOM(newdom);
                        }
                    }

                    break;
                }
            }

            if (self.pauseAt == self.eventIndex) {
                // We wanted to play forwards to here and then stop.
                self.pause();
            } else if (!self.paused) {
                if (self.eventIndex < self.replayEvents.length) {
                    if (!self.timerRunning) {
                        // Start a timer for the next one.  Don't make it too long, and if we've not got a DOM yet, or
                        // we're playing forwards to a pause, speed things up.
                        var diff =  parseInt(event.clientdiff);
                        diff = Math.min(5000, diff);
                        diff = self.currentDOM ? diff : 0;
                        diff = self.pauseAt ? 0 : diff;
                        self.timerRunning = true;
                        window.setTimeout(_.bind(self.playNext, self), diff);
                    }
                } else {
                    self.finished();
                }
            }
        },

        playNext: function() {
            this.timerRunning = false;
            this.playEvent();
        },

        render: function() {
            var self = this;
            
            // For a reply we don't have the usual page structure - we need the whole window.
            Iznik.View.prototype.render.call(this);
            $('body').html(this.el);

            self.headerHeight = $('#replayHeader').outerHeight();
            
            // Can't use backbone events because of the way we mess with the DOM.
            $('#js-play').click(_.bind(self.play, self));
            $('#js-pause').click(_.bind(self.pause, self));
            $('#replayBar').click(_.bind(self.jump, self));

            // Retrieve the session
            $.ajax({
                url: API + 'event',
                type: 'GET',
                data: {
                    sessionid: self.options.sessionid
                }, success: function(ret) {
                    console.log(ret);
                    if (ret.ret == 0) {
                        self.replayEvents = ret.events;
                        self.playEvent();
                    }
                }
            });

            return(this);
        }
    });
});

