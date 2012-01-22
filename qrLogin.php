<?php 
/*
Plugin Name: QR Login
Plugin URI: http://www.jackreichert.com/plugins/qr-login/
Description: Lets WordPress users login using a QR code
Version: 0.1
Author: Jack Reichert
Author URI: http://www.jackreichert.com
License: GPL2
				
*/

// Enqueue script that creates and places QR-code on login page
wp_enqueue_script( 'qrLogin_js', plugins_url('/qrLogin.js', __FILE__), array( 'jquery' ) );
wp_localize_script( 'qrLogin_js', 'qrLoginAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );

// Creates Hash places as meta tag in header (for js to find) inserts into db.
function my_custom_login_logo() {
	$hash = md5(uniqid(rand(), true)); ?>
	<meta name="qrHash" content="<?php echo $hash; ?>"?>

<?php
	global $wpdb;
	$table_name = $wpdb->prefix . "qrLogin";
	$rows_affected = $wpdb->insert( $table_name, array( 'timestamp' => current_time('mysql'), 'uname' => 'guest', 'hash' => $hash ) );
	
}
// adds init to login header
add_action('login_head', 'my_custom_login_logo');

// The viewer will not be logged in
add_action( 'wp_ajax_nopriv_ajax-qrLogin', 'ajax_check_logs_in' );
function ajax_check_logs_in() {

	// Gets current time
	$time = time();
	while((time() - $time) < 30) {
		
		// get the submitted qrHash
		$qrHash = mysql_real_escape_string($_POST['qrHash']);
		global $wpdb;
		$qrUserLogin = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."qrLogin WHERE hash = '".$qrHash."'");
	 
	    if($qrUserLogin[0]->uname != 'guest') {
	    	header( "Content-Type: application/json" );
			echo json_encode($qrUserLogin[0]);
	        break;
	    }
	 
	    usleep(25000);
	}	
 
    // IMPORTANT: don't forget to "exit"
    exit;
}


// manage db version
global $qrLogin_db_version;
$qrLogin_db_version = "0.1";

// Sets up db
function jal_install() {
   global $wpdb;
   global $qrLogin_db_version;

   $table_name = $wpdb->prefix . "qrLogin";
      
   $sql = "CREATE TABLE " . $table_name . " (
	  id mediumint(9) NOT NULL AUTO_INCREMENT,
	  timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
	  hash text NOT NULL,
	  uname tinytext NOT NULL,
	  UNIQUE KEY id (id)
    );";

   require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
   dbDelta($sql);
 
   add_option("qrLogin_db_version", $qrLogin_db_version);
}

// Installs db on plugin activation
register_activation_hook(__FILE__,'jal_install');

// Admin page. Saves user to db.
function qrLogin_plugin_menu() {
	add_options_page('QR Login Plugin Options', 'QR Login', 'manage_options', 'qr-login', 'qrLogin_plugin_options');
}
function qrLogin_plugin_options() {
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	$current_user = wp_get_current_user();
	$hash = $_GET['qrHash'];
	echo '<p>Hello '.$current_user->user_login.'</p>';
	echo '<div class="wrap">';
	echo '<p>You have successfully logged in.</p>';
	echo '</div>';
	
	global $wpdb;
	if (isset($_GET['qrHash'])){
		$mylink = $wpdb->get_results("UPDATE ".$wpdb->prefix."qrLogin SET uname = '".$current_user->user_login."' WHERE hash = '".$hash."'");
	}
}
add_action('admin_menu', 'qrLogin_plugin_menu');

// Cleans db from extra entries hourly
function qr_cron_activate() {
	wp_schedule_event(time(), 'hourly', 'qr_hourly_clean');
}
function qr_clean_hourly(){
	global $wpdb;
	$mylink = $wpdb->get_results("DELETE FROM ".$wpdb->prefix."qrLogin WHERE uname = 'guest'");
}
register_activation_hook(__FILE__, 'qr_cron_activate');
add_action('qr_hourly_clean', 'qr_clean_hourly');

// Clears cron on deactivation
function qr_cron_deactivate() {
	wp_clear_scheduled_hook('qr_hourly_clean');
	qr_clean_hourly();
}
register_deactivation_hook(__FILE__, 'qr_cron_deactivate');
