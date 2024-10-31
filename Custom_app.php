<?php
/*
Plugin Name: PermaPlate Furniture Protection
Plugin URI: https://www.chetu.com/
Description: Plugin allows merchants to sell PermaPlate protection plans designed to keep furniture looking new.
Version: 3.7.9
Author: PermaPlate Furniture Protection.
Author URI: https://wordpress.org/
Requires at least: 5.6
Tested up to: 6.0.2
WC requires at least: 5.0
WC tested up to: 5.6.0
Requires PHP: 7.2

*/

if (!class_exists('Permaplate')) {

class Permaplate {

  private $wpdb, $jal_db_version;
  const API_BASE_URL = "https://api.quickcover.me/v1/";

  public function __construct() {

    global $wpdb;
    $this->wpdb = $wpdb;
    global $jal_db_version;
    $this->jal_db_version = "3.3.6";

	  register_activation_hook( __FILE__, array($this, 'activation_table') );
	  register_activation_hook( __FILE__, array($this, 'jal_install_data') );

	  register_activation_hook( __FILE__, array($this, 'activation_warrantytable') );
	  register_activation_hook( __FILE__, array($this, 'jal_install_data') );

	  register_activation_hook( __FILE__, array($this, 'activation_warrantytab') );
	  register_activation_hook( __FILE__, array($this, 'jal_install_data') );

	  register_activation_hook( __FILE__, array($this, 'activation_contractcancel') );
	  register_activation_hook( __FILE__, array($this, 'jal_install_data') );

	  register_activation_hook( __FILE__, array($this, 'activation_quickcovercrontable') );
	  register_activation_hook( __FILE__, array($this, 'jal_install_data') );
	 
	   register_activation_hook( __FILE__, array($this, 'plugin_backup') );
	   register_activation_hook( __FILE__, array($this, 'jal_install_data') );
	   
	 //batch sync log status
     register_activation_hook( __FILE__, array($this, 'batchSync_latestLogStatus') );
     register_activation_hook( __FILE__, array($this, 'jal_install_data') );
	   

    /* Add Javascript and CSS for admin screens*/
    add_action('admin_enqueue_scripts', array($this,'add_js_admin'));

    /* Add Javascript and CSS for front-end display*/
    add_action('wp_enqueue_scripts', array($this,'add_js_customer'));

    /*create menu Permaplate*/ 

    add_action("admin_menu", array($this,'customplugin_menu'));
   
     /*Add custom product new column in administration hook*/
    add_filter('manage_edit-product_columns', array($this,'woo_product_warranty_column'), 20);

    /*Display custom warrenty filed data form database hook*/
    add_action( 'manage_product_posts_custom_column', array($this,'woo_product_warranty_column_data'), 2 );

     /* Add custom product new is_sync column in administration*/
    add_filter( 'manage_edit-product_columns', array($this,'woo_product_sync_column'), 20 );

    add_action( 'manage_product_posts_custom_column', array($this,'woo_product_sync_column_data'), 2 );

    /*add custom filed warranting text filed hook*/
    add_action( 'woocommerce_product_options_general_product_data', array($this,'woo_add_custom_general_fields'));

    /* Save Fields values to database when submitted (Backend)*/
    //add_action('woocommerce_process_product_meta', array($this,'woo_save_custom_general_fields'), 30, 1 );

    /*add custom file Sync product text filed*/
     add_action('woocommerce_product_options_general_product_data', array($this,'sync_add' ));

     /* Save Fields values to database when submitted (Backend)*/
     //add_action( 'woocommerce_process_product_meta', array($this,'sync_save_custom_fields'), 30, 1 );

     /*add css for wp-admin customization*/
     //add_action('admin_head', array($this,'my_custom_fonts'));

     add_action('transition_post_status', array($this,'sync_product_on_publish'), 10, 3);
    /*update hooks*/
     add_action( 'woocommerce_update_product', array($this,'action_woocommerce_update_product'), 10, 1 ); 

     /*Get warranty products hook*/
     add_action( 'woocommerce_before_add_to_cart_button', array($this,'warranty_on_product_details'), 50 );
      /*call ajax and change price*/
     add_action( "wp_ajax_pricechange", array($this,"pricechange" ));   
     add_action( "wp_ajax_nopriv_pricechange", array($this,"pricechange" ));

     add_action( 'woocommerce_before_calculate_totals', array($this,'add_custom_price'), 1000, 1);

     /*add loader before cart table*/
     add_filter( 'woocommerce_before_cart_table', array($this,'add_loader_before_cart_table'), 20, 3);
     
     /*add edit button on cart hook page*/
     add_filter( 'woocommerce_cart_item_name', array($this,'cart_product_title'), 20, 3);
     /* cart page and change price hook*/
     add_action( "wp_ajax_pricechangeoncart", array($this,"pricechangeoncart" ));   
     add_action( "wp_ajax_nopriv_pricechangeoncart", array($this,"pricechangeoncart"));
      /*save config ajax */
     add_action( "wp_ajax_saveconfig", array($this,"saveconfig" ));   
     add_action( "wp_ajax_nopriv_saveconfig", array($this,"saveconfig" ));

      /*Get batchs */
     add_action( "wp_ajax_getBatch", array($this,"getBatch" ));   
     add_action( "wp_ajax_nopriv_getBatch", array($this,"getBatch" ));

     /*Sync Product */
     add_action( "wp_ajax_syncProduct", array($this,"syncProduct" ));   
     add_action( "wp_ajax_nopriv_syncProduct", array($this,"syncProduct" ));
	 
	   /*Authentication status for API key and StoreId */
     add_action( "wp_ajax_testauthentication", array($this,"testauthentication")); 
     add_action( "wp_ajax_nopriv_testauthentication", array($this,"testauthentication" ));
	 
	  /*Update status of local Product */ 
     add_action( "wp_ajax_updateLocalProduct", array($this,"updateLocalProduct")); 
     add_action( "wp_ajax_nopriv_updateLocalProduct", array($this,"updateLocalProduct" ));
	 
     /*contract cancel on both side*/
     add_action( "wp_ajax_cancelContract", array($this,"cancelContract" ));   
     add_action( "wp_ajax_nopriv_cancelContract", array($this,"cancelContract" ));

     //add_action('publish_to_trash', array($this,'deleteProduct'));
	 
		  add_action( 'wp_trash_post', array($this,'wpdocs_trash_multiple_posts'));

		 add_action( 'woocommerce_remove_cart_item', array($this,'action_woocommerce_cart_item_removed'), 10, 2 );
		/*Add custom data to Cart in hook*/
		 add_filter('woocommerce_add_cart_item_data',array($this,'AddNewProductInsteadChangeQuantity'),11,2); 
		/* Display information as Meta on Cart page hook*/
		  add_filter('woocommerce_get_item_data',array($this,'add_item_meta'),10,2);

		add_action( 'woocommerce_checkout_create_order_line_item', array($this,'add_custom_order_line_item_meta'),10,4 );

		add_action( 'woocommerce_thankyou', array($this,'afterinc_sync_order_quickcover' ));

		 /*contract cancel from customer end hook*/
		add_filter( 'woocommerce_order_item_name', array($this,'woocommerce_order_item_name'), 10, 2 );
		/* cancel from admin End hook*/
		add_action( 'woocommerce_order_status_cancelled', array($this,'cancel_whole_contract_from_admin'), 21, 1 );

		/*add button order details page in admin side and cancel order functionality hook*/
		add_action('woocommerce_before_order_itemmeta',array($this,'woocommerce_before_order_itemmeta'),10,3);
	   
		/* add javascript in customer side hook*/
		add_action( 'woocommerce_view_order', array($this,'view_order_and_thankyou_page'), 20 );
		
		/* Add reset button hook*/
		add_action('admin_head-edit.php',array($this,'addCustomImportButton'));
		
		add_action( 'woocommerce_admin_order_data_after_order_details', array($this,'add_loader_in_admin_order_detail_page') );
	
		/* bulk edit warranty hook*/
		add_action( 'woocommerce_product_bulk_edit_start', array($this,'warranty_bulk_edit_input' ));
		/* bulk warranty save hook*/
		add_action( 'woocommerce_product_bulk_edit_save', array($this,'warranty_bulk_edit_save' ));

		/* bulk edit sync hook*/
		add_action( 'woocommerce_product_bulk_edit_start', array($this,'sync_bulk_edit_input' ));
		/* bulk edit save sync hook*/
		add_action( 'woocommerce_product_bulk_edit_save', array($this,'sync_bulk_edit_save' ));
		/* warranty filter slect drop down hook*/
		add_action( 'restrict_manage_posts', array($this,'warranty_product_filters' ));
		/* sync filter slect drop down hook*/
		add_action( 'restrict_manage_posts', array($this,'sync_product_filters' ));

		/* apply filter for warranty and sync hook*/
		add_action( 'pre_get_posts', array($this,'apply_warranty_product_filters' ));

		add_action('phpmailer_init', array($this, 'my_phpmailer_afterinc'));

		/* cron hooks*/
		 add_filter('cron_schedules', array($this, 'afterinc_add_cron_interval'));

		 add_action('my_oneminute_event', array($this, 'order_scheduler_cron'));
		 
		  add_action('my_custom_5_minute_cron', array($this,'my_custom_5_minute_function'));
          add_filter('cron_schedules', array($this,'add_custom_cron_intervals'));
		  
		  add_action('save_post', array($this,'save_custom_bulk_edit_data'));
 
}

  /* add JS file for customer into the plugin*/
  function add_js_customer() {

      wp_enqueue_script( 'jquery-core', includes_url( 'js/jquery/jquery.min.js', __FILE__ ));
      wp_enqueue_script( 'add_model_js', plugins_url( '/assets/js/jquery.modal.min.js', __FILE__ ));
      wp_enqueue_script('contract_admin_js', plugin_dir_url(__FILE__) . 'assets/js/permaPlate-customer.js');
      wp_enqueue_script( 'sweet_alert_js', plugins_url( '/assets/js/sweetalert.min.js', __FILE__ ));
      wp_enqueue_style( 'add_model_css', plugins_url('/assets/css/jquery.modal.min.css', __FILE__));
      wp_enqueue_style( 'add_customer_css', plugins_url('/assets/css/permaplate-customer.css', __FILE__));
	 
      wp_localize_script('jquery', 'ajax', array(
          'url' => admin_url('admin-ajax.php'),
          'nonce' => wp_create_nonce('Permaplate##Inc@20'),
      ));
  }

 /* add JS file for admin into the plugin*/
  function add_js_admin() {

      wp_enqueue_script('contract_admin_js', plugin_dir_url(__FILE__) . 'assets/js/permaPlate-customer.js');
      wp_enqueue_script('bootstrap_js', plugin_dir_url(__FILE__) . 'assets/js/bootstrap.min.js');
      wp_enqueue_script('sweet_alert_js', plugin_dir_url(__FILE__) . 'assets/js/sweetalert.min.js');
      wp_enqueue_style('bootstrap_css', plugin_dir_url(__FILE__) . 'assets/css/bootstrap.min.css');
      wp_enqueue_style('style_css', plugin_dir_url(__FILE__) . 'assets/css/admin/permaplate-style.css');
      wp_enqueue_script('contract_admin_form_js', plugin_dir_url(__FILE__) . 'assets/js/admin/custom.js',null, null, true);

      wp_localize_script('jquery', 'ajax', array(
          'url' => admin_url('admin-ajax.php'),
          'nonce' => wp_create_nonce('Permaplate##Inc@20'),
      ));

  }

  function customplugin_menu() {
    add_menu_page("Custom Plugin", "Permaplate","manage_options", "Permaplate", array( $this, 'configuration_form' ), 'dashicons-format-aside');
	
	 add_submenu_page( 'Permaplate', 'setting', 'Settings', 'manage_options', 'Permaplate', array( $this, 'configuration_form' ), 'dashicons-format-aside');
	
	 add_submenu_page( 'Permaplate', 'updateProduct', 'Bulk Product Update', 'manage_options', 'Bulk Product Update', array( $this, 'Submenu_function' ), 'dashicons-format-aside');
  }
  
   function Submenu_function(){
    require "bulkUpdate.php";
  }

  function configuration_form(){
    require "form.php";    
  }

    /*create table plugin*/
  function activation_table() {
    
      $table_name = 'app_configurations';
	  if($this->wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
      $charset_collate = $this->wpdb->get_charset_collate();
      $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id int(20) NOT NULL,
        configuration_property varchar(100) NOT NULL,
		warranty_status varchar(100) NOT NULL,
		backup_status varchar(100) NOT NULL,
        sync_type varchar(100) NOT NULL,
        api_key varchar(100) NOT NULL,
        store_id varchar(100) NOT NULL,
        batch_size varchar(100) NOT NULL,
        popup_type varchar(100) NOT NULL,
		email varchar(100) NOT NULL,
		updated_date varchar(100) NULL,
        status varchar(100) NULL,
        PRIMARY KEY  (id)
      ) $charset_collate;";

      require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
      dbDelta($sql);
	}
	  else{
      //$result = $this->wpdb->query( $this->wpdb->prepare( "UPDATE $table_name SET configuration_property = %s WHERE id = %d", '1', 1));
      }
	  
      add_option( 'jal_db_version', $this->jal_db_version );
  }
  /*insert data in table*/
   function jal_install_data() {
      
      $user_ID = get_current_user_id(); 

       $auto_increment_id = 1;
       $user_id = $user_ID;
       $configuration_property='1';
	   $warranty_status='0';
       $backup_status='1';
       $sync_type='0';
       $api_key='API-KEY';
       $store_id='STORE-ID';
       $batch_size=100;
       $popup_type='1';
	    $email = 'Enter Email Id';
       $table_name = 'app_configurations';

       $this->wpdb->query(
         $this->wpdb->prepare(
            "INSERT INTO $table_name
            ( id, user_id, configuration_property, warranty_status, backup_status, sync_type, api_key, store_id, batch_size, popup_type, email )
            VALUES ( %d, %d, %d, %s, %s, %s, %s, %s, %s, %s, %s)",$auto_increment_id,$user_id,$configuration_property,$warranty_status,$backup_status,$sync_type,$api_key,$store_id,$batch_size,$popup_type, $email));

      // ============  backup plugin code ============ // 

    if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

      $pquery = array(
             'post_type' => 'product',
             'posts_per_page' => -1,
          );
         
      $getAllProducts = get_posts( $pquery );
      $productIdArr = array_column($getAllProducts,'ID');

      $wquery = array(
         'post_type' => 'product',
         'posts_per_page' => -1,
         'meta_key' => '_warranty'
      );

      $getWarranty = get_posts( $wquery ); 
      $wPostIdArr = array_column($getWarranty,'ID');

      $wDiff = array_diff($productIdArr,$wPostIdArr);
      if(!empty($wDiff)){

         $warranty = ''; $sync = '';
         foreach($wDiff as $key=>$productId) {
            $warranty .= "(".$productId.",'_warranty','Disable'),";
            $sync .= "(".$productId.",'_sync','New'),";
         }

         $warranties = rtrim($warranty,',');
         $syncs = rtrim($sync,',');

         $postmeta_table = $this->wpdb->prefix.'postmeta';

         $this->wpdb->query("INSERT INTO $postmeta_table(post_id, meta_key, meta_value)
          VALUES ".$warranties);

         $this->wpdb->query("INSERT INTO $postmeta_table(post_id, meta_key, meta_value)
          VALUES ".$syncs);

         }
      } 
	  
	  $querydata="SELECT wp_posts.* from wp_posts join product_backup on wp_posts.id = product_backup.product_id where wp_posts.post_modified !=product_backup.modified_date";
     $products_diff= $this->wpdb->get_results($querydata);

      $prdWrtIdArr = [];  $prdWrtIdArry = []; 
         foreach($products_diff as $product) { 
            $mvalue = get_post_meta($product->ID, '_sync');
            if($mvalue[0] == 'New'){
              $prdWrtIdArry[] = $product->ID;
            }else{
              $prdWrtIdArr[] = $product->ID;
            }
         }

         $result = 0;
         if(!empty($prdWrtIdArr)){
          $prdWrtIdStr = implode(",",$prdWrtIdArr);
          $result = $this->wpdb->query( $this->wpdb->prepare("UPDATE wp_postmeta SET meta_value='Not Synced' WHERE post_id IN (".$prdWrtIdStr.") AND meta_key='_sync'"));
        }
    
     if(!empty($prdWrtIdArry)){
          $prdWrtIdStry = implode(",",$prdWrtIdArry);
          $result = $this->wpdb->query( $this->wpdb->prepare("UPDATE wp_postmeta SET meta_value='New' WHERE post_id IN (".$prdWrtIdStry.") AND meta_key='_sync'"));
        }
       $this->wpdb->query('TRUNCATE TABLE product_backup');
   }


  /* 2nd table create product warranty*/
  function activation_warrantytable() {
      $table_name = 'product_warranty';
      $charset_collate = $this->wpdb->get_charset_collate();
      $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        product_id int(20) NOT NULL,
        veriant_id int(20) NOT NULL,
        priceCurrency varchar(255) NOT NULL,
		pname varchar(255) NOT NULL,
		description varchar(255) NOT NULL,
        priceCategory varchar(255) NOT NULL,
        termsAndConditionsURL varchar(255) NOT NULL,
        learnMoreURL varchar(255) NOT NULL,
        plans text NOT NULL,
        PRIMARY KEY  (id)
      ) $charset_collate;";
      require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
      dbDelta( $sql );
      add_option( 'jal_db_version', $this->jal_db_version );
  }


  /* 3rd table create warranty_calc*/
   function activation_warrantytab() {

       $table_name = 'warranty_calc';
       $charset_collate = $this->wpdb->get_charset_collate();
       $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id int(20) NOT NULL,
        post_id int(20) NOT NULL,
        warranty_id int(20) NOT NULL,
        warranty_total varchar(255) NOT NULL,
        plan_id varchar(255) NOT NULL,
        PRIMARY KEY  (id)
      ) $charset_collate;";
      require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
      dbDelta( $sql );
      add_option( 'jal_db_version', $this->jal_db_version );
  }


/* 4th table create quickcover_contract */
  function activation_contractcancel() {
      $table_name = 'quickcover_contract';
      $charset_collate = $this->wpdb->get_charset_collate();
      $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id int(20) NOT NULL,
        product_id int(50) NOT NULL,
        order_id varchar(255) NOT NULL,
        lineitem_id varchar(255) NOT NULL,
        quantity int(100) NOT NULL,
        contract_id varchar(255) NOT NULL,
        contract_status varchar(255) NOT NULL,
        refund_amount varchar(255) NOT NULL,
        contract_canceled_by varchar(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
      ) $charset_collate;";
      require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
      dbDelta( $sql );
      add_option( 'jal_db_version', $this->jal_db_version );
  }


  /*5th table create product warranty*/
  function activation_quickcovercrontable() {
      $table_name = 'quickcover_cron_status';
      $charset_collate = $this->wpdb->get_charset_collate();
      $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        order_id int(20) NOT NULL,
        status varchar(255) NOT NULL,
		counter varchar(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
      ) $charset_collate;";
      require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
      dbDelta( $sql );
      add_option( 'jal_db_version', $this->jal_db_version );
  }
  
   function get_currency_symbol(){
      return get_woocommerce_currency_symbol();
  }
  
   function plugin_backup() {

      $table_name = 'product_backup';
      $charset_collate = $this->wpdb->get_charset_collate();
      $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        product_id int(20) NOT NULL,
        modified_date varchar(255) NOT NULL,
        PRIMARY KEY  (id)
      ) $charset_collate;";
      require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
      dbDelta( $sql );
      add_option( 'jal_db_version', $this->jal_db_version );
  }
  
   function  batchSync_latestLogStatus() {

      $table_name = 'batchsync_status';
      $charset_collate = $this->wpdb->get_charset_collate();
      $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        total_batch varchar(255) NOT NULL,
        success_batch varchar(255) NOT NULL,
        failed_batch varchar(255) NOT NULL,
        display_date varchar(255) NOT NULL,
        PRIMARY KEY  (id)
      ) $charset_collate;";
      require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
      dbDelta( $sql );
      add_option( 'jal_db_version', $this->jal_db_version );
  }
  

/*Add custom warranty column in product listing page in admin*/
  function woo_product_warranty_column( $columns ) {
    $columns['is_warranty'] = esc_html__( 'Eligible for QuickCover', 'woocommerce' );
    return $columns;
 }

/*Display custom warrenty filed data form database*/
  function woo_product_warranty_column_data( $column ) {
     global $post , $product; 
     $product_id = method_exists( $product, 'get_id' ) ? $product->get_id() : $product->id;
      if ( $column == 'is_warranty' ) {
      $product = wc_get_product($post->ID); 
        $warranty = get_post_meta( $product_id, '_warranty', true );
        if($warranty) {
         echo esc_html($warranty);
         }

      }
  }

   /*Add custom product is_sync column in admini*/
  function woo_product_sync_column( $columns ) {
      $columns['is_sync'] = esc_html__( 'QuickCover Sync', 'woocommerce' );
      return $columns;
  }

    
 function woo_product_sync_column_data( $column ) {
      global $post , $product;
      $product_id = method_exists( $product, 'get_id' ) ? $product->get_id() : $product->id;
      if ( $column == 'is_sync' ) {
      $product = wc_get_product($post->ID);
      
        $sync = get_post_meta( $product_id, '_sync', true );
        //echo esc_html($sync);
         if($sync) {
          echo esc_html($sync);
          }
      }
  }
  

  /*add custom filed warranting text filed*/
  function woo_add_custom_general_fields() {
	  
	   $result_warranty = $this->wpdb->get_results( $this->wpdb->prepare( "SELECT * FROM app_configurations WHERE id = %d", 1 ));
        $warranty_aviable = $result_warranty[0]->warranty_status;
        if($warranty_aviable==1){
          $final_warranty = "Enable";
        }
     else{
         $final_warranty = "Disable";
     }

      echo '<div class="options_group">';
      woocommerce_wp_select( array( // Text Field type
      'id'          => '_warranty',
      'label'       => __( 'Warranty', 'woocommerce' ),
      'description' => __( 'Warranty field to display status.', 'woocommerce' ),
      'desc_tip'    => true,
      'options'     => array(
        //  ''        => __( 'Select product warranty', 'woocommerce' ),
         $final_warranty    => __($final_warranty, 'woocommerce' ),
          //'No' => __('No', 'woocommerce' ),
         )
      ) );

     echo '</div>';
  }

  function sync_add() {

      echo '<div class="options_group_sync1">';
       woocommerce_wp_select( 
        array( // Text Field type
          'id'          => '_sync',
          'class'       => 'syncfield select short',
          'label'       => __( 'QuickCover® Sync', 'woocommerce' ),
          'description' => __( 'Sync field to display status.', 'woocommerce' ),
          'desc_tip'    => true,
          'default'     => 'New',
          'options'     => array(
              'New'    => __('New', 'woocommerce' ),
			   'Not Synced'    => __('Not Synced', 'woocommerce'),
              'Synced' => __('Synced', 'woocommerce' ),
			  'Failed' => __('Failed', 'woocommerce' ),
          ),
         ) );

        echo '</div>';

   }
   

   function sync_product_on_publish($new_status, $old_status, $post) {

    $currency = get_option('woocommerce_currency');

    if( $old_status != 'publish' && $new_status == 'publish' && !empty($post->ID) && in_array( $post->post_type, array( 'product') )) {

        $productId = sanitize_text_field($_POST['post_ID']);
        //$product_cat_arr = $_POST['tax_input']['product_cat'];
		 $product_cat_arr = isset($_POST['tax_input']['product_cat']) && is_array($_POST['tax_input']['product_cat']) ? $_POST['tax_input']['product_cat'] : [];
        $regular_price = sanitize_text_field($_POST['_regular_price']);
		
		 $product_cat = array_map( 'sanitize_text_field', $product_cat_arr );
        if(isset($product_cat[0])){
          unset($product_cat[0]);
        }

      $table_name = $this->wpdb->prefix.'postmeta';

      $result = $this->wpdb->get_results( $this->wpdb->prepare( "SELECT * FROM app_configurations WHERE id = %d", 1 ) );
	  
	    if($result[0]->warranty_status == 1){
        $is_warrantyupdate = 'Enable';
       }
     else{
         $is_warrantyupdate = 'Disable';
        }

       if(!empty($result) && $result[0]->sync_type == 1 && $result[0]->configuration_property == 1 && $result[0]->warranty_status == 1){

        $category = array();
         if(!empty($product_cat)) {
           foreach ($product_cat as $key => $value) {
               $category_array = get_term( $value );
               $category[] = strtolower($category_array->name);
           }
        }

        if(in_array("uncategorized", $category)) {
            if (($key = array_search("uncategorized", $category)) !== false) {
                unset($category[$key]);
            }
        }

        $category = implode(", ", $category);
        $apiKey = $result[0]->api_key;
        $storeId = $result[0]->store_id;

        $dbData = array();
        $dbData['priceCurrency'] = $currency;
        $dbData['id'] = $productId;
         $dbData['category'] = $category !='' ? $category : '';
        $dbData['description'] = sanitize_text_field($_POST['content'] != '' ? $_POST['content'] : '');
        //$dbData['model'] = sanitize_text_field($_POST['_sku']);
		$dbData['model'] = sanitize_text_field($_POST['_sku'] != '' ? $_POST['_sku']: $_POST['post_ID']);
		$dbData['price'] = (float) $regular_price;
		$regularprice = (round($dbData['price']));
        $dbData['price'] = (float) $regularprice * 100;
        $dbData['name'] = sanitize_text_field(wp_unslash($_POST['post_title']));
        //$dbData['sku'] = sanitize_text_field(wp_unslash($_POST['_sku']));
		$dbData['sku'] = sanitize_text_field(wp_unslash($_POST['_sku'] !='' ? $_POST['_sku']: $_POST['post_ID']));
        $product_json = json_encode($dbData);

		  $base_url = Permaplate::API_BASE_URL;
          $url = $base_url.'products/outlet/'.$storeId;

         $args = array(
          'headers' => array(
            'Content-Type' => 'application/json',
            'X-QUICKCOVER-API-KEY' => $apiKey

          ),
          'body' => $product_json,
		  'timeout' => 300
        );

        $response = wp_remote_post( $url, $args );
        $response_status = wp_remote_retrieve_response_code($response);
        $response = wp_remote_retrieve_body( $response );

        //print_r( $response_status);die();
         $this->wpdb->query( $this->wpdb->prepare( "INSERT INTO $table_name ( post_id, meta_key, meta_value)
         VALUES ( %d, %s, %s )", $productId, '_warranty', 'Enable'));

        if($response_status==201){
      $this->wpdb->query( $this->wpdb->prepare( "INSERT INTO $table_name ( post_id, meta_key, meta_value)
         VALUES ( %d, %s, %s )", $productId, '_sync', 'Synced'));
		 
          plugin_log(['status'=>$response_status,'response'=>$response]);
		  
            unset($_POST['_sync']);
        }else {
           $this->wpdb->query($this->wpdb->prepare("INSERT INTO $table_name ( post_id, meta_key,meta_value)
           VALUES ( %d, %s, %s )", $productId, '_sync', 'Failed'));
		   plugin_log(['status'=>$response_status,'response'=>$response,'request'=>$product_json]);
        }

    }else {

         $this->wpdb->query($this->wpdb->prepare("INSERT INTO $table_name ( post_id, meta_key, meta_value)
         VALUES (%d, %s, %s )", $productId, '_warranty', $is_warrantyupdate));
		 plugin_log(['status'=>$response_status,'response'=>$response]);

         $this->wpdb->query($this->wpdb->prepare("INSERT INTO $table_name ( post_id, meta_key, meta_value)
         VALUES (%d, %s, %s )", $productId, '_sync','New'));
		plugin_log(['status'=>$response_status,'response'=>$response,'request'=>$product_json]);


      }
    }
  }

  /*define the woocommerce_update_product callback*/
  function action_woocommerce_update_product( $product_id ) { 
  
    $url = $_POST['_wp_http_referer'];
    if(strpos($url, 'action=edit') != ""){
    $currency = get_option('woocommerce_currency');
    global $product;
    $result = $this->wpdb->get_results( $this->wpdb->prepare( "SELECT * FROM app_configurations WHERE id = %d", 1 ));
     
      $table_name = $this->wpdb->prefix.'postmeta';
     
	  global $pagenow;
          if (( $pagenow == 'post.php' ) || (get_post_type() == 'post')) {

             $is_warrantyupdate = get_post_meta($product_id, "_warranty")[0];
			 $warranty_type = get_post_meta($product_id, "_sync")[0];
			  $warranty_status = get_post_meta($product_id, "_warranty")[0];

           }

    if(!empty($result) && $result[0]->sync_type == 1 && $result[0]->configuration_property == 1 && $is_warrantyupdate== 'Enable'){
        $apiKey = $result[0]->api_key;
        $storeId = $result[0]->store_id;

	     $base_url = Permaplate::API_BASE_URL;
          $url = $base_url.'products/outlet/'.$storeId.'?productId='.$product_id;

        $args = array(
            'headers' => array(
              'Content-Type' => 'application/json',
              'X-QUICKCOVER-API-KEY' => $apiKey
            ),
			'timeout' => 300
			);

         $response = wp_remote_get( $url, $args );
         $responseStatus = wp_remote_retrieve_response_code($response);
         $response = wp_remote_retrieve_body( $response );

          if(($responseStatus == 200)){ 
              $product = wc_get_product($product_id);
              $category = wc_get_product_category_list($product_id);
              $category = strip_tags($category);
              $cat_array = explode(', ', strtolower($category));
              if(in_array("uncategorized", $cat_array)) {
                  if (($key = array_search("uncategorized", $cat_array)) !== false) {
                      unset($cat_array[$key]);
                  }
              }

              $category = implode(", ", $cat_array);

              $dbData = array();
              $dbData['priceCurrency'] = $currency;
              $dbData['id'] = $product_id;
              $dbData['category'] = $category != '' ? $category : '';
              $dbData['description'] = ($product->get_description() != '') ? $product->get_description() : '';
              //$dbData['model'] = $product->get_sku();
			   $dbData['model'] = ($product->get_sku() !='') ? $product->get_sku() : $product_id;
			  $dbData['price'] = (float) $product->get_price();
			  $price = $dbData['price'];
			  $newprice = round($price);
              $dbData['price'] = ((float) $newprice * 100);
              $dbData['name'] = $product->get_title();
              //$dbData['sku'] = $product->get_sku();
			   $dbData['sku'] = ($product->get_sku() !='') ? $product->get_sku() : $product_id;
              $product_json = json_encode($dbData);

			   $base_url = Permaplate::API_BASE_URL;
              $url = $base_url.'products/outlet/'.$storeId;

              $args = array(
                  'headers' => array(
                    'Content-Type' => 'application/json',
                    'X-QUICKCOVER-API-KEY' => $apiKey
                  ),
                  'body' => $product_json,
                  'method'    => 'PUT',
                   'timeout'     => 300
                );

               $response = wp_remote_request( $url, $args );
               $response_status = wp_remote_retrieve_response_code($response);
               $response = wp_remote_retrieve_body( $response );

               $table_name = $this->wpdb->prefix.'postmeta';
               
              if($response_status== 200){
				  
				 $resultx = update_post_meta($product_id, '_sync', 'Synced');
				 
				 plugin_log(['status'=>$response_status,'response'=>$response,'request'=>$product_json]);
                
                }
               
			   else	   
			   {

                  $results = $this->wpdb->query( $this->wpdb->prepare("UPDATE $table_name SET meta_value = %s WHERE post_id = %d AND meta_key = %s", 'Failed', $product_id, '_sync'));
				  plugin_log(['status'=>$response_status,'response'=>$response,'request=>$product_json']);

                   $adminBaseUrl = admin_url();
                   $adminUrl = $adminBaseUrl.'post.php?post='.$product_id.'&action=edit';
                   wp_redirect($adminUrl);
                   exit();
              }

          }else{

              $product = wc_get_product($product_id);
              $category = wc_get_product_category_list($product_id);
              $category = strip_tags($category);
              $category = strtolower($category);
              $cat_array = explode(', ', strtolower($category));
              if(in_array("uncategorized", $cat_array)) {
                  if (($key = array_search("uncategorized", $cat_array)) !== false) {
                      unset($cat_array[$key]);
                  }
              }
              $category = implode(", ", $cat_array);

              $dbData = array();
              $dbData['priceCurrency'] = $currency;
              $dbData['id'] = $product_id;
              $dbData['category'] = $category != '' ? $category : '';
             $dbData['description'] = ($product->get_description() != '') ? $product->get_description() : '';
              //$dbData['model'] = $product->get_sku();
			   $dbData['model'] = ($product->get_sku() !='') ? $product->get_sku() : $product_id;
              $dbData['price'] = ((float) $product->get_price() * 100);
              $dbData['name'] = $product->get_title();
              //$dbData['sku'] = $product->get_sku();
			   $dbData['sku'] = ($product->get_sku() !='') ? $product->get_sku() : $product_id;
              $product_json = json_encode($dbData);

			   $base_url = Permaplate::API_BASE_URL;
              $url = $base_url.'products/outlet/'.$storeId;

              $args = array(
                  'headers' => array(
                    'Content-Type' => 'application/json',
                    'X-QUICKCOVER-API-KEY' => $apiKey
                  ),
                  'body' => $product_json,
				  'timeout' => 300
                );

               $response = wp_remote_post( $url, $args );
               $response_status = wp_remote_retrieve_response_code($response);
               $response = wp_remote_retrieve_body( $response );

               $table_name = $this->wpdb->prefix.'postmeta';
              // update sync status
              if($response_status==201){

              $result = $this->wpdb->query( $this->wpdb->prepare("UPDATE $table_name SET meta_value = %s WHERE post_id = %d AND meta_key = %s", 'Synced', $product_id, '_sync'));
			  plugin_log(['status'=>$response_status,'response'=>$response]);

                   $adminBaseUrl = admin_url();
                   $adminUrl = $adminBaseUrl.'post.php?post='.$product_id.'&action=edit';
                   wp_redirect($adminUrl);
                   exit();
              }
			else{
                 $result_status = update_post_meta($product_id, '_sync', 'Failed');
               }
           }

       }else{
		global $pagenow;
		if (( $pagenow == 'post.php' ) || (get_post_type() == 'post')) {
		if($result[0]->sync_type ==0 && $result[0]->warranty_status ==0 && $warranty_type == 'New'){
          update_post_meta( $product_id, '_sync', 'New');
          }
         else if($result[0]->configuration_property == 1 && $result[0]->warranty_status == 1 && $result[0]->sync_type == 1 && $warranty_type == 'New'){
            update_post_meta( $product_id, '_sync', 'New'); 
            }

          else if($result[0]->configuration_property == 1 && $result[0]->warranty_status == 1 && $result[0]->sync_type == 1 && $warranty_status == 'Disable'){
            update_post_meta( $product_id, '_sync', 'New'); 
            }

            else if($result[0]->configuration_property == 1 && $result[0]->warranty_status == 0 && $result[0]->sync_type == 1 && $warranty_status == 'Disable'){
              update_post_meta( $product_id, '_sync', 'New'); 
              } 
        
		 else{
             update_post_meta( $product_id, '_sync', 'Not Synced');
       
             }
		   }
         }
	   }
    }
	
	 function afterinc_add_cron_interval($schedules){
                $schedules['scheduled_oneminute'] = array(
                'interval' => 4*60*60,
                'display'  => esc_html__('scheduled oneminute'),
                );
                return $schedules;
            }
			
	function add_custom_cron_intervals($schedules) {
            $schedules['every_five_minutes'] = array(
           'interval' => 300, // 20 minutes in seconds
          'display' => __('Every 20 Minutes')
        );
        return $schedules;
      }

    function my_custom_5_minute_function() {
       plugin_log("This is testing for every five minute cron");
      $appConfig = $this->wpdb->get_results($this->wpdb->prepare("SELECT * FROM app_configurations WHERE id = %d", 1));

      $cronStatus = $this->wpdb->get_results("SELECT * FROM quickcover_cron_status WHERE status = 404 ORDER BY order_id DESC ");

           if (!empty($cronStatus)) {

                  foreach ($cronStatus as $key => $value) {

                        $order_id = $value->order_id;
                        $order = wc_get_order($order_id);
                        $email = $order->get_billing_email();
                        $fname = $order->get_billing_first_name();
                        // $customername = $order->billing_first_name() .' '.$order->billing_last_name();

                      if (!empty($order)) {
                            $order_no =   $order->get_id();
                            $order_data = $order->get_data();
                            $order_items = $order->get_items();
                            $srlNo = count($order_items);
                            $order_date = $order->get_date_created()->format('Y-m-d H:i:s');
                            $order_status  = $order->get_status();

                          if ($order_status !== 'cancelled') {
                                  $orderItem = array();
                                  $in_warranty = array();
                                  $planNameArr = array();
                                  $planTitle = null;
                                foreach ($order_items as $key => $item) {
                                    $lineitem_id = $item->get_id();
                                    $productID = $item->get_product_id();

                                    $result = $this->wpdb->get_results($this->wpdb->prepare("SELECT * FROM product_warranty WHERE product_id = %d ", $productID));

                                    if (!empty($result)) {
                                          $planTitle = $result[0]->pname;
                                          $planDatas = $item->get_meta_data();
                                          $planName = '';
                                          $planPrice = '';
                                        foreach ($planDatas as $key => $planId) {
                                            if (isset($planId->key) && ($planId->key == '_warranty')) {
                                                    $plan_id = $planId->value['plan_id'];
                                                if ($plan_id !== 'none') {
                                                    $in_warranty[] = $result[0]->product_id;
                                                    $plans = json_decode($result[0]->plans, true);


                                                    foreach ($plans as $key => $plan) {
                                                        if ($plan['planId'] == $plan_id) {
                                                            $contract = array(
                                                            'priceCurrency' => $result[0]->priceCurrency,
                                                            'planId' => $plan['planId'],
                                                            'price' => $plan['price'],
                                                            'quantity' => $item->get_quantity(),
                                                            );
                                                            $planName = $plan['description'];
                                                            $planPrice = $plan['price'] * $item->get_quantity();
                                                        }
                                                    }

                                                    $orderItem[] = array(
                                                    'id' => $lineitem_id,
                                                    'product' => array(
                                                    'priceCurrency' => $result[0]->priceCurrency,
                                                    'id' => $item->get_product_id(),
                                                    'contract' => $contract,
                                                    'purchasePrice' => (float) ($item->get_product()->get_price()) * 100,
                                                    'quantity' => $item->get_quantity(),
                                                    'serialNumber' => $srlNo
                                                    )
                                                    );
                                                }
                                             }
                                         }
                                           
                                    }
                                }

                                if (!empty($in_warranty)) {

                                    if (($order_data['billing']['address_1'] == "")) {
                                        $order_data['billing']['address_1'] = "null";
                                    }
                                    if (($order_data['billing']['address_2'] == "")) {
                                        $order_data['billing']['address_2'] = "null";
                                    }
                                    if (($order_data['billing']['city'] == "")) {
                                        $order_data['billing']['city'] = "null";
                                    }
                                    if (($order_data['billing']['postcode'] == "")) {
                                        $order_data['billing']['postcode'] = "null";
                                    }
                                    if (($order_data['billing']['country'] == "")) {
                                        $order_data['billing']['country'] = "null";
                                    }

                                    if (($order_data['shipping']['address_1'] == "")) {
                                        $order_data['shipping']['address_1'] = "null";
                                    }
                                    if (($order_data['shipping']['address_2'] == "")) {
                                        $order_data['shipping']['address_2'] = "null";
                                    }
                                    if (($order_data['shipping']['city'] == "")) {
                                        $order_data['shipping']['city'] = "null";
                                    }
                                    if (($order_data['shipping']['postcode'] == "")) {
                                        $order_data['shipping']['postcode'] = "null";
                                    }
                                    if (($order_data['shipping']['country'] == "")) {
                                        $order_data['shipping']['country'] = "null";
                                    }

                                    $order_array = array(
                                    'priceCurrency' => $order_data['currency'],
                                    'id' => $order_data['id'],
                                    'customer' => array(
                                    'givenName'  => $order_data['billing']['first_name'],
                                    'alternateName' => "kumar",
                                    'lastName' => $order_data['billing']['last_name'],
                                    'email' => $order_data['billing']['email'],
                                    'phone' => $order_data['billing']['phone'],
                                    'billingAddress' => array(
                                    'address1' => $order_data['billing']['address_1'],
                                    'address2' => $order_data['billing']['address_2'],
                                    'locality' => $order_data['billing']['city'],
                                    'postalCode' => $order_data['billing']['postcode'],
                                    'region' => $order_data['billing']['country'],
                                    ),
                                    'shippingAddress' => array(
                                    'address1' => $order_data['shipping']['address_1'],
                                    'address2' => $order_data['shipping']['address_2'],
                                    'locality' =>  $order_data['shipping']['city'],
                                    'postalCode' => $order_data['shipping']['postcode'],
                                    'region' => $order_data['shipping']['country'],
                                    ),
                                    ),
                                    'purchaseDate' => $order_date,
                                    'totalPrice' =>  (float) ($order->get_total()) * 100,
                                    'poNumber' => $order_id,
                                    'orderItem' => $orderItem
                                    );

                                    $orderList = json_encode($order_array);

                                    if (!empty($appConfig)) {
                                        $apiKey = $appConfig[0]->api_key;
                                        $storeId = $appConfig[0]->store_id;
                                        $toemail = $appConfig[0]->email;

                                        $currentadmin_login = $appConfig[0]->user_id;

                                         $tablename = $this->wpdb->prefix . 'users';

                                        $get_admindetails = $this->wpdb->get_results($this->wpdb->prepare("SELECT user_login FROM $tablename WHERE ID = %s ", $currentadmin_login));
                                         $merchant_login=$get_admindetails[0]->user_login;

                                        $base_url = Permaplate::API_BASE_URL;
                                        $url = $base_url . 'orders/outlet/' . $storeId;

                                        $args = array(
                                        'headers' => array(
                                        'Content-Type' => 'application/json',
                                        'X-QUICKCOVER-API-KEY' => $apiKey
                                        ),
                                        'body' => $orderList,
                                        'method'    => 'POST',
                                        'timeout'     => 300
                                        );

                                        $response = wp_remote_post($url, $args);
                                        $response_status = wp_remote_retrieve_response_code($response);

                                        $response = wp_remote_retrieve_body($response);
                                        $orderData = json_decode($response);

                                        plugin_log(['contract_status' => $response_status, 'response' => $response, 'request' => $orderList]);

                                        if ($response_status == 201) {
                                            plugin_log('resolved');
                                            if (isset($orderData->contracts)) {
                                                $contractData = $orderData->contracts;

                                                foreach ($contractData as $key => $value) {

                                                    $currentID = get_current_user_id();
                                                    $contrats = $this->wpdb->query(
                                                        $this->wpdb->prepare(
                                                            "INSERT INTO quickcover_contract( user_id, product_id, order_id, lineitem_id, quantity, contract_id, contract_status, refund_amount, contract_canceled_by)
                     VALUES ( '%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s')", $currentID, $value->order->product->id, $value->order->id, $value->order->orderItemId, $value->order->product->quantity, $value->id, 0, 0, 0
                                                        )
                                                    );
                                                }
                                            }
                        $remove = $this->wpdb->query(
                         $this->wpdb->prepare("DELETE FROM quickcover_cron_status WHERE order_id = %d", $order_id)
                        );
                        plugin_log(['contract_status' => $response_status, 'response' => $response, 'request' => $orderList]);
                         
						 $to = 'asoanker@afterinc.com';
                       $subject = 'QuickCover Create Contract API Success : After Product Protection Plan';
                 
                   $headers = array('Content-Type: text/html; charset=UTF-8','From: noreply@quickcover.me');
                   $message = "<html>
                          <body>
                            <h2>QuickCover Server Resolved</h2>
                            <h4>Outlet Id: $storeId </h4></br>
                            <h5>Payload</h5>
                            <p>$orderList</p>
                            </br>
                            <h5>Response</h5>
                           <p>['status'=>$response_status,'response'=>$response]</p>
                         </body></html>";
              
                        wp_mail($to, $subject, $message, $headers);
                                
                        } 
                      }
                   }
                 }
               }
             }
           }
         }				  
			

  /* display warranty option on product details page */
  function warranty_on_product_details() { 

     global $product;
    $table_name = $this->wpdb->prefix.'postmeta';

	  $appConfig = $this->wpdb->get_results($this->wpdb->prepare( "SELECT * FROM app_configurations WHERE id = %d AND configuration_property = %d", 1,1 ));
	  if(!empty($appConfig)){
     $apiKey = $appConfig[0]->api_key;
     $storeId = $appConfig[0]->store_id;
     $product_id = $product->get_id();
     $productPrice1 = $product->get_price();
	   $productPrice= sprintf('%.2f',$productPrice1);
     $warrantypric=$productPrice*100;
     $current_user_id = get_current_user_id();
      $currency = get_option('woocommerce_currency');

       $base_url = Permaplate::API_BASE_URL;
       $url = $base_url.'price/outlet/'.$storeId.'?productId='.$product_id.'&currency='.$currency.'&price='.$warrantypric;

      $args = array(
          'headers' => array(
            'Content-Type' => 'application/json',
            'X-QUICKCOVER-API-KEY' => $apiKey
          ),
		  'timeout' => 300
		  );

       $response = wp_remote_get( $url, $args );
       $response_status = wp_remote_retrieve_response_code($response);
       $response = wp_remote_retrieve_body( $response );
       $warrantyPlan = json_decode($response);
    if($response_status==200){
      $veriant_id = 1;
      $priceCurrency=$warrantyPlan->priceCurrency; 
      $planName=$warrantyPlan->name;
      $description=$warrantyPlan->description;
      $priceCategory=$warrantyPlan->priceCategory; 
      $termsAndConditionsURL=$warrantyPlan->termsAndConditionsURL; 
      $learnMoreURL=$warrantyPlan->learnMoreURL; 
      $plansArray=$warrantyPlan->plans;
      $wrtyPlans = json_encode($plansArray);

       $productWrt = $this->wpdb->get_results( $this->wpdb->prepare( "SELECT * FROM product_warranty WHERE product_id = %d", $product_id ));

        plugin_log(['status'=>$response_status,'response'=>$response]);
       
      if(empty($productWrt)){
       
         $results = $this->wpdb->query($this->wpdb->prepare("INSERT INTO product_warranty
         ( product_id, veriant_id, priceCurrency, pname, description, priceCategory, termsAndConditionsURL, learnMoreURL, plans )
          VALUES ( %d, %d, %s, %s, %s, %s, %s, %s, %s )", $product_id, $veriant_id, $priceCurrency, $planName,$description, $priceCategory, $termsAndConditionsURL, $learnMoreURL, $wrtyPlans));

            $warrantyId = $this->wpdb->insert_id;

         $result = $this->wpdb->query(
         $this->wpdb->prepare("INSERT INTO warranty_calc( user_id, post_id, warranty_id, warranty_total, plan_id )
         VALUES ( %d, %d, %d, %s, %s )", $current_user_id, $product_id, $warrantyId,0,'none'));

      }else{ 

       $results = $this->wpdb->query( $this->wpdb->prepare( "UPDATE product_warranty SET veriant_id = %d, priceCurrency = %s, pname = %s, description = %s, priceCategory = %s, termsAndConditionsURL = %s, learnMoreURL = %s, plans = %s WHERE product_id = %d", $veriant_id, $priceCurrency, $planName, $description, $priceCategory, $termsAndConditionsURL, $learnMoreURL, $wrtyPlans ,$product_id));

        }
    } else {

      $remove = $this->wpdb->query(
      $this->wpdb->prepare( "DELETE FROM product_warranty WHERE product_id = %d", $product_id));
    }
	
     $productWrt = $this->wpdb->get_results($this->wpdb->prepare("SELECT * FROM product_warranty WHERE product_id = %d", $product_id));

    if(!empty($productWrt)){
      if($productWrt[0]->plans !== ""){
        $plans=json_decode($productWrt[0]->plans, true);

       $is_warranty = $this->wpdb->get_results( $this->wpdb->prepare( "SELECT * FROM warranty_calc WHERE user_id = %d AND post_id = %d", $current_user_id, $product_id ));
       
        if(empty($is_warranty)){

         $result = $this->wpdb->query( $this->wpdb->prepare("INSERT INTO warranty_calc( user_id, post_id, warranty_id, warranty_total, plan_id )
         VALUES ( %d, %d, %d, %s, %s )", $current_user_id,$product_id,$productWrt[0]->id,0,'none'));
      }
  
      $postmeta = get_post_meta( $product_id, '_warranty' );
      
    if(!empty($postmeta) && ($postmeta[0] == "Enable") ){

     ?>
	
	 <!-- The Modal -->
   <div id="popupModel" class="modal1">
  <!-- Modal content -->
   <div class="modal-content1">
   <span class="close1">&times;</span>
   <div><h3>After plan key benefits include:</h3>
  <ul><li>Your After plan documents will be delivered to you via email only to the address associated with your order.</li><li>Fast, easy service including parts and labor, with no deductible for covered failures.</li>
  <li>Nationwide network of service providers.</li>
  <li>Power surge protection.</li>
  <li>Accidental damage from Handling (AHD) coverage available on certain products.</li><a href="https://www.quickcover.me/after-plan" target="_blank">Learn More..</a></ul><ul></ul></div>
  <button id="addpopup" class="addpopup" name="addpopup">Add</button>
  <button id="nothankspopup" class="nothankspopup" name="nothankspopup">No Thanks</button>
  <input type="hidden" class="popup-type" id="popup-type" value="<?php echo esc_attr($appConfig[0]->popup_type); ?>">
  </div>
  </div>
    
  <div class="parent-warranty-class"> 
      <span class="sortOptions">  
      <p class="planheading"><?php echo esc_attr($productWrt[0]->description); ?> </p>
      
      <input type="hidden" class="warranty-id" value="<?php echo esc_attr($productWrt[0]->id); ?>">
      <input type="hidden" class="product-price" value="<?php echo esc_attr($productPrice); ?>">

    <div class="leranMore" id="LearnMore">
    <p><a href="javascript:void(0)" class="btn btn-primary lin_modal_btn">Learn More</a></p>
    </div>
        <!--<div id="ex1" class="modal">
       <iframe width="100%" src="<?php //echo esc_url_raw($result[0]->learnMoreURL); ?>"></iframe>
      </div>-->  

<!-- Modal -->
  <div class="modal fade bd-example-modal-lg" id="planModal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" style='max-width:100%; width: 60%!important;'>
    <div class="modal-dialog modal-lg">

      <div class="modal-content">
        <div class="modal-body">
    
          <iframe src="<?php echo esc_url_raw($productWrt[0]->learnMoreURL); ?>" width="100%" height="170" frameborder="0" sandbox="allow-same-origin allow-scripts allow-popups allow-forms allow-top-navigation" allowtransparency="true">
                
          </iframe>
        </div>
      </div>
       
    </div>

  </div>


     <?php
     $warrantyOption = $this->wpdb->get_results( $this->wpdb->prepare( "SELECT * FROM warranty_calc WHERE user_id = %d AND post_id = %d", $current_user_id, $product_id ));

      if(!empty($warrantyOption)){
         $plan_id = $warrantyOption[0]->plan_id;
       } 

      $i = 1;
      $seqPlans = array();
      foreach ($plans as $key => $plan) {
        $seqPlans[$plan['displaySeqNo']-1] = $plan;
      }
      $planLength = count($seqPlans);

      for($x = 0; $x < $planLength; $x++) {
   ?> 
      <input type="radio" id="war_<?php echo esc_attr($i); ?>" class="price-variation warranty-button" warranty-price="<?php echo esc_attr($seqPlans[$x]['price']/100); ?>" product-id="<?php echo esc_attr($product->get_id()) ?>" name="warranty" value="<?php echo esc_attr($seqPlans[$x]['planId']); ?>/<?php echo esc_attr($seqPlans[$x]['description']); ?>/<?php echo esc_attr($seqPlans[$x]['term']); ?>" plan-id="<?php echo esc_attr($seqPlans[$x]['planId']); ?>" <?php echo esc_attr($seqPlans[$x]['planId']) == $plan_id ? 'checked' : '' ?> >
      
	   <label for="age_<?php echo esc_attr($i); ?>" class="label-text"><?php echo esc_attr($seqPlans[$x]['description']); ?></label><br>
    <?php
        $i++;
      }

    ?>
	
	<input type="radio" id="war_0" name="warranty" class="price-variation warranty-button" warranty-price="0" plan-id='none' product-id="<?php echo esc_attr($product->get_id()); ?>" value="none/0" checked>
      <label for="age_0" class="label-text">None</label><br>
    </span> 
  
    <p class="trmcond"><a target="_blank" href="<?php echo esc_url_raw($productWrt[0]->termsAndConditionsURL); ?>">Terms and Conditions</a></p>
    </div>
  <?php  
         
        }
      }
    }
  }
  
   if ( ! wp_next_scheduled('my_oneminute_event')) {
    wp_schedule_event( time(), 'my_oneminute_event', 'my_oneminute_event');
    }
	
	 if (!wp_next_scheduled('my_custom_5_minute_cron')) {
       wp_schedule_event(time(), 'every_five_minutes', 'my_custom_5_minute_cron');
      }
  
}


  /*warranty price cange on product description page */
  function pricechange(){
    
    $current_user_id = get_current_user_id();
    $warrantyPrice = sanitize_text_field($_POST['warrantyPrice']);
    $productId = sanitize_text_field($_POST['productId']);
    $planId = sanitize_text_field($_POST['planId']);
    $warrantyId = sanitize_text_field($_POST['warrantyId']);
    $variationId = sanitize_text_field($_POST['variationId']);
	$currency_symbol = get_woocommerce_currency_symbol();
    $product = wc_get_product($productId);
    $productActualPrice = $product->get_price();
	
	 // get variable price  *****************
	 
	 if ( $product->is_type( 'variable' ) ) {

       $variations = $product->get_available_variations();

        foreach ($variations as $variation) {
          if($variation['variation_id'] == $variationId){
            $display_regular_price = $variation['display_regular_price'];
            $display_price = $variation['display_price'];
          }
        }

      //Check if Regular price is equal with Sale price (Display price)
      if ($display_regular_price == $display_price){
        $display_price = false;
      }

      if($display_price == ""){
          $productActualPrice = $display_regular_price;
      }else{
          $productActualPrice = $display_price;
      }
      
    }else{
      $productActualPrice = $product->get_price();
    }

     $sql = "SELECT * FROM warranty_calc WHERE user_id= %d AND post_id= %d";
     $result = $this->wpdb->query($this->wpdb->prepare($sql, array($current_user_id, $productId)));


    if(empty($result)){
        // insert
      $warrantyPrice = (float) $warrantyPrice;

      $result = $this->wpdb->query(
       $this->wpdb->prepare(
       "INSERT INTO warranty_calc
       ( user_id, post_id, warranty_id, warranty_total, plan_id)
      VALUES ( %d, %d, %d, %d, %s)",$current_user_id,$productId,$warrantyId,$warrantyPrice,$planId));

      $totalPrice = (float) $productActualPrice + (float) $warrantyPrice;
      echo esc_attr($totalPrice);
      exit();

    }else{
        // update
        $warrantyPrice = (float) $warrantyPrice;

        $dbData = array(
          'user_id' => $current_user_id,
          'post_id' => $productId,
          'warranty_id' => $warrantyId, 
          'warranty_total' => $warrantyPrice,
          'plan_id' => $planId,
        );
         $id = $result[0]->id;
          
        $result = $this->wpdb->query( $this->wpdb->prepare( "UPDATE warranty_calc SET id = %d, user_id = %d, post_id = %d, warranty_id = %d, warranty_total = %s, plan_id = %s WHERE id = %d", $current_user_id, $productId, $warrantyId, $warrantyPrice, $planId, $id));

           $totalPrice = (float) $productActualPrice + (float) $warrantyPrice;

           echo esc_attr($totalPrice);

          exit();
         }
       }
  
  /* Set custom cart item price */
  function add_custom_price( $cart ) {
    
    $current_user_id = get_current_user_id();
    if ( is_admin() && ! defined( 'DOING_AJAX' ) )
        return;

    // Avoiding hook repetition (when using price calculations for example | optional)
    if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 )
        return;

    /* Loop through cart items */
    foreach ( $cart->get_cart() as $cart_item ) {

      $productId = $cart_item['product_id'];
      $quantity = $cart_item['quantity'];
      $item_key = $cart_item['key'];
      $product_price1 = $cart_item['data']->get_price();
	  $product_price= sprintf('%.2f',$product_price1);

      if(isset($cart_item['warranty'])) {
         $plan_id = $cart_item['warranty'];
         if($plan_id == "none") {
            $cart_item['data']->set_price($product_price);
         }else{
           $result = $this->wpdb->get_results( $this->wpdb->prepare( "SELECT * FROM product_warranty WHERE product_id = %d", $productId));

            if(!empty($result)){
              $plans = json_decode($result[0]->plans);

              foreach ($plans as $key => $plan) {
                 if($plan->planId == $plan_id) {
                     $plan_price = $plan->price;
                 }
              }

              $priceWithWarranty = (float) $product_price + (float) $plan_price;
              $cart_item['data']->set_price($priceWithWarranty);

            }
         }
      }else{
         $cart_item['data']->set_price($product_price);
      }   
    }
  }

  function add_loader_before_cart_table(){
  ?>
     <img src='<?php echo esc_url(plugin_dir_url( __FILE__ ))?>assets/images/Chasing_arrows.gif' id="loaderImg" class="loadingimg">
  <?php
  }
  
  /*add edit button on cart page */
  function cart_product_title( $title, $values, $cart_item_key ) {
   
    $table_name = $this->wpdb->prefix.'postmeta';
    echo sanitize_title($title);
    $productId = $values['product_id'];
    $item_key = $values['key'];
    if(isset($values['warranty'])){
      $plan_id = $values['warranty'];
    // getting plan for product
      $result = $this->wpdb->get_results( $this->wpdb->prepare( "SELECT * FROM product_warranty WHERE product_id = %d", $productId));

      if(!empty($result)){
      
      if($result[0]->plans !== ""){

      $postmeta = $this->wpdb->get_results( $this->wpdb->prepare( "SELECT * FROM $table_name WHERE post_id = %d AND meta_key = %s", $productId, '_warranty'));
      
      if(!empty($postmeta) && ($postmeta[0]->meta_value == "Enable") ){

        $postmetasync = $this->wpdb->get_results( $this->wpdb->prepare( "SELECT * FROM $table_name WHERE post_id = %d AND meta_key = %s", $productId, '_sync'));

     // if(!empty($postmetasync) && ($postmetasync[0]->meta_value == "Synced") ){
       // $plans=json_decode($result[0]->plans, true);
       // $current_user_id = get_current_user_id();
		
		 if(!empty($postmetasync)){
        $plans=json_decode($result[0]->plans, true);
        $current_user_id = get_current_user_id();

        ?>
        <span class="sortOptions">
          <p class="warranty-plan warrantyButton"><u><a>Edit</a></u></p>
          <div class="warranty_option label-text" style='display:none'>
              <input type="hidden" class="warranty-id" value="<?php echo esc_attr($result[0]->id); ?>">

              <input type="radio" name="warranty_<?php echo esc_attr($item_key); ?>" class="price-variation-on-cart warranty-button" warranty-price="0" plan-id="none" product-id="<?php echo esc_attr($productId); ?>" item-key="<?php echo esc_attr($item_key); ?>" <?php echo esc_attr($plan_id) == 'none' ? 'checked' : 'disabled' ?>>None<br>     
            <?php
              $i = 1;
			  $seqPlans = array();
            foreach ($plans as $key => $plan) {
			 $seqPlans[$plan['displaySeqNo']-1] = $plan;
			}
             $planLength = count($seqPlans);
             for($x = 0; $x < $planLength; $x++) {			 
             ?>
               <input type="radio" name="warranty_<?php echo esc_attr($item_key); ?>" class="price-variation-on-cart warranty-button" warranty-price="<?php echo esc_attr($seqPlans[$x]['price']); ?>" product-id="<?php echo esc_attr($productId); ?>" item-key="<?php echo esc_attr($item_key); ?>" plan-id="<?php echo esc_attr($seqPlans[$x]['planId']); ?>" term="<?php echo esc_attr($seqPlans[$x]['term']); ?>/<?php echo esc_attr($seqPlans[$x]['description']); ?>" <?php echo esc_attr($seqPlans[$x]['planId']) == $plan_id ? "checked disabled" : "" ?>> 
                
				<label for="age_<?php echo esc_attr($i); ?>" class="label-text"><?php echo esc_attr($seqPlans[$x]['description']); ?></label><br>
               <?php
                $i++;
              }
            ?>
          </div>
        </span>
      <?php
       }
      }
     }
    }
  } 
}

  /* call ajax function on cart page and change */
  function pricechangeoncart(){
   
    $current_user_id = get_current_user_id();
    $warrantyPrice = sanitize_text_field($_POST['warrantyPrice']);
    $productId = sanitize_text_field($_POST['productId']);
    $planId = sanitize_text_field($_POST['planId']);
    $warrantyId = sanitize_text_field($_POST['warrantyId']);
    $itemKey = sanitize_text_field($_POST['itemKey']);
    $term = sanitize_text_field($_POST['term']);
	 $termDesc = explode('/', $term);
    $term = isset($termDesc[0]) ? $termDesc[0] : 'None';
    $description = isset($termDesc[1]) ? $termDesc[1] : 'None';

    global $woocommerce;
    $product = wc_get_product($productId);
    $productActualPrice = $product->get_price();

    /* Update meta data from cart for update */

    foreach ($woocommerce->cart->cart_contents as $key => $cart_item) {

          if(isset($cart_item['warranty'])) {

            if(($cart_item['warranty'] == $planId) && ($cart_item['product_id'] == $productId)) {

                $woocommerce->cart->cart_contents[$key]['quantity'] = $woocommerce->cart->cart_contents[$key]['quantity'] + $woocommerce->cart->cart_contents[$itemKey]['quantity'];
                unset($woocommerce->cart->cart_contents[$itemKey]);
                $woocommerce->cart->set_session();
            } 

          }
              
      }

    if(isset($woocommerce->cart->cart_contents[$itemKey]['warranty']) ){

     $woocommerce->cart->cart_contents[$itemKey]['warranty'] = $planId;
	  $woocommerce->cart->cart_contents[$itemKey]['description'] = $description;
     $woocommerce->cart->cart_contents[$itemKey]['term'] = $term;
     $woocommerce->cart->set_session();

     }
  }

  /* save configuraton setting from admin */
  function saveconfig(){

     if ( !wp_verify_nonce($_POST['nonce'], 'Permaplate##Inc@20') ){ 
      die('Permission Denied.'); 
     }
      $table_name ='app_configurations';
      $current_user_id = get_current_user_id();
      $dbData = array();
      $user_id = $current_user_id;
      $configuration_property = sanitize_text_field($_POST['ebaleqiuck']);
	  $warranty_status = sanitize_text_field($_POST['warranty_status']);
	  $backup_status = sanitize_text_field($_POST['backup_status']);
      $sync_type = sanitize_text_field($_POST['autosync']);
      $api_key = sanitize_text_field($_POST['apikey']);
      $store_id = sanitize_text_field($_POST['storeid']);
      $batch_size = sanitize_text_field($_POST['batchsize']);
      $popup_type = sanitize_text_field($_POST['enablepopup']);
	  $email = sanitize_text_field($_POST['email']);

      // $result = $this->wpdb->update('app_configurations', $dbData, array('id' => 1));

     $result = $this->wpdb->query( $this->wpdb->prepare( "UPDATE app_configurations SET configuration_property = %s, warranty_status = %s, backup_status = %d, sync_type = %s, api_key = %s, store_id = %s, batch_size = %s, popup_type = %s, email = %s WHERE id = %d", $configuration_property, $warranty_status, $backup_status, $sync_type, $api_key, $store_id, $batch_size, $popup_type, $email, 1));

      echo esc_attr($result); die;

    }
	
	
	 function testauthentication(){

     $apikey = sanitize_text_field($_POST['apikey']);
     $storeid = sanitize_text_field($_POST['storeid']);
     $current_date = sanitize_text_field($_POST['currentTime']);

          $base_url = Permaplate::API_BASE_URL;
          $url = $base_url.'authenticate-store/storeId='.$storeid.'/apiKey='.$apikey;	  

        $body = array(
            'headers' => array(
              'Content-Type' => 'application/json',
              'X-QUICKCOVER-API-KEY' => $apiKey
            ),
           'method'=> 'POST',
           'timeout' => 300
         );
        
        $response = wp_remote_post($url, $body);
        $response_status = wp_remote_retrieve_response_code($response);
        $response = wp_remote_retrieve_body('responce');
      if ($response_status == 200) {
          $this->wpdb->query($this->wpdb->prepare("UPDATE app_configurations SET updated_date=%s,status=%s WHERE id= %d",$current_date,1, 1));
          plugin_log(['statuscode'=>$response_status]);
        } 
        else {
           $this->wpdb->query($this->wpdb->prepare("UPDATE app_configurations SET updated_date=%s,status=%s WHERE id= %d",$current_date,0, 1));
		   plugin_log(['statuscode'=>$response_status]);
           }
        wp_send_json($response_status);
        exit();
   }

  function getBatch () {
        $batchSize = sanitize_text_field($_GET['batchsize']);
        $args = array(
		     'post_status' => 'publish',
              'post_type' => 'product',
              'posts_per_page' => '-1',
              'meta_query' => [
                [
                    'key' => '_sync',
                    'value' => ['New','Not Synced','Failed']
                ],
                [
                    'key' => '_warranty',
                    'value' => 'Enable'
                ]
            ],
            'orderby' => 'ID',
            'order' => 'DESC'
          );   

      $loop = new WP_Query( $args );

      $productList = array(); $i = 0;   
      if ( $loop->have_posts() ): while ( $loop->have_posts() ): $loop->the_post();
        
           global $product;
           $product_id=$product->get_id();
           $productList[] = $product_id;

           $i++;

       endwhile; endif; wp_reset_postdata();
      // print_r($productList);die();
        if (!empty($productList)) {
            $productsData = array_chunk($productList, $batchSize);
            $chunksCount = sizeof($productsData);
            $batchData = array(
                'productsData' => $productsData,
                'chunksCount' => $chunksCount,
                'status' => 200
            );
            echo json_encode($batchData); die();
        } else {
            $batchData = array('status' => 201);
            echo json_encode($batchData); die();
        }
    }

    function syncProduct() {
        $api_key = sanitize_text_field($_POST['api_key']);
        $quick_store_id = sanitize_text_field($_POST['quick_store_id']);

        $batchSize = sanitize_text_field($_POST['batchSize']);
        $failedCase = sanitize_text_field($_POST['failedCase']);
		$successCase = sanitize_text_field($_POST['successCase']);
        $totalBatch = sanitize_text_field($_POST['totalBatch']);
        
        if ($failedCase == 0) {
            $offset = 0;
        } else {
            $offset = $failedCase * $batchSize;
        }

        $successBatch = 0;
        $failBatch = 0;
        $query = array(
		     'post_status' => 'publish',
              'post_type' => 'product',
              'posts_per_page' => $batchSize,
              'meta_query' => [
                [
                    'key' => '_sync',
                   'value' => ['New','Not Synced','Failed']
                ],
                [
                    'key' => '_warranty',
                    'value' => 'Enable'
                ]
            ],
            'orderby' => 'ID',
            'offset' => $offset,
            'order' => 'DESC'
          );   

        $loop = new WP_Query( $query );
        // print_r($loop->have_posts()); die();
        if (!$loop->have_posts()) {
            $failBatch = 1;
			
			 $batchLogData = [
                "totalBatch" => $totalBatch,
                "successCase" => $successCase + $successBatch,
                "failedCase" => $failedCase + $failBatch
            ];
			$batchStats = $this->batchStats($batchLogData);
            $resposneArr = array(
                'success'=>$successBatch, 
                'failBatch'=>$failBatch, 
                'status'=>false
            );
            echo json_encode($resposneArr); die();
        }

        $productList = array(); $i = 0;   
      if ( $loop->have_posts() ): while ( $loop->have_posts() ): $loop->the_post();
        
           global $product;
           // $priceCurrency="USD";
           $priceCurrency= get_option('woocommerce_currency');
           $product_id=$product->get_id();
           $name=$product->get_name();
           $price = $product->get_price();
           $price = (100*(float)$price);
          // $sku = $product->get_sku();
		   $sku = ($product->get_sku() !='') ? $product->get_sku() : $product_id;
           $description = ($product->get_description() != '') ? $product->get_description() : '';
           //$model= $product->get_sku();
		   $model= ($product->get_sku() !='') ? $product->get_sku() : $product_id;
           $category = $product->get_categories();
           $category = strip_tags($category);
           $category = strtolower($category);

           if($category == "uncategorized"){
            $category = "";
           }

         $productList[$i]['priceCurrency'] = $priceCurrency;
         $productList[$i]['id'] = $product_id;
         $productList[$i]['category'] = $category;
         $productList[$i]['description'] = $description;
         $productList[$i]['model'] = $model;
         $productList[$i]['price'] = $price;
         $productList[$i]['name'] = $name;  
         $productList[$i]['sku'] = $sku; 

         $i++;

        endwhile; endif; wp_reset_postdata();

        $productLists['data'] = $productList;
        $productJson = json_encode($productLists);
     
	    $base_url = Permaplate::API_BASE_URL;
        $url = $base_url.'products/batch/outlet/'.$quick_store_id.'?upsert=true';
		
        $body = array(
            'headers' => array(
              'Content-Type' => 'application/json',
              'X-QUICKCOVER-API-KEY' => $api_key
            ),
            'body' => $productJson,
			'timeout' => 300
          );

         $response = wp_remote_post( $url, $body );
         $response_status = wp_remote_retrieve_response_code($response);
         $response = wp_remote_retrieve_body( $response ); 
         $table_name = $this->wpdb->prefix.'postmeta';

        if ($response_status == 202) {
            $successBatch = 1;
            foreach ($productList as $key => $value) {
                $result = $this->wpdb->query( $this->wpdb->prepare("UPDATE $table_name SET meta_value = %s WHERE post_id = %d AND meta_key = %s", 'Synced', $value['id'], '_sync'));
            }
			plugin_log(['status'=>$response_status,'response'=>$response,'request'=>$productJson]); 
        } else {
             $failBatch = 1;
           foreach ($productList as $key => $value) {
                $result = $this->wpdb->query( $this->wpdb->prepare("UPDATE $table_name SET meta_value = %s WHERE post_id = %d AND meta_key = %s", 'Failed', $value['id'], '_sync'));
              }
            plugin_log(['status'=>$response_status,'response'=>$response,'request'=>$productJson]); 
        }
		$batchLogData = array(
            'totalBatch'=> $totalBatch, 
            'successCase'=> $successCase + $successBatch, 
            'failedCase'=> $failedCase + $failBatch
        ); 
		$batchStats = $this->batchStats($batchLogData);
        $resposneArr = array(
            'success'=>$successBatch, 
            'failBatch'=>$failBatch, 
            'status'=>true,
			'batchStats'=>$batchStats
        );
        echo json_encode($resposneArr); die();
    }
	
	 function batchStats($data){
       $this->wpdb->query('TRUNCATE TABLE batchsync_status');
       
       $total_batch = sanitize_text_field($data['totalBatch']);
       $success_batch = sanitize_text_field($data['successCase']);
       $failed_batch = sanitize_text_field($data['failedCase']);
       $current_time = date('Y-m-d H:i:s');

      $result_status = $this->wpdb->query($this->wpdb->prepare("INSERT INTO batchsync_status
       (total_batch, success_batch, failed_batch, display_date)
      VALUES ( %s, %s, %s, %s)", $total_batch, $success_batch, $failed_batch,$current_time));
      return $current_time;  
    }
	
	
	//Update warranty and sync status in local database
  function updateLocalProduct(){

     $warranty_status = sanitize_text_field($_POST['warranty']);
     $sync_status = sanitize_text_field($_POST['sync']);
     $category_id = sanitize_text_field($_POST['category']);

     $table_name = $this->wpdb->prefix.'postmeta';

     if($warranty_status !== "" && $sync_status !== ""){
          
         if($warranty_status == 'Enable'){
            $wstatus = 'Disable';
         }else{
            $wstatus = 'Enable';
         } 

          // Update status for warranty

          $query = array(
             'post_type' => 'product',
             'posts_per_page' => -1,
             'meta_query' => [
                    [
                        'key' => '_warranty',
                        'value' => $wstatus
                    ]
                ],
             'orderby' => 'id',
             'order' => 'DESC'
          );
          if($category_id !== "All"){
               $product_cats = get_term( $category_id );
               $query['product_cat'] = $product_cats->name;
           }

          $wproducts = get_posts( $query );
          
         $prdWrtIdArr = [];
         foreach($wproducts as $product) {
            $prdWrtIdArr[] = $product->ID;
         }

         $result = 0;
         if(!empty($prdWrtIdArr)){
          $prdWrtIdStr = implode(",",$prdWrtIdArr);
          $result = $this->wpdb->query( $this->wpdb->prepare("UPDATE $table_name SET meta_value='".$warranty_status."' WHERE post_id IN (".$prdWrtIdStr.") AND meta_key='_warranty'"));
        }

        // Update status for sync
          $querys = array(
          'post_type' => 'product',
          'posts_per_page' => -1,
          'meta_key' => '_sync',
          'orderby' => 'id',
          'order' => 'DESC'
        );

         if($category_id !== "All"){
               $product_cats = get_term( $category_id );
               $querys['product_cat'] = $product_cats->name;
           }

         $sproducts = get_posts( $querys );
         $prdSyncIdArr = [];
         foreach($sproducts as $product) {
            $prdSyncIdArr[] = $product->ID;
         }

         $results = 0;
         if(!empty($prdSyncIdArr)){
          $prdSyncIdStr = implode(",",$prdSyncIdArr);
          $results = $this->wpdb->query( $this->wpdb->prepare("UPDATE $table_name SET meta_value='".$sync_status."' WHERE post_id IN (".$prdSyncIdStr.") AND meta_key='_sync'"));
        }

         $updtStatus = array(
            'warranty' => $result,
            'sync' => $results,
         );
         
		  wp_send_json($updtStatus);
         exit();

     } elseif($warranty_status !== ""){

         if($warranty_status == 'Enable'){
            $status = 'Disable';
         }else{
            $status = 'Enable';
         }

           $query = array(
             'post_type' => 'product',
             'posts_per_page' => -1,
             'meta_query' => [
                 [
                   'key' => '_warranty',
                   'value' => $status
                 ]
             ],
             'orderby' => 'id',
             'order' => 'DESC'
           );

            if($category_id !== "All"){
                $product_cats = get_term( $category_id );
                $query['product_cat'] = $product_cats->name;
            }

          $products = get_posts( $query );

          $productIdArray = [];
          foreach($products as $product) {
             $productIdArray[] = $product->ID;
          }

          $result = 0;
          if(!empty($productIdArray)){
            $productIdString = implode(",",$productIdArray);
            $result = $this->wpdb->query( $this->wpdb->prepare("UPDATE $table_name SET meta_value='".$warranty_status."' WHERE post_id IN (".$productIdString.") AND meta_key='_warranty'"));
        }

          //echo "<pre>"; print_r($result); die;

          $updtStatus = array(
            'warranty' => $result,
            'sync' => 0,
         );
		 
          wp_send_json($updtStatus);
         exit();

     }elseif($sync_status !== ""){

         $query = array(
          'post_type' => 'product',
          'posts_per_page' => -1,
          'meta_key' => '_sync',
          'orderby' => 'id',
          'order' => 'DESC'
          );

           if($category_id !== "All"){
               $product_cats = get_term( $category_id );
               $query['product_cat'] = $product_cats->name;
           }

         $products = get_posts( $query );

         $productIdArray = [];
         foreach($products as $product) {
            $productIdArray[] = $product->ID;
         }

        $result = 0;
        if(!empty($productIdArray)){
          $productIdString = implode(",",$productIdArray);
          $result = $this->wpdb->query( $this->wpdb->prepare("UPDATE $table_name SET meta_value='".$sync_status."' WHERE post_id IN (".$productIdString.") AND meta_key='_sync'"));
        }

         $updtStatus = array(
            'warranty' => 0,
            'sync' => $result,
         );
		  wp_send_json($updtStatus);
         exit();
     }
        
  }

  /*cancel functionality for both admin and customer end */
 function cancelContract(){

   $cancel_result = $this->wpdb->get_results( $this->wpdb->prepare( "SELECT * FROM app_configurations WHERE id = %d", 1 ));

    if(!empty($cancel_result)){
      $apiKey = $cancel_result[0]->api_key;
      $storeId = $cancel_result[0]->store_id;
      $cancel_email = $cancel_result[0]->email;
	  $currentuser_id = $cancel_result[0]->user_id;
	  
	  $tablename = $this->wpdb->prefix . 'users';      
     $get_loginDetails = $this->wpdb->get_results($this->wpdb->prepare("SELECT user_login FROM $tablename WHERE ID = %s ", $currentuser_id));
     $admin_login=$get_loginDetails[0]->user_login;
	 
    if(!empty($_POST['orderID'] && !empty($_POST['lineItemID']))){
      
      $orderID = sanitize_text_field($_POST['orderID']);
      $lineItemID = sanitize_text_field($_POST['lineItemID']);
	  
	  $orderDetails = wc_get_order($orderID);
		$orderDate = $orderDetails->order_date;
		$billing_first_name = $orderDetails->get_billing_first_name();
		$billing_last_name  = $orderDetails->get_billing_last_name();
		$fullName = $billing_first_name . " " . $billing_last_name;
		$billing_email = $orderDetails->get_billing_email();
		$siteTitle = get_bloginfo('name');
        $order_items = $orderDetails->get_items();
		$item_data = $orderDetails->get_data();
		$getLineItem = $order_items[$lineItemID];
		$item_quantity  = $getLineItem->get_quantity();
		$item_datanew = $getLineItem->get_data();
		$planName = $item_datanew['name'];
		$item_meta_data = $getLineItem->get_meta_data();
		$planDesc = $item_meta_data[0]->value['description'];
		 $cancelPlan = $planName . ": " . $planDesc;
						
      if(isset($_POST['cancelby']) && !empty($_POST['cancelby'])){
           $cancelBy = sanitize_text_field($_POST['cancelby']);
      }

    $getContracts = $this->wpdb->get_results( $this->wpdb->prepare( "SELECT * FROM quickcover_contract WHERE order_id = %s AND lineitem_id = %s", $orderID, $lineItemID ));
       $totalrow = count($getContracts);
       if(!empty($getContracts)){
		   $resStatusArr = []; $pass = []; $fail = []; $subtotal;
		    foreach ($getContracts as $key => $getContract) {

                                $contractID = $getContract->contract_id;
                                $cancel_contract = array(
                                'action' => 'Cancel',
                                'contractId' => $contractID,
                                'orderId' => $getContract->order_id,
                                'orderItemId' => $getContract->lineitem_id,
                                'quantity' => 1,
                                );

                                $cancel_contract = json_encode($cancel_contract);

                                $base_url = Permaplate::API_BASE_URL;
                                $url = $base_url . 'contracts/outlet/' . $storeId . '/cancel';

                                $args = array(
                                'headers' => array(
                                'Content-Type' => 'application/json',
                                'X-QUICKCOVER-API-KEY' => $apiKey
                                ),
                                'body' => $cancel_contract,
                                'method'      => 'POST',
                                'timeout'     => 300
                                );

                                $response = wp_remote_post($url, $args);
                                $response_status = wp_remote_retrieve_response_code($response);
                                $response = wp_remote_retrieve_body($response);

                                $contract_responce = json_decode($response, true);

                                if ($response_status == 200) {
                                    $rfamount = ($contract_responce['contracts'][0]['refundAmount'] / 100);
                                    $contractUpdate = $this->wpdb->query($this->wpdb->prepare("UPDATE quickcover_contract SET contract_status = %s, refund_amount = %s, contract_canceled_by = %s WHERE contract_id = %s", $response_status, $rfamount, $cancelBy, $contractID));

                                    if (isset($contract_responce['contracts'][0]['refundAmount']) && !empty(isset($contract_responce['contracts'][0]['refundAmount']))) {

                                        $subtotal += (float)$rfamount;
                                    }

                                    $pass[] = $contractID;

                                    $resStatusArr['status'] = $response_status; 
                                    $resStatusArr['res'] = $rfamount;

                                } else if ($response_status == 250) {

                                    $contractUpdate = $this->wpdb->query($this->wpdb->prepare("UPDATE quickcover_contract SET contract_status = %s WHERE contract_id = %s", $response_status, $contractID));

                                    $fail[] = $contractID;

                                    $resStatusArr['status'] = $response_status; 

                                } else {

                                    $fail[] = $contractID;
                                    $resStatusArr['status'] = 404;
                                }
   
                            } // foreach end
							
							$totalPass = count($pass);
                            $totalFail = count($fail);

                            $resStatusArr['pass'] = $totalPass;
                            $resStatusArr['fail'] = $totalFail;
                            if ($totalPass == $totalrow) {
                                $status = 1;
                            } else if ($totalFail == $totalrow) {
                                $status = 0;
                            } else {
                                $status = 2;
                            }
            plugin_log(['contractCancel' => $contract_responce, 'response' => $resStatusArr]);

	            if($status==1) {
					$to =  $cancel_email;
					$subject = 'After Product Protection Plan Cancelled – Refund Required';
					
					$headers = array('Content-Type: text/html; charset=UTF-8', 'From: noreply@quickcover.me');
					$message = '<html>
								<head>
								<meta charset="UTF-8">
							<meta http-equiv="X-UA-Compatible" content="IE=edge">
								<meta name="viewport" content="width=device-width, initial-scale=1.0">
							<title>eamil</title>
						<link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;1,100;1,300;1,400;1,700&display=swap" rel="stylesheet">
					<style>
				*{margin: 0;}
				body{font-family: "Roboto", sans-serif; font-size: 14px;}
			</style>
			</head>
			<body>
        <table bgcolor="#d4e2ea" style="margin: 0 auto; width: 600px; background-color: #d4e2ea; padding: 25px;">
            <tr>
                <td style="text-align: center; padding: 15px 0; border-bottom: solid 1px #ccc;">
                <img src="' . esc_url(plugin_dir_url(__FILE__)) . 'assets/images/logo.png">
                </td>
            </tr>
            <tr>
                <td>
                    <p style="padding-bottom: 10px;"> Hello ' . $admin_login . '</p>
                </td>
            </tr>
            <tr style="padding-top: 20px; margin-bottom: 10px;">
                <td>
                   <p>Your customer has been cancelled '. $planDesc .' <b>Please initiate a refund of</b> $'. $subtotal .' through '.$siteTitle.'. <br /> 
                    Here are the order details:</p>
                </td>
            </tr>
            <tr>
                <td>
                    <table>
                        <tr>
                            <td style="width: 124px;">Order Number: </td>
                            <td>' . $orderID . '</td>
                        </tr>
                        <tr>
                            <td>Order Date:</td>
                            <td>' . date("m/d/Y", strtotime($orderDate)) . '</td>
                        </tr>
                        <tr>
                            <td>Customer Name: </td>
                            <td>' . $fullName . '</td>
                        </tr>
                        <tr>
                            <td>Customer Email:</td>
                            <td><a href="mailto:' . $billing_email . '" target="_blank">' . $billing_email . '</a></td>
                        </tr>
                        <tr>
                            <td>Quantity: </td>
                            <td>' . $item_quantity . '</td>
                        </tr>
                        
                        <tr>
                            <td>Plan Cancelled: </td>
                            <td>' . $cancelPlan . '</td>
                        </tr>
                       
                    </table>
                </td>
            </tr>
            <tr>
                <td style="margin-top: 10px;">
                    <p>
                        If you have any questions about your account, please contact
                        <a href="mailto:jfdkslfj@jdfj.com" target="_blank">quick-techsupport@afterinc.com</a>
                        and we’ll get right back to you. <br />
                        Reference:</br>
                        Store Id:' . $storeId . '<br />
                        Woocommerce 
                        
                    </p>
                    
                </td>

            </tr> 
            
            <tr>
                <td>
                    <p>
                        Thank you again for using QuickCover<br>
                        Sincerely,<br>
                        QuickCover Support</p>
                </td>
            </tr>
            <tr>
                <td>
                    <p>
                        Copyright © 2023 All Rights Reserved | After, Inc. Powered by QuickCover®
                    </p>
                    </td>
                </tr>
                </body>
                </html>';

		  wp_mail($to, $subject, $message, $headers);   

		     echo json_encode($resStatusArr);  
			  die();
                    }
                  }
               }
            }
         }
			
      function deleteProduct($product_id='') {
       $post_id= $product_id;
       $result = $this->wpdb->get_results( $this->wpdb->prepare( "SELECT * FROM app_configurations WHERE id = %d", 1 ));
     
     if(!empty($result)){
      $apiKey = $result[0]->api_key;
      $storeId = $result[0]->store_id;  
	 
	  $base_url = Permaplate::API_BASE_URL;
      $url = $base_url.'products/outlet/'.$storeId.'?productId='.$post_id;

      $args = array(
          'headers' => array(
            'Content-Type' => 'application/json',
            'X-QUICKCOVER-API-KEY' => $apiKey
          ),
          'method'    => 'DELETE',
           'timeout'     => 300
        );

       $response = wp_remote_request( $url, $args );
       $response_status = wp_remote_retrieve_response_code($response);
       $response = wp_remote_retrieve_body( $response );   

       }
   }
   
   //delete single and multiple product hook
   function wpdocs_trash_multiple_posts( $post_id = '' ) {
    if ( isset( $_GET['post'] ) && is_array( $_GET['post'] ) ) {
        foreach ( $_GET['post'] as $post_id ) {
         $this->deleteProduct($post_id);
        }
    } else {
        $this->deleteProduct($post_id);
    }
}

   function action_woocommerce_cart_item_removed( $cart_item_key, $cart ) {
 
    $current_user_id = get_current_user_id();
    $product_id = $cart->cart_contents[ $cart_item_key ]['product_id'];

    $result = $this->wpdb->get_results( $this->wpdb->prepare( "SELECT * FROM warranty_calc WHERE user_id = %d AND post_id = %d", $current_user_id, $product_id ));
    
      if(!empty($result)){
       $dbData = array('warranty_total' => 0,
       'plan_id' => 'none',
       );
          $this->wpdb->query( $this->wpdb->prepare( "UPDATE warranty_calc SET id = %d, warranty_total = %s, plan_id = %s; WHERE id = %d", 0, 'none', $result[0]->id ));
    
       }
     }

      /*Add custom data to Cart in */
      function AddNewProductInsteadChangeQuantity( $cart_item_data, $product_id ) {

      if(isset($_REQUEST['warranty']))
       {
          $warranty = sanitize_text_field($_REQUEST['warranty']);
          $warranty = explode('/', $warranty);

         $cart_item_data['warranty'] = isset($warranty[0]) ? $warranty[0] : 'None';
		  $cart_item_data['description'] = isset($warranty[1]) ? $warranty[1] : 'None';
           $cart_item_data['term'] = isset($warranty[2]) ? $warranty[2] : 'None';

         $cart_items = WC()->cart->cart_contents;
         
         foreach ($cart_items as $key => $cart_item) {
            if(isset($cart_item['warranty']) && isset($cart_item['distinctive_key'])) {

              if(($cart_item['warranty'] == $warranty[0]) && ($cart_item['product_id'] == $product_id)) {

                  $cart_item_data['distinctive_key'] = $cart_item['distinctive_key'];
                  return $cart_item_data;

              } else {

                if(($cart_item['warranty'] !== $warranty[0]) && ($cart_item['product_id'] !== $product_id)) {
                   $distinctive_cart_item_key = md5( microtime() . rand() );
                   $cart_item_data['distinctive_key'] = $distinctive_cart_item_key;
                   return $cart_item_data;
                 }

               }

             }
            
          }

         return $cart_item_data;
      }
    }
    
    /**
 * Display information as Meta on Cart page
 * @param  [type] $item_data [description]
 * @param  [type] $cart_item [description]
 * @return [type]            [description]
 */

  function add_item_meta($item_data, $cart_item)
  {

    if(array_key_exists('warranty', $cart_item))
    {
	   $product_id = $cart_item['product_id'];	
      //echo "<pre>"; print_r($cart_item); die;
	   $planname = $this->wpdb->get_results( $this->wpdb->prepare("SELECT * FROM product_warranty"));
	    $pname = $planname[0]->pname;
		
      $plan_id = $cart_item['warranty'];
      $description = $cart_item['description'];

      if($plan_id == "none") {
          $item_data[] = array(
              'key'   => $pname,
              'value' =>  'None'
          );
      }else {

        $item_data[] = array(
            'key'   => $pname,
            'value' => $description
        );
          
      }
    }
      return $item_data;
  }


  function add_custom_order_line_item_meta($item, $cart_item_key, $values, $order)
  {
     if(array_key_exists('warranty', $values))
      {
          $warrantyData = array(
            'plan_id' => $values['warranty'],
			'description' => $values['description'],
            'term' => $values['term']
          );
          $item->add_meta_data('_warranty',$warrantyData);
      }
  }

  function afterinc_sync_order_quickcover($order_id){
               // print_r($order_id);die(); 
                ini_set('max_execution_time', '300');
                plugin_log('we are checking order sync hook');
                plugin_log('data log::'.json_encode([$order_id, $posted_data, $order]));
                $appConfig = $this->wpdb->get_results($this->wpdb->prepare("SELECT * FROM app_configurations WHERE id = %d", 1));
                $order = wc_get_order($order_id);
                $status = $order->get_status();
               
                if (!empty($order)) {
                    $order_data = $order->get_data();
                    $order_items = $order->get_items();
                    $srlNo = count($order_items);
                    $order_date = $order->get_date_created()->format('Y-m-d H:i:s');
                    $orderItem = array();
                    $in_warranty = array();
                    foreach ($order_items as $key => $item) {
                        $lineitem_id = $item->get_id();

                        $result = $this->wpdb->get_results($this->wpdb->prepare("SELECT * FROM product_warranty WHERE product_id = %d", $item->get_product_id()));

                        if (!empty($result)) {

                            $planDatas = $item->get_meta_data();

                            foreach ($planDatas as $key => $planId) {
                                if (isset($planId->key) && ($planId->key == '_warranty')) {
                                    $plan_id = $planId->value['plan_id'];
                                    if ($plan_id !== 'none') {
                                          $in_warranty[] = $result[0]->product_id;
                                          $plans = json_decode($result[0]->plans, true);

                                        foreach ($plans as $key => $plan) {
                                            if ($plan['planId'] == $plan_id) {
                                                    $contract = array(
                                                'priceCurrency' => $result[0]->priceCurrency,
                                                'planId' => $plan['planId'],
                                                'price' => $plan['price'],
                                                'quantity' => $item->get_quantity(),
                                                    );
                                            }
                                        }

                                        $orderItem[] = array(
                                        'id' => $lineitem_id,
                                        'product' => array(
                                        'priceCurrency' => $result[0]->priceCurrency,
                                        'id' => $item->get_product_id(),
                                        'contract' => $contract,
                                        'purchasePrice' => (float) ($item->get_product()->get_price()) * 100,
                                        'quantity' => $item->get_quantity(),
                                        'serialNumber' => $srlNo
                                        )
                                        );
                                    }
                                }
                            }
                        }
                    }

                  plugin_log("after order item ===========>>>>>>");

                    if (!empty($in_warranty)) {

                        if (($order_data['billing']['address_1'] == "")) {
                            $order_data['billing']['address_1'] = "null";
                        }
                        if (($order_data['billing']['address_2'] == "")) {
                            $order_data['billing']['address_2'] = "null";
                        }
                        if (($order_data['billing']['city'] == "")) {
                            $order_data['billing']['city'] = "null";
                        }
                        if (($order_data['billing']['postcode'] == "")) {
                            $order_data['billing']['postcode'] = "null";
                        }
                        if (($order_data['billing']['country'] == "")) {
                            $order_data['billing']['country'] = "null";
                        }

                        if (($order_data['shipping']['address_1'] == "")) {
                            $order_data['shipping']['address_1'] = "null";
                        }
                        if (($order_data['shipping']['address_2'] == "")) {
                            $order_data['shipping']['address_2'] = "null";
                        }
                        if (($order_data['shipping']['city'] == "")) {
                            $order_data['shipping']['city'] = "null";
                        }
                        if (($order_data['shipping']['postcode'] == "")) {
                            $order_data['shipping']['postcode'] = "null";
                        }
                        if (($order_data['shipping']['country'] == "")) {
                            $order_data['shipping']['country'] = "null";
                        }

                        $order_array = array(
                        'priceCurrency' => $order_data['currency'],
                        'id' => $order_data['id'],
                        'customer' => array(
                        'givenName'  => $order_data['billing']['first_name'],
                        'alternateName' => "kumar",
                        'lastName' => $order_data['billing']['last_name'],
                        'email' => $order_data['billing']['email'],
                        'phone' => $order_data['billing']['phone'],
                        'billingAddress' => array(
                        'address1' => $order_data['billing']['address_1'],
                        'address2' => $order_data['billing']['address_2'],
                        'locality' => $order_data['billing']['city'],
                        'postalCode' => $order_data['billing']['postcode'],
                        'region' => $order_data['billing']['country'],
                        ),
                        'shippingAddress' => array(
                        'address1' => $order_data['shipping']['address_1'],
                        'address2' => $order_data['shipping']['address_2'],
                        'locality' =>  $order_data['shipping']['city'],
                        'postalCode' => $order_data['shipping']['postcode'],
                        'region' => $order_data['shipping']['country'],
                        ),
                        ),
                        'purchaseDate' => $order_date,
                        'totalPrice' =>  (float) ($order->get_total()) * 100,
                        'poNumber' => $order_id,
                        'orderItem' => $orderItem
                        );

                        $orderList = json_encode($order_array);
                        // print_r($orderList);die;

                        if (!empty($appConfig)) {
                            $apiKey = $appConfig[0]->api_key;
                            $storeId = $appConfig[0]->store_id;

                            $base_url = Permaplate::API_BASE_URL;
                            $url = $base_url . 'orders/outlet/' . $storeId;

                            $args = array(
                            'headers' => array(
                            'Content-Type' => 'application/json',
                            'X-QUICKCOVER-API-KEY' => $apiKey
                            ),
                            'body' => $orderList,
                            'method'    => 'POST',
                            'timeout'     => 300

                            );

                            $response = wp_remote_post($url, $args);

                            $response_status = wp_remote_retrieve_response_code($response);

                            $response = wp_remote_retrieve_body($response);
                           $orderData = json_decode($response);

                            plugin_log(['contract_status' => $response_status, 'response' => $response, 'request' => $orderList]);

                            if ($response_status == 201) {
                                // echo '<pre>'; print_r($response_status); die('hi');
                                if (isset($orderData->contracts)) {
                                    $contractData = $orderData->contracts;

                                    foreach ($contractData as $key => $value) {

                                          $currentID = get_current_user_id();
                                          $contrats = $this->wpdb->query(
                                            $this->wpdb->prepare(
                                                "INSERT INTO quickcover_contract( user_id, product_id, order_id, lineitem_id, quantity, contract_id, contract_status, refund_amount, contract_canceled_by)
             VALUES ( %d, %d, %s, %s, %d, %s, %s, %s, %s)", $currentID, $value->order->product->id, $value->order->id, $value->order->orderItemId, $value->order->product->quantity, $value->id, 0, 0, 0
                                            )
                                        );

                          plugin_log(['status' => $response_status, 'response' => $response, 'request' => $orderList]);
                                    }
                                }
                            } else {

                                $to = 'asoanker@afterinc.com';
                                $subject = 'QuickCover Create Contract API Failure : After Product Protection Plan';
                                
                                $headers = array('Content-Type: text/html; charset=UTF-8', 'From: noreply@quickcover.me');
                                $message = "<html>
								  <body>
									<h2>QuickCover Server Failure</h2>
									<h4>Outlet Id: $storeId </h4></br>
									<h5>Payload</h5>
									<p>$orderList</p>
									</br>
									<h5>Response</h5>
									 <p>['status'=>$response_status,'response'=>$response]</p>
								</body>
							   </html>";

                                wp_mail($to, $subject, $message, $headers);
                                // date_default_timezone_set('America/Bogota');
                                $dataCron = $this->wpdb->insert(
                                    'quickcover_cron_status', array(
                                    'order_id' => $order->get_id(),
                                    'status' => 404,
                                    'counter' => 0,
                                    'created_at' => date('Y-m-d H:i:s')
                                    )
                                );
                         plugin_log(['status' => $response_status, 'response' => $response, 'request' => $orderList]);
                            }
                        }
                    }
                }
        }

   //contract cancel from customer end.
  function woocommerce_order_item_name( $name, $item ){
	  
	  $get_configuration_property = $this->wpdb->get_results( $this->wpdb->prepare("SELECT * FROM app_configurations"));
      $quickCover_status = $get_configuration_property[0]->configuration_property;

    $planname = $this->wpdb->get_results( $this->wpdb->prepare("SELECT * FROM product_warranty"));  
	 if(!empty($planname)){
    $pnamecustomer = $planname[0]->pname;
 
    $customUrl = esc_url_raw($_SERVER['REQUEST_URI']);
    $customUrl = strtolower($customUrl);
    $isOrderreceived = strpos($customUrl, 'order-received');
    $isOrderReview = strpos($customUrl, 'view-order');
    $productOrderId = $item['order_id'];
    $order = wc_get_order( $productOrderId );
    $order_status = $order->get_status();
     
    $lineItemId = $item->get_id(); 
    $item_quantity  = $item->get_quantity();
    $product_id = $item['product_id'];
    $item_warrantys = $item->get_meta_data();
   foreach ($item_warrantys as $key => $item_warranty) {
    if(isset($item_warranty->key) && ($item_warranty->key == '_warranty')){ 
      $plan_id = $item_warranty->value['plan_id'];
      if($plan_id !== 'none') {

      // $term = $item_warranty->value['term'];
	   $description = $item_warranty->value['description'];
       $result = $this->wpdb->get_results( $this->wpdb->prepare( "SELECT * FROM quickcover_contract WHERE order_id = %s AND lineitem_id = %s", $productOrderId, $lineItemId ));
	   
	    if($quickCover_status == 1 ){

        if(isset($result[0]->contract_status) && $result[0]->contract_status == '200' && $result[0]->refund_amount !=''){

          $refundtxt = "<b>Cancelled By: <b>".$result[0]->contract_canceled_by."<br> Contract has been Cancelled Successfully. Refund ";
          $refundamtqty = $result[0]->refund_amount * $item['quantity'];
         $refundamt = number_format((float)$refundamtqty, 2, '.', '');

         $cancelButton = "<button class='btn' data-orderId=".$productOrderId." data-lineItemID =".$lineItemId." disabled=true style='cursor: not-allowed; pointer-events: none; color: #a7aaad; padding: 5px 10px; font-size: 13px; border: 1px solid #dcdcde; background: #f6f7f7; font-family: sans-serif;'> Cancel ".$pnamecustomer." </button>";

           if (($isOrderreceived !== false) && ($isOrderreceived > 0)) {
               return $name." ".$description;  
            }else{
               return $name . " " . $description . " " . $cancelButton . " <br> <span class='contract-refund-text'>" . $refundtxt . $this->get_currency_symbol() . $refundamt . "</span>"; 
            }

      } else if(isset($result[0]->contract_status) && $result[0]->contract_status == '250' && $result[0]->refund_amount !=''){

         $refundtxt = "Cancel Request outside the cancel period,Please contract Administrator";
         $cancelButton = "<button class='btn' data-orderId=".$productOrderId." data-lineItemID =".$lineItemId." disabled=true style='cursor: not-allowed; pointer-events: none; color: #a7aaad; padding: 5px 10px; font-size: 13px; border: 1px solid #dcdcde; background: #f6f7f7; font-family: sans-serif;'> Cancel ".$pnamecustomer." </button>";

            if (($isOrderreceived !== false) && ($isOrderreceived > 0)) {
                return $name." ".$description;   
            }else{
               return $name." ".$description." ".$cancelButton." <br> ".$refundtxt; 
            }
      } else {
    
            
            $results = $this->wpdb->get_results( $this->wpdb->prepare( "SELECT * FROM quickcover_contract") );
           
            $order_id = array();
            foreach ($results as $key => $value) { 
             $order_id[] = $value->order_id;
           }

           $orderIDs = array_unique($order_id);
            
           if (in_array($productOrderId, $orderIDs)){

            $cancelButton = "<button class='cancelContract' id=".$item_quantity." style='display: block; padding: 5px 10px; font-size: 13px; border: none; background: #009688; font-family: sans-serif;' data-orderId=".$productOrderId." data-lineItemID=".$lineItemId." data-cancelby='customer'> Cancel ".$pnamecustomer."</button>";

             if (($isOrderreceived !== false) && ($isOrderreceived > 0)) {
                  return $name." ".$description;  
              }else{
                  return $name." ".$description." ". $cancelButton;  
              }
           } else{

             $cancelButton = "<button class='btn' data-orderId=".$productOrderId." data-lineItemID =".$lineItemId." disabled=true style='cursor: not-allowed; pointer-events: none; color: #a7aaad; padding: 5px 10px; font-size: 13px; border: 1px solid #dcdcde; background: #009688; font-family: sans-serif;'> Due to some technical issue your contract not created yet.<br>please wait for some time. </button>";

              if (($isOrderreceived !== false) && ($isOrderreceived > 0)) {
                  return $name." ".$description;  
                }else if(($isOrderReview !== false) && ($isOrderReview > 0)){
                  return $name." ".$description." ". $cancelButton; 
              }else{
                return $name." ".$description;
              }
            }
         }  
       }
     }
   }
 }
  return $name ; 
  }  
}
  /*Contract cancel from admin End */
  function cancel_whole_contract_from_admin( $order_id ) {
   $order = new WC_Order( $order_id ); 
   $noRefundLimit = 24 * 60; //in minutes until booking
   $user_id = get_current_user_id();
   $result = $this->wpdb->get_results( $this->wpdb->prepare( "SELECT * FROM quickcover_contract WHERE order_id = %d", $order_id ));
   
   if(!empty($result)){
   foreach ( $result as $res => $values ){
    $contractID = $values->contract_id;
    $admin_cancel_contract = array(
    'action' => 'Cancel',
    'contractId' => $contractID,
    'orderId' => $values->order_id,
    'orderItemId' => $values->lineitem_id,
    'quantity' => 1,

     );
      $admin_cancel_contract = json_encode($admin_cancel_contract);
     $result = $this->wpdb->get_results( $this->wpdb->prepare( "SELECT * FROM app_configurations WHERE id = %d", 1));

        if(!empty($result)){
        $apiKey = $result[0]->api_key;
        $storeId = $result[0]->store_id;  

	   $base_url = Permaplate::API_BASE_URL;
       $url = $base_url.'contracts/outlet/'.$storeId.'/cancel';

      $args = array(
          'headers' => array(
            'Content-Type' => 'application/json',
            'X-QUICKCOVER-API-KEY' => $apiKey
          ),
          'body' => $admin_cancel_contract,
          'method'      => 'POST',
          'timeout'     => 300
        );

       $response = wp_remote_post( $url, $args );
       $response_status = wp_remote_retrieve_response_code($response);
       $response = wp_remote_retrieve_body( $response );

       if($response_status == 200) {
        $admin_contract_responce = json_decode($response,TRUE);
           
            $rfamount = ($admin_contract_responce['contracts'][0]['refundAmount']/100);
           $contractUpdate = $this->wpdb->query( $this->wpdb->prepare( "UPDATE quickcover_contract SET contract_status = %s, refund_amount = %s, contract_canceled_by = %s WHERE contract_id = %s", 200, $rfamount, 'Admin', $contractID ));
           
         }
         else if($response_status == 250){
            
         $contractUpdate = $this->wpdb->query( $this->wpdb->prepare( "UPDATE quickcover_contract SET contract_status = %s WHERE contract_id = %s", $response_status, $contractID));
            
            }else{
               echo json_encode(array("status"=>404));
            }

      $this->table_name = $this->wpdb->prefix . 'posts';
     $querystr = "
     SELECT $this->table_name.* 
     FROM $this->table_name
     WHERE $this->table_name.post_parent = $order_id";

    $pageposts = $this->wpdb->get_results($querystr, OBJECT);
    if(!empty($pageposts)) {
      $bookingId = current($pageposts)->ID;
      $bookingStart = current(get_post_meta($bookingId, "_booking_start"));
      $time = (new DateTime($bookingStart, new 
      DateTimeZone("America/Los_Angeles")))->getTimestamp();
      $nowTime = (new DateTime())->getTimestamp();
      $difference = round(($time - $nowTime)/60);//in minutes
      if($difference >= $noRefundLimit) {
          $refundPercentage = 1; //how much will we give back? fraction of 1.
          // Get Items
          $order_items   = $order->get_items();
          $refund_amount = 0;
          $line_items = array();

          if ( $order_items ) {
          foreach( $order_items as $item_id => $item ) {
          $refund_amount += $item->get_total();
          }
          }
          $refund_amount = ($refund_amount * $refundPercentage);
          $refund_reason = "Order Cancelled";
          $refund = wc_create_refund( array(
          'amount'         => $refund_amount,
          'reason'         => $refund_reason,
          'order_id'       => $order_id,
          'line_items'     => $line_items,
          'refund_payment' => true
          ));

          $order->update_status('wc-refunded', 'Order Cancelled And Completely 
          Refunded');
         }
        } 
      }
     }
    }
  }
  
   function add_loader_in_admin_order_detail_page($orderID) {
	echo '<div class="admin-loader-div" style="display: none;"><img src='.esc_url(plugin_dir_url( __FILE__ )).'assets/images/loader.gif id="loaderImg" alt="loader" height="40" width=""></div>';
  }
  
   /*add button order details page in admin side and cancel order functionality */
function woocommerce_before_order_itemmeta($item_id, $item, $product){
	
	$planname = $this->wpdb->get_results( $this->wpdb->prepare("SELECT * FROM product_warranty"));  
	 if(!empty($planname)){
    $pnameadmin = $planname[0]->pname;
    $productOrderId = $item['order_id'];
    $order = wc_get_order( $productOrderId );
    $order_status = $order->get_status();
    $lineItemId = $item->get_id();
    $item_quantity  = $item->get_quantity();
    $product_id = $item['product_id'];
    $item_warrantys = $item->get_meta_data();
		 foreach ($item_warrantys as $key => $item_warranty) {
     if(isset($item_warranty->key) && ($item_warranty->key == '_warranty')){ 
      $plan_id = $item_warranty->value['plan_id'];
     if($plan_id !== 'none') {
  ?>
   <style type="text/css">
    .display_meta{
      display: none;
    } 
   </style>
     <?php
        
        //$term = $item_warranty->value['term'];
		$description = $item_warranty->value['description'];

        echo "<b>".esc_attr($description)."</b>";

        $results = $this->wpdb->get_results( $this->wpdb->prepare( "SELECT * FROM quickcover_contract WHERE order_id = %s and lineitem_id = %s", $productOrderId, $lineItemId ));

     if(isset($results[0]->contract_status) && $results[0]->contract_status == '200' && $results[0]->refund_amount !=''){
      $refundamtqty = $results[0]->refund_amount * $item['quantity'];
      $refundamt = number_format((float)$refundamtqty, 2, '.', '');
      echo '<p><button type="button" class="btn button-primary" data-orderId="'.esc_attr($productOrderId).'" data-lineItemID ="'.esc_attr($lineItemId).'" disabled=true style="cursor: not-allowed;">Cancelled '.esc_attr($pnameadmin).'</button></p><b>Cancel By : '.esc_attr($results[0]->contract_canceled_by).'<b> <br>Contract has been cancelled successfully. Please initiate a Refund of '. $this->get_currency_symbol() . esc_attr($refundamt).' to the customer.';
     }else if(isset($results[0]->contract_status) && $results[0]->contract_status == '250' && $results[0]->refund_amount !=''){
      echo '<p><button type="button" class="btn button-primary" data-orderId="'.esc_attr($productOrderId).'" data-lineItemID ="'.esc_attr($lineItemId).'" disabled=true style="cursor: not-allowed;">Cancelled '.esc_attr($pnameadmin).'</button></p><b><br>Cancel Request outside the cancel period,Please contract Administrator'; 
       }
    
    else {
    $order_id = array();
    $results = $this->wpdb->get_results( $this->wpdb->prepare( "SELECT * FROM quickcover_contract" ));
    
    foreach ($results as $key => $value) { 
     $order_id[] = $value->order_id;
     }
    if (in_array($productOrderId, $order_id)){
		
		 $get_configuration_property = $this->wpdb->get_results($this->wpdb->prepare("SELECT * FROM app_configurations"));
         $quickCover_status = $get_configuration_property[0]->configuration_property;
		  if ($quickCover_status == 1) {
       echo '<p><button type="button" class="btn button-primary cancelContract" style ="background: #009688; color: white;" data-orderId="'.esc_attr($productOrderId).'" data-lineItemID ="'.esc_attr($lineItemId).'" id ="'.esc_attr($item_quantity).'" data-cancelby=admin> Cancel '.esc_attr($pnameadmin).'</button></p>';
		 }
      }
           else{
              echo '<p><button type="button" class="btn button-primary" data-orderId="'.esc_attr($productOrderId).'" data-lineItemID ="'.esc_attr($lineItemId).'" disabled=true style="cursor: not-allowed;">Due to some technical issue your contract not created yet.<br>please wait for some time.</button></p>';
           }
         } 
        }
      }   
    }
  }
 }
  
   /*add javascript in customer side */
    function view_order_and_thankyou_page($order_id) {
      echo '<div class="admin-loader-div" style="display: none;">'."<img src=".esc_url(plugin_dir_url( __FILE__ ))."assets/images/loader.gif id='loaderImg'>".'<div>';
   }
   
    /* bulk warranty edit function*/
     function warranty_bulk_edit_input() {
        ?>
        <div class="inline-edit-group">
          <label class="alignleft">
             <span class="title"><?php _e( 'Eligible for QuickCover', 'woocommerce' ); ?></span>
             <span class="input-text-wrap">
                <select class="_warranty" name="_warranty">
                <option value="">— No change —</option>
                <option value="Enable">Enable</option>
                <option value="Disable">Disable</option>
                </select>
             </span>
            </label>
        </div>
        <?php
    }
	
	
	/* bulk warranty save function*/
    function warranty_bulk_edit_save( $product ) {
       $post_id = $product->get_id();    
      if ( isset( $_REQUEST['_warranty'] ) && $_REQUEST['_warranty'] !='' ) {
        $warranty_value = sanitize_text_field($_REQUEST['_warranty']);
          update_post_meta( $post_id, '_warranty',$warranty_value);
          
      }
  }
  
  /* bulk edit sync function*/
  function sync_bulk_edit_input() {
    ?>
       <div class="inline-edit-group">
        <label class="alignleft">
         <span class="title"><?php _e( 'QuickCover Sync', 'woocommerce' ); ?></span>
         <span class="input-text-wrap">
            <select class="_sync" name="_sync">
             <option value="">— No change —</option>
              <option value="Not Synced">Not Synced</option>
            </select>
         </span>
        </label>
       </div>
     <?php
  }
  
  /* bulk edit save sync function*/
  function sync_bulk_edit_save( $product ) {
      $post_id = $product->get_id();    
       if (isset( $_REQUEST['_sync']) && $_REQUEST['_sync'] == 'Not Synced') {
        $sync_value = sanitize_text_field($_REQUEST['_sync']);
        update_post_meta( $post_id, '_sync', wc_clean( $sync_value ) );
    }
  }
  
  
  /* custom filter for warranty*/
   function warranty_product_filters( $post_type ) {

// Check this is the products screen
  if( $post_type == 'product' ) {

  $Getwarranty_selected = sanitize_text_field(isset($_GET['warranty'])) ? $_GET['warranty'] : "";
  $selectedYes=($Getwarranty_selected=='Enable')?'Selected':'';
  $selectedNo=($Getwarranty_selected=='Disable')?'Selected':'';
  // Add your filter input here. Make sure the input name matches the $_GET value you are checking above.
    echo '<select name="warranty">';
    echo '<option value>Eligible for QuickCover</option>';
    echo '<option value="Enable" '.$selectedYes.'>Enable</option>';
    echo '<option value="Disable" '.$selectedNo.'>Disable</option>';

    echo '</select>';

      }

   }
   
   /* custom filter for sync*/

   function sync_product_filters( $post_type ) {

// Check this is the products screen
    if( $post_type == 'product' ) {

   $Getsync_selected = sanitize_text_field(isset($_GET['sync'])) ? $_GET['sync'] : "";

    $selectedYes =($Getsync_selected=='Synced')?'Selected':'';
     $selectedNo =($Getsync_selected=='Not Synced')?'Selected':'';
	 $selectedF =($Getsync_selected=='Failed')?'Selected':'';
	 $selectedNew =($Getsync_selected=='New')?'Selected':'';
  // Add your filter input here. Make sure the input name matches the $_GET value you are checking above.
    echo '<select name="sync">';

    echo '<option value>QuickCover Sync</option>';
    echo '<option value="Synced" '.$selectedYes.'>Synced</option>';
    echo '<option value="Not Synced" '.$selectedNo.'>Not Synced</option>';
	echo '<option value="Failed" '.$selectedF.'>Failed</option>';
	echo '<option value="New" '.$selectedNew.'>New</option>';

    echo '</select>';

    }

  }
  
   /* apply filter warranty and sync function*/
 function apply_warranty_product_filters( $query ) {

   global $pagenow;
// Ensure it is an edit.php admin page, the filter exists and has a value, and that it's the products page
 if ( $query->is_admin && $pagenow == 'edit.php' && isset( $_GET['warranty'] ) && $_GET['warranty'] != '' && $_GET['sync'] == '' && $_GET['post_type'] == 'product' ) {
  // Create meta query array and add to WP_Query
    $meta_key_query = array(
    array(
      'key'     => '_warranty',
      'value'   => sanitize_text_field($_GET['warranty']),
    )
  );

    $query->set( 'meta_query', $meta_key_query );
    
 }

  elseif( $query->is_admin && $pagenow == 'edit.php' && isset( $_GET['sync'] ) && $_GET['sync'] != '' && $_GET['warranty'] == '' && $_GET['post_type'] == 'product'){
     $meta_key_query = array(
      array(
        'key'     => '_sync',
        'value'   => sanitize_text_field($_GET['sync']),
       )
    );
      $query->set( 'meta_query', $meta_key_query ); 
   }

  elseif($query->is_admin && $pagenow == 'edit.php' && isset( $_GET['sync'] ) && $_GET['sync'] != '' && $_GET['warranty'] != '' && $_GET['post_type'] == 'product'){
    $meta_key_query = array(
    array(
      'key'     => '_warranty',
      'value'   => sanitize_text_field($_GET['warranty']),
    ),
     array(
        'key'     => '_sync',
        'value'   => sanitize_text_field( $_GET['sync'] ),
      )
  );


     $query->set( 'meta_query', $meta_key_query );
   }
 }
 
 /**
 * Adds "Import" button on module list page
 */

 public function addCustomImportButton()
{
    ?>
         <script type="text/javascript">
            jQuery(document).ready( function(jquery)
            {
                jQuery(".wrap h1").append("<a id='doc_popup' class='add-new-h2'href='<?php echo site_url('wp-admin/edit.php?post_type=product') ?>'>Reset</a>");
            });
        </script>
    <?php
  }
  
   function my_phpmailer_afterinc($phpmailer){
                $phpmailer->isSMTP();
                $phpmailer->Host = 'email-smtp.us-east-1.amazonaws.com';
                $phpmailer->SMTPAuth = true; // Ask it to use authenticate using the Username and Password properties
                $phpmailer->Port = 587;
                $phpmailer->Username = 'AKIAUEBI6KITMPVFRS5M';
                $phpmailer->Password = 'BKM3nw0o2nEKhMrKbOXsBL8ReD3ZRabjSkvqHfQJOKo/';
                // Additional settings…
                $phpmailer->SMTPSecure = 'tls'; // Choose 'ssl' for SMTPS on port 465, or 'tls' for SMTP+STARTTLS on port 25 or 587
                $phpmailer->From = "noreply@quickcover.me";
        }
		
  function save_custom_bulk_edit_data($post_id) {
		
    if($_REQUEST['action'] === 'edit'){	
	
     $appConfig = $this->wpdb->get_results($this->wpdb->prepare("SELECT * FROM app_configurations WHERE id = %d", 1));
	
      $productsbulk_data = array();
      global $woocommerce,$product; 
       //$getselected_data = $_REQUEST['post'];
        $all_cat = $_GET['tax_input']['product_cat'];
        $cat_name = [];
        foreach ($all_cat as $key => $valueid) {
             $allterm = get_term_by( 'id', $valueid, 'product_cat');
             if(isset($allterm->name)){
                 $cat_name[] = trim($allterm->name);
             }    
        }
		 
      if(!empty($appConfig)){

       $api_key = $appConfig[0]->api_key;
       $quick_store_id = $appConfig[0]->store_id;
       $autosync_status = $appConfig[0]->sync_type;

		   if($autosync_status == 1){
		   global $woocommerce,$product; 

		   $productsbulk_data = array();
		   $getselected_data = $_REQUEST['post'];

      foreach ($_REQUEST['post'] as $product_id) {

        $product_list = wc_get_product($product_id);
        $priceCurrency = get_option('woocommerce_currency');
        $name = $product_list->get_name();
        $pricebulk = ($product_list->get_price() != '') ? $product_list->get_price() : '';
        $sku = ($product_list->get_sku() != '') ? $product_list->get_sku() : $product_id;
        $description = ($product_list->get_description() != '') ? $product_list->get_description() : '';
        $model = ($product_list->get_sku() != '') ? $product_list->get_sku() : $product_id;
        $category = ($product_list->get_categories() != '') ? trim($product_list->get_categories()) : '';
        $category = strip_tags($category);

        if ($category == "uncategorized") {
                $category = "";
       }

        $category_into_array= explode(",",$category);
        $category_into_array=array_map('trim',$category_into_array);
        $final_array = array_merge($category_into_array,$cat_name);
        $new_final_array  = array_unique($final_array);
        $productsbulk_data['priceCurrency'] = $priceCurrency;
        $productsbulk_data['id'] = $product_id;
        $productsbulk_data['category'] = implode(',',$new_final_array);
        $productsbulk_data['description'] = $description;
        $productsbulk_data['model'] = $model;
        $productsbulk_data['price'] = (100*(float)$_GET['_regular_price']) ? (100*(float)$_GET['_regular_price']) : 100*($pricebulk);
        $productsbulk_data['name'] = $name;
        $productsbulk_data['sku'] = $sku;
        $data[] = $productsbulk_data;

        }
      
			$productbulk_array['data'] = $data;
			$productbulk_Json = json_encode($productbulk_array);
			$base_url = Permaplate::API_BASE_URL;
			$url = $base_url . 'products/batch/outlet/' . $quick_store_id . '?upsert=true';

        $body = array(
				'headers' => array(
					'Content-Type' => 'application/json',
					'X-QUICKCOVER-API-KEY' => $api_key
                ),
					'body' => $productbulk_Json,
					'method'      => 'POST',
					'timeout' => 400
                );
				
                $response = wp_remote_post($url, $body);
			
                $response_status = wp_remote_retrieve_response_code($response);
                $response = wp_remote_retrieve_body($response);
                $table_name = $this->wpdb->prefix . 'postmeta';
				
             if($response_status == 202){
                foreach ($productbulk_array['data'] as $postid){
                $updatedPost[] =$postid['id'];
                update_post_meta((int)$postid['id'], '_sync', 'Synced');   
                } 
                  plugin_log(['status' => $response_status, 'response' => $response, 'request' => $productbulk_Json]);
                } 

                 else
                 {
                    foreach ($productbulk_array['data'] as $postid ){
                    
                     update_post_meta((int)$postid['id'], '_sync', 'Failed'); 
                    }
                   plugin_log(['status' => $response_status, 'response' => $response, 'request' => $productbulk_Json]);
                }
          } else{
			   $getselected_data = $_REQUEST['post'];
             
                foreach ($getselected_data as $postid ){
                 
                 update_post_meta((int)$postid, '_sync', 'Not Synced'); 
                 }
				
                plugin_log(['status' => $response_status, 'response' => $response, 'request' => $productbulk_Json]);
           }    
        }
     }
  }
	
		
     //cron code start here
            function order_scheduler_cron(){
				
                plugin_log("This is testing for cron job");
                ini_set('max_execution_time', '300');
                $appConfig = $this->wpdb->get_results($this->wpdb->prepare("SELECT * FROM app_configurations WHERE id = %d", 1));
                $cronStatus = $this->wpdb->get_results("SELECT * FROM quickcover_cron_status WHERE status = 404 ORDER BY order_id DESC ");
                if (!empty($cronStatus)) {

                    foreach ($cronStatus as $key => $value) {

                        $order_id = $value->order_id;
                        $order = wc_get_order($order_id);
                        $email = $order->get_billing_email();
                        $fname = $order->get_billing_first_name();
                        // $customername = $order->billing_first_name() .' '.$order->billing_last_name();

                        if (!empty($order)) {
                            $order_no =   $order->get_id();
                            $order_data = $order->get_data();
                            $order_items = $order->get_items();
                            $srlNo = count($order_items);
                            $order_date = $order->get_date_created()->format('Y-m-d H:i:s');
                            $order_status  = $order->get_status();

                            if ($order_status !== 'cancelled') {
                                  $orderItem = array();
                                  $in_warranty = array();
                                  $planNameArr = array();
                                  $planTitle = null;
                                foreach ($order_items as $key => $item) {
                                    $lineitem_id = $item->get_id();
                                    $productID = $item->get_product_id();

                                    $result = $this->wpdb->get_results($this->wpdb->prepare("SELECT * FROM product_warranty WHERE product_id = %d ", $productID));

                                    if (!empty($result)) {
                                          $planTitle = $result[0]->pname;
                                          $planDatas = $item->get_meta_data();
                                          $planName = '';
                                          $planPrice = '';
                                        foreach ($planDatas as $key => $planId) {
                                            if (isset($planId->key) && ($planId->key == '_warranty')) {
                                                    $plan_id = $planId->value['plan_id'];
                                                if ($plan_id !== 'none') {
                                                    $in_warranty[] = $result[0]->product_id;
                                                    $plans = json_decode($result[0]->plans, true);


                                                    foreach ($plans as $key => $plan) {
                                                        if ($plan['planId'] == $plan_id) {
                                                            $contract = array(
                                                            'priceCurrency' => $result[0]->priceCurrency,
                                                            'planId' => $plan['planId'],
                                                            'price' => $plan['price'],
                                                            'quantity' => $item->get_quantity(),
                                                            );
                                                            $planName = $plan['description'];
                                                            $planPrice = $plan['price'] * $item->get_quantity();
                                                        }
                                                    }

                                                    $orderItem[] = array(
                                                    'id' => $lineitem_id,
                                                    'product' => array(
                                                    'priceCurrency' => $result[0]->priceCurrency,
                                                    'id' => $item->get_product_id(),
                                                    'contract' => $contract,
                                                    'purchasePrice' => (float) ($item->get_product()->get_price()) * 100,
                                                    'quantity' => $item->get_quantity(),
                                                    'serialNumber' => $srlNo
                                                    )
                                                    );
                                                }
                                            }
                                        }
                                        $planNameArr[] = $planName;
                                        $planpriceArr[] = $planPrice;
                                    }
                                }

                                if (!empty($in_warranty)) {

                                    if (($order_data['billing']['address_1'] == "")) {
                                        $order_data['billing']['address_1'] = "null";
                                    }
                                    if (($order_data['billing']['address_2'] == "")) {
                                        $order_data['billing']['address_2'] = "null";
                                    }
                                    if (($order_data['billing']['city'] == "")) {
                                        $order_data['billing']['city'] = "null";
                                    }
                                    if (($order_data['billing']['postcode'] == "")) {
                                        $order_data['billing']['postcode'] = "null";
                                    }
                                    if (($order_data['billing']['country'] == "")) {
                                        $order_data['billing']['country'] = "null";
                                    }

                                    if (($order_data['shipping']['address_1'] == "")) {
                                        $order_data['shipping']['address_1'] = "null";
                                    }
                                    if (($order_data['shipping']['address_2'] == "")) {
                                        $order_data['shipping']['address_2'] = "null";
                                    }
                                    if (($order_data['shipping']['city'] == "")) {
                                        $order_data['shipping']['city'] = "null";
                                    }
                                    if (($order_data['shipping']['postcode'] == "")) {
                                        $order_data['shipping']['postcode'] = "null";
                                    }
                                    if (($order_data['shipping']['country'] == "")) {
                                        $order_data['shipping']['country'] = "null";
                                    }

                                    $order_array = array(
                                    'priceCurrency' => $order_data['currency'],
                                    'id' => $order_data['id'],
                                    'customer' => array(
                                    'givenName'  => $order_data['billing']['first_name'],
                                    'alternateName' => "kumar",
                                    'lastName' => $order_data['billing']['last_name'],
                                    'email' => $order_data['billing']['email'],
                                    'phone' => $order_data['billing']['phone'],
                                    'billingAddress' => array(
                                    'address1' => $order_data['billing']['address_1'],
                                    'address2' => $order_data['billing']['address_2'],
                                    'locality' => $order_data['billing']['city'],
                                    'postalCode' => $order_data['billing']['postcode'],
                                    'region' => $order_data['billing']['country'],
                                    ),
                                    'shippingAddress' => array(
                                    'address1' => $order_data['shipping']['address_1'],
                                    'address2' => $order_data['shipping']['address_2'],
                                    'locality' =>  $order_data['shipping']['city'],
                                    'postalCode' => $order_data['shipping']['postcode'],
                                    'region' => $order_data['shipping']['country'],
                                    ),
                                    ),
                                    'purchaseDate' => $order_date,
                                    'totalPrice' =>  (float) ($order->get_total()) * 100,
                                    'poNumber' => $order_id,
                                    'orderItem' => $orderItem
                                    );

                                    $orderList = json_encode($order_array);

                                    if (!empty($appConfig)) {
                                        $apiKey = $appConfig[0]->api_key;
                                        $storeId = $appConfig[0]->store_id;
                                        $toemail = $appConfig[0]->email;

                                        $currentadmin_login = $appConfig[0]->user_id;

                                         $tablename = $this->wpdb->prefix . 'users';

                                        $get_admindetails = $this->wpdb->get_results($this->wpdb->prepare("SELECT user_login FROM $tablename WHERE ID = %s ", $currentadmin_login));
                                         $merchant_login=$get_admindetails[0]->user_login;

                                        $base_url = Permaplate::API_BASE_URL;
                                        $url = $base_url . 'orders/outlet/' . $storeId;

                                        $args = array(
                                        'headers' => array(
                                        'Content-Type' => 'application/json',
                                        'X-QUICKCOVER-API-KEY' => $apiKey
                                        ),
                                        'body' => $orderList,
                                        'method'    => 'POST',
                                        'timeout'     => 300
                                        );

                                        $response = wp_remote_post($url, $args);
                                        $response_status = wp_remote_retrieve_response_code($response);

                                        $response = wp_remote_retrieve_body($response);
                                        $orderData = json_decode($response);

                                        plugin_log(['contract_status' => $response_status, 'response' => $response, 'request' => $orderList]);

                                        if ($response_status == 201) {
                                            plugin_log('resolved');
                                            if (isset($orderData->contracts)) {
                                                $contractData = $orderData->contracts;

                                                foreach ($contractData as $key => $value) {

                                                    $currentID = get_current_user_id();
                                                    $contrats = $this->wpdb->query(
                                                        $this->wpdb->prepare(
                                                            "INSERT INTO quickcover_contract( user_id, product_id, order_id, lineitem_id, quantity, contract_id, contract_status, refund_amount, contract_canceled_by)
                     VALUES ( '%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s')", $currentID, $value->order->product->id, $value->order->id, $value->order->orderItemId, $value->order->product->quantity, $value->id, 0, 0, 0
                                                        )
                                                    );
                                                }
                                            }
                                            $remove = $this->wpdb->query(
                                                $this->wpdb->prepare("DELETE FROM quickcover_cron_status WHERE order_id = %d", $order_id)
                                            );
                                            plugin_log(['contract_status' => $response_status, 'response' => $response, 'request' => $orderList]);
                                             $to = 'asoanker@afterinc.com';
                                            $subject = 'QuickCover Create Contract API Success : After Product Protection Plan';
                                            // $headers = 'From: admin <kishorchetu123@gmail.com>' . "\r\n";  
                                            $headers = array('Content-Type: text/html; charset=UTF-8', 'From: noreply@quickcover.me');
                                            $message = "<html>
                                           <body>
                                            <h2>QuickCover Server Resolved</h2>
                                            <h4>Outlet Id: $storeId </h4></br>
                                            <h5>Payload</h5>
                                            <p>$orderList</p>
                                            </br>
                                            <h5>Response</h5>
                                           <p>['status'=>$response_status,'response'=>$response]</p>
                                         </body></html>";

                                            wp_mail($to, $subject, $message, $headers);
                                        } else {

                                            $this->wpdb->query($this->wpdb->prepare("UPDATE quickcover_cron_status SET `counter`=(`counter`+1) WHERE `order_id` = " . $order_no . ""));

                                           $increment_ord = $this->wpdb->get_results($this->wpdb->prepare("SELECT counter FROM quickcover_cron_status WHERE order_id = %d", $order_no));

                                            $planName = implode(",", $planNameArr);
                                            $totalplan = array_sum($planpriceArr) / 100;
                                             $siteTitleName = get_bloginfo('name');
                                            
                                            if ($increment_ord[0]->counter > 11) {
                                                plugin_log('checking if part for final:');
                                                //$to = 'testvariable@yopmail.com';
                                                 $to = $toemail;
                                                 $subject = 'After Product Protection Plan BC Failure Action Required!';
                                                
                                                $headers = array(
                                                'Content-Type: text/html; charset=UTF-8', 'From: noreply@quickcover.me',
                                                'CC: asoanker@afterinc.com'
                                                );
                                      $message = '<html>
                            <head>
                        <meta charset="UTF-8">
                       <meta http-equiv="X-UA-Compatible" content="IE=edge">
                        <meta name="viewport" content="width=device-width, initial-scale=1.0">
                      <title>eamil</title>
                <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;1,100;1,300;1,400;1,700&display=swap" rel="stylesheet">
             <style>
          *{margin: 0;}
        body{font-family: "Roboto", sans-serif; font-size: 14px;}
       </style>
      </head>
     <body>
    <table bgcolor="#d4e2ea" style="margin: 0 auto; width: 600px; background-color: #d4e2ea; padding: 25px;">
          <tr>
            <td style="text-align: center; padding: 15px 0; border-bottom: solid 1px #ccc;">
             <img src="' . esc_url(plugin_dir_url(__FILE__)) . 'assets/images/logo.png">
            </td>
          </tr>
          <tr>
             <td>
                <p style="padding-bottom: 10px;"> Hello ' . $merchant_login .'</p>
                <p>Your customer has attempted to purchase the ' . $planTitle . ' through '. $siteTitleName.'.</p>
             </td>
          </tr>
         <tr style="padding-top: 20px; margin-bottom: 10px;">
            <td>
                <p>Due to technical difficulties, we are unable to fulfill the request. Please cancel the plan order through the app and initiate a refund of $' . $totalplan . '.  <a href="https://docs.quickcover.me/woo-commerce/03-faq.html#how-do-i-cancel-plan-orders" target="_blank">Click here</a>  for instructions. 
                <br>Here are the order details:</p>
            </td>
         </tr>
          <tr>
            <td>
                <table>
                    <tr>
                        <td style="width: 124px;">Order Number: </td>
                        <td>' . $order_id . '</td>
                    </tr>
                    <tr>
                        <td>Order Date:</td>
                        <td>' . date("m/d/Y", strtotime($order_date)) . '</td>
                    </tr>
                    <tr>
                        <td>Customer Name: </td>
                        <td>' . $fname . '</td>
                    </tr>
                    <tr>
                        <td>Customer Email:</td>
                        <td><a href="mailto:' . $email . '" target="_blank">' . $email . '</a></td>
                    </tr>
                    <tr>
                        <td style="vertical-align: top;">Plan Purchased:</td>
                        <td>' . $planTitle . ": " . $planName . '</td>
                    </tr>
                </table>
            </td>
          </tr>
          <tr>
            <td style="margin-top: 10px;">
                <p>
                    If you have any questions about your account, please contact
                    <a href="mailto:jfdkslfj@jdfj.com" target="_blank">quick-techsupport@afterinc.com</a>
                      and we’ll get right back to you. <br />
                      
                     Reference:<br />
                     ' . $storeId . '<br />
                      Woocommerce
                      
                 </p>
                 
            </td>

          </tr> 
         
          <tr>
            <td>
                <p>
                    Thank you again for using QuickCover<br>
                    Sincerely,<br>
                    QuickCover Support</p>
            </td>
          </tr>
          <tr>
            <td>
                <p>
                    Copyright © 2023 All Rights Reserved | After, Inc. Powered by QuickCover®
                 </p>
                </td>
               </tr>
               </body>
               </html>';
                            wp_mail($to, $subject, $message, $headers);

                            $remove = $this->wpdb->query(
                                $this->wpdb->prepare("DELETE FROM quickcover_cron_status WHERE order_id = %d", $order_id)
                            );
                        } else {
                            plugin_log('checking else part:');
                            $to = 'asoanker@afterinc.com';
                            $subject = 'QuickCover Create Contract API Failure';
                            
                            $headers = array('Content-Type: text/html; charset=UTF-8', 'From: noreply@quickcover.me');
                            $message = "<html>
                            <body>
                                <h2>QuickCover Server Failure</h2>
                                <h4>Outlet Id: $storeId </h4></br>
                                <h5>Payload</h5>
                                <p>$orderList</p>
                                </br>
                                <h5>Response</h5>
                                <p>['status'=>$response_status,'response'=>$response]</p>
                            </body>
                             </html>";

                                   wp_mail($to, $subject, $message, $headers);

                                    plugin_log(['status' => $response_status, 'response' => $response, 'request' => $orderList]);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }


/**
 * Write an entry to a log file in the uploads directory.
 * 
 * @since x.x.x
 * 
 * @param mixed $entry String or array of the information to write to the log.
 * @param string $file Optional. The file basename for the .log file.
 * @param string $mode Optional. The type of write. See 'mode' at https://www.php.net/manual/en/function.fopen.php.
 * @return boolean|int Number of bytes written to the lof file, false otherwise.
 */
if ( ! function_exists( 'plugin_log' ) ) {
  function plugin_log( $entry, $mode = 'a', $file = 'plugin' ) { 
     $upload_dir = wp_upload_dir();
            $upload_dir = $upload_dir['basedir'];
             if (!file_exists($upload_dir . '/Log/')) {
              mkdir($upload_dir . '/Log/', 0777, true);
             }

        if (is_array($entry)) {
            $entry = json_encode($entry);
        }
        // Write the log file.

        $file  = $upload_dir . '/Log/' . $file . '-' . date('Y-m-d') . '.log';
        $file  = fopen($file, $mode);
        $bytes = fwrite($file, current_time('mysql') . "::" . $entry . "\n");
        fclose($file);
        return $bytes;
       }
    }
   global $canonWarranty;
  $canonWarranty = new Permaplate();
}

?>
