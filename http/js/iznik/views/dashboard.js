define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base'
], function($, _, Backbone, Iznik) {
    Iznik.Collections.DateCounts = Iznik.Collection.extend({});

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
                            if (self.options.data.indexOf(count) < self.options.data.length - 1) {

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
                            chartArea: {'width': '80%', 'height': '80%'},
                            vAxis: {viewWindow: {min: 0}},
                            hAxis: {
                                format: 'dd MMM'
                            },
                            series: {
                                0: {color: 'blue'}
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
                            chartArea: {'width': '80%', 'height': '80%'},
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
                            chartArea: {'width': '80%', 'height': '80%'},
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
                            chartArea: {'width': '80%', 'height': '80%'},
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
                            chartArea: {'width': '80%', 'height': '80%'},
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