console.info("hello");
_stack.push(function (di) {
    jQuery(document).ready(function ($) {
        $("body").on("selectmenuchange change", "select.openOnChange", function () {
            var selected = $(this).find("option:selected");
            var href = selected.attr("data-href");
            if (!href) throw new Error("Href not found");
            di.getService("page").open(href, "get");
        });

        $("body").onCreate("select:not(.no-ui)", function (elements) {
            console.info(elements)
            elements.selectmenu();
            elements.on("remove", function () {
                Managing
                elements.selectmenu("destroy");
            })
        }, true);

        $("body").onCreate("input[type=checkbox]:not(.no-ui)", function (elements) {
            console.info(elements)
            elements.siblings("label").hide()
            elements.each(function (i, element) {
                var e = $(element);
                console.info(e, e.parent("label"), e.parent("label").contents())
                e.parent("label").contents().filter(function (i,child) {
                    console.info(i)
                    console.info(child);
                    /*console.info(child.nodeType)*/
                    return child.nodeType === 3;
                }).remove();
            })
            elements.bootstrapSwitch();
        });
    });
});