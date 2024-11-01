<?php
function wp_updatr_pmpro_level_settings() {

	$level_id = 0;

	if( !empty( $_REQUEST['edit'] ) ){
		if( $_REQUEST['edit'] < 0 ){
			//New level
		} else {
			$level_options = get_option( 'wp_updatr_levels_'.intval( $_REQUEST['edit'] ) );
		}
	}

	$verify_text = '';

	if( isset( $level_options['key'] ) ){

		$product_key = $level_options['key'];

		$wpupdatr = new WP_Updatr();

		if( $wpupdatr->verify_product( $product_key ) ){
			$verify_text = __('Valid', 'wp-updatr');
		} else {
			$verify_text = __('Invalid', 'wp-updatr');
		}

	}

	?>
	<hr />
	<h3><?php esc_html_e( 'WP Updatr Settings', 'wp-updatr' ); ?></h3>
	<p class="description">
		<?php
			$espress_allowed_link = array(
				'a' => array (
					'href' => array(),
					'target' => array(),
					'title' => array(),
				),
			);
			echo sprintf( wp_kses( __( 'Link your Paid Memberships Pro levels to your WP Updatr products. Alternatively, <a href="%s" title="Register Now" target="_blank">Register Now</a>.', 'wp-updatr' ), $espress_allowed_link ), 'https://wpupdatr.com/?utm_source=plugin&utm_medium=wpupdatr-plugin&utm_campaign=settings&utm_content=pmpro#pricing' );
		?>
	</p>
	<table class="form-table">
		<tbody>
			<tr>
				<th><?php echo __('Product Key', 'wp-updatr') .' - '.$verify_text; ?></th>
				<td>
					<input type='text' name='wp_updatr_product_key' value='<?php if( isset( $level_options['key'] ) ){ echo $level_options['key']; } ?>' class='regular_text'/>
					<p class="description"><?php echo wp_updatr_descriptions( 'key' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php _e('License Lifespan', 'wp-updatr'); ?></th>
				<td>
					<input type='text' name='wp_updatr_lifespan' value='<?php if( isset( $level_options['lifespan'] ) ){ echo $level_options['lifespan']; } ?>' class='regular_text'/>
					<p class="description"><?php echo wp_updatr_descriptions( 'lifespan' ); ?> <?php _e( 'Recurring level renewal frequency will override this value. Only applies to Once Off levels.', 'wp-updatr' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php _e('Site Limit', 'wp-updatr'); ?></th>
				<td>
					<input type='text' name='wp_updatr_limit' value='<?php if( isset( $level_options['limit'] ) ){ echo $level_options['limit']; } ?>' class='regular_text'/>
					<p class="description"><?php echo wp_updatr_descriptions( 'limit' ); ?></p>
				</td>
			</tr>
		</tbody>
	</table>
	<?php
}
add_action( 'pmpro_membership_level_after_other_settings', 'wp_updatr_pmpro_level_settings', 10 );

function wp_updatr_pmpro_edit_level( $saveid ){

	if( isset( $_REQUEST['wp_updatr_product_key'] ) ){

		$license_key = sanitize_text_field( $_REQUEST['wp_updatr_product_key'] );
		$site_limit = intval( $_REQUEST['wp_updatr_limit'] );
		$lifespan = intval( $_REQUEST['wp_updatr_lifespan'] );

		update_option( 'wp_updatr_levels_'.$saveid, array( 'key' => $license_key, 'limit' => $site_limit, 'lifespan' => $lifespan ) );
	}
}
add_action( 'pmpro_save_membership_level', 'wp_updatr_pmpro_edit_level', 10, 1 );

function wp_updatr_pmpro_delete_level( $delete_id ){

	delete_option( 'wp_updatr_levels_'.$saveid );

}
add_action( 'pmpro_delete_membership_level', 'wp_updatr_pmpro_delete_level', 10, 1 );

function wp_updatr_pmpro_after_checkout( $user_id, $morder ){

	wp_updatr_pmpro_setup_api_keys( $morder );

}
add_action( 'pmpro_after_checkout', 'wp_updatr_pmpro_after_checkout', 10, 2 );

function wp_updatr_pmpro_renewals( $morder ){

	wp_updatr_pmpro_setup_api_keys( $morder, true );

}
add_action( 'pmpro_subscription_payment_completed', 'wp_updatr_pmpro_renewals', 10, 1 );

function wp_updatr_pmpro_fails( $morder ){

	wp_updatr_pmpro_cancel_api_key( $morder->membership_id, $morder->user_id );

}
add_action( 'pmpro_subscription_payment_failed', 'wp_updatr_pmpro_fails', 10, 1 );
add_action( 'pmpro_stripe_subscription_deleted', 'wp_updatr_pmpro_fails', 10, 1 );

function wp_updatr_pmpro_cancelled( $level_id, $user_id, $cancelled_level ){

	if( $cancelled_level ){
		//Cancelling, lets cancel their api key too
		wp_updatr_pmpro_cancel_api_key( $cancelled_level, $user_id );
	}

}
// add_action( 'pmpro_after_change_membership_level', 'wp_updatr_pmpro_cancelled', 10, 3 );

function wp_updatr_pmpro_cancel_api_key( $level_id, $user_id ){

	global $wpdb;

	$sql = "SELECT * FROM $wpdb->pmpro_membership_orders WHERE membership_id = '".$level_id."' AND user_id = '".$user_id."' ORDER BY id DESC";

	$recent_transactions = $wpdb->get_results( $sql );

	$level_options = get_option( 'wp_updatr_levels_'.$level_id );

	$wpupdatr = new WP_Updatr();

	if( !empty( $level_options ) ){

		foreach( $recent_transactions as $recent ){

			$license_key = wp_updatr_pmpro_get_license_key( $recent->notes );

			if( $license_key ){
				$api_key = $wpupdatr->cancel_purchase( $level_options['key'], $license_key );
			}
		}

	}

}

function wp_updatr_pmpro_setup_api_keys( $morder, $renewals = false ){

	global $wpdb;

	$api_keys = array();	

	$level_id = $morder->membership_id;

	$level_options = get_option( 'wp_updatr_levels_'.$level_id );

    $product_key = isset( $level_options['key'] ) ? $level_options['key'] : '';

    $frequency = $morder->BillingFrequency;
    $period = $morder->BillingPeriod;

    $multiplier = 1;

    switch( $period ){
    	case 'Day':
    		$multiplier = 1;
    		break;
		case 'Week': 
			$multiplier = 7;
			break;
		case 'Month': 
			$multiplier = 30;
			break;
		case 'Year': 
			$multiplier = 365;
			break;
    }

    if( $period == '' ){
    	//Once off 
    	$lifespan_days = isset( $level_options['lifespan'] ) ? intval( $level_options['lifespan'] ) : 0;
    } else {
    	$lifespan_days = $multiplier * $frequency;	
    }

    $site_limit = isset( $level_options['limit'] ) ? intval( $level_options['limit'] ) : 0;

   	if( !empty( $product_key ) ){

   		$wpupdatr = new WP_Updatr();

        $api_key = $wpupdatr->process_purchase( $product_key, $lifespan_days, $renewals, $site_limit, 'active', $morder->code );

        $notes = "";
		$notes .= "\n---\n";
		$notes .= "{LICENSE_KEY:" . $api_key . "}\n";
		$notes .= "---\n";

		$morder->notes .= $notes;

		$sqlQuery = "UPDATE $wpdb->pmpro_membership_orders SET notes = '" . esc_sql( $morder->notes ) . "' WHERE id = '" . intval( $morder->id ) . "' LIMIT 1";
		
		$wpdb->query($sqlQuery);

	}	

}

function wp_updatr_pmpro_get_license_key( $order_notes ){

	$value = pmpro_getMatches( "/{LICENSE_KEY:([^}]*)}/", $order_notes, true );
	
	return $value;

}

function wp_updatr_pmpro_display_confirmation( $morder ){
	?>
	<li><strong><?php _e('API Key', 'wp-updatr' );?>:</strong> <?php echo wp_updatr_pmpro_get_license_key( $morder->notes ); ?></li>
	<?php
}
add_action( 'pmpro_invoice_bullets_bottom', 'wp_updatr_pmpro_display_confirmation', 10, 1 );