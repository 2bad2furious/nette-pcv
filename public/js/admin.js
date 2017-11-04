jQuery(document).ready(function ($) {
    var HeaderOpener = $("#header-opener");
    var ckSelector = "textarea[data-ckeditor=true]";

    var InitJs = function () {
        var noJs = $(".no-js");
        noJs.addClass("js");
        noJs.removeClass("no-js");
    }

    var initHeader = function () {
        HeaderOpener.addClass(HeaderOpener.attr("class-close"));
    }

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

    ckInited = function (editor) {
        console.log("inited", editor)
    }

    InitJs(); //TODO call this everytime we load a new page
    initHeader();

    if ($(ckSelector).length) {
        console.log("initing")
        ClassicEditor
            .create(document.querySelector(ckSelector))
            .then(function (editor) {
                ckInited(editor)
            })
            .catch(function (error) {
                alert(error);
                console.error(error);
            });
    }

    //var page = _context.lookup("di").getService("page");
})