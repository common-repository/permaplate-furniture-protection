  
jQuery(document).ready(function(){
	 jQuery('._warranty_field').css('display','none');

    var i = 1;
   jQuery('.single_add_to_cart_button ').click(function(event) {
        var popup = jQuery("#popup-type").val();
        clickaddtocart(event, i, popup); 
    });

    function clickaddtocart(event, i, popup)
    {
         if(jQuery("input[name='warranty']:checked").val().includes("none") == true && i % 2 != 0 && popup==1) {
          event.preventDefault();
            jQuery("#popupModel").show();
          }
    }

     jQuery("#nothankspopup").click(function(e){
       e.preventDefault();
        i++;
          setTimeout(function(){
              jQuery(".single_add_to_cart_button").click();
          },200);
          
    });
	
      jQuery(document).on('click','.lin_modal_btn',function(e){
    
    e.preventDefault();
    
    jQuery('#planModal').modal('show');
    
  });
  

   jQuery("#addpopup").click(function(e){
     e.preventDefault();
    jQuery("input[name='warranty']:checked").prop('disabled',true);
    jQuery("input[name='warranty']:checked").nextAll(':radio:first').prop('checked', true);
      jQuery("#popupModel").hide();
   });
   
   jQuery("#addpopup").click(function(e){
     e.preventDefault();
    jQuery("input[name='warranty']:checked").prop('disabled',true);
    jQuery("input[name='warranty']:checked").prevAll(':radio:last').prop('checked', true);
      jQuery("#popupModel").hide();
   });


    jQuery(".close1").on("click", function(e){
        jQuery("#popupModel").hide();      
    });


    jQuery(".cancelContract").on('click', function(event){
       var trs = jQuery( "td" ).find('.cancelContract');
        if(trs){
      	let orderID = jQuery(this).attr('data-orderId');
      	let lineItemID = jQuery(this).attr('data-lineItemID');
        let quantity = jQuery(this).attr('id');
        let cancelby = jQuery(this).attr('data-cancelby');
      
            swal({
                  title: "Are you sure?",
                  text: "You would like to cancel service for this item from the order",
                  icon: "warning",
                  buttons: true,
                  dangerMode: true,
              })
          .then((willDelete) => {
              if (willDelete) { 
              jQuery('.admin-loader-div').show();
      	      jQuery.ajax({
                type : "POST",
                url : ajax.url,
                 data : {
                  nonce: ajax.nonce,
                	action: "cancelContract",
                  orderID: orderID,
                  lineItemID:lineItemID,
                  cancelby: cancelby
                },
              success: function(resp) {           
                console.log(resp);
              	if(resp !=''){
                    var re = JSON.parse(resp);
                     //console.log(re);
                      jQuery('#loaderImg').hide();
                      if(re.res !== "" && re.status == '200'){

                          var qprice = parseFloat(re.res);
                          var total = (parseFloat(qprice*quantity)).toFixed(2);
						              //total = total/100;
						             //var crnSymbol = parseFloat(re.currency_symbol);
                          swal({
                                title: "Success",
                                text: "Contract has been cancelled successfully, we have initiated the refund of $"+total,
                                icon: "success",
                                button: "Ok",
                              }).then((value) => { 
								jQuery('.admin-loader-div').hide();
                               location.reload();
                              });
                          } else if(re.status == '250'){
                                  swal({
                                    title: "Error!",
                                    text: "Cancel Request outside the cancel period,Please contract Administrator",
                                    icon: "error",
                                    button: "Ok",
                                  }).then((value) => {
                                      location.reload();
                                  });
                              }
                          else {
        
                              swal({
                              title: "Error!",
                              text: "Cancelation request failed. Please try again later.",
                              icon: "error",
                              button: "Ok",
                              }).then((value) => {
                              location.reload();
                              });
                           }
                      }else {
                              swal({
                              title: "Error!",
                              text: "Something Went Wrong Try Again!",
                              icon: "error",
                              button: "Ok",
                              }).then((value) => {
                              location.reload();
                              });
                        }
                    }
                 });
              }else{
                 jQuery('.admin-loader-div').hide(); 
              }
         });
      }
  	
  });


	jQuery('.price-variation').click(function(){
	  jQuery('.variations select').each(function () {
			if(jQuery(this).val() == ""){
			  swal("Please select the dropdown");
			  jQuery('#war_0').prop("checked", true);
			  return false;
			}
		});

	  if(jQuery(this).is(":checked")){
		var warrantyPrice = jQuery(this).attr('warranty-price');
		var productId = jQuery(this).attr('product-id');
		var planId = jQuery(this).attr('plan-id');
		var warrantyId = jQuery('.warranty-id').val();
		var variationId = jQuery('input.variation_id').val();
	  
			jQuery.ajax({
			type : "POST",
			url : ajax.url,
			data : { 
			  nonce: ajax.nonce,  
			  action: "pricechange",
			  warrantyPrice:warrantyPrice,
			  productId:productId,
			  planId:planId,
			  warrantyId:warrantyId,
			  variationId:variationId,
			},
			success: function(response) {
			  var exist = jQuery('.woocommerce-variation-price .price ins .woocommerce-Price-amount bdi');
			   var respprice = parseFloat(response).toFixed(2);
			  if(exist.length){
				jQuery('.woocommerce-variation-price .price ins .woocommerce-Price-amount bdi').text("$"+respprice);
			  }else{
				jQuery('.price .woocommerce-Price-amount bdi').text("$"+respprice);
			  }
			  
			}
			 
		  });
	  }
    });

    jQuery(document).on('click', '.price-variation-on-cart', function(){

        jQuery('#loaderImg').show();
        var warrantyPrice = jQuery(this).attr('warranty-price');
        var productId = jQuery(this).attr('product-id');
        var planId = jQuery(this).attr('plan-id');
        var itemKey = jQuery(this).attr('item-key');
        var term = jQuery(this).attr('term');
        var warrantyId = jQuery('.warranty-id').val();

        jQuery.ajax({
        type : "POST",
        url : ajax.url,
        data : { 
          nonce: ajax.nonce,  
          action: "pricechangeoncart",
          warrantyPrice:warrantyPrice,
          productId:productId,
          planId:planId,
          warrantyId:warrantyId,
          term:term,
          itemKey:itemKey,
        },
        success: function(response) {

          setTimeout(() => {
            jQuery('#loaderImg').hide();
          }, 3000);
          window.location=document.location.href;
        }
         
      });
    });

    jQuery(document).on('click','.warrantyButton', function(){
         jQuery(this).closest('tr').find('.warranty_option').css("display", "block"); 
     });


     //cancel the order in customer end conformation
     //jQuery('.woocommerce-button.button.cancel').click( function(event){
     // event.preventDefault();
     // var href = this.href;
     // console.log(this.href);
     // swal({
           // title: "Are you sure?",
          //  text: "You would like to cancel service for this Complete order",
          //  icon: "warning",
          //  buttons: true,
           // dangerMode: true,
       // })
        //.then((willDelete) => {
         // if (willDelete) {
            //  window.location.href = href;
         // } else {
          //  return false;
          //}
        //});
      // });

 window.onload = function() {
    var productPrice = jQuery('.product-price').val();
    if(typeof productPrice === undefined){
      var warrantyPrice = jQuery('.price-variation:checked').attr('warranty-price');
      productPrice = parseFloat(productPrice);
      warrantyPrice = parseFloat(warrantyPrice)
      var total =  productPrice + warrantyPrice;
       if(total > 0){
       var exist = jQuery('.woocommerce-variation-price .price ins .woocommerce-Price-amount bdi');
        if(exist.length){
        jQuery('.woocommerce-variation-price .price ins .woocommerce-Price-amount bdi').text("$"+total.toFixed(2));
        }else{
        jQuery('.price ins .woocommerce-Price-amount bdi').text("$"+total.toFixed(2));
        }
      }else{
        jQuery('.price .woocommerce-Price-amount bdi').text("$"+productPrice.toFixed(2));
      }
    }
    
}

});