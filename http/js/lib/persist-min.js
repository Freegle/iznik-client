(function(){if(window.google&&google.gears){return;}
    var F=null;if(typeof GearsFactory!='undefined'){F=new GearsFactory();}else{try{F=new ActiveXObject('Gears.Factory');if(F.getBuildInfo().indexOf('ie_mobile')!=-1){F.privateSetGlobalObject(this);}}catch(e){if((typeof navigator.mimeTypes!='undefined')&&navigator.mimeTypes["application/x-googlegears"]){F=document.createElement("object");F.style.display="none";F.width=0;F.height=0;F.type="application/x-googlegears";document.documentElement.appendChild(F);}}}
    if(!F){return;}
    if(!window.google){google={};}
    if(!google.gears){google.gears={factory:F};}})();Persist=(function(){var VERSION='0.3.1',P,B,esc,init,empty,ec;ec=(function(){var EPOCH='Thu, 01-Jan-1970 00:00:01 GMT',RATIO=1000*60*60*24,KEYS=['expires','path','domain'],esc=escape,un=unescape,doc=document,me;var get_now=function(){var r=new Date();r.setTime(r.getTime());return r;};var cookify=function(c_key,c_val){var i,key,val,r=[],opt=(arguments.length>2)?arguments[2]:{};r.push(esc(c_key)+'='+esc(c_val));for(var idx=0;idx<KEYS.length;idx++){key=KEYS[idx];val=opt[key];if(val){r.push(key+'='+val);}}
    if(opt.secure){r.push('secure');}
    return r.join('; ');};var alive=function(){var k='__EC_TEST__',v=new Date();v=v.toGMTString();this.set(k,v);this.enabled=(this.remove(k)==v);return this.enabled;};me={set:function(key,val){var opt=(arguments.length>2)?arguments[2]:{},now=get_now(),expire_at,cfg={};if(opt.expires){if(opt.expires==-1){cfg.expires=-1}
else{var expires=opt.expires*RATIO;cfg.expires=new Date(now.getTime()+expires);cfg.expires=cfg.expires.toGMTString();}}
    var keys=['path','domain','secure'];for(var i=0;i<keys.length;i++){if(opt[keys[i]]){cfg[keys[i]]=opt[keys[i]];}}
    var r=cookify(key,val,cfg);doc.cookie=r;return val;},has:function(key){key=esc(key);var c=doc.cookie,ofs=c.indexOf(key+'='),len=ofs+key.length+1,sub=c.substring(0,key.length);return((!ofs&&key!=sub)||ofs<0)?false:true;},get:function(key){key=esc(key);var c=doc.cookie,ofs=c.indexOf(key+'='),len=ofs+key.length+1,sub=c.substring(0,key.length),end;if((!ofs&&key!=sub)||ofs<0){return null;}
    end=c.indexOf(';',len);if(end<0){end=c.length;}
    return un(c.substring(len,end));},remove:function(k){var r=me.get(k),opt={expires:EPOCH};doc.cookie=cookify(k,'',opt);return r;},keys:function(){var c=doc.cookie,ps=c.split('; '),i,p,r=[];for(var idx=0;idx<ps.length;idx++){p=ps[idx].split('=');r.push(un(p[0]));}
    return r;},all:function(){var c=doc.cookie,ps=c.split('; '),i,p,r=[];for(var idx=0;idx<ps.length;idx++){p=ps[idx].split('=');r.push([un(p[0]),un(p[1])]);}
    return r;},version:'0.2.1',enabled:false};me.enabled=alive.call(me);return me;}());var index_of=(function(){if(Array.prototype.indexOf){return function(ary,val){return Array.prototype.indexOf.call(ary,val);};}else{return function(ary,val){var i,l;for(var idx=0,len=ary.length;idx<len;idx++){if(ary[idx]==val){return idx;}}
    return-1;};}})();empty=function(){};esc=function(str){return'PS'+str.replace(/_/g,'__').replace(/ /g,'_s');};var C={search_order:['localstorage','globalstorage','gears','cookie','ie','flash'],name_re:/^[a-z][a-z0-9_ \-]+$/i,methods:['init','get','set','remove','load','save','iterate'],sql:{version:'1',create:"CREATE TABLE IF NOT EXISTS persist_data (k TEXT UNIQUE NOT NULL PRIMARY KEY, v TEXT NOT NULL)",get:"SELECT v FROM persist_data WHERE k = ?",set:"INSERT INTO persist_data(k, v) VALUES (?, ?)",remove:"DELETE FROM persist_data WHERE k = ?",keys:"SELECT * FROM persist_data"},flash:{div_id:'_persist_flash_wrap',id:'_persist_flash',path:'persist.swf',size:{w:1,h:1},params:{autostart:true}}};B={gears:{size:-1,test:function(){return(window.google&&window.google.gears)?true:false;},methods:{init:function(){var db;db=this.db=google.gears.factory.create('beta.database');db.open(esc(this.name));db.execute(C.sql.create).close();},get:function(key){var r,sql=C.sql.get;var db=this.db;var ret;db.execute('BEGIN').close();r=db.execute(sql,[key]);ret=r.isValidRow()?r.field(0):null;r.close();db.execute('COMMIT').close();return ret;},set:function(key,val){var rm_sql=C.sql.remove,sql=C.sql.set,r;var db=this.db;var ret;db.execute('BEGIN').close();db.execute(rm_sql,[key]).close();db.execute(sql,[key,val]).close();db.execute('COMMIT').close();return val;},remove:function(key){var get_sql=C.sql.get,sql=C.sql.remove,r,val=null,is_valid=false;var db=this.db;db.execute('BEGIN').close();db.execute(sql,[key]).close();db.execute('COMMIT').close();return true;},iterate:function(fn,scope){var key_sql=C.sql.keys;var r;var db=this.db;r=db.execute(key_sql);while(r.isValidRow()){fn.call(scope||this,r.field(0),r.field(1));r.next();}
    r.close();}}},globalstorage:{size:5*1024*1024,test:function(){if(window.globalStorage){var domain='127.0.0.1';if(this.o&&this.o.domain){domain=this.o.domain;}
    try{var dontcare=globalStorage[domain];return true;}catch(e){if(window.console&&window.console.warn){console.warn("globalStorage exists, but couldn't use it because your browser is running on domain:",domain);}
        return false;}}else{return false;}},methods:{key:function(key){return esc(this.name)+esc(key);},init:function(){this.store=globalStorage[this.o.domain];},get:function(key){key=this.key(key);return this.store.getItem(key);},set:function(key,val){key=this.key(key);this.store.setItem(key,val);return val;},remove:function(key){var val;key=this.key(key);val=this.store.getItem[key];this.store.removeItem(key);return val;}}},localstorage:{size:-1,test:function(){try{if(window.localStorage&&window.localStorage.setItem("test",null)==undefined){if(/Firefox[\/\s](\d+\.\d+)/.test(navigator.userAgent)){var ffVersion=RegExp.$1;if(ffVersion>=9){return true;}
    if(window.location.protocol=='file:'){return false;}}else{return true;}}else{return false;}
    return window.localStorage?true:false;}catch(e){return false;}},methods:{key:function(key){return this.name+'>'+key;},init:function(){this.store=localStorage;},get:function(key){key=this.key(key);return this.store.getItem(key);},set:function(key,val){key=this.key(key);this.store.setItem(key,val);return val;},remove:function(key){var val;key=this.key(key);val=this.store.getItem(key);this.store.removeItem(key);return val;},iterate:function(fn,scope){var l=this.store,key,keys;for(var i=0;i<l.length;i++){key=l.key(i);keys=key.split('>');if((keys.length==2)&&(keys[0]==this.name)){fn.call(scope||this,keys[1],l.getItem(key));}}}}},ie:{prefix:'_persist_data-',size:64*1024,test:function(){return window.ActiveXObject?true:false;},make_userdata:function(id){var el=document.createElement('div');el.id=id;el.style.display='none';el.addBehavior('#default#userdata');document.body.appendChild(el);return el;},methods:{init:function(){var id=B.ie.prefix+esc(this.name);this.el=B.ie.make_userdata(id);if(this.o.defer){this.load();}},get:function(key){var val;key=esc(key);if(!this.o.defer){this.load();}
    val=this.el.getAttribute(key);return val;},set:function(key,val){key=esc(key);this.el.setAttribute(key,val);if(!this.o.defer){this.save();}
    return val;},remove:function(key){var val;key=esc(key);if(!this.o.defer){this.load();}
    val=this.el.getAttribute(key);this.el.removeAttribute(key);if(!this.o.defer){this.save();}
    return val;},load:function(){this.el.load(esc(this.name));},save:function(){this.el.save(esc(this.name));}}},cookie:{delim:':',size:4000,test:function(){return P.Cookie.enabled?true:false;},methods:{key:function(key){return this.name+B.cookie.delim+key;},get:function(key,fn){var val;key=this.key(key);val=ec.get(key);return val;},set:function(key,val,fn){key=this.key(key);ec.set(key,val,this.o);return val;},remove:function(key,val){var val;key=this.key(key);val=ec.remove(key);return val;}}},flash:{test:function(){try{if(!swfobject){return false;}}catch(e){return false;}
    var major=swfobject.getFlashPlayerVersion().major;return(major>=8)?true:false;},methods:{init:function(){if(!B.flash.el){var key,el,fel,cfg=C.flash;el=document.createElement('div');el.id=cfg.div_id;fel=document.createElement('div');fel.id=cfg.id;el.appendChild(fel);document.body.appendChild(el);B.flash.el=swfobject.createSWF({id:cfg.id,data:this.o.swf_path||cfg.path,width:cfg.size.w,height:cfg.size.h},cfg.params,cfg.id);}
    this.el=B.flash.el;},get:function(key){var val;key=esc(key);val=this.el.get(this.name,key);return val;},set:function(key,val){var old_val;key=esc(key);old_val=this.el.set(this.name,key,val);return old_val;},remove:function(key){var val;key=esc(key);val=this.el.remove(this.name,key);return val;}}}};init=function(){var i,l,b,key,fns=C.methods,keys=C.search_order;for(var idx=0,len=fns.length;idx<len;idx++){P.Store.prototype[fns[idx]]=empty;}
    P.type=null;P.size=-1;for(var idx2=0,len2=keys.length;!P.type&&idx2<len2;idx2++){b=B[keys[idx2]];if(b.test()){P.type=keys[idx2];P.size=b.size;for(key in b.methods){P.Store.prototype[key]=b.methods[key];}}}
    P._init=true;};P={VERSION:VERSION,type:null,size:0,add:function(o){B[o.id]=o;C.search_order=[o.id].concat(C.search_order);init();},remove:function(id){var ofs=index_of(C.search_order,id);if(ofs<0){return;}
    C.search_order.splice(ofs,1);delete B[id];init();},Cookie:ec,Store:function(name,o){if(!C.name_re.exec(name)){throw new Error("Invalid name");}
    if(!P.type){throw new Error("No suitable storage found");}
    o=o||{};this.name=name;o.domain=o.domain||location.hostname||'localhost';o.domain=o.domain.replace(/:\d+$/,'');o.domain=(o.domain=='localhost')?'':o.domain;this.o=o;o.expires=o.expires||365*2;o.path=o.path||'/';if(this.o.search_order){C.search_order=this.o.search_order;init();}
    this.init();}};init();return P;})();