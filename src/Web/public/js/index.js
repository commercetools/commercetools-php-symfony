/**
 * Created by ylambers on 19/04/16.
 */

var required = false;
jQuery(document).ready(function($){
    $("#form_check").on("click",function(){
        //$("#form_billingAddress").fadeToggle();
        $("#form_billingAddress input[data-required='true']").attr('required',required);
        required = !required;
    });

    var required = true;
    jQuery(document).ready(function($){
        $("#form_check").on("click",function(){
            $("input[id^='form_shippingAddress_']").each(function(){
                var id = $(this).attr("id").replace("shipping","billing");
                $("#"+id).val($(this).val());
            });
        });
    });
})

