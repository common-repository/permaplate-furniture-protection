jQuery( document ).ready(function() {
	
jQuery('#saveconfig').click(function(){
    const enablequick = jQuery('#quick_cover_module').val();
    const warranty_status = jQuery('#warranty_status').val();
	const backup_status = jQuery('#backup_status').val();
    const autosync = jQuery('#autosync').val();
    const apikey = jQuery('#apikey').val();
    const storeid = jQuery('#storeid').val();
    const batchsize = jQuery('#batchsize').val();
    const enablepopup = jQuery('#enablepopup').val();
    const email = jQuery("#email").val();
    
     if (email != "") {
     // var validRegex =
     //   /^([a-zA-Z0-9_\-\.]+)@([a-zA-Z0-9_\-\.]+)\.([a-zA-Z]{2,5})$/;
      if (isValidEmail(email)) {
        jQuery("#errormessage").html("");
   if(jQuery("#apikey").val().length != 0 && jQuery("#storeid").val().length != 0)
   {
    jQuery('.admin-loader-div').show();
    jQuery.ajax({
        type : "POST",
        url : ajax.url,
        data : {
           nonce: ajax.nonce,
           action: "saveconfig",
           warranty_status:warranty_status,
		       backup_status:backup_status,
           ebaleqiuck:enablequick,
           autosync:autosync,
           apikey:apikey,
           storeid:storeid,
           batchsize:batchsize,
           enablepopup:enablepopup,
           email:email,
        },
        success: function(response) {
        if(response == 1){
          swal("Configuration save successfully");
		  jQuery('.sync_button').removeClass('changed-input');
           jQuery('.admin-loader-div').hide();
		   
		   jQuery('#quick_cover_module').removeClass('changed-input');
           jQuery('#autosync').removeClass('changed-input');
           jQuery('#warranty_status').removeClass('changed-input');
           jQuery('#enablepopup').removeClass('changed-input');
           jQuery("#email").removeClass("changed-input");
           jQuery('#apikey').removeClass('changed-input');
           jQuery('#storeid').removeClass('changed-input');
           jQuery('#batchsize').removeClass('changed-input');
           jQuery('#backup_status').removeClass('changed-input');
		   
		    setTimeout(function(){// wait for 5 secs(2)
            location.reload(); // then reload the page.(3)
            }, 2000);

          }
		  
		   else{
            jQuery('.admin-loader-div').hide();
          }
       
        }
    });
  }
  
 else
    {
        swal("API Key & Store ID cannot be null", "", "error");
    }
      } else {
          swal("Enter the valid email.", "", "error");
        // jQuery("#errormessage").html("Enter the valid email.");
       
      }
    } else {
    //   jQuery("#errormessage").html("Email is required.");
     swal("Email is required.", "", "error");
    }

});

function isValidEmail(email) {
  const regex = /^([a-zA-Z0-9_\-\.]+)@([a-zA-Z0-9_\-\.]+)\.([a-zA-Z]{2,5})$/; // Regular expression to match email format
  if (!regex.test(email)) {
    return false; // Invalid email format
  }

  const domain = email.split('@')[1]; // Extract domain from email
  const splitDomain = domain.split('.'); // Split domain into parts
  if (splitDomain.length > 2 || splitDomain[0] === splitDomain[1]) {
    return false; // Invalid domain format (double domain or more than 2 domain parts)
  }

  return true; // Email is valid and domain is correctly formatted
}


 jQuery('#product-update').on('click', function(){
      var warranty = jQuery('.is_warranty').val();
      var sync = jQuery('.is_sync').val();
      var category = jQuery('.category').val();
      if(warranty == ""){
        if(sync == ""){
            swal("Please make proper selection from the dropdowns.");
          return false;
        }
      }

      if(category == ""){
         swal("Please make proper selection from the dropdowns.");
         return false;
      }

      jQuery('.admin-loader-div').show();
      jQuery.ajax({
            type : "POST",
            url : ajax.url,
            data : {
               nonce: ajax.nonce,
               action: "updateLocalProduct",
               warranty:warranty,
               sync:sync,
               category:category,
            },
            success: function(response) {
             //var data = JSON.parse(response);
			  var data = response;
              if(data.warranty > 0){
                if(data.sync > 0){
                  swal("Total updated Warranty "+data.warranty+ " Total updated Sync "+data.sync);
                  jQuery('.admin-loader-div').hide();
                }else{
                  swal("Total updated Warranty "+data.warranty);
                  jQuery('.admin-loader-div').hide();
                }
              }else{
                if(data.sync > 0){
                  swal("Total updated Sync "+data.sync);
                    jQuery('.admin-loader-div').hide();
                }else{
                  swal("No Record to update");
                    jQuery('.admin-loader-div').hide();
                }
                
              }
            
            }
        });

    });
	
	jQuery('#testautentication').on('click', function(){
   var apikey = jQuery('#apikey').val();
   var storeid = jQuery('#storeid').val();

    var d = new Date();
    var strDate = d.getFullYear() + "/" + (d.getMonth()+1) + "/" + d.getDate();
    var time = d.getHours() + ":" + d.getMinutes() + ":" + d.getSeconds();
     jQuery.ajax({
        type : 'POST',
            url : ajax.url,
            data : {
              nonce: ajax.nonce,
              action: "testauthentication",
              apikey:apikey,
              storeid:storeid,
              currentTime: strDate +' '+time,
           }, 
            beforeSend: function() {
               jQuery('.test').html('Loading...');
          },
           success: function(data) {

              var datastatus = data;
              if(datastatus == 200) {

                var d = new Date();
                var strDate = (d.getMonth()+1) + "/" + d.getDate() + "/"+ d.getFullYear();
                var time = d.getHours() + ":" + d.getMinutes() + ":" + d.getSeconds();
                
                var message ="Authentication Successful" +' '+strDate+' '+ time;
                
                jQuery('#authmessage').removeClass('text-danger')
                jQuery('#authmessage').text(message).addClass('text-success');
                jQuery('.test').html('Test Authentication');
               
              }
              else{
                var d = new Date();
                var strDate = (d.getMonth()+1) + "/" + d.getDate() + "/"+ d.getFullYear();
                var time = d.getHours() + ":" + d.getMinutes() + ":" + d.getSeconds();
                var message ="Authentication Failed" +' '+ strDate+' '+ time;
                jQuery('#authmessage').removeClass('text-success')
                jQuery('#authmessage').text(message).addClass('text-danger');
               jQuery('.test').html('Test Authentication');
             
              }
            
            }
         });
     });

//change get method to post metho from core functionality for bulk update
   // jQuery('#bulk_edit ').click(function(event) {

  // jQuery("#posts-filter").attr("method", 'POST');
       
   // });


// code for batch product sync

  var cancelButton = false;
  var batchCounter = 1;
  var batchSize = '';
  var message = '';
  var totalBatch = 0;
  var successBatch = 0;
  var failBatch = 0;
  jQuery(".cancel_button").click(function() {
    cancelButton = true;
  });

   jQuery(document).on('click','.sync_button',function() {
	   jQuery('#product_sync').addClass('changed-input');
	   //jQuery('#batchsyncstatus').css("display", "none");
	   jQuery('.cancel_button').css("display", "block");
	   jQuery('.printBatches').css("display", "block");
	   jQuery('.sync_button').prop('disabled',true);
       console.log('hhhhh');
	 if (jQuery("#apikey").val().length != 0 && jQuery("#storeid").val().length != 0) {
		 jQuery('.sync_button').prop('disabled',true);
        jQuery('.syncLoader').show();
    if (jQuery("#quick_cover_module").val() == 1) {
      if (jQuery("#apikey").val().length != 0 && jQuery("#storeid").val().length != 0) {
        var batchSize = jQuery('#batchsize').val();
        console.log(batchSize); 
        jQuery.ajax({
          type: 'GET',
          url : ajax.url,
          data: {
             nonce: ajax.nonce,
             action: "getBatch", 
             batchsize:batchSize
          },
          success: function(data) {
            data = JSON.parse(data);
			totalBatch = data.chunksCount;
            if (data.status == 201) {
              swal("There is no data to sync.", "", "error");
              jQuery('.syncLoader').hide();
			  jQuery('.sync_button').prop('disabled',false);
			  jQuery('.cancel_button').css("display", "none");
			  jQuery('#product_sync').addClass('changed-input');
              // jQuery('.displayTime').hide();
              
            } else {
              message = '';
              successBatch = 0;
              failBatch = 0;
			if(totalBatch <= 0)
            {
          
              jQuery('#batchsyncstatus').hide();
           }
           else if(isNaN(totalBatch))
          {
              jQuery('#batchsyncstatus').hide();
          }
          else{
            jQuery('#batchsyncstatus').show();
            }
             
              jQuery("#totalBatchPrint").text(totalBatch);
              jQuery("#successBatchPrint").text(successBatch);
              jQuery("#failedBatchPrint").text(failBatch);
              // console.log(batchSize, '0', data.chunksCount);
             batchTest(batchSize, failBatch,successBatch,totalBatch,data.chunksCount);
            }
          }
        });
      }
	}	    
	  else {
        swal("API Key & Store ID cannot be null", "", "error");
      }
    } else {
      swal("API Key & Store ID cannot be null", "", "error");
    }

   });

    function batchTest(limit, failedCase,successCase,totalBatch, chunksCount) {
    jQuery.ajax({
      type: 'POST',
      url : ajax.url,
      data: {
       // "_token": "{{ csrf_token() }}",
        "batchSize": limit,
        "failedCase": failedCase,
        "successCase": successCase,
        "totalBatch": totalBatch,
		
        "action": "syncProduct",
        "api_key": jQuery("#apikey").val(),
        "quick_store_id": jQuery("#storeid").val()
      },
      success: function(data) {
        data = JSON.parse(data);
        if (data.success == 1) {
          successBatch = successBatch + 1;
        } else if (data.failBatch == 1) {
          failBatch = failBatch + 1;
        }

        if(cancelButton == false && batchCounter != chunksCount)
        {
          batchCounter = batchCounter + 1;
          // offset = parseInt(offset) + parseInt(limit);
         // console.log(limit, failBatch, chunksCount, batchCounter);
            batchTest(limit, failBatch,successBatch,totalBatch, chunksCount);
        }
        else if(batchCounter >= chunksCount)
        {
		  jQuery('#currentdateprint').text(data.batchStats);	
          message = message + chunksCount + ' Batch found' + '\n';
          message = message + successBatch + ' Batch succeeded' + '\n';
          message = message + failBatch + ' Batch failed' + '\n';
          batchCounter = 1;
          jQuery('.displayTime').show();
          swal("Sync process has completed.", message, "success");
		  jQuery('.syncLoader').hide();
		  jQuery('.cancel_button').css("display", "none");

         jQuery('.sync_button').prop('disabled',false);
		 jQuery('#product_sync').removeClass('changed-input');
          //setTimeout(function() {
           // jQuery('.displayTime').hide();
          //}, 10000);
        }
        else if(cancelButton == true)
        {

          message = message+totalBatch+' Batch found'+'\n';
          message = message+successBatch+' Batch is success'+'\n';
          message = message+failBatch+' Batch is Fail'+'\n';
          message = message+(totalBatch-successBatch-failBatch)+' Batches has been cancelled'+'\n';

          cancelButton = false;
          batchCounter = 1;
		  jQuery('#currentdateprint').text(data.batchStats);

          jQuery('.displayTime').show();
          swal("Sync Process has been completed.", message, "success");
          jQuery('.syncLoader').hide();
		      jQuery('.cancel_button').css("display", "none");
		      jQuery('.sync_button').prop('disabled',false);
		      jQuery('#product_sync').removeClass('changed-input');
          //setTimeout(function(){
           // jQuery('.displayTime').hide();
          //}, 10000);
          
          //console.log('abort');
        }
        jQuery("#totalBatchPrint").text(totalBatch);
        jQuery("#successBatchPrint").text(successBatch);
        jQuery("#failedBatchPrint").text(failBatch);
      }
    });
   }   

});

function activeModule() {
    const quick_cover_module = jQuery('#quick_cover_module').val();   
    if(quick_cover_module == 0){
      jQuery('input').attr('readonly', true);
      jQuery('#autosync').attr('disabled', true);
	    jQuery('#warranty_status').attr('disabled', true);
	    jQuery('#backup_status').attr('disabled', true);
      jQuery('#apikey').attr('disabled', true);
      jQuery('#storeid').attr('disabled', true);
      jQuery('#batchsize').attr('disabled', true);
      jQuery('#enablepopup').attr('disabled', true);
      jQuery("#email").attr("disabled", true);
      jQuery('.setting_disable').attr('disabled', true);
	    jQuery('#testautentication').attr('disabled', true);

    }else{
      jQuery('input').attr('readonly', false);
      jQuery('#autosync').attr('disabled', false);
	    jQuery('#warranty_status').attr('disabled', false);
	    jQuery('#backup_status').attr('disabled', false);
      jQuery('#apikey').attr('disabled', false); 
      jQuery('#storeid').attr('disabled', false);
      jQuery('#batchsize').attr('disabled', false);
      jQuery('#enablepopup').attr('disabled', false);
      jQuery("#email").attr("disabled", false);
      jQuery('.setting_disable').attr('disabled', false);
	   jQuery('#testautentication').attr('disabled', false);
    }

  }
  
 jQuery(window).load(function() {
    const quick_cover_module = jQuery('#quick_cover_module').val();
    if(quick_cover_module == 0){
      jQuery('input').attr('readonly', true);
      jQuery('#autosync').attr('disabled', true);
	  jQuery('#warranty_status').attr('disabled', true);
	  jQuery('#backup_status').attr('disabled', true);
      jQuery('#apikey').attr('disabled', true);
      jQuery('#storeid').attr('disabled', true);
      jQuery('#batchsize').attr('disabled', true);
      jQuery('#enablepopup').attr('disabled', true);
      jQuery("#email").attr("disabled", true);
      jQuery('.setting_disable').attr('disabled', true);
	  jQuery('#testautentication').attr('disabled', true);
    }
})