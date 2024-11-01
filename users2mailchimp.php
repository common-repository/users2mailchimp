<?php
/**
 * users2mailchimp.php
 *
 * Copyright (c) 2011,2012 Antonio Blanco http://www.blancoleon.com
 *
 * This code is released under the GNU General Public License.
 * See COPYRIGHT.txt and LICENSE.txt.
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This header and all notices must be kept intact.
 *
 * @author Antonio Blanco	
 * @package users2mailchimp
 * @since users2mailchimp 1.0.0
 *
 * Plugin Name: Users2MailChimp
 * Plugin URI: http://www.eggemplo.com/plugins/users2mailchimp
 * Description: Connect wordpress users to MailChimp
 * Version: 1.5
 * Author: eggemplo
 * Author URI: http://www.eggemplo.com
 * License: GPLv3
 */

define( 'USERS2MAILCHIMP_DOMAIN', 'users2mailchimp' );

define( 'USERS2MAILCHIMP_FILE', __FILE__ );

if ( !defined( 'USERS2MAILCHIMP_CORE_DIR' ) ) {
	define( 'USERS2MAILCHIMP_CORE_DIR', WP_PLUGIN_DIR . '/users2mailchimp' );
}

include_once 'class-users2mailchimp.php';


class Users2Mailchimp_Plugin {
	
	private static $notices = array();
	
	public static function init() {
			
		load_plugin_textdomain( USERS2MAILCHIMP_DOMAIN, null, 'users2mailchimp/languages' );
		
		register_activation_hook( USERS2MAILCHIMP_FILE, array( __CLASS__, 'activate' ) );
		register_deactivation_hook( USERS2MAILCHIMP_FILE, array( __CLASS__, 'deactivate' ) );
		
		add_action( 'init', array( __CLASS__, 'wp_init' ) );
		add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );
	}
	
	public static function wp_init() {
		
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ), 40 );
				
		//call register settings function
		add_action( 'admin_init', array( __CLASS__, 'register_users2mailchimp_settings' ) );
		
	}
	
	/**
	 * Register settings as users-mailchimp-settings
	 */
	public static function register_users2mailchimp_settings() {
		//register our settings
		register_setting( 'users2mailchimp-settings', 'u2mc-api_key' );
		register_setting( 'users2mailchimp-settings', 'u2mc-list' );
		register_setting( 'users2mailchimp-settings', 'u2mc-group' );
		register_setting( 'users2mailchimp-settings', 'u2mc-subgroup' );
		register_setting( 'users2mailchimp-settings', 'u2mc-use_roles' );
		register_setting( 'users2mailchimp-settings', 'u2mc-needconfirm' );
		register_setting( 'users2mailchimp-settings', 'u2mc-accept' );
		add_option( 'u2mc-accept','1' ); // by default YES
		
	}
	
	public static function admin_notices() { 
		if ( !empty( self::$notices ) ) {
			foreach ( self::$notices as $notice ) {
				echo $notice;
			}
		}
	}
	
	/**
	 * Adds the admin section.
	 */
	public static function admin_menu() {
		$admin_page = add_users_page(
				
				__( 'Users2MailChimp' ),
				__( 'Users2MailChimp' ),
				'manage_options',
				'users2mailchimp',
				array( __CLASS__, 'users2mailchimp' )
		);
		
	}
	
	/**
	 * Show Groups MailChimp setting page.
	 */
	public static function users2mailchimp () {
	?>
	<div class="wrap">
	<h2><?php echo __( 'Users 2 MailChimp', USERS2MAILCHIMP_DOMAIN ); ?></h2>
	<?php 
	if ( isset( $_POST['submit'] ) ) {
	
		add_option( 'u2mc-api_key', $_POST['api_key'] ); // WP 3.3.1 : update alone wouldn't create the option when value is false
		update_option( 'u2mc-api_key', $_POST['api_key'] );
		
		add_option( 'u2mc-list', $_POST['list'] ); // WP 3.3.1 : update alone wouldn't create the option when value is false
		update_option( 'u2mc-list', $_POST['list'] );
	
		add_option( 'u2mc-group', $_POST['group'] ); // WP 3.3.1 : update alone wouldn't create the option when value is false
		update_option( 'u2mc-group', $_POST['group'] );

		add_option( 'u2mc-subgroup', $_POST['subgroup'] ); // WP 3.3.1 : update alone wouldn't create the option when value is false
		update_option( 'u2mc-subgroup', $_POST['subgroup'] );
		
		if ( isset ( $_POST['use_roles'] ) ) {
			add_option( 'u2mc-use_roles', $_POST['use_roles'] ); // WP 3.3.1 : update alone wouldn't create the option when value is false
			update_option( 'u2mc-use_roles', $_POST['use_roles'] );
		} else {
			add_option( 'u2mc-use_roles', "0" ); // WP 3.3.1 : update alone wouldn't create the option when value is false
			update_option( 'u2mc-use_roles', "0" );
		}

		add_option( 'u2mc-needconfirm', $_POST['needconfirm'] ); // WP 3.3.1 : update alone wouldn't create the option when value is false
		update_option( 'u2mc-needconfirm', $_POST['needconfirm'] );
		
		add_option( 'u2mc-accept', $_POST['accept'] );
		update_option( 'u2mc-accept', $_POST['accept'] );
		
	} elseif ( isset( $_POST['generate'] ) ) {
		Users2Mailchimp::synchronize();
	}
	
	?>
	<form method="post" action="">
	    <table class="form-table">
	        <tr valign="top">
	        <th scope="row"><?php echo __( 'API Key:', USERS2MAILCHIMP_DOMAIN ); ?></th>
	        <td>
	        	<input type="text" name="api_key" value="<?php echo get_option('u2mc-api_key'); ?>" />
	        	<p class="description"><?php echo __( 'MailChimp API KEY. You can get it in MailChimp: Account -> API Keys & Authorized Apps ', USERS2MAILCHIMP_DOMAIN  ); ?></p>
	        </td>
	        </tr>
	         
	        <tr valign="top">
	        <th scope="row"><?php echo __( 'List name:', USERS2MAILCHIMP_DOMAIN ); ?></th>
	        <td><input type="text" name="list" value="<?php echo get_option('u2mc-list'); ?>" /></td>
	        </tr>
	    
	        <tr valign="top">
	        <th scope="row"><?php echo __( 'Group name:', USERS2MAILCHIMP_DOMAIN ); ?></th>
	        <td><input type="text" name="group" value="<?php echo get_option('u2mc-group'); ?>" /></td>
	        </tr>
	  
	        <tr valign="top">
	        <th scope="row"><?php echo __( 'Subgroup name:', USERS2MAILCHIMP_DOMAIN ); ?></th>
	        <td><input type="text" name="subgroup" value="<?php echo get_option('u2mc-subgroup'); ?>" /></td>
	        </tr>
	  
	  		<tr valign="top">
	        <th scope="row"><?php echo __( 'Use roles as subgroups:', USERS2MAILCHIMP_DOMAIN ); ?></th>
	        <td>
	        <?php
	        $check = ""; 
	        if ( get_option('u2mc-use_roles')=="1" )
	        	$check = "checked";
	        ?>
	        	<input type="checkbox" name="use_roles" value="1" <?php echo $check;?>>
	        	<p class="description"><?php echo __( 'Creates as many subgroups as there are roles (if checked, "Subgroup name" is ignored).', USERS2MAILCHIMP_DOMAIN  ); ?></p>
	        </td>
	        </tr>
	  
	        <tr valign="top">
	        <th scope="row"><?php echo __( 'Need to accept the subscription:', USERS2MAILCHIMP_DOMAIN ); ?></th>
	        <td>
	        	<select name="accept">
	        	<?php 
				if (get_option('u2mc-accept') == "1") {
	        	?>
  					<option value="1" SELECTED><?php echo __( 'YES', USERS2MAILCHIMP_DOMAIN ); ?></option>
  				<?php 
  				} else {
  				?>
  					<option value="1"><?php echo __( 'YES', USERS2MAILCHIMP_DOMAIN ); ?></option>
  				<?php 
  				}
  				if (get_option('u2mc-accept') == "0") {
	        	?>
  					<option value="0" SELECTED><?php echo __( 'NO', USERS2MAILCHIMP_DOMAIN ); ?></option>
  				<?php 
  				} else {
  				?>
  					<option value="0"><?php echo __( 'NO', USERS2MAILCHIMP_DOMAIN ); ?></option>
  				<?php 
  				}
	        	?>
  				</select> 
	        	
	        	<p class="description"><?php echo __( 'Shows a "Subscribe me ..." message that user must accept to be added to newsletter.' ); ?></p>
  				
	        </tr>
	  
	  		<tr valign="top">
	        <th scope="row"><?php echo __( 'Need email confirmation:', USERS2MAILCHIMP_DOMAIN ); ?></th>
	        <td>
	        	<select name="needconfirm">
	        	<?php 
				if (get_option('u2mc-needconfirm') == "1") {
	        	?>
  					<option value="1" SELECTED><?php echo __( 'YES', USERS2MAILCHIMP_DOMAIN ); ?></option>
  				<?php 
  				} else {
  				?>
  					<option value="1"><?php echo __( 'YES', USERS2MAILCHIMP_DOMAIN ); ?></option>
  				<?php 
  				}
  				if (get_option('u2mc-needconfirm') == "0") {
	        	?>
  					<option value="0" SELECTED><?php echo __( 'NO', USERS2MAILCHIMP_DOMAIN ); ?></option>
  				<?php 
  				} else {
  				?>
  					<option value="0"><?php echo __( 'NO', USERS2MAILCHIMP_DOMAIN ); ?></option>
  				<?php 
  				}
	        	?>
  				</select> 
	        	
	        	<p class="description"><?php echo __( 'Control whether a double opt-in confirmation message is sent. Abusing this may cause your mailchimp account to be suspended.' , USERS2MAILCHIMP_DOMAIN ); ?></p>
  				
	        </tr>
	  
	    </table>
	    
	    <?php submit_button(); ?>
	    <?php settings_fields( 'users2mailchimp-settings' ); ?>
	    
	</form>
	
	</div>
	
	<div class="wrap">
	<h3><?php echo __( 'Synchronize', USERS2MAILCHIMP_DOMAIN ); ?></h3>
	
	<form method="POST" action="">
	<table class="form-table">
		<tr>
	    	<th scope="row">
	    		<?php submit_button(__("Synchronize", USERS2MAILCHIMP_DOMAIN), "secondary", "generate");?>
	    	</th>
	        <td>
				<p class="description"><?php echo __("Use this for synchronize existing users in website with mailchimp. If the user has not subscribed, then he will not be synchronized.", USERS2MAILCHIMP_DOMAIN ); ?></p>
			</td>
		</tr>
	</table>
	</form>
	</div>
	<?php 
	}
	
	
	/**
	 * Plugin activation work.
	 * 
	 */
	public static function activate() {
			
	}
	
	/**
	 * Plugin deactivation work. Delete database table.
	 * 
	 */
	public static function deactivate() {
		
	}
	
	
}
Users2Mailchimp_Plugin::init();

