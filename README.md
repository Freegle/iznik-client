# iznik-client

Iznik is a platform for online reuse of unwanted items.  This is the client half.  

The development has been funded by Freegle for use in the UK, 
but it is an open source platform which can be used or adapted by others.

This codebase supports two sites - the user site Freegle Direct (aka FD), and the moderator site ModTools (aka MT).  If you're not sure, you're probably interested in MT. 

We welcome potential developers with open arms.  Have  a look at the wiki section.

License
=======

This code is licensed under the GPL v2 (see LICENSE file).  If you intend to use it, Freegle would be interested to
hear about it; you can mail <geeks@ilovefreegle.org>.

Freegle's own use of this code includes a database of UK locations which is derived in part from OpenStreetMap data, and
is therefore subject to the Open Database License.  You can request a copy of this data by mailing 
<geeks@ilovefreegle.org>.

# Development

You'll need npm (>= v5.5.1).  Then install all the dependencies:
```
npm install
```

Then start the dev server.  If you are developing the user website (Freegle Direct aka FD) then run
```
npm run dev:fd 
```
Or for the ModTools site aka MT: 
```
npm run dev:mt 
```

This will serve up the site at [localhost:3000](http://localhost:3000) for FD, or [localhost:3000/modtools](http://localhost:3000/modtools) for MT. API requests are proxied to [dev.ilovefreegle.org](https://dev.ilovefreegle.org) so you can use your account there, but social login won't work  - only an email/password.

It will watch for changes and do hot module reloading.

# Production

When you are ready to build a production version run:

```
npm run build:fd
```
Or
```
npm run build:mt
```

The files end up in `dist/`. When accessed it will connect to [www.ilovefreegle.org](https://www.ilovefreegle.org).

## Try it out locally

If you want to try out the production build, but serve it locally, you can build a local version:

```
npm run build:localfd
```
Or
```
npm run build:localmt
```

This will be exactly like the production build, but connect to [localhost:3000](http://localhost:3000) or [localhost:3000/modtools](http://localhost:3000/modtools). Therefore you must run this local server, which you can do with:

```
npm run serve:fd
```
Or
```
npm run serve:mt
```

This will serve up the built files from `dist/` and proxy to the backend.

