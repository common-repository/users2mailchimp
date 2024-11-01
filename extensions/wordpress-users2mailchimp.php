<?php

class Wordpress_Users2Mailchimp {

	public static function init() {
		$needaccept = get_option( 'u2mc-accept' );
		$needaccept = $needaccept=="1"? true : false;
		if ( $needaccept ) {
			add_action('register_form',array( __CLASS__, 'register_form'));
			add_action('user_register',array( __CLASS__, 'user_register'), 1);
		}
	}

	public static function register_form () {
		$u2mc_mailchimp = ( isset( $_POST['u2mc_mailchimp'] ) ) ? $_POST['u2mc_mailchimp']: '1';
		?>
		<p>
			<label for="u2mc_mailchimp"> <input type="checkbox"
				name="u2mc_mailchimp" id="u2mc_mailchimp" 
				value="<?php echo ($u2mc_mailchimp); ?>" /> <?php echo __('Subscribe me to newsletter.', USERS2MAILCHIMP_DOMAIN) ?>
			</label>
		</p>
		<?php
	}


	public static function user_register ($user_id) {
	    if ( isset( $_POST['u2mc_mailchimp'] ) ) {
	    	update_user_meta($user_id, 'u2mc_mailchimp', $_POST['u2mc_mailchimp']);
	    }
	}
}

Wordpress_Users2Mailchimp::init();
?>