define([
  'jquery',
  'underscore',
  'backbone',
  'iznik/base',
  'moment',
  'typeahead',
  'iznik/views/pages/pages',
  'iznik/views/dashboard',
  'iznik/models/group',
  'iznik/models/authority'
], function ($, _, Backbone, Iznik, moment, typeahead) {
  Iznik.Views.User.Pages.StatsGroup = Iznik.Views.Page.extend({
    template: 'user_stats_main',

    events: {},

    render: function () {
      var self = this
      var p

      if (self.options.id) {
        // Called for a specific group
        self.model = new Iznik.Models.Group({
          id: self.options.id
        })

        p = self.model.fetch()
      } else {
        // We want all of them, or perhaps within an area
        self.model = new Iznik.Model({
          'namedisplay': self.options.region ? self.options.region : (self.options.area ? self.options.area : SITE_NAME),
          'tagline': SITE_DESCRIPTION
        })

        p = Iznik.resolvedPromise(self)
      }

      p.then(function () {
        Iznik.Views.Page.prototype.render.call(self, {
          model: self.model
        }).then(function () {
          var count = self.model.get('membercount')
          if (count) {
            self.$('.js-membercount').html(count.toLocaleString())
          }

          var founded = self.model.get('founded')
          if (founded) {
            var m = new moment(founded)
            self.$('.js-foundeddate').html(m.format('Do MMMM, YYYY'))
            self.$('.js-founded').show()
          }

          // Add the description
          var desc = self.model.get('description')

          if (desc) {
            self.$('.js-gotdesc').show()
            self.$('.js-description').html(desc)

            // Any links in here are real.
            self.$('.js-description a').attr('data-realurl', true)
          }

          var v = new Iznik.Views.PleaseWait({
            timeout: 1
          })
          v.render()

          var d = new Date()
          var start = (d.getFullYear() - 1) + '-' + d.getMonth() + '-01'

          $.ajax({
            url: API + 'dashboard',
            data: {
              group: self.model.get('id'),
              start: start,
              grouptype: 'Freegle',
              systemwide: (self.options.id || self.options.area || self.options.region) ? false : true,
              area: self.options.area,
              region: self.options.region
            },
            success: function (ret) {
              v.close()

              if (ret.dashboard) {
                self.$('.js-donations').html(ret.dashboard.donationsthismonth ? ret.dashboard.donationsthismonth : '0')
                self.$('.js-donationwrapper').fadeIn('slow')

                var coll = new Iznik.Collections.DateCounts(ret.dashboard.Activity)
                var graph = new Iznik.Views.DateGraph({
                  target: self.$('.js-messagegraph').get()[0],
                  data: coll,
                  title: 'Activity'
                })

                graph.render()

                if (ret.dashboard.hasOwnProperty('ApprovedMemberCount')) {
                  self.$('.js-membergraphholder').show()
                  var coll = new Iznik.Collections.DateCounts(ret.dashboard.ApprovedMemberCount)
                  var graph = new Iznik.Views.DateGraph({
                    target: self.$('.js-membergraph').get()[0],
                    data: coll,
                    title: 'Members'
                  })

                  graph.render()
                }

                // We only want to show Offer vs Wanted.
                delete ret.dashboard.MessageBreakdown.Admin
                delete ret.dashboard.MessageBreakdown.Other
                delete ret.dashboard.MessageBreakdown.Received
                delete ret.dashboard.MessageBreakdown.Taken

                // Make sure they're percentages so that people don't try to cross-reference
                // with the activity numbers, which wouldn't be possible.
                var total = ret.dashboard.MessageBreakdown.Offer + ret.dashboard.MessageBreakdown.Wanted
                ret.dashboard.MessageBreakdown.Offer = Math.round(100 * ret.dashboard.MessageBreakdown.Offer / total)
                ret.dashboard.MessageBreakdown.Wanted = Math.round(100 * ret.dashboard.MessageBreakdown.Wanted / total)

                graph = new Iznik.Views.TypeChart({
                  target: self.$('.js-balancechart').get()[0],
                  data: ret.dashboard.MessageBreakdown,
                  title: 'Message Balance'
                })

                graph.colours = [
                  'green',
                  'blue'
                ]

                graph.render()

                // Success
                // Make sure they're percentages so that people don't try to cross-reference
                // with the activity numbers, which wouldn't be possible.
                var offer = ret.dashboard.Outcomes.Offer
                var total = 0, taken = 0, offerwithdrawn = 0
                _.each(offer, function (val) {
                  total += val.count
                  if (val.outcome == 'Taken') {
                    taken = val.count
                  } else if (val.outcome == 'Withdrawn') {
                    offerwithdrawn = val.count
                  }
                })

                var data = {
                  'Taken': Math.round((100 * taken) / total),
                  'Withdrawn': Math.round((100 * offerwithdrawn) / total)
                }

                graph = new Iznik.Views.TypeChart({
                  target: self.$('.js-offeroutcome').get()[0],
                  data: data,
                  title: 'Offer Outcome'
                })

                graph.colours = [
                  'green',
                  'blue'
                ]

                graph.render()

                var wanted = ret.dashboard.Outcomes.Wanted
                var total = 0, received = 0, wantedwithdrawn = 0
                _.each(wanted, function (val) {
                  total += val.count
                  if (val.outcome == 'Received') {
                    received = val.count
                  } else if (val.outcome == 'Withdrawn') {
                    wantedwithdrawn = val.count
                  }
                })

                var data = {
                  'Received': Math.round((100 * received) / total),
                  'Withdrawn': Math.round((100 * wantedwithdrawn) / total)
                }

                graph = new Iznik.Views.TypeChart({
                  target: self.$('.js-wantedoutcome').get()[0],
                  data: data,
                  title: 'Wanted Outcome'
                })

                graph.colours = [
                  'green',
                  'blue'
                ]

                graph.render()

                // Weights - show per month.
                var months = {}
                var tonnes = 0;

                _.each(ret.dashboard.Weight, function (ent) {
                  var date = new moment(ent.date)
                  var key = date.format('01 MMM YYYY')
                  if (ent.count > 0) {
                    tonnes += ent.count;
                    if (key in months) {
                      months[key] = months[key] + ent.count
                    } else {
                      months[key] = ent.count
                    }
                  }
                })

                tonnes = Math.round(tonnes / 1000)

                // Headline stats.
                //
                // Benefit of reuse per tonne is £711 and CO2 impact is -0.51tCO2eq based on WRAP figures.
                // http://www.wrap.org.uk/content/monitoring-tools-and-resources
                self.$('.js-weight').html(tonnes.toLocaleString() + '<br />TONNES')
                self.$('.js-benefit').html('&pound;' + (Math.round(tonnes * 711)).toLocaleString() + '<br />BENEFIT')
                self.$('.js-co2').html((Math.round(tonnes * 0.51)).toLocaleString() + '<br />TONNES CO2')
                self.$('.js-stats').show();

                var data = []

                for (var key in months) {
                  data.push({
                    date: key,
                    count: months[key]
                  })
                }

                var graph = new Iznik.Views.DateBar({
                  target: self.$('.js-weightgraph').get()[0],
                  data: new Iznik.Collections.DateCounts(data),
                  title: 'Weights (kg)',
                  hAxisFormat: 'MMM yyyy'
                })

                graph.render()

                if (ret.dashboard.groupids && ret.dashboard.groupids.length > 0 && ret.dashboard.groupids.length < 30) {
                  // Add a list of the groups.
                  var promises = []
                  var mods = []
                  _.each(ret.dashboard.groupids, function (groupid) {
                    if (groupid) {
                      var mod = new Iznik.Models.Group({
                        id: groupid
                      })

                      mods.push(mod)
                      promises.push(mod.fetch())
                    }
                  })

                  Promise.all(promises).then(function () {
                    var names = []
                    _.each(mods, function (mod) {
                      names.push(mod.get('namedisplay'))
                    })
                    var list = names.join(', ')
                    self.$('.js-arealist').html(list)
                  })
                }
              }
            }
          })
        })
      })

      return (p)
    }
  })

  Iznik.Views.User.Pages.Heatmap = Iznik.Views.Page.extend({
    template: 'user_stats_heatmap',

    events: {},

    filterData: function () {
      var self = this
      var bounds = self.map.getBounds()

      var data = []

      // If the max is too high, then everything else looks idle.  So use a logarithmic scale.
      var max = 0;
      _.each(self.data, function (d) {
        max = Math.max(d.weight, max)
      })

      var minlog = Math.log10(1);
      var maxlog = Math.log10(max);
      var range = maxlog - minlog;
      var lineartolog = function(n) {
        return (Math.log10(n) - minlog) / range;
      };

      _.each(self.data, function (d) {
        if (bounds.contains(d.location)) {
          var n = _.clone(d)
          n.weight = lineartolog(n.weight)
          data.push(n)
        }
      })

      if (self.heatmap) {
        self.heatmap.setMap(null)
      }

      self.heatmap = new google.maps.visualization.HeatmapLayer({
        data: data
      })

      var zoom = self.map.getZoom()
      self.heatmap.setOptions({radius: zoom})

      self.heatmap.setMap(self.map)
    },

    render: function () {
      var self = this

      var p = Iznik.Views.Page.prototype.render.call(this)
      p.then(function () {
        var mapWidth = self.$('.js-usermap').outerWidth()
        $(self.$('.js-usermap')).css('height', mapWidth + 'px')

        var mapOptions = {
          mapTypeControl: false,
          streetViewControl: false,
          center: new google.maps.LatLng(53.9450, -2.5209),
          panControl: mapWidth > 400,
          zoomControl: mapWidth > 400,
          zoom: 6,
          maxZoom: 13
        }

        self.map = new google.maps.Map(self.$('.js-usermap').get()[0], mapOptions)

        // Searchbox
        var input = document.getElementById('pac-input')
        self.searchBox = new google.maps.places.SearchBox(input)
        self.map.controls[google.maps.ControlPosition.TOP_CENTER].push(input)

        self.map.addListener('bounds_changed', function () {
          self.searchBox.setBounds(self.map.getBounds())
        })

        self.searchBox.addListener('places_changed', function () {
          // Put the map here.
          var places = self.searchBox.getPlaces()

          if (places.length == 0) {
            return
          }

          var bounds = new google.maps.LatLngBounds()
          places.forEach(function (place) {
            if (place.geometry.viewport) {
              // Only geocodes have viewport.
              bounds.union(place.geometry.viewport)
            } else {
              bounds.extend(place.geometry.location)
            }
          })

          self.map.fitBounds(bounds)
        })

        var v = new Iznik.Views.PleaseWait({
          timeout: 1
        })
        v.render()

        $.ajax({
          url: API + 'dashboard',
          data: {
            heatmap: true
          },
          success: function (ret) {
            v.close()

            self.data = []

            if (ret.heatmap) {
              _.each(ret.heatmap, function (loc) {
                var ent = {
                  location: new google.maps.LatLng(loc.lat, loc.lng),
                  weight: loc.count
                }

                self.data.push(ent)
              })
            }

            self.filterData.call(self)

            google.maps.event.addListener(self.map, 'idle', function () {
              self.filterData()
            })
          }
        })
      })

      return (p)
    }
  })
  Iznik.Views.User.Pages.Ebay = Iznik.Views.Page.extend({
    template: 'user_stats_ebay',

    events: {},

    render: function () {
      var self = this

      var p = Iznik.Views.Page.prototype.render.call(this)
      p.then(function () {
        $.ajax({
          url: API + 'dashboard',
          data: {
            start: '2 months ago',
            grouptype: 'Freegle',
            group: 1
          },
          success: function (ret) {
            var coll = new Iznik.Collections.DateCounts(ret.dashboard.eBay)

            if (!_.isUndefined(coll)) {
              var first = coll.first()

              if (first) {
                var base = coll.first().get('count')
                var baserival = coll.first().get('rival')
                coll.each(function (s) {
                  s.set('count', s.get('count') - base)
                  s.set('rival', s.get('rival') - baserival)
                  s.set('date', s.get('timestamp'))
                })

                function apiLoaded () {
                  // Defer so that it's in the DOM - google stuff doesn't work well otherwise.
                  _.defer(function () {
                    Iznik.View.prototype.render.call(self).then(function (self) {
                      var data = new google.visualization.DataTable()
                      data.addColumn('date', 'Date')
                      data.addColumn('number', 'Count')
                      data.addColumn('number', 'Rival')
                      coll.each(function (count) {
                        if (coll.indexOf(count) < coll.length) {
                          data.addRow([new Date(count.get('date')), parseInt(count.get('count'), 10), parseInt(count.get('rival'), 10)])
                        }
                      })

                      var formatter = new google.visualization.DateFormat({formatType: 'yy-M-d H'})
                      formatter.format(data, 1)

                      self.chart = new google.visualization.LineChart(self.$('.js-graph').get()[0])
                      self.data = data
                      self.chartOptions = {
                        title: 'eBay Voting',
                        interpolateNulls: false,
                        animation: {
                          duration: 5000,
                          easing: 'out',
                          startup: true
                        },
                        legend: {position: 'none'},
                        chartArea: {'width': '80%', 'height': '80%'},
                        vAxis: {viewWindow: {min: 0}},
                        hAxis: {
                          format: 'dd MMM'
                        },
                        series: {
                          0: {color: 'darkgreen'},
                          1: {color: 'red'}
                        }
                      }
                      self.chart.draw(self.data, self.chartOptions)
                    })
                  })
                }

                google.charts.load('current', {packages: ['corechart', 'annotationchart']})
                google.charts.setOnLoadCallback(apiLoaded)
              }
            }
          }
        })
      })

      return (p)
    }
  })

  Iznik.Views.User.Pages.Authorities = Iznik.Views.Page.extend({
    template: 'user_stats_authorities',

    authoritySource: function (query, syncResults, asyncResults) {
      var self = this

      $.ajax({
        type: 'GET',
        url: API + 'authority',
        data: {
          search: query
        }, success: function (ret) {
          var matches = []
          _.each(ret.authorities, function (authority) {
            matches.push(authority)
          })

          asyncResults(matches)
        }
      })
    },

    suggestion: function (s) {
      var tpl = _.template('<div><span><%-name%></span>&nbsp;<span class="small faded"><%-area_code%></div></div>')
      var html = tpl(s)
      return (html)
    },

    render: function () {
      var self = this

      var p = Iznik.Views.Page.prototype.render.call(this)
      p.then(function () {
        self.$('.js-search').typeahead({
          minLength: 3,
          highlight: true
        }, {
          name: 'authority',
          limit: 10,
          source: _.bind(self.authoritySource, self),
          display: function (s) {
            return s.name
          },
          templates: {
            suggestion: self.suggestion,
            limit: 50
          }
        })

        self.$('.js-search').bind('typeahead:select', function (ev, suggestion) {
          Router.navigate('/stats/authority/' + suggestion.id, true)
        })
      })

      return (p)
    }
  })

  Iznik.Views.User.Pages.StatsAuthority = Iznik.Views.Page.extend({
    template: 'user_stats_authority',

    start: '12 months ago',
    end: 'today',

    naked: true,

    events: {
      'click .js-fetchstats': 'newStatsRange'
    },

    newStatsRange: function() {
      var self = this;

      self.wait = new Iznik.Views.PleaseWait()
      self.wait.closeAfter = 600000
      self.wait.render()

      self.coll = new Iznik.Collection()
      self.start = (new moment(self.$('.js-startyear').val() + '-' + self.$('.js-startmonth').val() + '-01')).format('YYYY-MM-DD');
      self.end = self.$('.js-endyear').val() + '-' + self.$('.js-endmonth').val() + '-01';
      var m = new moment(self.end);
      m.add(1, 'months')
      m.date(1);
      m.subtract(1, 'days');
      self.end = m.format('YYYY-MM-DD');
      self.groupIndex = 0;
      self.getGroupStats();
    },

    resize: function (e) {
      var mapWidth = $(e.target).outerWidth()
      $(e.target).css('height', mapWidth + 'px')
      google.maps.event.trigger(this.map, 'resize')
    },

    groupIndex: 0,

    getGroupStats: function () {
      var self = this
      var group = self.groups[self.groupIndex++]

      var g = new Iznik.Models.Group({
        id: group.id
      })

      g.fetch().then(function () {
        self.coll.add(g)

        $.ajax({
          url: API + 'dashboard',
          data: {
            start: self.start,
            end: self.end,
            grouptype: 'Freegle',
            group: group.id
          },
          success: function (ret) {
            var g = self.coll.get(group.id)
            g.set('dashboard', ret.dashboard)

            if (self.groupIndex < self.groups.length) {
              self.getGroupStats()
            } else {
              self.showGroupStats()
            }
          }
        })
      })
    },

    showGroupStats: function () {
      var self = this

      self.coll.comparator = function (mod) {
        return (mod.get('namedisplay').toLowerCase())
      }

      self.coll.sort()

      // Find the range we cover - we want to exclude the last month as it will
      // be partial.
      var date = new Date()
      var firstDay = (new Date(date.getFullYear(), date.getMonth(), 1)).getTime()
      var firstdate = null
      var firsttime = null
      var someoverlaps = false
      var lastdate = null
      var lasttime = null

      self.coll.each(function (g) {
        var dash = g.get('dashboard')
        _.each(dash.Weight, function (w) {
          if (!firsttime || firsttime > (new moment(w.date)).unix()) {
            var m = new moment(w.date)
            firstdate = m.format('MMM YYYY')
            firsttime = m.unix()
          }

          if ((new Date(w.date)).getTime() < firstDay && (!lasttime || lasttime < (new moment(w.date)).unix())) {
            var m = new moment(w.date)
            lastdate = m.format('MMM YYYY')
            lasttime = m.unix()
          }
        })
      })

      var totalweight = 0
      var totalmembers = 0
      var totaloutcomes = 0
      var monthweights = {}
      var monthmembers = {}

      self.$('.js-grouptableremove').remove()

      self.coll.each(function (g) {
        var dashboard = g.get('dashboard')
        var overlap = 0
        var gs = self.model.get('groups')
        _.each(gs, function (s) {
          if (s.id == g.get('id') && s.overlap) {
            overlap = s.overlap
          }
        })

        var total = 0
        var unweighted = 0
        var members = 0

        _.each(dashboard.OutcomesPerMonth, function (o) {
          totaloutcomes += o.count * overlap
        })

        _.each(dashboard.Weight, function (w) {
          if ((new Date(w.date)).getTime() < firstDay) {
            var date = new moment(w.date)
            var key = date.format('01 MMM YYYY')

            total += w.count * overlap
            unweighted += w.count

            if (key in monthweights) {
              monthweights[key] += w.count * overlap
            } else {
              monthweights[key] = w.count * overlap
            }
          }
        })

        var maxmembers = 0
        var maxunweighted = 0

        _.each(dashboard.ApprovedMemberCount, function (w) {
          if ((new Date(w.date)).getTime() < firstDay) {
            var members = Math.round(w.count * overlap)
            maxmembers = Math.max(members, maxmembers)
            var unweighted = Math.round(w.count)
            maxunweighted = Math.max(unweighted, maxunweighted)

            var date = new moment(w.date)
            var key = date.format('01 MMM YYYY')

            if (key in monthmembers) {
              if (g.get('id') in monthmembers[key]) {
                monthmembers[key][g.get('id')] = Math.max(monthmembers[key][g.get('id')], members)
              } else {
                monthmembers[key][g.get('id')] = members
              }
            } else {
              monthmembers[key] = []
              monthmembers[key][g.get('id')] = members
            }
          }
        })

        var avgweight = Math.round(total / 12)
        var avgunweighted = Math.round(unweighted / 12)

        totalweight += total
        totalmembers += maxmembers

        var overlapstr = ''

        if (avgweight > 0) {
          if (overlap < 1) {
            overlapstr = ' *'
            someoverlaps = true
            self.$('.js-grouptable').append('<tr class="js-grouptableremove"><td>' + g.get('namedisplay') + ' *</td><td>' + maxmembers.toLocaleString() + ' <span class="text-muted">(of ' + maxunweighted.toLocaleString() + ')</span></td><td>' + avgweight.toLocaleString() + ' <span class="text-muted">(of ' + avgunweighted.toLocaleString() + ')</span></td></tr>')
          } else {
            self.$('.js-grouptable').append('<tr class="js-grouptableremove"><td>' + g.get('namedisplay') + '</td><td>' + maxmembers.toLocaleString() + '</td><td>' + avgweight.toLocaleString() + '</td></tr>')
          }
        }
      })

      var tonnes = Math.round(totalweight / 100) / 10

      self.$('.js-grouptable').append('<tr class="js-grouptableremove"><td><b>Totals</b></td><td><b>' + totalmembers.toLocaleString() + '</b></td><td><b>' + Math.round(totalweight / 12).toLocaleString() + 'kg (' + (Math.round(totalweight / 12 / 100) / 10) + ' tonnes) monthly</b></td></tr>')

      // Headline stats.
      //
      // Benefit of reuse per tonne is £711 and CO2 impact is -0.51tCO2eq based on WRAP figures.
      // http://www.wrap.org.uk/content/monitoring-tools-and-resources
      self.$('.js-weight').html(tonnes.toLocaleString() + '<br />TONNES')
      self.$('.js-benefit').html('&pound;' + (Math.round(tonnes * 711)).toLocaleString() + '<br />BENEFIT')
      self.$('.js-co2').html((tonnes * 0.51).toLocaleString() + '<br />TONNES CO2')
      self.$('.js-outcomes').html(Math.round(totaloutcomes).toLocaleString() + '<br />GIFTS MADE')
      self.$('.js-weightnobr').html(tonnes.toLocaleString() + ' TONNES REUSED')
      self.$('.js-groupcount').html(self.groups.length.toLocaleString() + '<br />COMMUNITIES')
      self.$('.js-membercount').html(totalmembers.toLocaleString() + '<br />MEMBERS')
      self.$('.js-firstdate').html(firstdate.toUpperCase())
      self.$('.js-lastdate').html(lastdate.toUpperCase())
      self.$('.js-lastdatelc').html(lastdate)

      // Weight chart
      var data = []

      for (var key in monthweights) {
        data.push({
          date: key,
          count: monthweights[key]
        })
      }

      var graph = new Iznik.Views.DateBar({
        target: self.$('.js-weightchart').get()[0],
        data: new Iznik.Collections.DateCounts(data),
        hAxisFormat: 'MMM yyyy',
        width: '100%',
        height: '200px',
        chartArea: null
      })

      graph.render()

      // Member chart
      var data = []

      for (var key in monthmembers) {
        var total = 0

        for (var groupid in monthmembers[key]) {
          total += monthmembers[key][groupid]
        }

        data.push({
          date: key,
          count: total
        })
      }

      var graph = new Iznik.Views.DateGraph({
        target: self.$('.js-memberchart').get()[0],
        data: new Iznik.Collections.DateCounts(data),
        hAxisFormat: 'MMM yyyy',
        width: '100%',
        height: '200px',
        chartArea: null
      })

      graph.render()

      if (someoverlaps) {
        self.$('.js-partial').show()
      }

      // We're done
      self.wait.close()

      self.$('.js-stats').fadeIn('slow')

      if (self.coll.length < 30) {
        self.$('.js-groups').show()
      }
    },

    render: function () {
      var self = this

      self.model = new Iznik.Models.Authority({
        id: self.options.id
      })

      var p = self.model.fetch()
      p.then(function () {
        Iznik.Views.Page.prototype.render.call(self, {
          model: self.mode
        }).then(function () {
          // Add the date selectors.
          var curryear = (new Date()).getFullYear();
          var currmonth = (new Date()).getMonth();

          _.each(['.js-startmonth', '.js-endmonth'], function(sel) {
            $(sel).empty();
            for (var mon = 1; mon <=12; mon++) {
              const date = new Date(2009, mon - 1, 1);
              const month = date.toLocaleString('en-uk', { month: 'short' });
              $(sel).append('<option value="' + mon + '">' + month + "</option>")
            }
          });

          _.each(['.js-startyear', '.js-endyear'], function(sel) {
            $(sel).empty();
            for (var year = 2015; year <= curryear; year++) {
              $(sel).append('<option value="' + year + '">' + (year+'').substring(2) + "</option>")
            }
          });

          // Default to last year.
          if (currmonth === 0) {
            self.$('.js-startyear').val(curryear - 2);
            self.$('.js-endyear').val(curryear - 1);

            self.$('.js-startmonth').val(1);
            self.$('.js-endmonth').val(12);
          } else {
            self.$('.js-startyear').val(curryear - 1);
            self.$('.js-endyear').val(curryear);

            self.$('.js-startmonth').val(currmonth + 1);
            self.$('.js-endmonth').val(currmonth);
          }

          self.waitDOM(self, function () {
            try {
              $('nav').slideUp('slow')

              var target = self.$('.js-map')

              var mapWidth = target.outerWidth()
              target.css('height', Math.min(400, mapWidth) + 'px')

              var centre = self.model.get('centre')

              var mapOptions = {
                mapTypeControl: false,
                streetViewControl: false,
                center: new google.maps.LatLng(centre.lat, centre.lng),
                panControl: mapWidth > 400,
                zoomControl: mapWidth > 400,
                zoom: 7
              }

              self.map = new google.maps.Map(target.get()[0], mapOptions)

              google.maps.event.addDomListener(window, 'resize', _.bind(self.resize, self))
              google.maps.event.addDomListener(window, 'load', _.bind(self.resize, self))

              // Add a polygon for the area.
              require(['wicket-gmap3', 'wicket'], function (gm, Wkt) {
                // No getBounds on polygon by default.
                google.maps.Polygon.prototype.getBounds = function () {
                  var bounds = new google.maps.LatLngBounds()
                  var paths = this.getPaths()
                  var path
                  for (var i = 0; i < paths.getLength(); i++) {
                    path = paths.getAt(i)
                    for (var ii = 0; ii < path.getLength(); ii++) {
                      bounds.extend(path.getAt(ii))
                    }
                  }
                  return bounds
                }

                var wkt = new Wkt.Wkt()
                var wktstr = self.model.get('polygon')
                wkt.read(wktstr)

                var obj = wkt.toObject(self.map.defaults) // Make an object

                if (obj) {
                  var options = {
                    fillColor: 'blue',
                    strokeWeight: 0,
                    fillOpacity: 0.1
                  }

                  // This might be a multipolygon.
                  var bounds = new google.maps.LatLngBounds()

                  if (_.isArray(obj)) {
                    _.each(obj, function (ent) {
                      ent.setMap(self.map)
                      ent.setOptions(options)
                      var thisbounds = ent.getBounds()
                      bounds.extend(thisbounds.getNorthEast())
                      bounds.extend(thisbounds.getSouthWest())
                    })
                  } else {
                    obj.setMap(self.map)
                    obj.setOptions(options)
                    bounds = obj.getBounds()
                  }

                  // Zoom the map to show the authority
                  var mapDim = {
                    height: self.$('.js-map').height(),
                    width: self.$('.js-map').width()
                  }

                  var zoom = Iznik.getBoundsZoomLevel(bounds, mapDim)
                  self.map.setZoom(zoom)

                  // Add the group markers.
                  self.groups = self.model.get('groups')

                  if (self.groups.length === 0) {
                    self.$('.js-nogroup').fadeIn('slow')
                    self.wait.close()
                  } else {
                    _.each(self.groups, function (group) {
                      var icon = '/images/mapmarkerbrightgreen.gif'
                      var marker = new google.maps.Marker({
                        position: new google.maps.LatLng(group.lat, group.lng),
                        icon: icon,
                        title: group.namedisplay,
                        map: self.map
                      })

                      if (self.groups.length < 10) {
                        // If not too many, shade them.
                        var wkt = new Wkt.Wkt()
                        var wktstr = group.poly

                        try {
                          wkt.read(wktstr)
                        } catch (e1) {
                          try {
                            self.Wkt.read(wktstr.replace('\n', '').replace('\r', '').replace('\t', ''))
                          } catch (e2) {
                          }
                        }

                        obj = wkt.toObject(self.map.defaults)
                        obj.setMap(self.map)
                        obj.setOptions({
                          fillColor: 'grey',
                          strokeWeight: 0,
                          fillOpacity: 0.1
                        })
                      }
                    })

                    self.newStatsRange()
                  }
                }
              })
            } catch (e) {
              self.wait.close()
              self.$('.js-error').fadeIn('slow')
            }
          })
        })
      })

      return (p)
    }
  })
})

