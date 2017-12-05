# Webpack

Iznik is now using webpack for asset packaging :)

## Development

First, install all the dependencies:
```
npm install
```

Then start the dev server:
```
npm run dev
```

This will serve up the site at [localhost:3000](http://localhost:3000). API requests are proxied to [dev.ilovefreegle.org](https://dev.ilovefreegle.org) so you can use your account there.

It will watch for changes and do hot module reloading.

## Production

When you are ready to build a production version run:

```
npm run build
```

The files end up in `dist/`. When accessed it will connect to [www.ilovefreegle.org](https://www.ilovefreegle.org).

### Try it out locally

If you want to try out the production build, but serve it locally, you can build a local version:

```
npm run build:local
```

This will be exactly like the production build, but connect to [localhost:3000](http://localhost:3000). Therefore you must run this local server, which you can do with:

```
npm run serve
```

This will serve up the built files from `dist/` and proxy to the backend.

## Shims

As this has been converted from a requirejs loader, there are a lot of shims to avoid having to change the existing code. These are all contained in `dev/shims.js`.

Over time it would be nice to remove all of these.

### External scripts loading

As requirejs runs in the client, it can load scripts from the internet. Webpack is a bundler so it cannot load scripts from the internet.

We hack together an approach whereby we dynamically create a little script (at build time) that will fetch the script (at runtime).

This is configured in the shims file and implemented in `dev/script-loader.js`.

I am not sure if this is implemented very well at the moment. Maybe it doesn't work async properly, need to check if it properly waits for the script to load, and also need to support IE better (re: the `onload` bit).

Another solution is to give up with that, and simply add them in the `<head>` section. Might be simpler for now. Can use a template with the webpack html plugin if needed and stick them in.