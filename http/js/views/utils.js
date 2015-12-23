Iznik.Collections.DateCounts = IznikCollection.extend({});

Iznik.Views.MessageGraph = IznikView.extend({
    template: 'utils_message_graph',

    render: function() {
        var self = this;

        // Defer so that it's in the DOM - google stuff doesn't work well otherwise.
        _.defer(function() {
            self.$el.html(window.template(self.template)());

            var data = new google.visualization.DataTable();
            data.addColumn('date', 'Date');
            data.addColumn('number', 'Messages');
            self.options.data.each(function(count){
                // Don't show today's value, because it may be very low early in the day, which
                // will confuse people.
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
                legend: {position: 'none'},
                chartArea: {'width': '80%', 'height': '80%'},
                hAxis: {
                    format: 'dd MMM'
                },
                series: {
                    0: {color: 'blue'}
                }
            };
            self.chart.draw(self.data, self.chartOptions);
        });
    }
});

Iznik.Views.TypeChart = IznikView.extend({
    template: 'utils_type_chart',

    render: function() {
        var self = this;

        // Defer so that it's in the DOM - google stuff doesn't work well otherwise.
        _.defer(function() {
            self.$el.html(window.template(self.template)());
            var arr = [['Type', 'Count']];

            self.options.data.each(function(count) {
                arr.push([count.get('type'), count.get('count')]);
            });

            self.data = google.visualization.arrayToDataTable(arr);
            self.chart = new google.visualization.PieChart(self.options.target);
            self.chartOptions = {
                title: self.options.title,
                chartArea: {'width': '80%', 'height': '80%'},
                colors: [
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
                ],
                slices2: {
                    1: {offset: 0.2},
                    2: {offset: 0.2}
                }
            };
            self.chart.draw(self.data, self.chartOptions);
        });
    }
});

Iznik.Views.DeliveryChart = IznikView.extend({
    template: 'utils_delivery_chart',

    render: function() {
        var self = this;

        // Defer so that it's in the DOM - google stuff doesn't work well otherwise.
        _.defer(function() {
            self.$el.html(window.template(self.template)());
            var arr = [['Email Delivery', 'Count']];

            self.options.data.each(function(count) {
                arr.push([count.get('yahooDeliveryType'), count.get('count')]);
            });

            self.data = google.visualization.arrayToDataTable(arr);
            self.chart = new google.visualization.PieChart(self.options.target);
            self.chartOptions = {
                title: self.options.title,
                chartArea: {'width': '80%', 'height': '80%'},
                colors: [
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
                ],
                slices2: {
                    1: {offset: 0.2},
                    2: {offset: 0.2}
                }
            };
            self.chart.draw(self.data, self.chartOptions);
        });
    }
});

Iznik.Views.DomainChart = IznikView.extend({
    template: 'utils_domain_chart',

    render: function() {
        var self = this;

        // Defer so that it's in the DOM - google stuff doesn't work well otherwise.
        _.defer(function() {
            self.$el.html(window.template(self.template)());
            var arr = [['Domain', 'Count']];

            self.options.data.each(function(count) {
                arr.push([count.get('domain'), count.get('count')]);
            });

            self.data = google.visualization.arrayToDataTable(arr);
            self.chart = new google.visualization.PieChart(self.options.target);
            self.chartOptions = {
                title: self.options.title,
                chartArea: {'width': '80%', 'height': '80%'},
                colors: [
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
                ],
                slices2: {
                    1: {offset: 0.2},
                    2: {offset: 0.2}
                }
            };
            self.chart.draw(self.data, self.chartOptions);
        });
    }
});

Iznik.Views.SourceChart = IznikView.extend({
    template: 'utils_source_chart',

    render: function() {
        var self = this;

        // Defer so that it's in the DOM - google stuff doesn't work well otherwise.
        _.defer(function() {
            self.$el.html(window.template(self.template)());
            var arr = [['Source', 'Count']];

            self.options.data.each(function(count) {
                arr.push([count.get('source'), count.get('count')]);
            });

            self.data = google.visualization.arrayToDataTable(arr);
            self.chart = new google.visualization.PieChart(self.options.target);
            self.chartOptions = {
                title: self.options.title,
                chartArea: {'width': '80%', 'height': '80%'},
                colors: [
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
                ],
                slices2: {
                    1: {offset: 0.2},
                    2: {offset: 0.2}
                }
            };
            self.chart.draw(self.data, self.chartOptions);
        });
    }
});
