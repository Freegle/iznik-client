require(['jquery.validate.min'], function() {
    $.validator.addMethod('mindate',function(v,el,self){
        for (var i = 0; i < self.dates.length; i++) {
            var start = new Date(self.dates[i].getStart());
            var end = new Date(self.dates[i].getEnd());
            console.log("Compare", start, end, end.getTime() <= start.getTime(), start.getTime() < (new Date()).getTime());
            if (end.getTime() <= start.getTime() ||
                start.getTime() < (new Date()).getTime()) {
                console.log("Bad date");
                return(false);
            }
        }

        return(true);
    }, 'Please use dates in the future and end dates after the start date.');

    $.validator.addMethod(
        "ourPostcode",
        function(value, element, self) {
            console.log("Validate postcode", value, element, self);
            var response = false;

            $.ajax({
                type: 'GET',
                url: API + 'locations',
                async: false,
                data: {
                    typeahead: value
                }, success: function(ret) {
                    if (ret.ret == 0 && ret.locations.length > 0) {
                        self.locationid = ret.locations[0].id;
                        response = true;
                    }
                }
            });
            return response;
        },
        "Please use a valid UK postcode, including the space."
    );
});
