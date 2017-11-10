define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base'
], function($, _, Backbone, Iznik) {
    Iznik.Collections.DateCounts = Iznik.Collection.extend({
        comparator: function(a, b) {
            var ret = (new Date(a.get('date'))).getTime() - (new Date(b.get('date'))).getTime();
            return(ret);
        }
    });

    var leftColours = [
        '#8e0152',
        '#c51b7d',
        '#de77ae',
        '#f1b6da',
        '#fde0ef',
        '#e6f5d0',
        '#b8e186',
        '#7fbc41',
        '#4d9221',
        '#276419'
    ];

    var rightColours = [
        '#40004b',
        '#762a83',
        '#9970ab',
        '#c2a5cf',
        '#e7d4e8',
        '#d9f0d3',
        '#a6dba0',
        '#5aae61',
        '#1b7837',
        '#00441b'
    ];

    Iznik.Views.DateGraph = Iznik.View.extend({
        template: 'dashboard_message',

        render: function () {
            var self = this;

            function apiLoaded() {
                // Defer so that it's in the DOM - google stuff doesn't work well otherwise.
                _.defer(function () {
                    Iznik.View.prototype.render.call(self).then(function(self) {
                        var data = new google.visualization.DataTable();
                        data.addColumn('date', 'Date');
                        data.addColumn('number', 'Count');
                        self.options.data.each(function (count) {
                            if (self.options.data.indexOf(count) < self.options.data.length) {

                                data.addRow([new Date(count.get('date')), parseInt(count.get('count'), 10)]);
                            }
                        });

                        var formatter = new google.visualization.DateFormat({formatType: 'yy-M-d H'});
                        formatter.format(data, 1);

                        self.chart = new google.visualization.LineChart(self.options.target);
                        self.data = data;
                        self.chartOptions = {
                            title: self.options.title,
                            interpolateNulls: false,
                            animation: {
                                duration: 5000,
                                easing: 'out',
                                startup: true
                            },
                            legend: {position: 'none'},
                            chartArea: self.options.hasOwnProperty('chartArea') ? self.options.chartArea : {'width': '80%', 'height': '80%'},
                            vAxis: {viewWindow: {min: 0}},
                            hAxis: {
                                format: self.options.hAxisFormat ? self.options.hAxisFormat : 'dd MMM'
                            },
                            series: {
                                0: {color: 'blue'}
                            }
                        };

                        if (self.options.width) {
                            self.chartOptions.width = self.options.width;
                        }

                        if (self.options.height) {
                            self.chartOptions.height = self.options.height;
                        }

                        self.chart.draw(self.data, self.chartOptions);
                    });
                });
            }

            google.load('visualization', '1.0', {
                'packages':['corechart', 'annotationchart'],
                'callback': apiLoaded
            });
        }
    });

    Iznik.Views.DateBar = Iznik.View.extend({
        template: 'dashboard_message',

        render: function () {
            var self = this;

            function apiLoaded() {
                // Defer so that it's in the DOM - google stuff doesn't work well otherwise.
                _.defer(function () {
                    Iznik.View.prototype.render.call(self).then(function(self) {
                        var data = new google.visualization.DataTable();
                        data.addColumn('date', 'Date');
                        data.addColumn('number', 'Count');
                        self.options.data.each(function (count) {
                            if (self.options.data.indexOf(count) < self.options.data.length) {
                                data.addRow([new Date(count.get('date')), parseInt(count.get('count'), 10)]);
                            }
                        });

                        self.chart = new google.visualization.ColumnChart(self.options.target);
                        self.data = data;
                        self.chartOptions = {
                            title: self.options.title,
                            interpolateNulls: false,
                            animation: {
                                duration: 5000,
                                easing: 'out',
                                startup: true
                            },
                            legend: {position: 'none'},
                            chartArea: self.options.hasOwnProperty('chartArea') ? self.options.chartArea : {'width': '80%', 'height': '80%'},
                            bar: { groupWidth: "100%" },
                            vAxis: {viewWindow: {min: 0}},
                            hAxis: {
                                format: self.options.hAxisFormat ? self.options.hAxisFormat : 'dd MMM'
                            },
                            series: {
                                0: {color: 'darkgreen'}
                            }
                        };
                        self.chart.draw(self.data, self.chartOptions);
                    });
                });
            }

            google.load('visualization', '1.0', {
                'packages':['corechart', 'annotationchart'],
                'callback': apiLoaded
            });
        }
    });

    Iznik.Views.TypeChart = Iznik.View.extend({
        template: 'dashboard_type',

        render: function () {
            var self = this;

            function apiLoaded() {
                // Defer so that it's in the DOM - google stuff doesn't work well otherwise.
                _.defer(function () {
                    Iznik.View.prototype.render.call(self).then(function(self) {
                        var arr = [['Type', 'Count']];

                        _.each(self.options.data, function (count, key) {
                            arr.push([key, count]);
                        });

                        self.data = google.visualization.arrayToDataTable(arr);
                        self.chart = new google.visualization.PieChart(self.options.target);
                        self.chartOptions = {
                            title: self.options.title,
                            chartArea: self.options.hasOwnProperty('chartArea') ? self.options.chartArea : {'width': '80%', 'height': '80%'},
                            colors: self.colours ? self.colours : leftColours,
                            slices2: {
                                1: {offset: 0.2},
                                2: {offset: 0.2}
                            }
                        };
                        self.chart.draw(self.data, self.chartOptions);
                    });
                });
            }

            google.load('visualization', '1.0', {
                'packages':['corechart', 'annotationchart'],
                'callback': apiLoaded
            });
        }
    });

    Iznik.Views.StackGraph = Iznik.View.extend({
        template: 'dashboard_stack',

        render: function () {
            var self = this;

            function apiLoaded() {
                // Defer so that it's in the DOM - google stuff doesn't work well otherwise.
                _.defer(function () {
                    Iznik.View.prototype.render.call(self).then(function(self) {
                        var arr = [['Date']];

                        for (var i = 0; i < self.options.data.length; i++) {
                            arr[0].push(self.options.data[i].tag);
                        }

                        var colours = [];
                        _.each(self.options.data, function(row) {
                            colours.push({color: row.colour});
                        });

                        var bydate = {};
                        _.each(self.options.data, function (row) {
                            row.data.each(function(point) {
                                if (!bydate[point.get('date')]) {
                                    bydate[point.get('date')] = {};
                                }

                                bydate[point.get('date')][row.tag] = point.get('count');
                            });
                        });

                        _.each(bydate, function(counts, thedate) {
                            var line = [ thedate ];
                            _.each(counts, function(count) {
                                line.push(count);
                            });

                            arr.push(line);
                        });

                        self.data = google.visualization.arrayToDataTable(arr);
                        self.chart = new google.visualization.ColumnChart(self.options.target);
                        self.chartOptions = {
                            title: self.options.title,
                            chartArea: self.options.hasOwnProperty('chartArea') ? self.options.chartArea : {'width': '80%', 'height': '80%'},
                            isStacked: 'percent',
                            legend: 'none',
                            series: colours
                        };
                        self.chart.draw(self.data, self.chartOptions);
                    });
                });
            }

            google.load('visualization', '1.0', {
                'packages':['corechart', 'annotationchart'],
                'callback': apiLoaded
            });
        }
    });

    Iznik.Views.DeliveryChart = Iznik.View.extend({
        template: 'dashboard_delivery',

        render: function () {
            var self = this;

            function apiLoaded() {
                // Defer so that it's in the DOM - google stuff doesn't work well otherwise.
                _.defer(function () {
                    Iznik.View.prototype.render.call(self).then(function (self) {
                        var arr = [['Email Delivery', 'Count']];

                        _.each(self.options.data, function (count, key) {
                            arr.push([key, count]);
                        });

                        self.data = google.visualization.arrayToDataTable(arr);
                        self.chart = new google.visualization.PieChart(self.options.target);
                        self.chartOptions = {
                            title: self.options.title,
                            chartArea: self.options.hasOwnProperty('chartArea') ? self.options.chartArea : {'width': '80%', 'height': '80%'},
                            colors: leftColours,
                            slices2: {
                                1: {offset: 0.2},
                                2: {offset: 0.2}
                            }
                        };
                        self.chart.draw(self.data, self.chartOptions);
                    });
                });
            }

            google.load('visualization', '1.0', {
                'packages':['corechart', 'annotationchart'],
                'callback': apiLoaded
            });
        }
    });

    Iznik.Views.PostingChart = Iznik.View.extend({
        template: 'dashboard_posting',

        render: function () {
            var self = this;

            function apiLoaded() {
                // Defer so that it's in the DOM - google stuff doesn't work well otherwise.
                _.defer(function () {
                    Iznik.View.prototype.render.call(self).then(function (self) {
                        var arr = [['Posting Status', 'Count']];

                        _.each(self.options.data, function (count, key) {
                            arr.push([key, count]);
                        });

                        self.data = google.visualization.arrayToDataTable(arr);
                        self.chart = new google.visualization.PieChart(self.options.target);
                        self.chartOptions = {
                            title: self.options.title,
                            chartArea: self.options.hasOwnProperty('chartArea') ? self.options.chartArea : {'width': '80%', 'height': '80%'},
                            colors: leftColours,
                            slices2: {
                                1: {offset: 0.2},
                                2: {offset: 0.2}
                            }
                        };
                        self.chart.draw(self.data, self.chartOptions);
                    });
                });
            }

            google.load('visualization', '1.0', {
                'packages':['corechart', 'annotationchart'],
                'callback': apiLoaded
            });
        }
    });

    Iznik.Views.SourceChart = Iznik.View.extend({
        template: 'dashboard_source',

        render: function () {
            var self = this;

            function apiLoaded() {
                // Defer so that it's in the DOM - google stuff doesn't work well otherwise.
                _.defer(function () {
                    Iznik.View.prototype.render.call(self).then(function (self) {
                        var arr = [['Source', 'Count']];

                        _.each(self.options.data, function (count, key) {
                            arr.push([key, count]);
                        });

                        self.data = google.visualization.arrayToDataTable(arr);
                        self.chart = new google.visualization.PieChart(self.options.target);
                        self.chartOptions = {
                            title: self.options.title,
                            chartArea: self.options.hasOwnProperty('chartArea') ? self.options.chartArea : {'width': '80%', 'height': '80%'},
                            colors: leftColours,
                            slices2: {
                                1: {offset: 0.2},
                                2: {offset: 0.2}
                            }
                        };
                        self.chart.draw(self.data, self.chartOptions);
                    });
                });
            }

            google.load('visualization', '1.0', {
                'packages':['corechart', 'annotationchart'],
                'callback': apiLoaded
            });
        }
    });
});