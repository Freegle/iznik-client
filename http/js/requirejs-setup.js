// We load everything using require.  We need some shims for scripts which aren't AMD-compatible.
requirejs.config({
    baseUrl: "/js/lib",

    urlArgs: "bust=" +  (new Date()).getTime(),

    shim : {
        "bootstrap" : [ 'jquery' ],
        "bootstrap-select": [ "bootstrap" ],
        "bootstrap-switch": [ "bootstrap" ],
        "bootstrap-tagsinput": [ "bootstrap" ],
        "wicket": [ "jquery" ],
        "wicket-gmap3": [ "wicket" ],
        "ga": {
            exports: "ga"
        },
        "waypoints": {
            deps: [ "jquery" ],
            exports: 'Waypoint'
        },
        "jquery.dd": [ "jquery" ],
        "jquery.dotdotdot": [ "jquery" ],
        "jquery.geocomplete": [ "jquery" ],
        "jquery-show-first": [ "jquery" ],
        "fileupload": [ "jquery" ],
        "jquery.ui.widget": [ "jquery" ],
        "iznik/accordionpersist": [ "jquery" ],
        "iznik/selectpersist": [ "jquery" ],

        // Converse
        'crypto.aes':           { deps: ['crypto.cipher-core'] },
        'crypto.cipher-core':   { deps: ['crypto.enc-base64', 'crypto.evpkdf'] },
        'crypto.enc-base64':    { deps: ['crypto.core'] },
        'crypto.evpkdf':        { deps: ['crypto.md5'] },
        'crypto.hmac':          { deps: ['crypto.core'] },
        'crypto.md5':           { deps: ['crypto.core'] },
        'crypto.mode-ctr':      { deps: ['crypto.cipher-core'] },
        'crypto.pad-nopadding': { deps: ['crypto.cipher-core'] },
        'crypto.sha1':          { deps: ['crypto.core'] },
        'crypto.sha256':        { deps: ['crypto.core'] },
        'bigint':               { deps: ['crypto'] },
        'strophe.disco':        { deps: ['strophe'] },
        'strophe.register':     { deps: ['strophe'] },
        'strophe.roster':       { deps: ['strophe'] },
        'strophe.vcard':        { deps: ['strophe'] }
    },

    paths: {
        "bootstrap" :  "//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min",
        "ga": "//www.google-analytics.com/analytics",
        "waypoints": "/js/lib/jquery.waypoints",
        "fileupload": "/js/lib/jquery-file-upload/jquery.fileupload",
        "jquery.ui.widget": "/js/lib/jquery-file-upload/vendor/jquery.ui.widget",

        // Converse
        "converse": "/js/lib/converse/converse",
        "converse-templates": "/js/lib/converse/templates",
        "utils": "/js/lib/converse/utils",
        "action":                   "/js/lib/converse/templates/action",
        "add_contact_dropdown":     "/js/lib/converse/templates/add_contact_dropdown",
        "add_contact_form":         "/js/lib/converse/templates/add_contact_form",
        "change_status_message":    "/js/lib/converse/templates/change_status_message",
        "chat_status":              "/js/lib/converse/templates/chat_status",
        "chatarea":                 "/js/lib/converse/templates/chatarea",
        "chatbox":                  "/js/lib/converse/templates/chatbox",
        "chatroom":                 "/js/lib/converse/templates/chatroom",
        "chatroom_password_form":   "/js/lib/converse/templates/chatroom_password_form",
        "chatroom_sidebar":         "/js/lib/converse/templates/chatroom_sidebar",
        "chatrooms_tab":            "/js/lib/converse/templates/chatrooms_tab",
        "chats_panel":              "/js/lib/converse/templates/chats_panel",
        "choose_status":            "/js/lib/converse/templates/choose_status",
        "contacts_panel":           "/js/lib/converse/templates/contacts_panel",
        "contacts_tab":             "/js/lib/converse/templates/contacts_tab",
        "controlbox":               "/js/lib/converse/templates/controlbox",
        "controlbox_toggle":        "/js/lib/converse/templates/controlbox_toggle",
        "field":                    "/js/lib/converse/templates/field",
        "form_captcha":             "/js/lib/converse/templates/form_captcha",
        "form_checkbox":            "/js/lib/converse/templates/form_checkbox",
        "form_input":               "/js/lib/converse/templates/form_input",
        "form_select":              "/js/lib/converse/templates/form_select",
        "form_textarea":            "/js/lib/converse/templates/form_textarea",
        "form_username":            "/js/lib/converse/templates/form_username",
        "group_header":             "/js/lib/converse/templates/group_header",
        "info":                     "/js/lib/converse/templates/info",
        "login_panel":              "/js/lib/converse/templates/login_panel",
        "login_tab":                "/js/lib/converse/templates/login_tab",
        "message":                  "/js/lib/converse/templates/message",
        "new_day":                  "/js/lib/converse/templates/new_day",
        "occupant":                 "/js/lib/converse/templates/occupant",
        "pending_contact":          "/js/lib/converse/templates/pending_contact",
        "pending_contacts":         "/js/lib/converse/templates/pending_contacts",
        "register_panel":           "/js/lib/converse/templates/register_panel",
        "register_tab":             "/js/lib/converse/templates/register_tab",
        "registration_form":        "/js/lib/converse/templates/registration_form",
        "registration_request":     "/js/lib/converse/templates/registration_request",
        "requesting_contact":       "/js/lib/converse/templates/requesting_contact",
        "requesting_contacts":      "/js/lib/converse/templates/requesting_contacts",
        "room_description":         "/js/lib/converse/templates/room_description",
        "room_item":                "/js/lib/converse/templates/room_item",
        "room_panel":               "/js/lib/converse/templates/room_panel",
        "roster":                   "/js/lib/converse/templates/roster",
        "roster_item":              "/js/lib/converse/templates/roster_item",
        "search_contact":           "/js/lib/converse/templates/search_contact",
        "select_option":            "/js/lib/converse/templates/select_option",
        "status_option":            "/js/lib/converse/templates/status_option",
        "toggle_chats":             "/js/lib/converse/templates/toggle_chats",
        "toolbar":                  "/js/lib/converse/templates/toolbar",
        "trimmed_chat":             "/js/lib/converse/templates/trimmed_chat",        

        "iznik": "/js/iznik"
    },

    tpl: {
        // Configuration for requirejs-tpl
        // Use Mustache style syntax for variable interpolation
        templateSettings: {
            evaluate : /\{\[([\s\S]+?)\]\}/g,
            interpolate : /\{\{([\s\S]+?)\}\}/g
        }
    },

    waitSeconds: 0    
});