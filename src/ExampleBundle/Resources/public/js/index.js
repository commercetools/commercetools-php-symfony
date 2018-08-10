/**
 */

var required = false;

function debounce(fn, delay) {
    var timer = null;
    return function () {
        var context = this, args = arguments;
        clearTimeout(timer);
        timer = setTimeout(function () {
            fn.apply(context, args);
        }, delay);
    };
}

jQuery(document).on('submit', '.add-review-form', function(e){
    e.preventDefault();
    let data = $(this).serialize();
    let ajaxUrl = $(this).data('submit-url');

    $.ajax({
        url: ajaxUrl,
        type: 'POST',
        dataType: 'json',
        data: data,
        success:function(data){

            if(data.success === true){
                $.ajax({
                    url: data.fetchReviewsUrl,
                    type: 'GET',
                    dataType: 'html',
                    success: function(response){
                        $('.reviews-container').parent().html(response);
                    }
                });
            }

            if(data.success === false){
                console.log("probably need to handle errors");
            }

            // $('#loadingSpinner').hide();
            // $('#mySubmitButton').attr("disabled",false);
        }
    });
});

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

    // disable autocomplete, anoying
    $("#form_search").attr("autocomplete", "off");

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

    // animate the search results container
    $("#form_search").keypress(debounce(function () {
        $(".search-container").css("display", "block");

    }, 1));

    // ajax handeling
    $("#form_search").on("keyup", debounce(function (event) {
        var searchTerm = $(event.target).val();
        var productUrl = '/product/slug/';
        var itemUrl = '/suggest/';
        var col4 = '<div class="col-md-4">';

        $("#search-results").html("");

        $.ajax({
            type: 'POST',
            url: itemUrl + searchTerm,
            dataType: "json"
        }).done(function (data) {
            if(data) {
                for (i in data) {
                    // build founded items
                    var img = '<img class="ctp-img" src="' + data[i].image + '" alt="' + data[i].name + '"/>';
                    var price = '<p>' + data[i].price + '</p>';
                    var product = col4+'<a href="' + productUrl + data[i].link + '"><div><h3>' + data[i].name + '</h3>' + img + '<p>' + data[i].desc + '</p>'+ price +'</div></a></div>';
                    $("#search-results").append(product);
                }
            }

            var jsonData = data.toString();

            if (jsonData.length <= 0){
                setTimeout(function emptyResult(){
                    var msg = '<div class="empty-result"><h3>No result</h3><p>Try a different search query</p></div>';
                    $("#search-results").append(msg);
                }, 400);
            }

            // activate the loading gif
            $(document).on({
                ajaxStart: function() {
                    $(".loading").css("display", "block");
                },
                ajaxStop: function() {
                    $(".loading").css("display", "none");
                }
            });

            //check for input
            $("#form_search").on("keyup", function(){
                if( $(this).val().length <= 0 ) {
                    $(".search-container").css("display", "none");
                }
            });

        });

    }, 300));//time ajax request

});
