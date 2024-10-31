    <?php
    global $wpdb;
    $result = $wpdb->get_results ( "SELECT * FROM app_configurations WHERE `user_id` = 1 ");
    if(!empty($result)){

       $id = $result[0]->id;
       $user_id = $result[0]->user_id; 
       $configuration_type = $result[0]->configuration_property;
	   $warranty_status = $result[0]->warranty_status;
	   $backup_status = $result[0]->backup_status;
       $synctype = $result[0]->sync_type;
       $api = $result[0]->api_key;
       $storeid = $result[0]->store_id;
       $batch =  $result[0]->batch_size;
       $popup =  $result[0]->popup_type;
	   $email =  $result[0]->email;
	   $current_date = $result[0]->updated_date;
	  


    }
	
	echo '<div class="admin-loader-div" style="display: none;"><img src='.esc_url(plugin_dir_url( __FILE__ )).'assets/images/loader.gif id="loaderImg" alt="loader" height="40" width=""></div>';

    ?>

    <!DOCTYPE>
    <html>
    <title>Permaplate</title>
    <head>
    
    </head>
    <body>
    <!-- Page Content -->
    <div class="form-internal">
      
    <form method="post" action="" name="customapp" id="customapp" onchange="activeModule();">
    <div class="w3-container w3-teal genset">
      <h3 class="genralsetting">General Settings</h3>
    </div>
      <div class="form-group row">
        <label for="enable" class="col-sm-2 col-form-label">QuickCover® Plugin</label>
        <div class="col-sm-10">
          <select class="form-control" id="quick_cover_module" name="quick_cover_module">
            <option>Select option</option>
            <option value="1" <?php echo esc_attr($configuration_type) == '1'? 'selected="selected"':""; ?>>Enable</option>
            <option value="0"<?php echo esc_attr($configuration_type) == '0'? 'selected="selected"':""; ?>>Disable</option>
          </select>
        <p>Select enable to turn the plugin on to sell plans. Select disable to turn the plugin off.</p>
      </div>
      </div>
      <div class="form-group row">
        <label for="autoproduct" class="col-sm-2 col-form-label">Auto Product Sync</label>
        <div class="col-sm-10">
          <select class="form-control" id="autosync" name="autosync">
            <option>Select option</option>
            <option value="1" <?php echo esc_attr($synctype) == '1'? "selected":""; ?>>Enable</option>
            <option value="0" <?php echo esc_attr($synctype) == '0'? "selected":""; ?>>Disable</option>
          </select>
          <p>If Auto Product Sync is enabled, after the initial bulk product catalog sync, any new products added to your product catalog will automatically be enabled to sync by QuickCover®.  This is regardless if product is eligible for service plan or not. Select disable to manually sync new product eligibility.</p>
        </div>
      </div>
	  
	  
	  <div class="form-group row">
        <label for="warranty_status" class="col-sm-2 col-form-label">Default Eligible for QuickCover®</label>
        <div class="col-sm-10">
          <select class="form-control" id="warranty_status" name="warranty_status">
            <option>Select option</option>
			<option value="1" <?php echo esc_attr($warranty_status) == '1'? "selected":""; ?>>Enable</option>
            <option value="0" <?php echo esc_attr($warranty_status) == '0'? "selected":""; ?>>Disable</option>
            
          </select>
          <p>If Default Eligible for Quickcover® is enabled, after the initial bulk product catalog sync, any new products added to your product catalog will automatically be flagged by QuickCover® as eligible for service plan coverage.  This is regardless if product is eligible for service plan or not. Select disable to manually set new product eligibility.</p>
        </div>
      </div>


      <div class="form-group row">
        <label for="enablepopup" class="col-sm-2 col-form-label">Popup</label>
        <div class="col-sm-10">
          <select class="form-control" id="enablepopup" name="enablepopup">
            <option>Select option</option>
            <option value="1" <?php echo esc_attr($popup) == '1'? "selected":""; ?>>Enable</option>
            <option value="0" <?php echo esc_attr($popup) == '0'? "selected":""; ?>>Disable</option>
          </select>
          <p>Select enable to display a popup message to customers if they didn't select a plan. (Popup may not display if you have customized your checkout functionality.) Select disable to suppress popup.</p>
        </div>
      </div>
	  
	   <div class="form-group row">
        <label for="email" class="col-sm-2 col-form-label">Email</label>
        <div class="col-sm-4">
         <input type="email" class="form-control" id="email" name="email" value="<?php echo esc_attr($email);?>" placeholder="Email" style="width:108%"/>
         <span id="errormessage" style="color:red; font-weight: 200px"></span>
         <p>The Email is required for send mail in QuickCover® account.</p>
        </div>
      </div>

      <div class="w3-container w3-teal">
      <h3>Authentication</h3>
      </div>
    <div>
    <p>In order for the QuickCover® plugin to communicate with your QuickCover® account (to sync products, present plans, and to record sales and cancellations), please enter the API Key and Store ID from your QuickCover® account. 
    If you have not signed up for a QuickCover® account, please do so at,

   <a href="https://signup.quickcover.me/join/permaplate" target="_blank">https://signup.quickcover.me/join/permaplate</a></p> 
    </div>
    <div>
    <div class="form-group row">
        <label for="apikey" class="col-sm-2 col-form-label">API Key</label>
        <div class="col-sm-10">
         <input type="text" class="form-control" id="apikey" name="apikey" value="<?php echo esc_attr($api);?>" placeholder="API Key">
         <p>The API key is required for communication between your store and your QuickCover® account.</p>
        </div>
      </div>
    </div>
    <div>
    <div class="form-group row">
        <label for="storeid" class="col-sm-2 col-form-label">Store ID</label>
        <div class="col-sm-10">
         <input type="text" class="form-control" id="storeid" name="storeid" value="<?php echo esc_attr($storeid); ?>" placeholder="Store ID">
          <p>The Store ID is used with the API key to identify your store in your QuickCover® account.</p>
        </div>
      </div>
	  <?php 
     $status = $result[0]->status;
     if($status==1){

      $auth_message = 'Authentication Successful';

     }else{
      $auth_message = 'Authentication Failed';

     }
    ?>
	  <button type="button" class="btn btn-success test test_auth" data-style="expand-left" id="testautentication" name="testautentication" style="margin-right: 4px !important">Test Authentication</button><span name="authmessage" id="authmessage" class='testauth font-weight-bold'><?php if (isset($current_date) || $current_date != "") {echo $auth_message." " .date("m/d/Y H:i:s", strtotime($current_date))."\n";} ?></span> 
    </div>
    <div class="w3-container w3-teal">
      <h3>Sync Products</h3>
    </div>
    <div>
    <p>In order to present plans for eligible products, you need to sync your products with your QuickCover® account. If you have disabled Auto Product Sync, then you can sync here. This processing time is dependent on the number of products in the sync.</p>
    </div>

    <div class="form-group row">
        <label for="batch-size" class="col-sm-2 col-form-label">Batch Size</label>
        <div class="col-sm-10">
          <select class="form-control" id="batchsize" name="batchsize">
           <option value=100 <?php echo esc_attr($batch) == '100'? "selected":""; ?>>100</option>
		    <option value=50 <?php echo esc_attr($batch) == '50'? "selected":""; ?>>50</option>
            <option value=25 <?php echo esc_attr($batch) == '25'? "selected":""; ?>>25</option>
           
            
          </select>
          <p>Setting the batch size from 25 to 100 breaks up the syncing operation into separate batches. Batch size is the number of products in a batch. Save the configuration before syncing.</p>
		  
		   <?php
             global $wpdb;
              $result_sync = $wpdb->get_row ( "SELECT * FROM batchsync_status");
            if(!empty($result_sync)){
            $getTotal_batch = $result_sync->total_batch; 
            $getSuccess_batch = $result_sync->success_batch;
            $getFailed_batch = $result_sync->failed_batch;
            $get_Latestdate = $result_sync->display_date;
         }

     ?>
		  
		      <div id="batchsyncstatus" class="syncstatus">
                    <ul class="mt-2 mb-2 printBatches">
                     <li>Total Batch   : <span id="totalBatchPrint"><?php echo $getTotal_batch; ?></span></li>
                     <li>Success Batch : <span id="successBatchPrint"><?php echo $getSuccess_batch; ?></span></li>
                      <li>Failed Batch  : <span id="failedBatchPrint"><?php echo $getFailed_batch; ?></span></li>
					  <li class="displayTime">Last Synced Date : <span id="currentdateprint" ><?php echo $get_Latestdate; ?>
                    </ul>
             </div>
        </div>
      
     <div class="buttons">
      <div class="button_response">
        <input type="hidden" id="countbatch" name="countbatch" value="<?php echo esc_attr($counProduct); ?>">
        <button type="button" class="btn btn-success setting_disable sync_button ladda-button" data-style="expand-left"  id="product_sync" name="product_sync">Sync Products</button> 
        <button type="button" id="batch_cancel" flag="1" class="btn btn-danger setting_disable cancel_button" style="display: none;"><i class="fas fa-times-circle"></i> Cancel Sync</button>
        <img class="btn syncLoader" src='<?php echo esc_url(plugin_dir_url( __FILE__ ))?>assets\images\Chasing_arrows.gif' alt="loader" />

      </div>
      <div class="sync_response">
        <div class="btn spacemargin" id="total_batch"></div><br>
        <div class="btn spacemargin success-message" id="pass_result"></div><br>
        <div class="btn spacemargin failed-message" id="fail_result"></div><br>
        <div class="btn spacemargin" id="cancel_batch"></div><br>
      </div>
	  </div> 
	  
	  <div class="w3-container w3-teal">
      <h3>Plugin Uninstallation</h3>
      </div>
	  
	  <div>
        <div class="form-group row">
        <label for="backup_status" class="col-sm-2 col-form-label">Backup</label>
        <div class="col-sm-10" style="margin-top: 10px">
          <select class="form-control" id="backup_status" name="backup_status">
            <option>Select option</option>
            <option value="1" <?php echo esc_attr($backup_status) == '1'? "selected":""; ?>>Yes</option>
            <option value="0" <?php echo esc_attr($backup_status) == '0'? "selected":""; ?>>No</option>
          </select>
          <p>Select Yes to save configurations and settings for quick set up if you want to reinstall later. Select No if you do not want to save.</p>
        </div>
      </div>
      </div>
	  
    <div class="buttons">
       <div class="config_button">
        <button type="button" class="btn btn-danger" id="saveconfig">Save Configuration</button>
      </div>
    </div>
   </div>
    </form>
    </div>
	
 <script type="text/javascript">

    jQuery('#customapp').on('change keyup keydown', 'input, textarea, select', function (e) {
    jQuery(this).addClass('changed-input');
   });

    jQuery(window).on('beforeunload', function () {
    if (jQuery('.changed-input').length) {

        return 'You haven\'t saved your changes.';
    }
});

 jQuery(document).ready(function(){
  var totalbatch = parseInt('<?=$getTotal_batch?>');
  console.log(totalbatch);
   if(totalbatch <= 0)
   {
    
     jQuery('#batchsyncstatus').hide();
   }
   else if(isNaN(totalbatch))
   {
      jQuery('#batchsyncstatus').hide();
   }
  
 });

</script>
 </body>
 </html>
    