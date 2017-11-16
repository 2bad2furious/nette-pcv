_stack.push(function (di) {
        jQuery(document).ready(function ($) {

                var HeaderOpener = $("#header-opener");

                //style purposes
                $("body").addClass("js");

                HeaderOpener.on("click", function () {
                    var opener = $(this);
                    console.info(opener.hasClass("active"), opener);
                    var openClass = opener.attr("class-open");
                    var closeClass = opener.attr("class-close");
                    var body = $("body");
                    if (opener.hasClass(closeClass)) {
                        opener.removeClass(closeClass);
                        opener.addClass(openClass);
                        body.addClass("header-closed");
                    } else {
                        opener.addClass(closeClass);
                        opener.removeClass(openClass);
                        body.removeClass("header-closed");
                    }
                })

                HeaderOpener.addClass(HeaderOpener.attr("class-close"));
            }
        )
    }
)