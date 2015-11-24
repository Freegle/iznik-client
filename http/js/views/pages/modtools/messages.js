Iznik.Views.ModTools.Message.Photo = IznikView.extend({
    tagName: 'li',

    template: 'modtools_message_photo',

    events: {
        'click .js-img': 'click'
    },

    click: function(e) {
        e.preventDefault();
        e.stopPropagation();

        var v = new Iznik.Views.Modal({
            model: this.model
        });

        v.open('modtools_message_photozoom');
    }
});

Iznik.Views.ModTools.StdMessage.Modal = Iznik.Views.Modal.extend({
    recentDays: 31,

    keywordList: ['Offer', 'Taken', 'Wanted', 'Received', 'Other'],

    expand: function() {
        this.$el.html(window.template(this.template)(this.model.toJSON2()));

        // Apply standard message settings
        var stdmsg = this.options.stdmsg.attributes;
        var config = this.options.config.attributes;

        var subj = this.model.get('subject');
        this.$('.js-subject').val((stdmsg.subjpref ? stdmsg.subjpref : 'Re') +
        ': ' + subj +
        (stdmsg.subjsuff ? stdmsg.subjsuff : ''));
        this.$('.js-myname').html(Iznik.Session.get('me').displayname);

        // Quote original message.
        var msg = this.model.get('textbody');
        msg = '> ' + msg.replace(/((\r\n)|\r|\n)/gm, '\n> ');

        // Add text
        msg = (stdmsg.body ? (stdmsg.body + '\n\n') : '') + msg;

        // Expand substitution strings
        msg = this.substitutionStrings(msg, this.model.attributes, config, this.model.get('groups')[0]);

        // Put it in
        this.$('.js-text').val(msg);

        this.open(null);
        $('.modal').on('shown.bs.modal', function () {
            $('.modal .js-text').focus();
        });
    },

    substitutionStrings: function(text, message, config, group) {
        var self = this;

        text = text.replace(/\$networkname/g, config.network);

        text = text.replace(/\$groupname/g, group.nameshort);
        text = text.replace(/\$groupnonetwork/g, group.nameshort.replace(config.network, ''));
        text = text.replace(/\$owneremail/g, group.nameshort + "-owner@yahoogroups.com");
        text = text.replace(/\$groupemail/g, group.nameshort + "@yahoogroups.com");
        text = text.replace(/\$groupurl/g, "https://groups.yahoo.com/neo/groups/" + group.nameshort + "/info");
        text = text.replace(/\$myname/g, Iznik.Session.get('me').displayname);
        text = text.replace(/\$nummembers/g, group.membercount);
        text = text.replace(/\$nummods/g, group.nummods);

        text = text.replace(/\$origsubj/g, message.subject);

        var history = message.fromuser.messagehistory;
        var recentmsg = '';
        var count = 0;
        _.each(history, function(msg) {
            if (msg.daysago < self.recentDays) {
                recentmsg += moment(msg.date).format('lll') + ' - ' + msg.subject + "\n";
                count++;
            }
        })
        text = text.replace(/\$recentmsg/gim, recentmsg);
        text = text.replace(/\$numrecentmsg/gim, count);

        _.each(this.keywordList, function(keyword) {
            var recentmsg = '';
            var count = 0;
            _.each(history, function(msg) {
                if (msg.type == keyword && msg.daysago < self.recentDays) {
                    recentmsg += moment(msg.date).format('lll') + ' - ' + msg.subject + "\n";
                    count++;
                }
            })

            text = text.replace(new RegExp('\\$recent' + keyword.toLowerCase(), 'gim'),recentmsg);
            text = text.replace(new RegExp('\\$numrecent' + keyword.toLowerCase(), 'gim'), count);
        })

        //if (message.hasOwnProperty('comment')) {
        //    text = text.replace(/\$memberreason/g, message['comment'].trim());
        // TODO }

        // TODO $otherapplied

        text = text.replace(/\$membermail/g, message.fromaddr);
        var from = message.fromuser.hasOwnProperty('realemail') ? message.fromuser.realemail : message.fromaddr;
        var fromid = from.substring(0, from.indexOf('@'));
        text = text.replace(/\$memberid/g, fromid);

        //if (message['headerdate']) {
        //    text = text.replace(/\$membersubdate/g, formatDate(message['headerdate'], false, false));
        //}
        //
        //if (message['duplicates']) {
        //    var summ = '';
        //
        //    for (var m in message['duplicates']) {
        //        var cmsg = message['duplicates'][m]['msg'];
        //        summ += "#" + cmsg['id'] + ": " + formatDate(cmsg['date'], false, false) + " - " + cmsg['subject'] + "\n";
        //    }
        //
        //    var regex = new RegExp("\\$duplicatemessages", "gim");
        //    text = text.replace(regex, summ);
        //}

        return(text);
    }
});

