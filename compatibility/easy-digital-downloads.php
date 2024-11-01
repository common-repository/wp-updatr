<?php

function wp_updatr_edd_add_metabox() {

    add_meta_box( 'wp-updatr-edd-metabox', __( 'WP Updatr', 'wp-updatr' ), 'wp_updatr_edd_meta_box_contents', 'download' );

}
add_action( 'add_meta_boxes', 'wp_updatr_edd_add_metabox' );

function wp_updatr_edd_meta_box_contents(){

	global $post;

	$post_id = $post->ID;

	?>
	<table class='form-table'>
		<tr>
			<th><?php _e('Product Key', 'wp-updatr'); ?></th>
			<td>
				<input type='text' name='wp_updatr_edd_product_key' value='<?php echo get_post_meta( $post_id, 'wp_updatr_edd_key', true ); ?>' />
				<p class='description'><?php echo wp_updatr_descriptions( 'key' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php _e('Site Limit', 'wp-updatr'); ?></th>
			<td>
				<input type='text' name='wp_updatr_edd_limit' value='<?php echo get_post_meta( $post_id, 'wp_updatr_edd_limit', true ); ?>' />
				<p class='description'><?php echo wp_updatr_descriptions( 'limit' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php _e('License Lifespan', 'wp-updatr'); ?></th>
			<td>
				<input type='text' name='wp_updatr_edd_licence_lifespan' value='<?php echo get_post_meta( $post_id, 'wp_updatr_edd_lifespan', true ); ?>' />
				<p class='description'><?php echo wp_updatr_descriptions( 'lifespan' ); ?></p>
			</td>
		</tr>
	</table>
	<?php

}

function wp_updatr_edd_save_post( $post_id ){

	if( isset( $_REQUEST['wp_updatr_edd_product_key'] ) ){
		update_post_meta( $post_id, 'wp_updatr_edd_key', sanitize_text_field( $_REQUEST['wp_updatr_edd_product_key'] ) );
	}
	if( isset( $_REQUEST['wp_updatr_edd_limit'] ) ){
		update_post_meta( $post_id, 'wp_updatr_edd_limit', sanitize_text_field( $_REQUEST['wp_updatr_edd_limit'] ) );
	}
	if( isset( $_REQUEST['wp_updatr_edd_licence_lifespan'] ) ){
		update_post_meta( $post_id, 'wp_updatr_edd_lifespan', sanitize_text_field( $_REQUEST['wp_updatr_edd_licence_lifespan'] ) );
	}

}
add_action( 'save_post', 'wp_updatr_edd_save_post', 99, 1 );

function wp_updatr_edd_order_complete( $payment_id ) {

	wp_updatr_edd_new_api_key( $payment_id );	

}
add_action( 'edd_complete_purchase', 'wp_updatr_edd_order_complete' );


function wp_updatr_edd_payment_status_update( $payment_id, $new_status, $old_status ){

	$revoke_triggers = apply_filters( 'wpupdatr_edd_revoke_triggers_array', array( 'refunded', 'revoked', 'failed' ), $payment_id, $new_status, $old_status );

	if( in_array( $new_status, $revoke_triggers ) ){
		//Cancel API Key
		$api_keys = get_post_meta( $payment_id, 'wp_updatr_api_keys', true );

		if( !empty( $api_keys ) ){

			$wpupdatr = new WP_Updatr();

			foreach( $api_keys as $product_id => $api_key ){
				$product_key = get_post_meta( $product_id, 'wp_updatr_edd_key', true );
				$wpupdatr->cancel_purchase( $product_key, $api_key );
			}
		}
	}

}
add_action( 'edd_update_payment_status', 'wp_updatr_edd_payment_status_update', 10, 3 );

function wp_updatr_edd_new_api_key( $payment_id ){

	$wpupdatr = new WP_Updatr();
	// Basic payment meta
	$payment_meta = edd_get_payment_meta( $payment_id );

	// Cart details
	$cart_items = edd_get_payment_meta_cart_details( $payment_id );

	$api_keys = array();

	if( !empty( $cart_items ) ){
		foreach( $cart_items as $item ){

			$product_id = $item['id'];

			$product_key = get_post_meta( $product_id, 'wp_updatr_edd_key', true );

	       	if( !empty( $product_key ) ){

	       		$lifespan_days = get_post_meta( $product_id, 'wp_updatr_edd_lifespan', true );

	       		$site_limit = get_post_meta( $product_id, 'wp_updatr_edd_limit', true );

		        $api_key = $wpupdatr->process_purchase( $product_key, $lifespan_days, false, $site_limit, 'active', $payment_id );

		        if( $api_key ){
					$api_keys[$product_id] = $api_key;
				}

			}

		}
	}

	if( !empty( $payment_meta['user_info']['id'] ) && !empty( $api_keys ) ){
		update_post_meta( $payment_id, 'wp_updatr_api_keys', $api_keys );
	}

}

function wp_updatr_edd_show_api_keys_order_details( $payment_id ){

	$api_keys = get_post_meta( $payment_id, 'wp_updatr_api_keys', true );
	?>
	<div id="edd-api-keys" class="postbox">
		<h3 class="hndle"><span><?php _e( 'WP Updatr API Keys', 'wp-updatr' ); ?></span></h3>
		<div class="inside">
			<div id="edd-api-keys-inner">
				<?php 
				if( !empty( $api_keys ) ){
					foreach( $api_keys as $product_id => $product_key ){
						$product = get_post( $product_id );
						if( $product ){
							echo "<p><strong>".$product->post_title."</strong> - ".$product_key."</p>";
						}
					}
				} else {
					echo "<p>".__('No API Keys found.', 'wp-updatr')."</p>";
				}
				?>
			</div>
		</div>
	</div>
	<?php

}
add_action( 'edd_view_order_details_main_after', 'wp_updatr_edd_show_api_keys_order_details', 10, 1 );

function wp_updatr_edd_show_api_keys_frontend( $payment, $args ){

	$api_keys = get_post_meta( $payment->ID, 'wp_updatr_api_keys', true );

	if( !empty( $api_keys ) ){

		?>
		<h3><?php _e('API Keys', 'wp-updatr'); ?></h3>

		<table id="edd_purchase_api_keys" class="edd-table">
			<thead>
				<th><?php _e( 'Product', 'wp-updatr' ); ?></th>
				<th><?php _e( 'API Key', 'wp-updatr' ); ?></th>
			</thead>
			<tbody>
				<?php
				if( !empty( $api_keys ) ){
					foreach( $api_keys as $product_id => $product_key ){
						$product = get_post( $product_id );
						if( $product ){
							?>
							<tr>
								<td><?php echo $product->post_title; ?></td>
								<td><?php echo $product_key; ?></td>
							</tr>
							<?php
						}
					}
				} else {
					echo "<tr><td colspan='2'>".__('No API Keys found.', 'wp-updatr')."</td></tr>";
				}
				?>
			</tbody>
		</table>
		<?php

	}

}
add_action( 'edd_payment_receipt_after_table', 'wp_updatr_edd_show_api_keys_frontend', 10, 2 );