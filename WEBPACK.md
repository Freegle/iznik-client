# Webpack

Main things to run:

```
# install all node deps
yarn

# build webpack project into dist/ directory
yarn build

# same, but and watch for changes in app code
yarn watch
```

To access the files:
```
cd dist

# run an http server from this dir
# using python3
python -m http.server
```

Then visit site the URL and check the console for messages to see how it is doing.

The entry point script is `./client/app.js` so you can add things to that, or import different modules to see whether they are able to load ok.

## External scripts loading

As requirejs runs in the client, it can load scripts from the internet. Webpack is a bundler so it cannot load scripts from the internet.

We hack together an approach whereby we dynamically create a little script (at build time) that will fetch the script (at runtime).

My hacky solution is not great as I *think* it does not work properly async, so if something requires them it will not wait, and so the thing will not be available.

Another solution is to give up with that, and simply add them in the `<head>` section. Might be simpler for now. Can use a template with the webpack html plugin if needed and stick them in.

## TODO

* [ ] get templates to load
  * [ ] getting them to be loaded through a file-loader (or something)
  * [ ] as they are dynamic, find a way to ensure webpack knows to bundle them
* [ ] continue working through any other loading issues that come up
* [ ] consider loading the requirejs config directly in the webpack config (rather than copying in bits)
* [ ] fix external script URLs (some of them seem to be wrong, e.g. 404s)
* [ ] work out how to serve up a _real_ iznik/freegle page with the webpack (about injecting the webpack built scripts into the right place)
