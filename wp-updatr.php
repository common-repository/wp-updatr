<?php
/**
 * Plugin Name: WP Updatr
 * Description: Making it easy to launch and support paid WordPress products by integrating with WooCommerce, Paid Memberships Pro & Easy Digital Downloads allowing your customers to update your plugin through their dashboard.
 * Author: WP Updatr
 * Author URI: https://wpupdatr.com/
 * Version: 1.0.1
 * Text Domain: wp-updatr
 * Domain Path: /languages
 */

define( 'WPUPDATR_PLUGIN_URL', __FILE__ );

require_once plugin_dir_path( __FILE__ ).'class.wp_updatr.php';

function wp_updatr_load_admin_scripts(){

	if( !empty( $_REQUEST['welcome'] ) && ( !empty( $_REQUEST['page'] ) && $_REQUEST['page'] == 'wp-updatr' ) ){
		wp_enqueue_style( 'wpupdatr-admin-styles', plugins_url( 'assets/css/admin.css', __FILE__ ) );
	}

}
add_action( 'admin_enqueue_scripts', 'wp_updatr_load_admin_scripts' );

function wp_updatr_hide_welcome_page(){

	if( !empty( $_REQUEST['ignore'] ) && ( !empty( $_REQUEST['page'] ) && $_REQUEST['page'] == 'wp-updatr' ) ){
		$user = get_current_user_id();
		update_user_meta( $user, 'wpupdatr_welcome_complete', true );
	}
}
add_action( 'admin_init', 'wp_updatr_hide_welcome_page' );


function wpupdatr_activate_redirect( $plugin ){

    if( $plugin == plugin_basename( __FILE__ ) ) {

   		exit( wp_redirect( admin_url( 'options-general.php?page=wp-updatr&welcome=true' ) ) );

   	}


}
add_action( 'activated_plugin', 'wpupdatr_activate_redirect');

function wp_updatr_load_integration(){

	switch( get_option( 'wp_updatr_integration' ) ){
		case 'woocommerce':
			require_once plugin_dir_path( __FILE__ ).'compatibility/woocommerce.php';
			break;
		case 'paid-memberships-pro':
			require_once plugin_dir_path( __FILE__ ).'compatibility/paid-memberships-pro.php';
			break;
		case 'easy-digital-downloads':
			require_once plugin_dir_path( __FILE__ ).'compatibility/easy-digital-downloads.php';
			break;
	}

	load_plugin_textdomain( 'wp-updatr', false, basename( dirname( __FILE__ ) ) . '/languages' );

}
add_action( 'plugins_loaded', 'wp_updatr_load_integration' );

function wpupdatr_admin_menu(){

	add_submenu_page( 'options-general.php', __( 'WP Updatr', 'wp-updatr' ), __( 'WP Updatr', 'wp-updatr' ), 'manage_options', 'wp-updatr', 'wp_updatr_menu_content' );

}
add_action( 'admin_menu', 'wpupdatr_admin_menu', 99 );

function wp_updatr_menu_content(){
	$user = get_current_user();

	$complete = get_user_meta( $user, 'wpupdatr_welcome_complete', true );

	if( !empty( $_REQUEST['welcome'] ) && $_REQUEST['welcome'] && !$complete ){
		require_once plugin_dir_path( __FILE__ ).'includes/welcome.php';
	} else {
		require_once plugin_dir_path( __FILE__ ).'includes/settings.php';
	}

}

function wp_updatr_validate_api_key(){

	$wpupdatr = new WP_Updatr();

	$valid = $wpupdatr->validate_client_api_key();

	return $valid;

}

function wp_updatr_save_settings(){

	if( isset( $_POST['wpur_save_settings'] ) ){

		$api_key = isset( $_POST['wpur_api_key'] ) ? $_POST['wpur_api_key'] : '';
		update_option( 'wp_updatr_api_key', $api_key );

		$integration = isset( $_POST['wpur_integration'] ) ? $_POST['wpur_integration'] : '';
		update_option( 'wp_updatr_integration', $integration );

	}

}
add_action( 'admin_init', 'wp_updatr_save_settings' );

function wp_updatr_descriptions( $key ){

	$descriptions = array(
		'key' 		=> __('Login to your WP Updatr account and navigate to "Products" to obtain a product key.', 'wp-updatr'),
		'limit' 	=> __('The maximum number of sites allowed to use a licence key. Leave empty or set to 0 for unlimited.', 'wp-updatr'),
		'lifespan' 	=> __('How long will a license key be valid for. Specify in days only.', 'wp-updatr'),
	);

	if( !empty( $descriptions[$key] ) ){
		return $descriptions[$key];
	}

	return;

}

/**
 * Function to add links to the plugin row meta
 *
 * @param array  $links Array of links to be shown in plugin meta.
 * @param string $file Filename of the plugin meta is being shown for.
 */
function pmproama_plugin_row_meta( $links, $file ) {
	if ( strpos( $file, 'wp-updatr.php' ) !== false ) {
		$new_links = array(
			'<a href="' . esc_url( 'https://wpupdatr.com/documentation/' ) . '" title="' . esc_attr( __( 'View Documentation', 'wp-updatr' ) ) . '">' . __( 'Docs', 'wp-updatr' ) . '</a>',
			'<a href="' . esc_url( 'https://wpupdatr.com/support/' ) . '" title="' . esc_attr( __( 'Support', 'wp-updatr' ) ) . '">' . __( 'Support', 'wp-updatr' ) . '</a>',
		);
		$links = array_merge( $links, $new_links );
	}
	return $links;
}
add_filter( 'plugin_row_meta', 'pmproama_plugin_row_meta', 10, 2 );

/**
 * Function to add links to the plugin action links
 *
 * @param array $links Array of links to be shown in plugin action links.
 */
function wp_updatr_add_plugin_action_link( $links ) {
	if ( current_user_can( 'manage_options' ) ) {
		$new_links = array(
			'<a href="' . get_admin_url( null, 'options-general.php?page=wp-updatr-settings' ) . '">' . __( 'Settings', 'wp-updatr' ) . '</a>',
		);
		if( !get_option( 'wp_updatr_api_key' ) ){
			$new_links[] = '<a href="https://wpupdatr.com/?utm_source=plugin&utm_medium=wpupdatr-plugin&utm_campaign=plugins-page&utm_content=action-links#pricing" target="_BLANK">' . __( 'Sign Up For Our Professional Plan', 'wp-updatr' ) . '</a>';
		}
	}
	return array_merge( $new_links, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wp_updatr_add_plugin_action_link' );

/**
 * Admin Notice on Activation.
 */
function wp_updatr_admin_notice() {

	if ( !get_option( 'wp_updatr_api_key' ) ) { ?>
		<div class="updated is-dismissible">
			<p><?php 
				_e( 'Thank you for using WP Updatr. <a href="'.get_admin_url( null, 'options-general.php?page=wp-updatr-settings' ).'">Get Started</a> by linking your website and products to the WP Updatr service. <a href="https://wpupdatr.com/?utm_source=plugin&utm_medium=wpupdatr-plugin&utm_campaign=admin-notice&utm_content=prompt#pricing" target="_BLANK">Sign Up For Our Professional Plan</a>', 'wp-updatr' ); ?></p>
		</div>
		<?php
	}
}
add_action( 'admin_notices', 'wp_updatr_admin_notice' );

