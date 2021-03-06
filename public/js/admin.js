console.info("hello");
_stack.push(function (di) {
    jQuery(document).ready(function ($) {
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
                                try {
                                    var els = nodeAdded.find(selector);
                                    if (els.length)
                                        addHandler.handler(els);
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
                                try {
                                    var els = nodeDeleted.find(selector);
                                    if (els.length)
                                        removeHandler.handler(els);
                                } catch (e) {
                                    console.error(e);
                                }
                            }
                        }
                    }
                }
            }
        );

        var b = document.getElementById('body');
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
                    toolbarButtons: ['fullscreen', 'bold', 'italic', 'underline', 'strikeThrough', 'subscript', 'superscript', '|', 'fontFamily', 'fontSize', 'color', 'inlineStyle', 'paragraphStyle', '|', 'paragraphFormat', 'align', 'formatOL', 'formatUL', 'outdent', 'indent', 'quote', '-', 'insertLink', 'insertImage', 'insertVideo', 'embedly', 'insertTable', '|', 'emoticons', 'specialCharacters', 'insertHR', 'selectAll', 'clearFormatting', '|', 'print', 'spellChecker', 'help', 'html', '|', 'undo', 'redo'],
                    htmlAllowedTags: ['a', 'abbr', 'address', 'area', 'article', 'aside', 'audio', 'b', 'base', 'bdi', 'bdo', 'blockquote', 'br', 'button', 'canvas', 'caption', 'cite', 'code', 'col', 'colgroup', 'datalist', 'dd', 'del', 'details', 'dfn', 'dialog', 'div', 'dl', 'dt', 'em', 'embed', 'fieldset', 'figcaption', 'figure', 'footer', 'form', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'header', 'hgroup', 'hr', 'i', 'iframe', 'img', 'input', 'ins', 'kbd', 'keygen', 'label', 'legend', 'li', 'link', 'main', 'map', 'mark', 'menu', 'menuitem', 'meter', 'nav', 'noscript', 'object', 'ol', 'optgroup', 'option', 'output', 'p', 'param', 'pre', 'progress', 'queue', 'rp', 'rt', 'ruby', 's', 'samp', 'script', 'style', 'section', 'select', 'small', 'source', 'span', 'strike', 'strong', 'sub', 'summary', 'sup', 'table', 'tbody', 'td', 'textarea', 'tfoot', 'th', 'thead', 'time', 'title', 'tr', 'track', 'u', 'ul', 'var', 'video', 'wbr', "script"],
                    htmlRemoveTags: []
                    /*  DEFAULT WAS
            ['fullscreen', 'bold', 'italic', 'underline', 'strikeThrough', 'subscript', 'superscript', '|', 'fontFamily', 'fontSize', 'color', 'inlineStyle', 'paragraphStyle', '|', 'paragraphFormat', 'align', 'formatOL', 'formatUL', 'outdent', 'indent', 'quote', '-', 'insertLink', 'insertImage', 'insertVideo', 'embedly', 'insertFile', 'insertTable', '|', 'emoticons', 'specialCharacters', 'insertHR', 'selectAll', 'clearFormatting', '|', 'print', 'spellChecker', 'help', 'html', '|', 'undo', 'redo']*/,
                    linkAutoPrefix: '',
                    linkList: linkList
                });
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
                e.addClass("btn");
                $(e).each(function (i, value) {
                    value = $(value);
                    if (value.attr("data-action") === '"confirm"')
                        value.addClass("delete")
                })
            }
        });

        body.on("selectmenuchange change", "select.openOnChange", function () {
            var selected = $(this).find("option:selected");
            var href = selected.attr("data-href");
            if (!href) throw new Error("Href not found");
            di.getService("page").open(href, "get");
        });


        body.on("click", ".fr-element a,.fr-view a", function (e) {
            e.preventDefault();
        });

        addHanlders.forEach(function (value) {
            try {
                console.debug(value)
                var els = $(b).find(value.selector);
                if (els.length)
                    value.handler(els);
            } catch (e) {
                console.error(e)
            }
        })
    });
});