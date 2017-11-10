define([
    'jquery',
    'underscore',
    'backbone',
    'moment',
    'iznik/base',
    'iznik/diff',
    "iznik/modtools",
    'iznik/views/pages/pages',
    "jquery.scrollTo"
], function($, _, Backbone, moment, Iznik, JsDiff) {
    Iznik.Views.ModTools.Pages.Sessions = Iznik.Views.Page.extend({
        modtools: true,
        template: "modtools_replay_sessions",

        render: function() {
            var self = this;

            var p = Iznik.Views.Page.prototype.render.call(this);
            p.then(function() {
                var v = new Iznik.Views.PleaseWait();
                v.render().then(function() {
                    $.ajax({
                        url: API + 'event',
                        type: 'GET',
                        success: function (ret) {
                            v.close();
                            if (ret.ret == 0) {
                                _.each(ret.sessions, function (session) {
                                    var v = new Iznik.Views.ModTools.Pages.Replay.Session({
                                        model: new Iznik.Model(session)
                                    });
                                    v.render();
                                    self.$('.js-sessions').append(v.$el);
                                });
                            }
                        }
                    })
                });
            });

            return(p);
        }
    });

    Iznik.Views.ModTools.Pages.Replay = Iznik.View.extend({
        modtools: true,

        template: "modtools_replay_main",

        paused: false,
        pauseAt: null,
        timerRunning: false,
        currentDOM: null,
        fastForward: false,
            
        lastMouseX: null,
        lastMouseY: null,
        replayEvents: [],

        eventIndex: 0,

        pause: function() {
            $('#js-pause').addClass('reallyHide');
            $('#js-play').removeClass('reallyHide');
            $('#js-id').html(this.eventIndex);
            this.paused = true;
        },

        play: function() {
            this.paused = false;
            $('#js-id').html('');
            $('#js-pause').removeClass('reallyHide');
            $('#js-play').addClass('reallyHide');
            this.playEvent();
        },

        forward: function() {
            this.fastForward = true;
            $('#js-forward').addClass('reallyHide');
            $('#js-forward-off').removeClass('reallyHide');
        },

        forwardOff: function() {
            this.fastForward = false;
            $('#js-forward-off').addClass('reallyHide');
            $('#js-forward').removeClass('reallyHide');
        },

        finished: function () {
            $('#js-pause').removeClass('reallyHide');
            $('#js-play').addClass('reallyHide');
            this.eventIndex = 0;
        },

        replaceDOM: function(data) {
            if (!this.pauseAt) {
                // When we're scanning we don't update the actual DOM for speed.
                $('#replayContent').html(data);

                // We can have some hidden IFRAMEs from e.g. Google.  We don't want those - some of them cause
                // network requests.
                $('#replayContent iframe').remove();
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
                var mom = new moment(event.clienttimestamp);
                $('#js-time').html(mom.format('DD/MM HH:mm:ss'));
            }
        },

        DOMchange: function(event) {
            var target = $((event.target == 'body') ? '#replayContent' : event.target);

            if (target.length > 0) {
                target.get(0).outerHTML = event.data;
            } else {
                // We can't find it.  They often turn up, though.
                window.setTimeout(_.bind(this.DOMchange, this), 200, event);
            }
        },

        playEvent: function() {
            var self = this;

            do {
                var keepgoing = false;

                var start = (new Date()).getTime();

                // Determine how long the replay has taken compared to the original session, which tells us if our
                // replay is lagging.
                var event = self.replayEvents[self.eventIndex++];
                var lag = (start - this.replayStart) - ((new Date(event.clienttimestamp)) - self.clientStart);
                lag = lag < 0 ? 0 : lag;
                // console.log("playEvent", self.eventIndex, event.clienttimestamp, start - this.replayStart, (new Date(event.clienttimestamp)) - self.clientStart, lag);

                // Adjust window size if necessary.
                var currHeight = $(window).height();
                var currWidth = $(window).width();

                if (currHeight != event.viewy || currWidth != event.viewx) {
                    if (event.viewx != self.lastWindowWidth &&
                        event.viewy != self.lastWindowHeight) {
                        // Although we try to resize the window, we may not get what we asked for - window.open tends to
                        // open without the bookmark bar, for example.  No point repeatedly trying to resize unless
                        // it's changed, especially as resizing the canvas clears it.
                        window.resizeTo(event.viewx, event.viewy);
                        self.lastWindowWidth = event.viewx;
                        self.lastWindowHeight = event.viewy;

                        var canvas = document.getElementById('replayCanvas');
                        canvas.width = event.viewx;
                        canvas.height = event.viewy;
                    }
                }

                // Update time and progress
                self.progress();

                try {
                    switch (event.event) {
                        case 'mutation':
                        {
                            if (event.target == 'body') {
                                self.replaceDOM(event.data);
                            } else {
                                self.DOMchange(event);
                            }
                            break;
                        }

                        case 'scroll': {
                            $('body').scrollTo(parseInt(event.data));
                            break;
                        }

                        case 'scrollpos': {
                            var target = (event.target == 'body') ? '#replayContent' : event.target;
                            $(target).get(0).scrollTop = event.data;
                            break;
                        }

                        case 'click':
                        case 'focus': {
                            // Don't actually click - just draw on the canvas to illustrate it.
                            var canvas = document.getElementById('replayCanvas');
                            var context = canvas.getContext('2d');
                            var x = Math.round(parseInt(event.posx));
                            var y = Math.round(parseInt(event.posy));

                            function drawClick(context, x, y, oldradius, newradius, grow) {
                                return (function () {
                                    // console.log("Draw click at", x, y);
                                    if (oldradius) {
                                        // Wipe any previous one.
                                        var old = context.globalCompositeOperation;
                                        context.globalCompositeOperation = "destination-out";
                                        context.beginPath();
                                        context.arc(x, y, oldradius + 10, 0, Math.PI * 2, true);
                                        context.fillStyle = "rgba(0,0,0,1)";
                                        context.fill();
                                        context.globalCompositeOperation = old;
                                    }

                                    // Draw the new one.
                                    context.beginPath();
                                    context.arc(x, y, newradius, 0, Math.PI * 2, true);
                                    context.fillStyle = 'red';
                                    context.fill();

                                    if (grow) {
                                        if (newradius < 20) {
                                            window.setTimeout(drawClick(context, x, y, newradius, newradius + 1, true), 100);
                                        } else {
                                            window.setTimeout(drawClick(context, x, y, newradius + 1, newradius, false), 100);
                                        }
                                    } else {
                                        if (newradius > 0) {
                                            window.setTimeout(drawClick(context, x, y, newradius, newradius - 1, false), 100);
                                        }
                                    }
                                });
                            }

                            drawClick(context, x, y, 0, 0, true)();

                            break;
                        }

                        case 'mousemove': {
                            // Mouse track - draw a line from the last one, then wipe it after a while.
                            if (self.lastMouseX && self.lastMouseY) {
                                var canvas = document.getElementById('replayCanvas');
                                var context = canvas.getContext('2d');
                                context.beginPath();
                                context.moveTo(self.lastMouseX, self.lastMouseY);
                                context.lineTo(event.posx, event.posy);
                                context.lineWidth = 5;
                                context.globalAlpha = 0.5;
                                context.strokeStyle = 'red';
                                context.stroke();

                                function wipe(context, lastMouseX, lastMouseY, posx, posy) {
                                    return (function () {
                                        //console.log("Wipe", lastMouseX, lastMouseY, posx, posy);
                                        var old = context.globalCompositeOperation;
                                        context.globalCompositeOperation = "destination-out";
                                        context.strokeStyle = "rgba(0,0,0,1)";
                                        context.beginPath();
                                        context.moveTo(lastMouseX, lastMouseY);
                                        context.lineTo(posx, posy);
                                        context.lineWidth = 10;
                                        context.globalAlpha = 1;
                                        context.stroke();

                                        context.globalCompositeOperation = old;
                                    });
                                }

                                window.setTimeout(wipe(context, self.lastMouseX, self.lastMouseY, event.posx, event.posy), 5000);
                            }

                            self.lastMouseX = event.posx;
                            self.lastMouseY = event.posy;

                            break;
                        }

                        case 'input': {
                            var target = (event.target == 'body') ? '#replayContent' : event.target;
                            $(target).focus();
                            $(target).val(event.data);
                            break;
                        }
                    }
                } catch (e) {
                    console.error("Event replay failed with", e.message, event.event, event.target);
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

                            if (diff == 0) {
                                // This is a case where we want to keep replaying without a break.  It's used, for
                                // example, where we get a DOM dump and then set the inputs.
                                keepgoing = true;
                            } else {
                                if (lag > diff) {
                                    // We're lagging - the loop will keep going.
                                    diff = 0;
                                } else {
                                    diff -= lag - 50;

                                    // If we don't yet have a DOM or we're running to a pause, we want to speed it up.
                                    diff = self.currentDOM ? diff : 0;
                                    diff = self.pauseAt ? 0 : diff;
                                }

                                // If we're fast forwarding, there's no delay.
                                diff = self.fastForward ? 0 : diff;

                                if (diff > 5000) {
                                    // Don't wait too long.  Adjust the replay start time so that our lagging calculations
                                    // still work.
                                    self.replayStart += diff - 5000;
                                    diff = 5000;
                                }

                                self.timerRunning = true;
                                // console.log("playEvent", event.clienttimestamp, event.event, diff);
                                window.setTimeout(_.bind(self.playNext, self), diff);
                            }
                        }
                    } else {
                        self.finished();
                    }
                }

                // console.log("Replayed", event.clienttimestamp, (new Date()).getTime() - start);
            } while (keepgoing);
        },

        playNext: function() {
            this.timerRunning = false;
            this.playEvent();
        },

        heatbar: function() {
            var gradient = [
                '#FF0001', '#FF191A', '#FF3333', '#FF4C4D', '#FF6666', '#FF7F80', '#FF9999', '#FFB2B2', '#FFCCCC',
                '#FFE5E5', '#FFFFFF'
            ]

            // The heatbar sits below the progress bar and shows the level of activity at each point.
            var chunkSize = 1000;
            var heatwidth = $('#heatBar').innerWidth();
            var timePerPixel = this.clientDuration / heatwidth;
            // console.log("Time per pixel", timePerPixel, heatwidth, this.clientDuration);
            var eventIndex = 0;
            var heats = [];
            var maxheat = 0;

            for (var chunk = 0; chunk < heatwidth; chunk++) {
                var heat = 0;
                var eventtime;
                var below;

                do {
                    below = false;
                    eventtime = (new Date(this.replayEvents[eventIndex].clienttimestamp)).getTime();

                    if (eventtime - this.clientStart < chunk * timePerPixel) {
                        heat++;
                        eventIndex++;
                        below = true;
                    }
                } while (below && eventIndex < this.replayEvents.length);

                heats[chunk] = heat;
                maxheat = heat > maxheat ? heat : maxheat;
            }

            //console.log("Heats", maxheat, heats);

            // Now we have an array of the heat for each chunk.  Apply it by creating a div of the appropriate colour.
            for (var chunk = 0; chunk < heatwidth; chunk++) {
                var div = $('<div />').appendTo($('#heatBar'));
                div.width(1);
                div.css('position', 'absolute');
                div.css('left', chunk + 'px');
                var colourind = Math.floor(gradient.length - heats[chunk] / maxheat * gradient.length);
                div.css('background-color', gradient[colourind]);
            }
        },

        render: function() {
            // For a reply we don't have the usual page structure - we need the whole window.
            var p = Iznik.View.prototype.render.call(this);
            p.then(function(self) {
                $('body').html(self.el);

                self.headerHeight = $('#replayHeader').outerHeight();

                // Can't use backbone events because of the way we mess with the DOM.
                $('#js-play').click(_.bind(self.play, self));
                $('#js-pause').click(_.bind(self.pause, self));
                $('#js-forward').click(_.bind(self.forward, self));
                $('#js-forward-off').click(_.bind(self.forwardOff, self));

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
                                var mom = new moment(ret.events[ret.events.length - 1].clienttimestamp);
                                $('#js-endtime').html(mom.format('DD/MM HH:mm:ss'));
                                self.clientDuration = self.clientEnd - self.clientStart;
                                self.replayStart = (new Date()).getTime();

                                _.defer(function() {
                                    // Now start playing.
                                    self.playEvent();

                                    // Do the heatbar after the first event as it may affect the window size, and therefore
                                    // introduce a scrollbar which reduces the space available for the heatbar.
                                    self.heatbar();
                                })
                            }
                        }
                    }
                });
            });

            return(p);
        }
    });

    Iznik.Views.ModTools.Pages.Replay.Session = Iznik.View.extend({
        tagName: 'li',

        template: "modtools_replay_session",

        events: {
            'click .js-play': 'play'
        },

        play: function() {
            var width = window.innerWidth * 0.66 ;
            var height = width * window.innerHeight / window.innerWidth ;

            // Open a window of the appropriate size, and also in with an URL which will cause us to pick up the
            // correct stylesheet.
            var modsess = this.model.get('modtools');
            var eventsite = $('meta[name=iznikevent]').attr("content");

            var pref = "https://" + eventsite + (modsess ? '/modtools' : '');
            window.open(pref + '/replay/' + this.model.get('id'), 'Session Replay', 'width=' + width + ', height=' + height + ', top=' + ((window.innerHeight - height) / 2) + ', left=' + ((window.innerWidth - width) / 2));
        },

        render: function() {
            var self = this;
            var p = Iznik.View.prototype.render.call(this);
            p.then(function(self) {
                var start = new moment(self.model.get('start'));
                var end = new moment(self.model.get('end'));
                var diff = end.diff(start);
                var duration = moment.duration(diff).humanize();

                self.$('.js-time').html(start.format('DD-MMM-YY HH:mm') + ', ' + duration);
            });

            return(p);
        }
    })
});

