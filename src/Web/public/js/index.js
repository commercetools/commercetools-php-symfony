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
});

if ($('#form_billingAddress').is(':empty')){
    console.log('hi');
}