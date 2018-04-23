_stack.push(function () {
    jQuery(document).ready(function ($) {
        $("a").on("click", function (e) {
            var href = $(this).attr("href");
            if (href[0] !== "#") return;

            e.preventDefault();
            var target = $(href);
            if (target.length) {
                var toffsetTop = $(target.children()[0]).offset().top

                $("html, body").animate({
                    scrollTop: toffsetTop
                }, 400)
            }

        });
    })
})
;