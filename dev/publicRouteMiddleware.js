/*
    Works a bit like connect-history-api-fallback
    but only on the routes that are actually handled by the frontend

    This is so we can do full page refreshes whilst running the dev server
    and still get the webpack index page (whether it's via the middleware or dist server)
*/

const url = require('url')

const routes = [
  'about',
  'alert',
  'aviva',
  'board',
  'chat',
  'chats',
  'communityevents',
  'councils',
  'contact',
  'disclaimer',
  'donate',
  'ebay',
  'edit',
  'events',
  'explore',
  'find',
  'give',
  'groups',
  'handbook',
  'help',
  'helpus',
  'invite',
  'spread',
  'legacy',
  'livemap',
  'localstorage',
  'location',
  'm',
  'message',
  'mobile',
  'modtools',
  'mygroups',
  'mypost',
  'myposts',
  'newsfeed',
  'chitchat',
  'newuser',
  'plugins',
  'post',
  'privacy',
  'profile',
  'recentfreegles',
  'schedule',
  'settings',
  'shortlinks',
  'stats',
  'stories',
  'story',
  'terms',
  'unsubscribe',
  'volunteering',
  'volunteers',
  'why',
  'yahoologin'
].reduce((obj, entry) => {
  obj[entry] = true
  return obj
}, {})

module.exports = () => (req, res, next) => {
  if (req.method === 'GET') {
    const pathname = url.parse(req.url).pathname
    const pathbase = pathname.replace(/^\//, '').replace(/\/.*/, '')
    if (routes[pathbase]) req.url = '/index.html'
  }
  next()
}
