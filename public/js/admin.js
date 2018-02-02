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

        body.onCreate("select:not(.no-ui)", function (elements) {
            console.info(elements);
            elements.selectmenu();
            elements.on("remove", function () {
                elements.selectmenu("destroy");
            })
        }, true);

        body.onCreate("input[type=checkbox]:not(.no-ui)", function (elements) {

            elements.bootstrapSwitch();
            elements.siblings("label").hide()
        });
    });
});