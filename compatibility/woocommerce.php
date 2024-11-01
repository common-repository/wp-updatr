<?php

function wp_updatr_woo_create_activation( $order_id ){

	$wpupdatr = new WP_Updatr();
	
	$order = new WC_Order( $order_id );

	$api_keys = array();

 	foreach ( $order->get_items() as $item_id => $product_item ) {

        $product_id = $product_item->get_product_id();

        $product_key = get_post_meta( $product_id, 'wp_updatr_product_key', true );

       	if( !empty( $product_key ) ){

       		$lifespan_days = intval( get_post_meta( $product_id, 'wp_updatr_licence_lifespan', true ) );

       		$site_limit = get_post_meta( $product_id, 'wp_updatr_licence_limit', true );

	        $api_key = $wpupdatr->process_purchase( $product_key, $lifespan_days, false, $site_limit, 'active', $order_id );

	        if( $api_key ){
				$api_keys[$product_id] = $api_key;
			}

		}

    }

	update_post_meta( $order_id, '_wp_updatr_licensing_api_keys', $api_keys );

}
add_action( 'woocommerce_order_status_completed', 'wp_updatr_woo_create_activation', 10, 1 );

// function wp_updatr_woo_pending_activation( $order_id ){

// 	//Leaving this here but we don't need to do anything with this for now

// }
// add_action( 'woocommerce_order_status_pending', 'wp_updatr_woo_pending_activation', 10, 1 );
// add_action( 'woocommerce_order_status_on-hold', 'wp_updatr_woo_pending_activation', 10, 1 );
// add_action( 'woocommerce_order_status_processing', 'wp_updatr_woo_pending_activation', 10, 1 );

function wp_updatr_woo_cancel_activation( $order_id ){

	$api_keys = get_post_meta( $order_id, '_wp_updatr_licensing_api_keys', true );

	if( !empty( $api_keys ) ){

		$wpupdatr = new WP_Updatr();
	
		$order = new WC_Order( $order_id );

	 	foreach ( $order->get_items() as $item_id => $product_item ) {

	        $product_id = $product_item->get_product_id();

	        $product_key = get_post_meta( $product_id, 'wp_updatr_product_key', true );

	       	if( !empty( $product_key ) ){

	       		$license_key = isset( $api_keys[$product_id] ) ? $api_keys[$product_id] : '';
	       		
		        $api_key = $wpupdatr->cancel_purchase( $product_key, $license_key );	     

			}

	    }

	}
	

}
add_action( 'woocommerce_order_status_failed', 'wp_updatr_woo_cancel_activation', 10, 1 );
add_action( 'woocommerce_order_status_refunded', 'wp_updatr_woo_cancel_activation', 10, 1 );
add_action( 'woocommerce_order_status_cancelled', 'wp_updatr_woo_cancel_activation', 10, 1 );

function wp_updatr_woo_add_account_columns( $actions ) {

  	$actions['els_api_key'] = __('API Key', 'wp-updatr');
  	$actions['els_version'] = __('Version', 'wp-updatr');

    return $actions;
}
add_filter( 'woocommerce_account_downloads_columns', 'wp_updatr_woo_add_account_columns', 10, 2 );

function wp_updatr_woo_downloads_api_key( $download ){

	$product_id = $download['product_id'];
	
	$api_keys = get_post_meta( $download['order_id'], '_wp_updatr_licensing_api_keys', true );

	if( isset( $api_keys[$download['product_id']] ) ){
		echo "<input type='text' readonly value='".$api_keys[$download['product_id']]."' />";
	}

}
add_action( 'woocommerce_account_downloads_column_els_api_key', 'wp_updatr_woo_downloads_api_key', 10, 1 );

function wp_updatr_downloads_version( $download ){

	$product_id = $download['product_id'];
	
	$api_keys = get_post_meta( $download['order_id'], '_wp_updatr_licensing_api_keys', true );

	if( isset( $api_keys[$download['product_id']] ) ){
		$wpupdatr = new WP_Updatr();

		$version = $wpupdatr->get_latest_version( $api_keys[$download['product_id']] );

		echo $version;
	}

}
add_action( 'woocommerce_account_downloads_column_els_version', 'wp_updatr_downloads_version', 10, 1 );

function wp_updatr_woo_product_options(){
 	
 	global $post;

	echo '<div class="options_group">';
 	
 	$valid_key = get_post_meta( $post->ID, 'wp_updatr_product_key_valid', true );

 	if( $valid_key ){
 		$status = __('Valid', 'wp-updatr');
 	} else {
		$status = __('Invalid', 'wp-updatr');
 	}

	woocommerce_wp_text_input( array(
		'id'      => 'wp_updatr_product_key',
		'value'   => get_post_meta( get_the_ID(), 'wp_updatr_product_key', true ),
		'label'   => __('Product Key', 'wp-updatr').' - '.$status,
		'desc_tip' => true,
		'description' => wp_updatr_descriptions( 'key' )
	) );

	woocommerce_wp_text_input( array(
		'id'      => 'wp_updatr_licence_lifespan',
		'value'   => get_post_meta( get_the_ID(), 'wp_updatr_licence_lifespan', true ),
		'label'   => 'License Lifespan',
		'desc_tip' => true,
		'description' => wp_updatr_descriptions( 'lifespan' ),
	) );

	woocommerce_wp_text_input( array(
		'id'      => 'wp_updatr_licence_limit',
		'value'   => get_post_meta( get_the_ID(), 'wp_updatr_licence_limit', true ),
		'label'   => 'Site Usage Limit',
		'desc_tip' => true,
		'description' => wp_updatr_descriptions( 'limit' )
	) );
 
	echo '</div>';
 
}
add_action( 'woocommerce_product_options_general_product_data', 'wp_updatr_woo_product_options');

function wp_updatr_woo_save_fields( $id, $post ){

	if( !empty( $_POST['wp_updatr_product_key'] ) ) {

		$product_key = sanitize_text_field( $_POST['wp_updatr_product_key'] );

		update_post_meta( $id, 'wp_updatr_product_key', $product_key );

		$wpupdatr = new WP_Updatr();

		$verify = $wpupdatr->verify_product( $product_key );

		if( $verify ){
			update_post_meta( $id, 'wp_updatr_product_key_valid', true );
		} else {
			update_post_meta( $id, 'wp_updatr_product_key_valid', false );
		}

		update_post_meta( $id, 'wp_updatr_licence_lifespan', intval( $_POST['wp_updatr_licence_lifespan'] ) );
		update_post_meta( $id, 'wp_updatr_licence_limit', intval( $_POST['wp_updatr_licence_limit'] ) );

	} else {
		delete_post_meta( $id, 'wp_updatr_product_key' );
	}
 
}
add_action( 'woocommerce_process_product_meta', 'wp_updatr_woo_save_fields', 10, 2 );