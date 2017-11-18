_stack.push(function (di) {
    jQuery(document).ready(function ($) {
        /* */

        var frm = di.getService('formLocator').getForm('frm-pageEditForm');
        console.info(frm);

        window.Nette.validators.url_rule1 = function (elem, args, val) {
            /* */
            $.ajax({
                url: "url",
                data: data,
                success: function (r) {
                    if (r === false) {
                        frm.trigger('error', {
                            element: elem,
                            message: args
                        });
                        /* */
                    } else {
                        /* */
                    }
                }
                ,
                failure:function () {
                    /* */
                }
            })

            return true;
        };
    })
})