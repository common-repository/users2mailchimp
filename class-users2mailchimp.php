<?php
/**
 * class-users2mailchimp.php
 *
 * Copyright (c) Antonio Blanco http://www.blancoleon.com
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
 */

/**
 * Groups Mailchimp class
 */
class Users2Mailchimp {

	public static function init() {
		if (!class_exists("MCAPI"))
			require_once 'API/MCAPI.class.php';

		add_action('user_register', array( __CLASS__, 'user_register' ) );
		
		add_action( 'edit_user_profile_update', array( __CLASS__, 'edit_user_profile_update' ) );
		add_action( 'personal_options_update', array( __CLASS__, 'edit_user_profile_update' ) );
		
		add_action( 'delete_user', array( __CLASS__, 'delete_user' ) );
		
		add_action( 'set_user_role', array( __CLASS__, 'edit_user_profile_update' ) );
		
		// extensions
		if (file_exists( USERS2MAILCHIMP_CORE_DIR . '/extensions/wordpress-users2mailchimp.php' ))
			require_once 'extensions/wordpress-users2mailchimp.php';
		
	}
	
	public static function user_register ( $user_id ) {
	
		$apikey = get_option('u2mc-api_key');
		$listname = get_option('u2mc-list');
		$groupname = get_option('u2mc-group');
		
		// subgroup
		$subgroupname = get_option('u2mc-subgroup');
		$use_roles = get_option('u2mc-use_roles');
		
		$user_info = get_userdata( $user_id );
		if ( $use_roles == "1" ) {
			$subgroupname = implode(",", $user_info->roles);
		}
		
		$needconfirm = get_option('u2mc-needconfirm');
		$he_want = get_user_meta($user_id, 'u2mc_mailchimp', true);
		
		$needaccept = get_option('u2mc-accept');
		$needaccept = $needaccept=="1"? true : false;
		if ( ( !$needaccept ) || ( $needaccept && $he_want ) ) {
			$api = new MCAPI($apikey);
			
			$retval = $api->lists();
			
			if ( $api->errorCode ) {
				error_log($api->errorMessage);
			} else {
			
				$listas = $retval["data"];
			
				$myList = null;
			
				if ( count ( $listas ) > 0 ) {
					foreach ($listas as $lista) {
						if ($lista["name"] == $listname)
							$myList = $lista;
					}
				}
			
				if ($myList !== null) {
					$grupos = $api->listInterestGroupings($myList["id"]);
			
					if ($grupos) {
						$groupingid = 0;
						$myGroup = null;
						foreach ($grupos as $grupo) {
							if ( $grupo['name'] == $groupname ) {
								$groupingid = $grupo['id'];
								$myGroup = $grupo;
							}
						}
				
						if ($groupingid !== 0) { // if exist the grouping
							
							// if subgroups not already exist, then create
							$subgroupsmc = $myGroup['groups'];
							$testGroups = explode(",", $subgroupname);
							foreach ($testGroups as $test) {
								if ( !in_array($test, $subgroupsmc) ) {
									$api->listInterestGroupAdd($myList["id"], $test);
								}
							}
							
							$merge_vars = array(
									'FNAME'=>$user_info->user_firstname,
									'LNAME'=>$user_info->user_lastname,
									'GROUPINGS'=>array(
											array('name'=>$groupname, "groups"=>$subgroupname),
									)
							);
							
							// By default this sends a confirmation email - you will not see new members
							// until the link contained in it is clicked!
							$retval = $api->listSubscribe( $myList["id"], $user_info->user_email, $merge_vars, "html", $needconfirm );
							
							if ( $api->errorCode ) {
								error_log($api->errorMessage);
							}
						} 
						
					}
				}
			}
		}
	}
	
	public static function edit_user_profile_update ( $user_id ) {
	
		
		$apikey = get_option('u2mc-api_key');
		$listname = get_option('u2mc-list');
		$groupname = get_option('u2mc-group');
		$subgroupname = get_option('u2mc-subgroup');
		$needconfirm = get_option('u2mc-needconfirm');
		$use_roles = get_option('u2mc-use_roles');
		$he_want = get_user_meta($user_id, 'u2mc_mailchimp', true);
		
		$needaccept = get_option('u2mc-accept');
		$needaccept = $needaccept=="1"? true : false;
		if ( ( !$needaccept ) || ( $needaccept && $he_want ) ) {
			$api = new MCAPI($apikey);
		
			$retval = $api->lists();
		
			$listas = $retval["data"];
		
			$myList = null;
		
			if ( count ( $listas ) > 0 ) {
				foreach ($listas as $lista) {
					if ($lista["name"] == $listname)
						$myList = $lista;
				}
			}
		
			if ($myList !== null) {
				$grupos = $api->listInterestGroupings($myList["id"]);
		
				if ($grupos) {
					$groupingid = 0;
					$myGroup = null;
					foreach ($grupos as $grupo) {
						if ( $grupo['name'] == $groupname ) {
							$groupingid = $grupo['id'];
							$myGroup = $grupo;
						}
					}
		
					if ($groupingid !== 0) {
	
						$user_info = get_userdata( $user_id );
							
						$use_roles = get_option('u2mc-use_roles');
						
						if ( $use_roles == "1" ) {
							$subgroupname = implode(",", $user_info->roles);
						}
						
						// if subgroups not already exist, then create
						$subgroupsmc = $myGroup['groups'];
						$testGroups = explode(",", $subgroupname);
						foreach ($testGroups as $test) {
							if ( !in_array($test, $subgroupsmc) ) {
								$api->listInterestGroupAdd($myList["id"], $test);
							}
						}
						
						$merge_vars = array(
								'FNAME'=>$user_info->user_firstname,
								'LNAME'=>$user_info->user_lastname,
								'GROUPINGS'=>array(
										array('name'=>$groupname, "groups"=>$subgroupname),
								)
						);
						
						$advice = $api->listUpdateMember($myList["id"], $user_info->user_email, $merge_vars);
	
						if ( $api->errorCode ) {
							error_log($api->errorMessage);
						}
							
					}
				}
			}
		}
	
	}
	
	public static function delete_user ( $user_id ) {
	
		
		$apikey = get_option('u2mc-api_key');
		$listname = get_option('u2mc-list');
		$groupname = get_option('u2mc-group');
		$subgroupname = get_option('u2mc-subgroup');
		$needconfirm = get_option('u2mc-needconfirm');
		
		$api = new MCAPI($apikey);
	
		$retval = $api->lists();
	
		$listas = $retval["data"];
	
		$myList = null;
	
		if ( count ( $listas ) > 0 ) {
			foreach ($listas as $lista) {
				if ($lista["name"] == $listname)
					$myList = $lista;
			}
		}
	
		if ($myList !== null) {
			$grupos = $api->listInterestGroupings($myList["id"]);
	
			if ($grupos) {
				$groupingid = 0;
				foreach ($grupos as $grupo) {
					if ( $grupo['name'] == $groupname ) {
						$groupingid = $grupo['id'];
					}
				}
	
				if ($groupingid !== 0) {
	
					$user_info = get_userdata( $user_id );
	
					$retval = $api->listUnsubscribe( $myList["id"], $user_info->user_email );
					
					if ( $api->errorCode ) {
						error_log($api->errorMessage);
					}
	
				}
			}
		}
	}
	
	public static function synchronize() {
		
		$apikey = get_option('u2mc-api_key');
		$listname = get_option('u2mc-list');
		$groupname = get_option('u2mc-group');
		$subgroupname = get_option('u2mc-subgroup');
		$needconfirm = get_option('u2mc-needconfirm');
		$use_roles = get_option('u2mc-use_roles');
		
		$needaccept = get_option('u2mc-accept');
		$needaccept = $needaccept=="1"? true : false;
		
		$api = new MCAPI($apikey);
		
		$retval = $api->lists();
		
		$listas = $retval["data"];
		
		$myList = null;
		
		if ( count ( $listas ) > 0 ) {
			foreach ($listas as $lista) {
				if ($lista["name"] == $listname)
					$myList = $lista;
			}
		}
		
		if ($myList !== null) {
			$grupos = $api->listInterestGroupings($myList["id"]);
		
			if ($grupos) {
				$groupingid = 0;
				$myGroup = null;
				foreach ($grupos as $grupo) {
					if ( $grupo['name'] == $groupname ) {
						$groupingid = $grupo['id'];
						$myGroup = $grupo;
					}
				}
		
				if ($groupingid !== 0) {
		
					if ( $needaccept ) {
						$users = get_users(array('meta_key' => 'u2mc_mailchimp', 'meta_value' => '1'));
					} else {
						$users = get_users();
					}
								
					$users_data = array();
					
					foreach ($users as $user) {
						$user_info = get_userdata( $user->ID );
						$he_want = get_user_meta($user->ID, 'u2mc_mailchimp', true);

						if ( ( !$needaccept ) || ( $needaccept && $he_want ) ) {
							$use_roles = get_option('u2mc-use_roles');
							
							if ( $use_roles == "1" ) {
								$subgroupname = implode(",", $user_info->roles);
							}
							
							// if subgroups not already exist, then create
							$subgroupsmc = $myGroup['groups'];
							$testGroups = explode(",", $subgroupname);
							foreach ($testGroups as $test) {
								if ( !in_array($test, $subgroupsmc) ) {
									$api->listInterestGroupAdd($myList["id"], $test);
								}
							}
							
							$merge_vars = array(
									'FNAME'=>$user_info->user_firstname,
									'LNAME'=>$user_info->user_lastname,
									'EMAIL'=>$user_info->user_email,
									'GROUPINGS'=>array(
											array('name'=>$groupname, "groups"=>$subgroupname),
									)
							);
							
							$users_data[] = $merge_vars;
						}	
					}
					
					$optin = false;
					if ( $needconfirm == "1" ) {
						$optin = true;
					}
					
					$up_exist = true; // yes, update currently subscribed users
					$replace_int = true; // no, add interest, don't replace
					
					$api->listBatchSubscribe($myList['id'],$users_data,$optin, $up_exist, $replace_int);
									
					
					if ( $api->errorCode ) {
						error_log($api->errorMessage);
					}
				}
			}
		}
	}
}
Users2Mailchimp::init();
