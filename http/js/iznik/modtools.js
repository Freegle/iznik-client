// This is a collection of requires which are used by most ModTools pages.  It's easier to keep them here than have
// them individually in each one.
define([
    'iznik/models/membership',
    'iznik/accordionpersist',
    'iznik/selectpersist',
    'iznik/models/location',
    'iznik/models/config/modconfig',
    'iznik/models/config/stdmsg',
    'iznik/models/config/bulkop',
    'iznik/models/spammer',
    'iznik/models/user/user',
    'iznik/models/yahoo/user',
    'iznik/views/yahoo/user',
    'iznik/views/plugin',
    'iznik/views/group/select',
    'iznik/views/user/user'
    ], function() {
        console.log("Load ModTools dependencies");
        return(this);
    });
