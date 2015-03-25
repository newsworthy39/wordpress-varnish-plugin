<?php
/**
 * @package Varnish_Purge
 * @version 1.0
 */
/*
Plugin Name: Varnish
Plugin URI: https://github.com/newsworthy39/wordpress-varnish-plugin
Description: Purges varnish, whenever a post is updated. Additional uri's may be purged, on several varnish-hosts.
Author: Michael g. Jensen <newsworthy39 @ github.com>
Version: 1.0
Author URI: http://mjay.me
*/

define("DEFAULT_INCLUDE_LIST", "/%s,/");
define("VARNISH_PLUGIN_DEFAULT_HOST_HEADER", $_SERVER['HTTP_HOST']);
define("VARNISH_PLUGIN_DEFAULT_IP_ADDR","localhost");  

// We will connect, to localhost, provide the proper HTTP verb,
// and disconnect. Remember to provide HTTP_host, to allow multiple
// wordpress installations, on the same varnish.
function varnish_post_stuff($slug) {

    // Since we're using localhost, according to the purge ACL in varnish. // the host header
    $hosts = split(',', get_option('varnish_plugin_ipaddr', VARNISH_PLUGIN_DEFAULT_IP_ADDR));

    foreach($hosts as $host) {

 	if ($host != "") {

	    $url = sprintf("http://%s%s", $host, $slug);
	    $curl = curl_init();

	    // Setup the usual curl-stuff.
	    curl_setopt($curl, CURLOPT_URL, $url);
	    curl_setopt($curl, CURLOPT_HEADER, false);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($curl, CURLOPT_TIMEOUT,1000);

	    // HTTP easily allows you to extend the protocol with your methods, as long
	    // as it follows the BNF. Guess, you didn't know that, huh? :)
	    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PURGE");

	    // the host header
	    $varnishHostHeader = get_option('varnish_plugin_hostheader', VARNISH_PLUGIN_DEFAULT_HOST_HEADER);

	    curl_setopt($curl, CURLOPT_HTTPHEADER, array ( sprintf("Host: %s", $varnishHostHeader ) ) );

	    // DO it (Todo: This is sequantial and inline. Perhaps some message-passing mechanism is favorable,
	    // for large site installations?)
	    curl_exec($curl);

	    // It was.. ?
	    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

	    /*
	    die("Error: call to URL $url failed with status $status, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
	    */

	    // close.
	    curl_close($curl);
	}
    }

    return $status;
}

// This servers as a handy helper, to attach additional uris, 
// you'd like to include in each purge.
function varnish_notify($post_id) {

    $include_list = split(',', get_option('varnish_plugin_include_list', DEFAULT_INCLUDE_LIST));

    $slug_array = array();

    foreach ($include_list as $listitem) {
	
	$slug_array []= sprintf($listitem, basename(get_permalink( $post_id ) ) );
     }

    foreach( $slug_array as $slug ) {
    	 varnish_post_stuff($slug);
    }
}

function varnish_plugin_menu() {
	add_options_page( 'Varnish plugin options', 'Varnish', 'manage_options', 'varnish_plugin_identifier_1', 'varnish_plugin_options');
	add_action('admin_init', 'register_varnish_settings');
}

function register_varnish_settings() {
	//register our settings
	register_setting( 'varnish_plugin_settings-group', 'varnish_plugin_ipaddr' );
	register_setting( 'varnish_plugin_settings-group', 'varnish_plugin_hostheader' );
	register_setting( 'varnish_plugin_settings-group', 'varnish_plugin_include_list' );
}

function varnish_plugin_options() {
?>
	<div class="wrap">
	<h2>Varnish plugin options</h2>
	<form method="post" action="options.php">

	<?php settings_fields('varnish_plugin_settings-group'); ?>
	<?php do_settings_sections('varnish_plugin_settings-group'); ?>


        <table class="form-table">

        <tr valign="top">
        <th scope="row">Varnish IP address(es) (Default: <?php echo VARNISH_PLUGIN_DEFAULT_IP_ADDR; ?>, specify multiple backends, semicolon-delimited)</th>
        <td><input type="text" name="varnish_plugin_ipaddr" value="<?php echo esc_attr( get_option('varnish_plugin_ipaddr',VARNISH_PLUGIN_DEFAULT_IP_ADDR) ); ?>" /></td>
        </tr>

        <tr valign="top">
        <th scope="row">Varnish HTTP Host-header (Default: <?php echo VARNISH_PLUGIN_DEFAULT_HOST_HEADER; ?>)</th>
        <td><input type="text" name="varnish_plugin_hostheader" value="<?php echo esc_attr( get_option('varnish_plugin_hostheader', VARNISH_PLUGIN_DEFAULT_HOST_HEADER) ); ?>" /></td>
        </tr>
         
        <tr valign="top">
        <th scope="row">Varnish include list. (For each save post, this will also be flushed. Semicolon-delimited, default: <?php echo DEFAULT_INCLUDE_LIST; ?>)</th>
        <td><input type="text" name="varnish_plugin_include_list" value="<?php echo esc_attr( get_option('varnish_plugin_include_list',DEFAULT_INCLUDE_LIST) ); ?>" /></td>
        </tr>
	    </table>

	<?php submit_button(); ?>
	</form>
	</div>
<?php
}

// Now we set that function up to execute when the admin_notices action is called
add_action( 'save_post', 'varnish_notify' );

// Lets hook up a menu,
add_action ( 'admin_menu', 'varnish_plugin_menu' );



?>
