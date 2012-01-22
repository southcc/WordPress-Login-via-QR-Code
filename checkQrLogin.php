<?php require('../../../wp-blog-header.php');

	global $wpdb;
	if (isset($_GET['qrHash'])){
		$hash = mysql_real_escape_string($_GET['qrHash']);
		if ($hash != 'used'){
			$qrUserLogin = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."qrLogin WHERE hash = '".$hash."'");
			$user_login = $qrUserLogin[0]->uname;

			if ($user_login != 'guest'){

		        $user = get_userdatabylogin($user_login);
		        $user_id = $user->ID;

		        wp_set_current_user($user_id, $user_login);
		        wp_set_auth_cookie($user_id);
		        do_action('wp_login', $user_login);
				
				$mylink = $wpdb->get_results("UPDATE ".$wpdb->prefix."qrLogin SET hash = 'used' WHERE uname = '".$user_login."'");
				echo '<script type="text/javascript">window.location = "'.get_bloginfo("url").'/wp-admin/";</script>';
								
			}
		}
	} else {
		header('Location: '.get_bloginfo('url'));	
	} 

?>