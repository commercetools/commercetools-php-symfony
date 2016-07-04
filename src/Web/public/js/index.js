/**
 * Created by ylambers on 19/04/16.
 */

var required = false;
jQuery(document).ready(function($){
    //Add class to submitbutton form
    $('#form_Submit').addClass('btn btn-primary spacer');

    // Clear form function
    $("#defaultCheck").on("click",function(){
        var clear = confirm('Are you sure?');

        if (clear == true){
            $("[id^='form_shippingAddress_']").val("");
            $("[id^='form_billingAddress_']").val("");
        }
    });

    $("#form_check").on("click",function(){
        if ($("#form_check:checked").val()) {
            $("#form_billingAddress").fadeOut();
        } else {
            $("#form_billingAddress").fadeIn();
        }
        $("#form_billingAddress input[data-required='true']").attr('required',required);
        required = !required;
    });

    $("#form_check").on("click",function(){
        $("[id^='form_shippingAddress_']").each(function(){
            var id = $(this).attr("id").replace("shipping","billing"),
                $country = $("#form_shippingAddress_country").val(),
                $salutation = $("#form_shippingAddress_salutation").val();
            if ($("#"+id).val() == '') {
                $("#"+id).val($(this).val());
            }
            $('#form_billingAddress_country').val($country);
            $('#form_billingAddress_salutation').val($salutation);
         });
    });
})