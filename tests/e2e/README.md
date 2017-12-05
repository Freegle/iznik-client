# End to end tests

We use [testcafe](https://devexpress.github.io/testcafe/) to run end to end tests.

This allows us to click through any pages on the site and verify they work.

This is the early stages of it so there are a few more steps to it than there would be in the future.

Firstly, build the "local production" version of the site:

```
npm run build:local

# can also use the watching version of it
npm run build:local:watch
```

Then run the local server:

```
npm run serve
```

Make sure you can visit the site at [localhost:3000](http://localhost:3000/), then run the test script:

```
npm run test:e2e
```

At the moment it relies on having chromium browser available, but if you need other browsers then make some modifications to `tests/e2e/run.js`.

**Note**: at the moment this will be fetching data from [dev.ilovefreegle.org](https://dev.ilovefreegle.org) as we have no easy way to run a local backend. This is not a good idea in the longer run. For now, do not make any tests that depend on specific data being present, or that modify data.
