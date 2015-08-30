$(document).ready(function(){
    (function(){
        var b = true, f = false;

        function g(a){
            var c = a || {};
            this.d = this.c = f;
            if(a.visible == undefined)a.visible = b;
            if(a.shadow == undefined)a.shadow = "7px -3px 5px rgba(88,88,88,0.7)";
            if(a.anchor == undefined)a.anchor = i.BOTTOM;
            this.setValues(c)
        }

        g.prototype = new google.maps.OverlayView;
        window.RichMarker = g;
        g.prototype.getVisible = function(){
            return this.get("visible")
        };
        g.prototype.getVisible = g.prototype.getVisible;
        g.prototype.setVisible = function(a){
            this.set("visible", a)
        };
        g.prototype.setVisible = g.prototype.setVisible;
        g.prototype.s = function(){
            if(this.c){
                this.a.style.display = this.getVisible() ? "" : "none";
                this.draw()
            }
        };
        g.prototype.visible_changed = g.prototype.s;
        g.prototype.setFlat = function(a){
            this.set("flat", !!a)
        };
        g.prototype.setFlat = g.prototype.setFlat;
        g.prototype.getFlat = function(){
            return this.get("flat")
        };
        g.prototype.getFlat = g.prototype.getFlat;
        g.prototype.p = function(){
            return this.get("width")
        };
        g.prototype.getWidth = g.prototype.p;
        g.prototype.o = function(){
            return this.get("height")
        };
        g.prototype.getHeight = g.prototype.o;
        g.prototype.setShadow = function(a){
            this.set("shadow", a);
            this.g()
        };
        g.prototype.setShadow = g.prototype.setShadow;
        g.prototype.getShadow = function(){
            return this.get("shadow")
        };
        g.prototype.getShadow = g.prototype.getShadow;
        g.prototype.g = function(){
            if(this.c)this.a.style.boxShadow = this.a.style.webkitBoxShadow = this.a.style.MozBoxShadow = this.getFlat() ? "" : this.getShadow()
        };
        g.prototype.flat_changed = g.prototype.g;
        g.prototype.setZIndex = function(a){
            this.set("zIndex", a)
        };
        g.prototype.setZIndex = g.prototype.setZIndex;
        g.prototype.getZIndex = function(){
            return this.get("zIndex")
        };
        g.prototype.getZIndex = g.prototype.getZIndex;
        g.prototype.t = function(){
            if(this.getZIndex() && this.c)this.a.style.zIndex = this.getZIndex()
        };
        g.prototype.zIndex_changed = g.prototype.t;
        g.prototype.getDraggable = function(){
            return this.get("draggable")
        };
        g.prototype.getDraggable = g.prototype.getDraggable;
        g.prototype.setDraggable = function(a){
            this.set("draggable", !!a)
        };
        g.prototype.setDraggable = g.prototype.setDraggable;
        g.prototype.k = function(){
            if(this.c)this.getDraggable() ? j(this, this.a) : k(this)
        };
        g.prototype.draggable_changed = g.prototype.k;
        g.prototype.getPosition = function(){
            return this.get("position")
        };
        g.prototype.getPosition = g.prototype.getPosition;
        g.prototype.setPosition = function(a){
            this.set("position", a)
        };
        g.prototype.setPosition = g.prototype.setPosition;
        g.prototype.q = function(){
            this.draw()
        };
        g.prototype.position_changed = g.prototype.q;
        g.prototype.l = function(){
            return this.get("anchor")
        };
        g.prototype.getAnchor = g.prototype.l;
        g.prototype.r = function(a){
            this.set("anchor", a)
        };
        g.prototype.setAnchor = g.prototype.r;
        g.prototype.n = function(){
            this.draw()
        };
        g.prototype.anchor_changed = g.prototype.n;
        function l(a, c){
            var d = document.createElement("DIV");
            d.innerHTML = c;
            if(d.childNodes.length == 1)return d.removeChild(d.firstChild);else{
                for(var e = document.createDocumentFragment(); d.firstChild;)e.appendChild(d.firstChild);
                return e
            }
        }

        function m(a, c){
            if(c)for(var d; d = c.firstChild;)c.removeChild(d)
        }

        g.prototype.setContent = function(a){
            this.set("content", a)
        };
        g.prototype.setContent = g.prototype.setContent;
        g.prototype.getContent = function(){
            return this.get("content")
        };
        g.prototype.getContent = g.prototype.getContent;
        g.prototype.j = function(){
            if(this.b){
                m(this, this.b);
                var a = this.getContent();
                if(a){
                    if(typeof a == "string"){
                        a = a.replace(/^\s*([\S\s]*)\b\s*$/, "$1");
                        a = l(this, a)
                    }
                    this.b.appendChild(a);
                    var c = this;
                    a = this.b.getElementsByTagName("IMG");
                    for(var d = 0, e; e = a[d]; d++){
                        google.maps.event.addDomListener(e, "mousedown", function(h){
                            if(c.getDraggable()){
                                h.preventDefault && h.preventDefault();
                                h.returnValue = f
                            }
                        });
                        google.maps.event.addDomListener(e, "load", function(){
                            c.draw()
                        })
                    }
                    google.maps.event.trigger(this, "domready")
                }
                this.c &&
                this.draw()
            }
        };
        g.prototype.content_changed = g.prototype.j;
        function n(a, c){
            if(a.c){
                var d = "";
                if(navigator.userAgent.indexOf("Gecko/") !== -1){
                    if(c == "dragging")d = "-moz-grabbing";
                    if(c == "dragready")d = "-moz-grab"
                }else if(c == "dragging" || c == "dragready")d = "move";
                if(c == "draggable")d = "pointer";
                if(a.a.style.cursor != d)a.a.style.cursor = d
            }
        }

        function o(a, c){
            if(a.getDraggable())if(!a.d){
                a.d = b;
                var d = a.getMap();
                a.m = d.get("draggable");
                d.set("draggable", f);
                a.h = c.clientX;
                a.i = c.clientY;
                n(a, "dragready");
                a.a.style.MozUserSelect = "none";
                a.a.style.KhtmlUserSelect = "none";
                a.a.style.WebkitUserSelect = "none";
                a.a.unselectable = "on";
                a.a.onselectstart = function(){
                    return f
                };
                p(a);
                google.maps.event.trigger(a, "dragstart")
            }
        }

        function q(a){
            if(a.getDraggable())if(a.d){
                a.d = f;
                a.getMap().set("draggable", a.m);
                a.h = a.i = a.m = null;
                a.a.style.MozUserSelect = "";
                a.a.style.KhtmlUserSelect = "";
                a.a.style.WebkitUserSelect = "";
                a.a.unselectable = "off";
                a.a.onselectstart = function(){
                };
                r(a);
                n(a, "draggable");
                google.maps.event.trigger(a, "dragend");
                a.draw()
            }
        }

        function s(a, c){
            if(!a.getDraggable() || !a.d)q(a);else{
                var d = a.h - c.clientX, e = a.i - c.clientY;
                a.h = c.clientX;
                a.i = c.clientY;
                d = parseInt(a.a.style.left, 10) - d;
                e = parseInt(a.a.style.top, 10) - e;
                a.a.style.left = d + "px";
                a.a.style.top = e + "px";
                var h = t(a);
                a.setPosition(a.getProjection().fromDivPixelToLatLng(new google.maps.Point(d - h.width, e - h.height)));
                n(a, "dragging");
                google.maps.event.trigger(a, "drag")
            }
        }

        function k(a){
            if(a.f){
                google.maps.event.removeListener(a.f);
                delete a.f
            }
            n(a, "")
        }

        function j(a, c){
            if(c){
                a.f = google.maps.event.addDomListener(c, "mousedown", function(d){
                    o(a, d)
                });
                n(a, "draggable")
            }
        }

        function p(a){
            if(a.a.setCapture){
                a.a.setCapture(b);
                a.e = [google.maps.event.addDomListener(a.a, "mousemove", function(c){
                    s(a, c)
                }, b), google.maps.event.addDomListener(a.a, "mouseup", function(){
                    q(a);
                    a.a.releaseCapture()
                }, b)]
            }else a.e = [google.maps.event.addDomListener(window, "mousemove", function(c){
                s(a, c)
            }, b), google.maps.event.addDomListener(window, "mouseup", function(){
                q(a)
            }, b)]
        }

        function r(a){
            if(a.e){
                for(var c = 0, d; d = a.e[c]; c++)google.maps.event.removeListener(d);
                a.e.length = 0
            }
        }

        function t(a){
            var c = a.l();
            if(typeof c == "object")return c;
            var d = new google.maps.Size(0, 0);
            if(!a.b)return d;
            var e = a.b.offsetWidth;
            a = a.b.offsetHeight;
            switch(c){
                case i.TOP:
                    d.width = -e / 2;
                    break;
                case i.TOP_RIGHT:
                    d.width = -e;
                    break;
                case i.LEFT:
                    d.height = -a / 2;
                    break;
                case i.MIDDLE:
                    d.width = -e / 2;
                    d.height = -a / 2;
                    break;
                case i.RIGHT:
                    d.width = -e;
                    d.height = -a / 2;
                    break;
                case i.BOTTOM_LEFT:
                    d.height = -a;
                    break;
                case i.BOTTOM:
                    d.width = -e / 2;
                    d.height = -a;
                    break;
                case i.BOTTOM_RIGHT:
                    d.width = -e;
                    d.height = -a
            }
            return d
        }

        g.prototype.onAdd = function(){
            if(!this.a){
                this.a = document.createElement("DIV");
                this.a.style.position = "absolute"
            }
            if(this.getZIndex())this.a.style.zIndex = this.getZIndex();
            this.a.style.display = this.getVisible() ? "" : "none";
            if(!this.b){
                this.b = document.createElement("DIV");
                this.a.appendChild(this.b);
                var a = this;
                google.maps.event.addDomListener(this.b, "click", function(){
                    google.maps.event.trigger(a, "click")
                });
                google.maps.event.addDomListener(this.b, "mouseover", function(){
                    google.maps.event.trigger(a, "mouseover")
                });
                google.maps.event.addDomListener(this.b, "mouseout", function(){
                    google.maps.event.trigger(a, "mouseout")
                })
            }
            this.c = b;
            this.j();
            this.g();
            this.k();
            var c = this.getPanes();
            c && c.overlayImage.appendChild(this.a);
            google.maps.event.trigger(this, "ready")
        };
        g.prototype.onAdd = g.prototype.onAdd;
        g.prototype.draw = function(){
            if(!(!this.c || this.d)){
                var a = this.getProjection();
                if(a){
                    var c = this.get("position");
                    a = a.fromLatLngToDivPixel(c);
                    c = t(this);
                    this.a.style.top = a.y + c.height + "px";
                    this.a.style.left = a.x + c.width + "px";
                    a = this.b.offsetHeight;
                    c = this.b.offsetWidth;
                    c != this.get("width") && this.set("width", c);
                    a != this.get("height") && this.set("height", a)
                }
            }
        };
        g.prototype.draw = g.prototype.draw;
        g.prototype.onRemove = function(){
            this.a && this.a.parentNode && this.a.parentNode.removeChild(this.a);
            k(this)
        };
        g.prototype.onRemove = g.prototype.onRemove;
        var i = {TOP_LEFT: 1, TOP: 2, TOP_RIGHT: 3, LEFT: 4, MIDDLE: 5, RIGHT: 6, BOTTOM_LEFT: 7, BOTTOM: 8, BOTTOM_RIGHT: 9};
        window.RichMarkerPosition = i;
    })();

    /*! Apache License
     //Version 2.0, January 2004
     //http://www.apache.org/licenses/
     //
     //TERMS AND CONDITIONS FOR USE, REPRODUCTION, AND DISTRIBUTION
     //
     //1. Definitions.
     //
     //"License" shall mean the terms and conditions for use, reproduction,
     //and distribution as defined by Sections 1 through 9 of this document.
     //
     //"Licensor" shall mean the copyright owner or entity authorized by
     //the copyright owner that is granting the License.
     //
     //"Legal Entity" shall mean the union of the acting entity and all
     //other entities that control, are controlled by, or are under common
     //control with that entity. For the purposes of this definition,
     //"control" means (i) the power, direct or indirect, to cause the
     //direction or management of such entity, whether by contract or
     //otherwise, or (ii) ownership of fifty percent (50%) or more of the
     //outstanding shares, or (iii) beneficial ownership of such entity.
     //
     //"You" (or "Your") shall mean an individual or Legal Entity
     //exercising permissions granted by this License.
     //
     //"Source" form shall mean the preferred form for making modifications,
     //including but not limited to software source code, documentation
     //source, and configuration files.
     //
     //"Object" form shall mean any form resulting from mechanical
     //transformation or translation of a Source form, including but
     //not limited to compiled object code, generated documentation,
     //and conversions to other media types.
     //
     //"Work" shall mean the work of authorship, whether in Source or
     //Object form, made available under the License, as indicated by a
     //copyright notice that is included in or attached to the work
     //(an example is provided in the Appendix below).
     //
     //"Derivative Works" shall mean any work, whether in Source or Object
     //form, that is based on (or derived from) the Work and for which the
     //editorial revisions, annotations, elaborations, or other modifications
     //represent, as a whole, an original work of authorship. For the purposes
     //of this License, Derivative Works shall not include works that remain
     //separable from, or merely link (or bind by name) to the interfaces of,
     //the Work and Derivative Works thereof.
     //
     //"Contribution" shall mean any work of authorship, including
     //the original version of the Work and any modifications or additions
     //to that Work or Derivative Works thereof, that is intentionally
     //submitted to Licensor for inclusion in the Work by the copyright owner
     //or by an individual or Legal Entity authorized to submit on behalf of
     //the copyright owner. For the purposes of this definition, "submitted"
     //means any form of electronic, verbal, or written communication sent
     //to the Licensor or its representatives, including but not limited to
     //communication on electronic mailing lists, source code control systems,
     //and issue tracking systems that are managed by, or on behalf of, the
     //Licensor for the purpose of discussing and improving the Work, but
     //excluding communication that is conspicuously marked or otherwise
     //designated in writing by the copyright owner as "Not a Contribution."
     //
     //"Contributor" shall mean Licensor and any individual or Legal Entity
     //on behalf of whom a Contribution has been received by Licensor and
     //subsequently incorporated within the Work.
     //
     //2. Grant of Copyright License. Subject to the terms and conditions of
     //this License, each Contributor hereby grants to You a perpetual,
     //worldwide, non-exclusive, no-charge, royalty-free, irrevocable
     //copyright license to reproduce, prepare Derivative Works of,
     //publicly display, publicly perform, sublicense, and distribute the
     //Work and such Derivative Works in Source or Object form.
     //
     //3. Grant of Patent License. Subject to the terms and conditions of
     //this License, each Contributor hereby grants to You a perpetual,
     //worldwide, non-exclusive, no-charge, royalty-free, irrevocable
     //(except as stated in this section) patent license to make, have made,
     //use, offer to sell, sell, import, and otherwise transfer the Work,
     //where such license applies only to those patent claims licensable
     //by such Contributor that are necessarily infringed by their
     //Contribution(s) alone or by combination of their Contribution(s)
     //with the Work to which such Contribution(s) was submitted. If You
     //institute patent litigation against any entity (including a
     //cross-claim or counterclaim in a lawsuit) alleging that the Work
     //or a Contribution incorporated within the Work constitutes direct
     //or contributory patent infringement, then any patent licenses
     //granted to You under this License for that Work shall terminate
     //as of the date such litigation is filed.
     //
     //4. Redistribution. You may reproduce and distribute copies of the
     //Work or Derivative Works thereof in any medium, with or without
     //modifications, and in Source or Object form, provided that You
     //meet the following conditions:
     //
     //(a) You must give any other recipients of the Work or
     //Derivative Works a copy of this License; and
     //
     //(b) You must cause any modified files to carry prominent notices
     //stating that You changed the files; and
     //
     //(c) You must retain, in the Source form of any Derivative Works
     //that You distribute, all copyright, patent, trademark, and
     //attribution notices from the Source form of the Work,
     //excluding those notices that do not pertain to any part of
     //the Derivative Works; and
     //
     //(d) If the Work includes a "NOTICE" text file as part of its
     //distribution, then any Derivative Works that You distribute must
     //include a readable copy of the attribution notices contained
     //within such NOTICE file, excluding those notices that do not
     //pertain to any part of the Derivative Works, in at least one
     //of the following places: within a NOTICE text file distributed
     //as part of the Derivative Works; within the Source form or
     //documentation, if provided along with the Derivative Works; or,
     //within a display generated by the Derivative Works, if and
     //wherever such third-party notices normally appear. The contents
     //of the NOTICE file are for informational purposes only and
     //do not modify the License. You may add Your own attribution
     //notices within Derivative Works that You distribute, alongside
     //or as an addendum to the NOTICE text from the Work, provided
     //that such additional attribution notices cannot be construed
     //as modifying the License.
     //
     //You may add Your own copyright statement to Your modifications and
     //may provide additional or different license terms and conditions
     //for use, reproduction, or distribution of Your modifications, or
     //for any such Derivative Works as a whole, provided Your use,
     //reproduction, and distribution of the Work otherwise complies with
     //the conditions stated in this License.
     //
     //5. Submission of Contributions. Unless You explicitly state otherwise,
     //any Contribution intentionally submitted for inclusion in the Work
     //by You to the Licensor shall be under the terms and conditions of
     //this License, without any additional terms or conditions.
     //Notwithstanding the above, nothing herein shall supersede or modify
     //the terms of any separate license agreement you may have executed
     //with Licensor regarding such Contributions.
     //
     //6. Trademarks. This License does not grant permission to use the trade
     //names, trademarks, service marks, or product names of the Licensor,
     //except as required for reasonable and customary use in describing the
     //origin of the Work and reproducing the content of the NOTICE file.
     //
     //7. Disclaimer of Warranty. Unless required by applicable law or
     //agreed to in writing, Licensor provides the Work (and each
     //Contributor provides its Contributions) on an "AS IS" BASIS,
     //WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or
     //implied, including, without limitation, any warranties or conditions
     //of TITLE, NON-INFRINGEMENT, MERCHANTABILITY, or FITNESS FOR A
     //PARTICULAR PURPOSE. You are solely responsible for determining the
     //appropriateness of using or redistributing the Work and assume any
     //risks associated with Your exercise of permissions under this License.
     //
     //8. Limitation of Liability. In no event and under no legal theory,
     //whether in tort (including negligence), contract, or otherwise,
     //unless required by applicable law (such as deliberate and grossly
     //negligent acts) or agreed to in writing, shall any Contributor be
     //liable to You for damages, including any direct, indirect, special,
     //incidental, or consequential damages of any character arising as a
     //result of this License or out of the use or inability to use the
     //Work (including but not limited to damages for loss of goodwill,
     //work stoppage, computer failure or malfunction, or any and all
     //other commercial damages or losses), even if such Contributor
     //has been advised of the possibility of such damages.
     //
     //9. Accepting Warranty or Additional Liability. While redistributing
     //the Work or Derivative Works thereof, You may choose to offer,
     //and charge a fee for, acceptance of support, warranty, indemnity,
     //or other liability obligations and/or rights consistent with this
     //License. However, in accepting such obligations, You may act only
     //on Your own behalf and on Your sole responsibility, not on behalf
     //of any other Contributor, and only if You agree to indemnify,
     //defend, and hold each Contributor harmless for any liability
     //incurred by, or claims asserted against, such Contributor by reason
     //of your accepting any such warranty or additional liability.
     //
     //END OF TERMS AND CONDITIONS
     //
     //APPENDIX: How to apply the Apache License to your work.
     //
     //To apply the Apache License to your work, attach the following
     //boilerplate notice, with the fields enclosed by brackets "[]"
     //replaced with your own identifying information. (Don't include
     //the brackets!)  The text should be enclosed in the appropriate
     //comment syntax for the file format. We also recommend that a
     //file or class name and description of purpose be included on the
     //same "printed page" as the copyright notice for easier
     //identification within third-party archives.
     //
     //Copyright 2013 Google Inc.
     //
     //Licensed under the Apache License, Version 2.0 (the "License");
     //you may not use this file except in compliance with the License.
     //You may obtain a copy of the License at
     //
     //http://www.apache.org/licenses/LICENSE-2.0
     //
     //Unless required by applicable law or agreed to in writing, software
     //distributed under the License is distributed on an "AS IS" BASIS,
     //WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
     //See the License for the specific language governing permissions and
     //limitations under the License. */
});
