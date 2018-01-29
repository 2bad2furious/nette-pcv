_stack.push(function () {
    jQuery(document).ready(function ($) {
        $("body").on("selectmenuchange change", "select.openOnChange", function () {
            var selected = $(this).find("option:selected");
            var href = selected.attr("data-href");
            if (!href) throw new Error("Href not found");
            di.getService("page").open(href, "get");
        });

        $("body").onCreate("select:not(.no-ui)", function (elements) {
            elements.selectmenu();
        }, true);
    });
});