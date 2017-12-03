module.exports = {
  parser: 'babel-eslint',
  rules: {
    'no-undef': 'warn'
  },
  env: {
    browser: true,
    amd: true,
    commonjs: true
  },
  globals: {
    Promise: true,

    // Libraries that end up as globals...
    jQuery: true,
    Backbone: true,
    google: true,
    gapi: true,
    twemoji: true,
    FB: true,
    RichMarker: true,
    Backform: true,
    Persist: true,

    // Iznik global definitions
    // TODO: make them not global
    Router: true,
    IznikYahooUsers: true,
    IznikPlugin: true,

    // Iznik global utility functions
    // TODO: make them not global
    templateFetch: true,
    haversineDistance: true,
    resolvedPromise: true,
    getURLParam: true,
    strip_tags: true,
    ABTestShown: true,
    ABTestAction: true,
    ABTestGetVariant: true,
    nullFn: true,
    twem: true,
    setTitleCounts: true,
    ellipsical: true,
    formatDuration: true,
    getBoundsZoomLevel: true,
    isXS: true,
    innerWidth: true,
    isSM: true,
    innerWidth: true,
    isShort: true,
    innerHeight: true,
    isVeryShort: true,
    innerHeight: true,
    canonSubj: true,
    setURLParam: true,
    removeURLParam: true,
    getDistanceFromLatLonInKm: true,
    deg2rad: true,
    decodeEntities: true,
    encodeHTMLEntities: true,
    orderedMessages: true,
    csvWriter: true,
    presdef: true,
    chunkArray: true,
    base64url: true,
    isValidEmailAddress: true,
    wbr: true,

    // Iznik configuration defined via webpack DefinePlugin's
    BASE_URL: true,
    CHAT_HOST: true,
    EVENT_HOST: true,
    API: true,
    USER_SITE: true,
    YAHOOAPI: true,
    YAHOOAPIv2: true,
    FACEBOOK_APPID: true,
    FACEBOOK_GRAFFITI_APPID: true,
    GOOGLE_CLIENT_ID: true,
    SITE_NAME: true,
    SITE_DESCRIPTION: true
  }
}
