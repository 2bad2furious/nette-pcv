console.info("hello");
_stack.push(function (di) {
    jQuery(document).ready(function ($) {
        /*var oldJQueryEventTrigger = jQuery.event.trigger;
        jQuery.event.trigger = function( event, data, elem, onlyHandlers ) {
            console.log( event, data, elem, onlyHandlers );
            oldJQueryEventTrigger( event, data, elem, onlyHandlers );
        }*/

        var addHanlders = [];
        var removeHandlers = [];


        var selectMO = new MutationObserver(
            function (mutations) {
                for (var x = 0; x < mutations.length; x++) {
                    var mutation = mutations[x];
                    if (mutation.type === 'childList') {
                        var nodesAdded = mutation.addedNodes;
                        var nodesRemoved = mutation.removedNodes;
                        var i;

                        for (i = 0; i < nodesAdded.length; i++) {
                            var nodeAdded = $(nodesAdded[i]);
                            for (y = 0; y < addHanlders.length; y++) {
                                var addHandler = addHanlders[y];
                                var selector = addHandler.selector;
                                var handler = addHandler.handler;
                                try {
                                    var els = nodeAdded.find(selector);
                                    if (els.length)
                                        handler(els);
                                } catch (e) {
                                    console.error(e)
                                }
                            }
                        }
                        for (i = 0; i < nodesRemoved.length; i++) {
                            var nodeDeleted = $(nodesRemoved[i]);
                            for (var y = 0; y < removeHandlers.length; y++) {
                                var removeHandler = removeHandlers[y];
                                var selector = removeHandler.selector;
                                var handler = removeHandler.handler;
                                try {
                                    var els = nodeDeleted.find(selector);
                                    if (els.length)
                                        handler(els);
                                } catch (e) {
                                    console.error(e);
                                }
                            }
                        }
                    }
                }
            }
            )
        ;

        var b = document.getElementById('body');
        console.info(b);
        var body = $("body");
        selectMO.observe(
            b,
            {
                childList: true,
                characterData: true,
                subtree: true
            }
        );

        addHanlders.push({
            selector: "fr-wrapper a",
            handler: function (e) {
                console.debug(e)
            }
        });

        addHanlders.push({
            selector: "textarea.froala",
            handler: function (e) {
                var linkList = window.listOfShortcodeLinks || [];
                e.froalaEditor({//font-family,
                    imageUpload: false,
                    fileUpload: false,
                    imageManagerLoadURL: '/admin/en_US/file/all',
                    colorsBackground: [
                        "#00a8ff", "#9c88ff", "#fbc531", "#4cd137", "#487eb0", "REMOVE",
                        "#0097e6", "#8c7ae6", "#e1b12c", "#44bd32", "#40739e", "#fff",
                        "#e84118", "#f5f6fa", "#7f8fa6", "#273c75", "#353b48", "#333",
                        "#c23616", "#dcdde1", "#718093", "#192a56", "#2f3640", "#000"
                    ],
                    colorsStep: 6,
                    colorsText: [
                        "#00a8ff", "#9c88ff", "#fbc531", "#4cd137", "#487eb0", "REMOVE",
                        "#0097e6", "#8c7ae6", "#e1b12c", "#44bd32", "#40739e", "#fff",
                        "#e84118", "#f5f6fa", "#7f8fa6", "#273c75", "#353b48", "#333",
                        "#c23616", "#dcdde1", "#718093", "#192a56", "#2f3640", "#000"
                    ],
                    //iframe: true,
                    scrollableContainer: '#content',
                    toolbarButtons: ['fullscreen', 'bold', 'italic', 'underline', 'strikeThrough', 'subscript', 'superscript', '|', 'fontFamily', 'fontSize', 'color', 'inlineStyle', 'paragraphStyle', '|', 'paragraphFormat', 'align', 'formatOL', 'formatUL', 'outdent', 'indent', 'quote', '-', 'insertLink', 'insertImage', 'insertVideo', 'embedly', 'insertTable', '|', 'emoticons', 'specialCharacters', 'insertHR', 'selectAll', 'clearFormatting', '|', 'print', 'spellChecker', 'help', 'html', '|', 'undo', 'redo']
                    /*  DEFAULT WAS
            ['fullscreen', 'bold', 'italic', 'underline', 'strikeThrough', 'subscript', 'superscript', '|', 'fontFamily', 'fontSize', 'color', 'inlineStyle', 'paragraphStyle', '|', 'paragraphFormat', 'align', 'formatOL', 'formatUL', 'outdent', 'indent', 'quote', '-', 'insertLink', 'insertImage', 'insertVideo', 'embedly', 'insertFile', 'insertTable', '|', 'emoticons', 'specialCharacters', 'insertHR', 'selectAll', 'clearFormatting', '|', 'print', 'spellChecker', 'help', 'html', '|', 'undo', 'redo']*/,
                    linkAutoPrefix: '',
                    linkList: linkList
                });
                console.info(linkList)
            }
        });
        removeHandlers.push({
            selector: "textarea.froala",
            handler: function (e) {
                e.froalaEditor("destroy")
            }
        });

        addHanlders.push({
            selector: "select.selectmenu",
            handler: function (e) {
                e.selectmenu();
            }
        });

        removeHandlers.push({
            selector: "select.selectmenu",
            handler: function (e) {
                console.debug(e);
                e.selectmenu("destroy");
            }
        });


        addHanlders.push({
            selector: "input[type=checkbox].switch",
            handler: function (e) {
                e.bootstrapSwitch();
                $("label[for=" + e.attr("id") + "]").hide();
            }
        });

        addHanlders.push({
            selector: ".nittro-dialog-button",
            handler: function (e) {
                e.addClass("btn")
                $(e).each(function (i, value) {
                    value = $(value)
                    console.debug(value)
                    console.debug(value.attr("data-action"))
                    if (value.attr("data-action") === '"confirm"')
                        value.addClass("delete")
                })
            }
        })

        body.on("selectmenuchange change", "select.openOnChange", function () {
            var selected = $(this).find("option:selected");
            var href = selected.attr("data-href");
            if (!href) throw new Error("Href not found");
            di.getService("page").open(href, "get");
        });


        addHanlders.forEach(function (value) {
            try {
                var els = $(value.selector);
                if (els.length)
                    value.handler(els);
            } catch (e) {
                console.error(e)
            }
        })
    });
});