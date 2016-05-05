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
        currentDOM: null,

        eventIndex: 0,

        jump: function(e) {
            var self = this;
            var x = e.pageX - $('#replayBar').offset().left;
            var fraction = x / $('#replayBar').width();
            var time = self.clientStart + fraction * (self.clientEnd - self.clientStart);

            this.eventIndex = 0;
            var eventtime;
            do {
                eventtime = (new Date(this.replayEvents[this.eventIndex].clienttimestamp)).getTime();
                if (eventtime > time) {
                    this.eventIndex--;
                    break;
                } else {
                    this.eventIndex++;
                }
            } while (true);

            this.eventIndex = Math.max(0, this.eventIndex);

            this.progress();

            // Find any previous full DOM, and then play forwards until we get to this point, then pause.
            this.pauseAt = this.eventIndex;
            $('#replayHeader').addClass('showclicked');
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
            $('#js-pause').removeClass('reallyHide');
            $('#js-play').addClass('reallyHide');
            this.eventIndex = 0;
        },

        replaceDOM: function(data) {
            if (!this.pauseAt) {
                // When we're scanning we don't update the actual DOM for speed.
                var canvas = $('#replayCanvas').detach();
                var header  = $('#replayHeader').detach();
                $('body')[0].outerHTML = data;
                $('body').prepend(header);
                $('body').append(canvas);
            }
            this.currentDOM = data;
        },

        progress: function() {
            var self = this;
            if (self.eventIndex < self.replayEvents.length) {
                var event = this.replayEvents[self.eventIndex];
                var percent = 100 * ((new Date(event.clienttimestamp)).getTime() - self.clientStart) / self.clientDuration;
                // console.log("Progress", event.clienttimestamp, (new Date(event.clienttimestamp)).getTime(), self.clientStart, self.clientDuration, percent)
                $('#js-progress').css('width',  percent + '%').attr('aria-valuenow', percent);
                $('#js-time').html(event.clienttimestamp + "&nbsp;GMT");
            }
        },

        playEvent: function() {
            var self = this;
            var start = (new Date()).getTime();

            // Determine how long the replay has taken compared to the original session, which tells us if our
            // replay is lagging.

            var event = self.replayEvents[self.eventIndex++];

            keepgoing = false;

            var lag = (start - this.replayStart) - ((new Date(event.clienttimestamp)) - self.clientStart);
            lag = lag < 0 ? 0 : lag;
            // console.log("playEvent", self.eventIndex, event.clienttimestamp, start - this.replayStart, (new Date(event.clienttimestamp)) - self.clientStart, lag);

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
                self.pauseAt = null;
                self.pause();
                $('#replayHeader').removeClass('showclicked');
                self.replaceDOM(self.currentDOM);
            } else if (!self.paused) {
                if (self.eventIndex < self.replayEvents.length) {
                    if (!self.timerRunning) {
                        // See when the next event is due, which might be immediately if we took a while to replay.
                        var diff = parseInt(event.clientdiff);

                        if (lag > diff) {
                            // We're lagging - the loop will keep going.
                            diff = 0;
                        } else {
                            diff -= lag - 50;

                            // If we don't yet have a DOM or we're running to a pause, we want to speed it up.
                            diff = self.currentDOM ? diff : 0;
                            diff = self.pauseAt ? 0 : diff;
                        }

                        self.timerRunning = true;
                        window.setTimeout(_.bind(self.playNext, self), diff);
                    }
                } else {
                    self.finished();
                }
            }

            // console.log("Replayed", event.clienttimestamp, (new Date()).getTime() - start);
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
                    if (ret.ret == 0) {
                        if (ret.events.length > 0) {
                            self.replayEvents = ret.events;
                            self.clientStart = (new Date(ret.events[0].clienttimestamp)).getTime();
                            self.clientEnd = (new Date(ret.events[ret.events.length - 1].clienttimestamp)).getTime();
                            $('#js-endtime').html(ret.events[ret.events.length - 1].clienttimestamp + '&nbsp;GMT');
                            self.clientDuration = self.clientEnd - self.clientStart;
                            console.log("Duration", self.clientDurtion, ret.events[0].clienttimestamp, ret.events[ret.events.length - 1].clienttimestamp);
                            self.replayStart = (new Date()).getTime();
                            self.playEvent();
                        }
                    }
                }
            });

            return(this);
        }
    });
});

