_stack.push(function () {
    jQuery(document).ready(function ($) {
        $(window).resize(function () {
            console.debug($(window).innerHeight());
            console.debug($(window).innerWidth())
            console.debug("\n");
        });
    })
});