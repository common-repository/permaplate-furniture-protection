<?php

global $wpdb; 
 $result = $wpdb->get_results ( "SELECT * FROM app_configurations WHERE id = 1 ");
  if(!empty($result)){
    $backup_status = $result[0]->backup_status;
    if($backup_status != 1){

        $tableArray = [   
          "app_configurations",
          "product_warranty",
          "quickcover_contract",
          "quickcover_cron_status",
          "warranty_calc",
		   "batchsync_status",
         ];

         foreach ($tableArray as $tablename) {
           $wpdb->query("DROP TABLE IF EXISTS $tablename");
        }

        $wpdb->query("DELETE FROM `wp_postmeta` WHERE meta_key IN ('_warranty', '_sync')"); 
        delete_option('sortsearchtitle_db_version');
        
    }
	
	else{
		
		$wpdb->query('TRUNCATE TABLE product_backup');

      $query = array(
             'post_type' => 'product',
             'posts_per_page' => -1,
             'orderby' => 'id',
             'order' => 'DESC'
          );

     $wproducts = get_posts( $query );
     $backup_data = "";
     foreach ($wproducts as $key => $value) {


        $backup_data.= "(";
        $backup_data.="'".$value->ID."'";
        $backup_data.=",";
        $backup_data.="'".$value->post_modified."'";
        $backup_data.=")";
        $backup_data.=",";
     }
     $backup_vluse=rtrim($backup_data,",");
    
     $query = "INSERT INTO product_backup(product_id, modified_date) VALUES".$backup_vluse;

       $result_backup = $wpdb->query($query);

    }
}

    