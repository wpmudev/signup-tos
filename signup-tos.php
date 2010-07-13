<?php
/*
Plugin Name: Signup TOS
Plugin URI: 
Description:
Author: Andrew Billits
Version: 1.1.0
Author URI:
WDP ID: 8
*/

/* 
Copyright 2007-2009 Incsub (http://incsub.com)

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
add_action('signup_extra_fields', 'signup_tos_field_wpmu');
add_action('bp_after_account_details_fields', 'signup_tos_field_bp');
add_filter('wpmu_validate_user_signup', 'signup_tos_filter_wpmu');
add_filter('bp_signup_validate', 'signup_tos_filter_bp');
add_action('admin_menu', 'signup_tos_plug_pages');
add_action('plugins_loaded', 'signup_tos_localization');

//------------------------------------------------------------------------//
//---Functions------------------------------------------------------------//
//------------------------------------------------------------------------//

function signup_tos_localization() {
  // Load up the localization file if we're using WordPress in a different language
	// Place it in the mu-plugins folder and name it "tos-LOCALE.mo"
	load_muplugin_textdomain( 'tos' );
}

function signup_tos_plug_pages() {
	global $wpdb, $wp_roles, $current_user;
	if ( is_site_admin() ) {
		add_submenu_page('ms-admin.php', __('TOS', 'tos'), __('TOS', 'tos'), 10, 'signup-tos', 'signup_tos_page_main_output');
	}
}

//------------------------------------------------------------------------//
//---Page Output Functions------------------------------------------------//
//------------------------------------------------------------------------//

function signup_tos_field_wpmu($errors) {
	if (!empty($errors)){
		$error = $errors->get_error_message('tos');
	}
	$signup_tos = get_site_option('signup_tos_data');
	if ( !empty( $signup_tos ) ) {
	?>
    <label for="tos_content"><?php _e('Terms Of Service', 'tos'); ?>:</label>
    <div id="tos_content" style="height:150px;width:95%;overflow:auto;background-color:white;padding:5px;border:1px gray inset;font-size:80%;"><?php echo $signup_tos ?></div>

		<?php
        if(!empty($error)) {
			echo '<p class="error">' . $error . '</p>';
        }
		?>
		<label for="tos_agree"><input type="checkbox" id="tos_agree" name="tos_agree" value="1" /> <?php _e('I Agree', 'tos'); ?></label>
	<?php
	}
}

function signup_tos_field_bp() {
	$signup_tos = get_site_option('signup_tos_data');
	if ( !empty( $signup_tos ) ) {
	?>
    <div class="register-section" id="blog-details-section">
    <label for="tos_content"><?php _e('Terms Of Service', 'tos'); ?></label>
    <div id="tos_content" style="height:150px;width:100%;overflow:auto;background-color:white;padding:5px;border:1px gray inset;font-size:80%;"><?php echo $signup_tos ?></div>
		<?php do_action( 'bp_tos_agree_errors' ) ?>
    <label for="tos_agree"><input type="checkbox" id="tos_agree" name="tos_agree" value="1" /> <?php _e('I Agree', 'tos'); ?></label>
    </div>
	<?php
	}
}

function signup_tos_filter_wpmu($content) {
	$signup_tos = get_site_option('signup_tos_data');
	if ( !empty( $signup_tos ) ) {
		$tos_agree = (int) $_POST['tos_agree'];
		if($tos_agree == '0' && $_POST['stage'] == 'validate-user-signup') {
			$content['errors']->add('tos', __('You must agree to the TOS in order to signup.', 'tos'));
		}
	
		if($tos_agree == '1') {
			//correct answer!
		} else {
			if($_POST['stage'] == 'validate-user-signup') {
				$content['errors']->add('tos', __('You must agree to the TOS in order to signup.', 'tos'));
			}
		}
	}
	return $content;
}

function signup_tos_filter_bp() {
	global $bp;
	$signup_tos = get_site_option('signup_tos_data');
	if ( !empty( $signup_tos ) ) {
		$tos_agree = (int) $_POST['tos_agree'];
		if($tos_agree == '0' && isset($_POST['signup_username'])) {
			$bp->signup->errors['tos_agree'] = __( 'You must agree to the TOS in order to signup.', 'tos' );
		}
	
		if($tos_agree == '1') {
			//correct answer!
		} else {
			if(isset($_POST['signup_username'])) {
				$bp->signup->errors['tos_agree'] = __( 'You must agree to the TOS in order to signup.', 'tos' );
			}
		}
	}
}

function signup_tos_page_main_output() {
	global $wpdb, $wp_roles, $current_user;

	if(!is_super_admin()) {
		echo "<p>Nice Try...</p>";  //If accessed properly, this message doesn't appear.
		return;
	}

	if (isset($_GET['updated'])) {
		?><div id="message" class="updated fade"><p><?php echo urldecode($_GET['updatedmsg']); ?></p></div><?php
	}
	echo '<div class="wrap">';
	switch( $_GET[ 'action' ] ) {
		//---------------------------------------------------//
		default:
		?>
        <h2><?php _e('Terms of Service', 'tos') ?></h2>
        <form method="post" action="ms-admin.php?page=signup-tos&action=update">
        <table class="form-table">
        <tr valign="top">
        <th scope="row"><?php _e('TOS Content: (HTML allowed)', 'tos') ?></th>
        <td>
        <textarea name="signup_tos_data" type="text" rows="5" wrap="soft" id="signup_tos_data" style="width: 95%"/><?php echo esc_attr(get_site_option('signup_tos_data')); ?></textarea>
        <br /></td>
        </tr>
        </table>
        
        <p class="submit">
        <input type="submit" name="Submit" value="<?php _e('Save Changes', 'tos') ?>" />
        </p>
        </form>
        <?php
		break;
		//---------------------------------------------------//
		case "update":
			update_site_option( "signup_tos_data", stripslashes($_POST['signup_tos_data']) );
			echo __("<p>Options saved.</p>", 'tos');
			echo "
			<SCRIPT LANGUAGE='JavaScript'>
			window.location='ms-admin.php?page=signup-tos&updated=true&updatedmsg=" . urlencode(__('Settings saved.', 'tos')) . "';
			</script>
			";
		break;
		//---------------------------------------------------//
		case "temp":
		break;
		//---------------------------------------------------//
	}
	echo '</div>';
}

?>
