<?php
/*
Plugin Name: Signup TOS
Plugin URI: http://premium.wpmudev.org/project/terms-of-service
Description: This plugin places a Terms of Service box on the WP Multisite or BuddyPress signup form forcing the user to tick the associated checkbox in order to continue
Author: WPMU DEV
Version: 1.3.2
Author URI: http://premium.wpmudev.org
Network: true
WDP ID: 8
Contributors: Umesh Kumar
*/

/*
Copyright 2007-2014 Incsub (http://incsub.com)
Author - Aaron Edwards & Andrew Billits (Incsub)
Contributo - Umesh Kumar
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

//------------------------------------------------------------------------//
//---Hook-----------------------------------------------------------------//
//------------------------------------------------------------------------//

add_action( 'signup_extra_fields', 'signup_tos_field_wpmu', 20 );
add_action( 'bp_before_registration_submit_buttons', 'signup_tos_field_bp' );
add_filter( 'wpmu_validate_user_signup', 'signup_tos_filter_wpmu' );
add_action( 'bp_signup_validate', 'signup_tos_filter_bp' );
add_action( 'admin_menu', 'signup_tos_plug_pages' );
add_action( 'network_admin_menu', 'signup_tos_plug_pages' );
add_action( 'plugins_loaded', 'signup_tos_localization' );
add_shortcode( 'signup-tos', 'signup_tos_shortcode' );

//------------------------------------------------------------------------//
//---Functions------------------------------------------------------------//
//------------------------------------------------------------------------//

function signup_tos_localization() {
  // Load up the localization file if we're using WordPress in a different language
	// Place it in the mu-plugins folder or plugins and name it "tos-LOCALE.mo"
	load_plugin_textdomain( 'tos', false, '/signup-tos/languages/' );
}
/**
 * Adds an entry in Dashboard
 */
function signup_tos_plug_pages() {
	$title = __( 'TOS', 'tos' );
	$slug = 'signup-tos';
	$callback = 'signup_tos_page_main_output';

	if ( is_multisite() ) {
		add_submenu_page( 'settings.php', $title, $title, 'manage_network_options', $slug, $callback );
	} else {
		add_options_page( $title, $title, 'manage_options', $slug, $callback );
	}
}

//------------------------------------------------------------------------//
//---Page Output Functions------------------------------------------------//
//------------------------------------------------------------------------//
/**
 * Shortcode for Adding TOS
 * @param type $atts
 * @return string
 */
function signup_tos_shortcode( $atts ) {
	extract( shortcode_atts( array(
		'checkbox'   => 0,
		'show_label' => 1,
		'error'      => '',
	), $atts ) );

	$signup_tos = get_site_option( 'signup_tos_data' );
	if ( empty( $signup_tos ) ) {
		return '';
	}

	ob_start();

	if ( $show_label ) : ?>
		<label for="tos_content"><?php _e( 'Terms Of Service', 'tos' ) ?>:</label>
	<?php endif; ?>

	<div id="tos_content" style="height:150px;overflow:auto;background-color:white;padding:5px;border:1px gray inset;font-size:80%;"><?php echo wpautop( $signup_tos ) ?></div>

	<?php if ( !empty( $error ) ) : ?>
	<p class="error"><?php echo $error ?></p>
	<?php endif; ?>

	<input type="hidden" name="tos_agree" value="0">
	<label>
		<input type="checkbox" id="tos_agree" name="tos_agree" value="1" <?php checked( filter_input( INPUT_POST, 'tos_agree', FILTER_VALIDATE_BOOLEAN ) ); ?> style="width:auto;display:inline">
		<?php _e( 'I Agree', 'tos' ) ?>
	</label><?php
	return ob_get_clean();
}
/**
 * Display error
 * @param type $errors
 */
function signup_tos_field_wpmu( $errors ) {
	// render error message if Membership plugin not exists otherwise Membership
	// plugin will use it's own errors rendering approach
	$message = !empty( $errors ) && !class_exists( 'Membership_Plugin', false )
		? $errors->get_error_message( 'tos' )
		: '';

	echo signup_tos_shortcode( array(
		'checkbox' => true,
		'error'    => $message,
	) );
}
/**
 * Render Checkbox on signup
 */
function signup_tos_field_bp() {
  $signup_tos = get_site_option('signup_tos_data');
	if ( !empty( $signup_tos ) ) {
	?>
    <div class="register-section" id="blog-details-section">
    <label for="tos_content"><?php _e('Terms Of Service', 'tos'); ?></label>
    <?php do_action( 'bp_tos_agree_errors' ) ?>
    <div id="tos_content" style="height:150px;width:100%;overflow:auto;background-color:white;padding:5px;border:1px gray inset;font-size:80%;"><?php echo $signup_tos ?></div>
    <label for="tos_agree"><input type="checkbox" id="tos_agree" name="tos_agree" value="1" <?php checked( filter_input( INPUT_POST, 'tos_agree', FILTER_VALIDATE_BOOLEAN ) ); ?>/> <?php _e('I Agree', 'tos'); ?></label>
    </div>
	<?php
	}
}
/**
 * Check if User agress to TOS or Display error
 * @param type $errors
 * @return type
 */
function signup_tos_filter_wpmu( $errors ) {
	if ( $_SERVER['REQUEST_METHOD'] != 'POST' || !isset( $_POST['tos_agree'] ) ) {
		return $errors;
	}

	$signup_tos = get_site_option( 'signup_tos_data' );
	if ( !empty( $signup_tos ) && (int)$_POST['tos_agree'] == 0 ) {
		$message = __( 'You must agree to the TOS in order to signup.', 'tos' );
		if ( is_array( $errors ) && isset( $errors['errors'] ) && is_wp_error( $errors['errors'] ) ) {
			$errors['errors']->add( 'tos', $message );
		} elseif ( is_wp_error( $errors ) ) {
			$errors->add( 'tos', $message );
		}
	}

	return $errors;
}
/**
 * Validate TOS if Buddypress is active and display error if TOS not checked
 * @global type $bp
 * @return type
 */
function signup_tos_filter_bp() {
	global $bp;
	if ( !is_object( $bp ) || !is_a( $bp, 'BuddyPress' ) ) {
		return;
	}
	$signup_tos = get_site_option( 'signup_tos_data' );
	if ( !empty( $signup_tos ) && (int)$_POST['tos_agree'] == 0 ) {
		$bp->signup->errors['tos_agree'] = __( 'You must agree to the TOS in order to signup.', 'tos' );
	}
}
/**
 * 
 * @return type
 */
function signup_tos_page_main_output() {
	if ( !current_user_can( 'edit_users' ) ) {
		echo "<p>Nice Try...</p>";  //If accessed properly, this message doesn't appear.
		return;
	}

	// update message if posted
	$message = '';
	if ( $_SERVER['REQUEST_METHOD'] == 'POST' && isset( $_POST['signup_tos_data'] ) ) {
		update_site_option( "signup_tos_data", wp_filter_kses( stripslashes( trim( $_POST['signup_tos_data'] ) ) ) );
		$message = esc_html__( 'Settings Saved.', 'tos' );
	}

	// render page
	?><div class="wrap">
		<h2><?php _e( 'Terms of Service', 'tos' ) ?></h2>

		<?php if ( !empty( $message ) ) : ?>
		<div id="message" class="updated fade"><p><?php echo $message ?></p></div>
		<?php endif; ?>

		<p class="description"><?php
			_e( 'Please enter the text for your Terms of Service here. It will be displayed on the multisite wp-signup.php page or BuddyPress registration form. You may also use the shortcode [signup-tos] in your posts or pages. Note that You can enable the checkbox (though it won\'t be functional) by adding the appropriate argument to the shortcode like [signup-tos checkbox="1"].', 'tos' )
		?></p>

		<br>

		<form method="post">
			<?php wp_editor( get_site_option( 'signup_tos_data' ), 'signuptosdata', array( 'textarea_name' => 'signup_tos_data' ) ) ?>

			<p class="submit">
				<input type="submit" class="button-primary" name="save_settings" value="<?php _e( 'Save Changes', 'tos' ) ?>">
			</p>
		</form>
	</div><?php
}

include_once( dirname( __FILE__ ) . '/dash-notice/wpmudev-dash-notification.php' );