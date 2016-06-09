/**
 * Created by ylambers on 19/04/16.
 */

var required = false;
jQuery(document).ready(function($){
    $("#form_check").on("click",function(){
        $("#form_billingAddress").fadeToggle();
        $("#form_billingAddress input[data-required='true']").attr('required',required);
        required = !required;
    });

    $("#form_check").on("click",function(){
        $("[id^='form_shippingAddress_']").each(function(){
            var id = $(this).attr("id").replace("shipping","billing");
            if ($("#"+id).val() == '') {
                $("#"+id).val($(this).val());
            }
        });
    });
})


