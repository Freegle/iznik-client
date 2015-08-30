$.validator.addMethod('mindate',function(v,el,self){
    console.log("Validate dates", self.dates);
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

