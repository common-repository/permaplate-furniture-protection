
<?php

	$categories = get_terms( ['taxonomy' => 'product_cat'] );
 	
?>

<div class="prd-updt-form">
	<div class="admin-loader-div" style="display: none;"><img src="<?php echo esc_url(plugin_dir_url( __FILE__ )) ?>assets/images/loader.gif" id="loaderImg" alt="loader" height="40" width=""></div>
	<table class="batch-update">
	<h6>QuickCover® allows you to perform bulk actions.</h6>
		<tr>
			<th>Product Category</th>
			<td>
				<select class="category">
					<option value="">Select Option</option>
					<option value="All">All</option>
					<?php
					  foreach ($categories as $key => $category) {
					 ?>
					  	<option value="<?php echo $category->term_id ?>"><?php echo $category->name." (".$category->count.")" ?></option>
					 <?php
					  }
					?>
				</select>
			</td>
		</tr>
		
		<tr>
			<td></td>
			<td>Select the category to sync or unsync.</td>
		</tr>
		<tr>
			<th>Eligible for QuickCover®</th>
			<td>
				<select class="is_warranty">
					<option value="">Select Option</option>
					<option value="Enable">Enable</option>
					<option value="Disable">Disable</option>
				</select>
			</td>
		</tr>
		<tr>
		<td></td>
		
		<td>Select enable to apply QuickCover® eligibility to the category you selected above. Select disable to remove eligibility.</td>	
			
		</tr>
		
		<tr>
			<th>QuickCover® Sync</th>
			<td>
				<select class="is_sync">
					<option value="">Select Option</option>
					<option value="Not Synced">Not Synced</option>
				</select>
			</td>
		</tr>
		<tr>
		<td></td>
		
		<td>Select Not Synced to desync from QuickCover®.</td>	
			
		</tr>
		<tr>
			<td colspan="3" class="txt-ctr">
			 <button type="button" id="product-update" name="button">Update Products</button>
			</td>
		</tr>
		<tr>
			<td colspan="3" class="txt-ctr"><a href="<?php echo site_url('wp-admin/edit.php?post_type=product') ?>" target="_blank">Products List &#8599;</a></td>
		</tr>
	</table>
</div>
