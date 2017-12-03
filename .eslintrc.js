module.exports = {
  parser: 'babel-eslint',
  rules: {
    'no-undef': 'warn'
  },
  env: {
    browser: true,
    amd: true
  },
  globals: {
    Promise: true,

    // Libraries that end up as globals...
    jQuery: true,
    Backbone: true,
    google: true,
    twemoji: true,

    // Iznik globals defined in webpack config
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
