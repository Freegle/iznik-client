// Copied from http://westciv.com/tools/manifestR/ as that site doesn't have an SSL certificate, and so the
// bookmarklet doesn't work on our site.

//manifesto is a little bookmarl driven app to create the manifest file for a web page (and ultimately site)
//it scans your DOM for resources used, and then creates a manifest from that.

//resources scanned for are

//images
//css files
//javascript files
//linked HTML files in the same domain

var manifestR={

    makeManifest: function(options){
        //make the manfest file for the document
        //in future ption may include things like links to other pages, etc

        // if (manifestR.insertHUD()){
        // 	manifestR.close();
        // 	return
        // }
        //if we have already loaded it, close it


        var newLineString="\n";
        var manifest="CACHE MANIFEST";

        var date =new Date;

        var manifest=manifest+newLineString+newLineString+"#version 1.0 "+ (date.getMonth() +1)+ "-" +date.getDate()+"-" + date.getFullYear();

        var manifest=manifest+newLineString+newLineString+"CACHE:"+newLineString;

        var manifest=manifest+newLineString+"#images"+newLineString;

        var location=window.location.hostname;
        // if(location.indexOf('www.')==0) location=location.substring(4); //not sure that this is acceptable cross domain policy - I think it needs to be the whole domain, subdomains and protocols - check
        var images=document.images;
        var imageSRCs=new Array();
        var imageSRC="";

        for (var i=0; i < images.length; i++) {
            if (images[i].src){
                // if (manifestR.sameDomain(images[i].src)){
                //check thse are in the same domain - won;t cache cross domain
                imageSRC=images[i].src.split("#")[0]
                imageSRCs.push(imageSRC)
                // }
            }
        };

        imageSRCs=eliminateDuplicates(imageSRCs);
        manifest=manifest+imageSRCs.join(newLineString)+newLineString;


        var manifest=manifest+newLineString+"#internal HTML documents"+newLineString;
        var links=document.links;
        var linkHREFs=new Array();
        var linkHREF="";

        for (var i=0; i < links.length; i++) {
            if (links[i].href){
                if (manifestR.sameDomain(links[i].href)){
                    linkHREF=links[i].href.split("#")[0];
                    linkHREFs.push(linkHREF)
                }
            }
        };

        linkHREFs=eliminateDuplicates(linkHREFs);
        manifest=manifest+linkHREFs.join(newLineString)+newLineString;

        var styleSheets=new Array();
        var styleSheetHREFs=new Array();
        var theRule;
        var cssImages=new Array();
        var imageStart, imageEnd;
        var cacheThisStyleSheet='true'

        for (var i=0; i < document.styleSheets.length; i++) {
            styleSheets.push(document.styleSheets[i])
        };

        for (var i=0; i < styleSheets.length; i++) {
            if (styleSheets[i].href)
            // if (manifestR.sameDomain(styleSheets[i].href, location)){
            //add the style sheet if it is same domain
                styleSheetHREFs.push(styleSheets[i].href)
            // }

            //now we check the style sheets rules
            if (styleSheets[i].cssRules){
                for (var j=0; j < styleSheets[i].cssRules.length; j++) {
                    theRule=styleSheets[i].cssRules[j];
                    if (theRule){
                        if (theRule.type==3){ //CSSRule.IMPORT_RULE
                            //if it has a URL, push the style sheet onto the array of style sheets to be processed
                            styleSheets.push(theRule.styleSheet); //let's process this too
                        }
                        else if(theRule.type==1){ //CSSRule.STYLE_RULE
                            imageStart=theRule.cssText.indexOf('url('); //assuming we are using that format
                            if(imageStart!=-1){
                                imageEnd=theRule.cssText.indexOf(')', imageStart); //first instance of ) after url(
                                if (imageEnd!=-1){
                                    imageSRC=theRule.cssText.substring(imageStart+4, imageEnd);
                                    if(imageSRC.indexOf('manifestR')==-1){
                                        cssImages.push(imageSRC);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }


        // style sheets
        var manifest=manifest+newLineString+"#style sheets"+newLineString;
        styleSheetHREFs=eliminateDuplicates(styleSheetHREFs);
        manifest=manifest+styleSheetHREFs.join(newLineString)+newLineString;

        // images in style sheets
        var manifest=manifest+newLineString+"#style sheet images"+newLineString;
        cssImages=eliminateDuplicates(cssImages);
        manifest=manifest+cssImages.join(newLineString)+newLineString;


        var manifest=manifest+newLineString+"#javascript files"+newLineString;
        var scripts=document.getElementsByTagName('script');
        for (var i=0; i < scripts.length; i++) {
            if (scripts[i].src){
                // if (manifestR.sameDomain(scripts[i].src)){
                //don't cache manifestR stuff
                if(scripts[i].src.indexOf('manifestR.js')==-1){
                    manifest=manifest+scripts[i].src+newLineString;
                }
            }
        };

        var manifest=manifest+newLineString+"NETWORK:"+newLineString +"*";

        var theHUD=manifestR.insertHUD();

        if (document.getElementById('manifestContent')){
            manifestR.insertElementContent(document.getElementById('manifestContent'), manifest)
        }


    },


    sameDomain: function(url1){
        //is the url in the same doman as this document?
        var currentDomain=window.location.protocol+"//"+document.domain;
        return url1.indexOf(currentDomain)!=-1
    },

    getHUD: function(){
        //get the HUD element for showing the manifest

        return document.getElementById("manifestHUD");
    },

    insertHUD: function () {
        // inserts a div we use for reporting element information
        //it has two divs, one as a titlebar, and one for its content

        var theHUD=manifestR.getHUD();

        if (!theHUD){

            var theHUD = document.createElement('div');
            document.body.appendChild(theHUD);
            // var titleBar=document.createElement('div');
            // theHUD.appendChild(titleBar);
            theHUD.id='manifestHUD';
            // theHUD.style.display="none";
            //we'll show it once the CSS is loaded
            theHUD.innerHTML= "<div id='manifestHUDInstructions'><p>To create a manifest for this document, allowing it to work offline in most modern browsers<span id='closeBtn' onclick='manifestR.close()'>&nbsp;</span></p> <ul> <li><span class='manifestStep'>1</span>  save the <code><strong>CACHE MANIFEST</strong></code> content below as a text file with the extension <code><strong>.appcache</strong></code> </li> <li><span class='manifestStep'>2</span>  add a <code><strong>manifest</strong></code>  attribute to the document's <code><strong>HTML</strong></code> element. The attribute's value is the location of the manifest file</li> <li><span class='manifestStep'>3</span>  ensure your server serves .appcache files as text/cache-manifest</li> </ul> <p>More details on manifests and creating offline web sites and apps (including why not all of your resources might appear here) <a href='http://www.webdirections.org/blog/get-offline/'>in our article on taking your sites and apps offline</a></p></div><div id='manifestContent' contenteditable='true'></div><div id='manifestFooter'> <p><a href='http://webdirections.org'><strong>ManifestR</strong> is brought to you by <strong>Web Directions</strong><br> Awesome conferences (tools, tutorials and more) for web developers just like you!</a></p></div>"
            setTimeout(function(){theHUD.style.top=0; theHUD.style.display='block'; theHUD.style.visibility='visible'}, 50);
        }

        return theHUD;

    },

    insertElementContent: function(theElement, theContent){
        //insert the content into the element

        if(theElement.innerText){
            theElement.innerText=theContent;
        }
        else {
            theElement.textContent=theContent;
        }
    },

    close: function(){
        //unistall manifestR
        var theHUD=manifestR.getHUD();
        theHUD.style.top="-100%";
        setTimeout('manifestR.getHUD().parentNode.removeChild(manifestR.getHUD())', 1000);
        // document.removeChild(theHUD);
    }

} //end manifesto

//cross browse add event courtesy of
// http://www.javascriptrules.com/2009/07/22/cross-browser-event-listener-with-design-patterns/

var addEvent = function (el, ev, fn) {
    if (el.addEventListener) {
        el.addEventListener(ev, fn, false);
    } else if (el.attachEvent) {
        el.attachEvent('on' + ev, fn);
    } else {
        el['on' + ev] = fn;
    }
};

//drag and drop support adapted fom http://www.hunlock.com/blogs/Javascript_Drag_and_Drop

var savedTarget=null;                           // The target layer (effectively vidPane)
var orgCursor=null;                             // The original mouse style so we can restore it
var dragOK=false;                               // True if we're allowed to move the element under mouse
var dragXoffset=0;                              // How much we've moved the element on the horozontal
var dragYoffset=0;                              // How much we've moved the element on the verticle

var didDrag=false;								//set to true when we do a drag


function moveHandler(e){
    if (e == null) { e = window.event }
    if (e.button<=1&&dragOK){
        savedTarget.style.left=e.clientX-dragXoffset+'px';
        savedTarget.style.top=e.clientY-dragYoffset+'px';
        return false;
    }
}

function cleanup(e) {
    // document.onmousemove=null;
    // document.onmouseup = xRayElement;
    savedTarget.style.cursor=orgCursor;

    dragOK=false; //its been dragged now
    didDrag=true;

}

function dragHandler(e){

    var htype='-moz-grabbing';
    if (e == null) { e = window.event;} // htype='move';}
    var target = e.target != null ? e.target : e.srcElement;
    orgCursor=target.style.cursor;


    if (inHUD(target)) {
        target=document.getElementById("manifestHUD");
        // target.style.webkitBoxShadow='0px 0px 0px #777777';
        savedTarget=target;
        target.style.cursor=htype;
        dragOK=true;
        dragXoffset=e.clientX-target.offsetLeft;
        dragYoffset=e.clientY-target.offsetTop;
        document.onmousemove=moveHandler;
        document.onmouseup=cleanup;
        return false;
    }
    else {

    }
}

function readyHandler(e){
    //when ready state changes,

}

//end drag handling
function inHUD(obj) {
    //is the element in the HUD element?

    if (obj.id=="manifestHUDTitleBar") return true;

//	alert(obj.parentNode);

    if (obj.parentNode) {
        while (obj = obj.parentNode) {
            if (obj.id=="manifestHUDTitleBar") return true;
        }
    }
}

function isIE(){
    //is this any version of IE?
    //alert(!document.defaultView);
    return (!document.defaultView)
}

var theCSS='#manifestHUD { /*zero out irritating effects*/ text-shadow: none; text-align: left; } #manifestHUD a:link, #manifestHUD a:visited { /*zero out irritating effects*/text-shadow: 0 1px 1px black; text-decoration: none; color: #ffffff; font-weight: bold; background-color: transparent; } #manifestHUD { position: fixed; top: -100%; left: 0; width: 100%; font-size: 14px; background-color: #ffffff; z-index: 1000000; resize: both; font-family: Helvetica, Arial, sans-serif; -webkit-transition: top 1s; -moz-transition: top 1s; -o-transition: top 1s; -ms-transition: top 1s; transition: top 1s; opacity: .96; display: block; /*visibility: hidden; */ } #manifestHUD * { background-color: transparent; background-image: none} #manifestHUD #closeBtn { display: inline-block; float: right; text-align: center; vertical-align: text-bottom; background-image: url("http://westciv.com/tools/manifestR/closebox.png"); width: 1em; background-repeat: no-repeat; cursor: pointer; } #manifestHUD #manifestContent { overflow-y: scroll; overflow-x: hidden; white-space: pre; -webkit-user-modify: read-write-plaintext-only; -moz-user-modify: read-write; text-overflow-mode:ellipsis; height: 200px; width: 300px; width: 98%; padding: .5em 1% 0 1%; border-left: solid 1px #222; border-bottom: solid 1px #222; outline: none; background-color: #191919; color: #acacac; text-shadow: none; resize: none ; -webkit-box-shadow: inset 0 0 10px rgba(0,0,0,0.3); font-size: .9em; font-family: "Courier New", "Courier", monospace; } #manifestHUD ul { list-style-type: none; margin:0; padding: 0 } #manifestHUD li { margin: 0; padding: .2em 0; } #manifestHUD li { background-color: #797979; color: #ebebeb; text-shadow: 0 1px 0 #000; border-bottom: solid 1px #444; border-top: solid 1px #999; -webkit-transition: background, .5s; -moz-transition: background, .5s -o-transition: background, .5s -ms-transition: background, .5s transition: background, .5s } #manifestHUD li:hover { background-color: #585858; color: #ebebeb; text-shadow: 0 1px 0 #000; } #manifestHUDInstructions p { background-color: #5f5f5f; color: #dcdcdc; text-shadow: 0 1px 0 #000; margin:0; padding:1em; text-align: left; } #manifestHUDInstructions p:first-child { font-size: 1em; font-weight: bold; } #manifestHUDInstructions .manifestStep { border: solid white 3px; background-color: #2c5ad5; -webkit-border-radius: 1em; -moz-border-radius: 1em; border-radius: 2em; display: inline-block; width: 1.2em; height: 1.2em; text-align: center; padding: .2em; margin:.2em .2em .2em .5em; font-size: .9em; -webkit-box-shadow: 0 1px 1px #111; -moz-box-shadow: 0 1px 1px #111; box-shadow: 0 1px 1px #111; } /*#manifestHUD li:nth-child(even) { background-color: #3c3c3c; color: #ebebeb;  text-shadow: 0 1px 0 #333}*/ #manifestHUD #manifestHUDTitleBar { text-align: center; background-color: #d3d3d3; -webkit-border-top-left-radius:.7em; -webkit-border-top-right-radius:.7em; background-color:#B8B8B8; background-image: -moz-linear-gradient(top , #B8B8B8, #000000 55%); background-image: -webkit-linear-gradient(top , #B8B8B8, #000000 55%); background-image: -o-linear-gradient(top , #B8B8B8, #000000 55%); background-image: -ms-linear-gradient(top , #B8B8B8, #000000 55%); background-image: linear-gradient(top , #B8B8B8, #000000 55%) text-shadow: none; padding: .2em 0; color: #fff; text-shadow: none; cursor: move; -webkit-user-select: none; -moz-user-select: none; -o-user-select: none; -ms-user-select: none; user-select: none; } #manifestFooter { background-color: #5f5f5f; color: #eee; text-shadow: 0 1px 0 #000; margin:0; padding:0; box-shadow: 2px 2px 2px #bbb; -webkit-box-shadow: 0 0  5px rgba(0, 0, 0, .6); -moz-box-shadow: 2px 2px 2px #bbb; } #manifestFooter p { margin: 0; padding: .6em; border-bottom: solid 1px #999; } #manifestFooter p { background-color:#fff; background-image: url(http://westciv.com/tools/manifestR/wdonly.png); background-repeat: no-repeat; background-position: right; background-size: contain } #manifestHUD #manifestFooter p a:link, #manifestHUD #manifestFooter p a:visited { color: #6c6c6c; text-shadow: none; font-weight: normal } #manifestFooter a { /* color: #eee; text-shadow: none; font-weight: bold; text-decoration: none; text-shadow: 0 1px 0 #000; */ } #manifestHUDTitleBar:active { cursor: move; } #manifestContent::-webkit-scrollbar { width: 10px; height: 6px; } #manifestContent::-webkit-scrollbar-button:start:decrement,#manifestContent::-webkit-scrollbar-button:end:increment { height: 0; display: block; background-color: transparent; } #manifestContent::-webkit-scrollbar-track-piece { background-color: #3b3b3b; -webkit-border-radius: 6px; } #manifestContent::-webkit-scrollbar-thumb:vertical { height: 50px; background-color: #666; -webkit-border-radius: 6px; } #manifestContent::-webkit-scrollbar-thumb:horizontal { width: 50px; background-color: #666; -webkit-border-radius: 3px; } #manifestContent::-webkit-scrollbar-corner { background:transparent; background-repeat:no-repeat; height: 12px }';

function eliminateDuplicates(arr) {
    var i,
        len=arr.length,
        out=[],
        obj={};

    for (i=0;i<len;i++) {
        obj[arr[i]]=0;
    }
    for (i in obj) {
        out.push(i);
    }
    return out.sort();
}

function addCSS (){

    var theHead = document.getElementsByTagName('head');
    var styleSheet = theHead[0].appendChild(document.createElement('style'));
    // theCSS.rel='stylesheet';
    // 		theCSS.href='http://westciv.com/tools/manifestR/manifestR.css';
    styleSheet.type='text/css';
    manifestR.insertElementContent(styleSheet, theCSS);

}

addCSS();

addEvent(document, "mousedown", dragHandler);
// addEvent(document, "readystatechange", readyHandler);
manifestR.makeManifest();
