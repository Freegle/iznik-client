/** The MIT License (MIT)

 Copyright (c) 2014 Solar Mosaic, Inc.

 Permission is hereby granted, free of charge, to any person obtaining a copy
 of this software and associated documentation files (the "Software"), to deal
 in the Software without restriction, including without limitation the rights
 to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 copies of the Software, and to permit persons to whom the Software is
 furnished to do so, subject to the following conditions:

 The above copyright notice and this permission notice shall be included in all
 copies or substantial portions of the Software.

 THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 SOFTWARE. */

define(["jquery"], // Require jquery
    function($) {
        jQuery(function ($) {

            /**
             * Show the first N items in a group and hide the rest, with an option to control.
             */
            $.fn.showLast = function (options) {
                var $group = $(this);
                var showLast = new ShowLast($group, options);
                showLast.init();
                return showLast;
            };

            var defaults = {
                controlTemplate: '<li><a href="#" class="show-first-control">Show Earlier</a></li>',
                count: 10,
                hasControl: true
            };

            /**
             * Construct a new ShowLast object.
             */
            function ShowLast($group, options) {
                // Handle arguments.
                this.$group = $group;
                this.settings = $.extend({}, defaults, options);

                // Set other properties.
                this.$items = this.$group.children();
                this.hasSurplus = this.$items.length > this.settings.count;

                if (this.hasSurplus) {
                    this.indexStartOfRest = this.$items.length - this.settings.count;
                    this.$itemsFirst = this.$items.slice(0, this.indexStartOfRest);
                    this.$itemsRest = this.$items.slice(this.indexStartOfRest);
                }
            }

            /**
             * Initialize behavior.
             */
            ShowLast.prototype.init = function () {
                if (this.hasSurplus) {
                    this.$itemsFirst.hide();

                    if (this.settings.hasControl) {
                        this.addControl();
                    }
                }
            };

            ShowLast.prototype.addControl = function () {
                // Replace tokens in control template.
                var tokens = [
                    {pattern: new RegExp("\\[TOTAL_COUNT\\]", "g"), value: this.$items.length},
                    {pattern: new RegExp("\\[FIRST_COUNT\\]", "g"), value: this.$itemsFirst.length},
                    {pattern: new RegExp("\\[REST_COUNT\\]", "g"), value: this.$itemsRest.length}
                ];
                this.controlWithValues = this.settings.controlTemplate;
                for (var i = 0; i < tokens.length; i++) {
                    this.controlWithValues = this.controlWithValues.replace(tokens[i].pattern, tokens[i].value);
                }

                // Insert control.
                this.$controlHtml = $(this.controlWithValues);
                this.$itemsFirst.last().after(this.$controlHtml);

                // Customize control behavior.
                $(".show-first-control", this.$controlHtml).click($.proxy(function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    this.$controlHtml.toggle();
                    this.$itemsFirst.toggle();
                }, this));
            };

            // Automatically create Show First elements based on class and data attributes.
            $(".show-first").each(function () {
                var $group = $(this);
                var options = {};
                options.controlTemplate = $group.data("show-first-control-template");
                options.count = $group.data("show-first-count");
                options.hasControl = $group.data("show-first-has-control");
                $group.showLast(options);
            });
        });
    });