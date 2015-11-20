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
        console.log("Substitute", text, message, config, group);
        text = text.replace(/\$groupname/g, group.nameshort);
        text = text.replace(/\$networkname/g, config.network);
        text = text.replace(/\$groupnonetwork/g, group.nameshort.replace(config.network, ''));

        text = text.replace(/\$owneremail/g, group.nameshort + "-owner@yahoogroups.com");
        text = text.replace(/\$groupemail/g, group.nameshort + "@yahoogroups.com");
        text = text.replace(/\$groupurl/g, "https://groups.yahoo.com/neo/groups/" + group.nameshort + "/info");
        text = text.replace(/\$myname/g, Iznik.Session.get('me').displayname);
        text = text.replace(/\$nummembers/g, group.membercount);
        text = text.replace(/\$origsubj/g, message.subject);

        //if (message.hasOwnProperty('comment')) {
        //    text = text.replace(/\$memberreason/g, message['comment'].trim());
        // TODO }

        // TODO $otherapplied

        // TODO var from = message['realemail'] ? message['realemail'] : (message['from'] ? message['from'] : message['email']);
        text = text.replace(/\$membermail/g, message.fromaddr);
        // TODO var fromid = from.substring(0, from.indexOf('@'));
        //text = text.replace(/\$memberid/g, fromid);

        var messagehistory = message.fromuser.messagehistory;

        //for (var i in keywordlist) {
        //    var keyword = keywordlist[i];
        //    var msgs = counts[keyword];
        //    var summ = '';
        //
        //    if (msgs) {
        //        var regex = new RegExp("\\$numrecent" + keyword.toLowerCase(), "gim");
        //        text = text.replace(regex, msgs['count']);
        //
        //        for (var m in msgs['messages']) {
        //            var cmsg = msgs['messages'][m];
        //            summ += "#" + cmsg['id'] + ": " + formatDate(cmsg['date'], false, false) + " - " + cmsg['subject'] + "\n";
        //        }
        //    }
        //
        //    var regex = new RegExp("\\$recent" + keyword.toLowerCase(), "gim");
        //    text = text.replace(regex, summ);
        //}
        //
        //var msgs = counts['All'];
        //
        //if (msgs) {
        //    var regex = new RegExp("\\$numrecentmsg", "gim");
        //    text = text.replace(regex, msgs['count']);
        //
        //    var summ = '';
        //    for (var m in msgs['messages']) {
        //        cmsg = msgs['messages'][m];
        //        summ += "#" + cmsg['id'] + ": " + formatDate(cmsg['date'], false, false) + " - " + cmsg['subject'] + "\n";
        //    }
        //}
        //
        //var regex = new RegExp("\\$recentmsg", "gim");
        //text = text.replace(regex, summ);

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

