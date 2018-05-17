/* eslint-disable no-undef */
module.exports = {
    init(){
        window.ga=window.ga||function(){(ga.q=ga.q||[]).push(arguments)};ga.l=+new Date;
        ga('create', ANALYTICS_ID, 'auto');
        ga('send', 'pageview');
        ga('set', 'anonymizeIp', true);
    }
}