# iznik

Iznik is a platform for online reuse of unwanted items.  The development has been funded by Freegle for use in the UK, 
but it is an open source platform which can be used or adapted by others.

We welcome potential developers with open arms.  Have  a look at the wiki section.

License
=======

This code is licensed under the GPL v2 (see LICENSE file).  If you intend to use it, Freegle would be interested to
hear about it; you can mail <geeks@ilovefreegle.org>.

Freegle's own use of this code includes a database of UK locations which is derived in part from OpenStreetMap data, and
is therefore subject to the Open Database License.  You can request a copy of this data by mailing 
<geeks@ilovefreegle.org>.

# Development

First, install all the dependencies:
```
npm install
```

Then start the dev server:
```
npm run dev
```

This will serve up the site at [localhost:3000](http://localhost:3000). API requests are proxied to [dev.ilovefreegle.org](https://dev.ilovefreegle.org) so you can use your account there, but social login won't work  - only an email/password.

It will watch for changes and do hot module reloading.

# Production

When you are ready to build a production version run:

```
npm run build
```

The files end up in `dist/`. When accessed it will connect to [www.ilovefreegle.org](https://www.ilovefreegle.org).

## Try it out locally

If you want to try out the production build, but serve it locally, you can build a local version:

```
npm run build:local
```

This will be exactly like the production build, but connect to [localhost:3000](http://localhost:3000). Therefore you must run this local server, which you can do with:

```
npm run serve
```

This will serve up the built files from `dist/` and proxy to the backend.

