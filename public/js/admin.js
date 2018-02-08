console.info("hello");
_stack.push(function (di) {
    jQuery(document).ready(function ($) {
        var body = $("body");

        body.on("selectmenuchange change", "select.openOnChange", function () {
            var selected = $(this).find("option:selected");
            var href = selected.attr("data-href");
            if (!href) throw new Error("Href not found");
            di.getService("page").open(href, "get");
        });

        var checkboxUI = function (elements) {
            elements.each(function (i, e) {
                $(e).bootstrapSwitch();
                $(e).siblings("label").hide()
            })
        };

        var selectUI = function (elements) {
            console.info(elements);
            elements.selectmenu();
            elements.on("remove", function () {
                elements.selectmenu("destroy");
            })
        };

        var froalaUI = function (elements) {
            var linkList = window.listOfShortcodeLinks || [];
            elements.froalaEditor({//font-family,
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
                iframe: true,
                scrollableContainer: '#content',
                toolbarButtons: ['fullscreen', 'bold', 'italic', 'underline', 'strikeThrough', 'subscript', 'superscript', '|', 'fontFamily', 'fontSize', 'color', 'inlineStyle', 'paragraphStyle', '|', 'paragraphFormat', 'align', 'formatOL', 'formatUL', 'outdent', 'indent', 'quote', '-', 'insertLink', 'insertImage', 'insertVideo', 'embedly', 'insertTable', '|', 'emoticons', 'specialCharacters', 'insertHR', 'selectAll', 'clearFormatting', '|', 'print', 'spellChecker', 'help', 'html', '|', 'undo', 'redo']
                /*  DEFAULT WAS
['fullscreen', 'bold', 'italic', 'underline', 'strikeThrough', 'subscript', 'superscript', '|', 'fontFamily', 'fontSize', 'color', 'inlineStyle', 'paragraphStyle', '|', 'paragraphFormat', 'align', 'formatOL', 'formatUL', 'outdent', 'indent', 'quote', '-', 'insertLink', 'insertImage', 'insertVideo', 'embedly', 'insertFile', 'insertTable', '|', 'emoticons', 'specialCharacters', 'insertHR', 'selectAll', 'clearFormatting', '|', 'print', 'spellChecker', 'help', 'html', '|', 'undo', 'redo']*/,
                linkAutoPrefix: '',
                linkList: linkList
            });
            console.info(linkList)
        };

        body.onCreate("textarea.froala", froalaUI)

        body.onCreate("select", selectUI);


        body.onCreate("input[type=checkbox]", checkboxUI);


    });
});