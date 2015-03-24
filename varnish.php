<?php
/**
 * @package Varnish_Purge
 * @version 1.0
 */
/*
Plugin Name: Varnish
Plugin URI: https://github.com/newsworthy39/wordpress-varnish-plugin
Description: Purges varnish $post_id
Author: Michael g. Jensen <newsworthy39 @ github.com>
Version: 1.0
Author URI: http://mjay.me
*/

$output = array('Varnish notify result:');

// We will connect, to localhost, provide the proper HTTP verb,
// and disconnect. Remember to provide HTTP_host, to allow multiple
// wordpress installations, on the same varnish.
function varnish_post_stuff($slug) {

    // Since we're using localhost, according to the purge ACL in varnish.
    $url = sprintf('http://127.0.0.1%s', $slug);
    $curl = curl_init();

    // Setup the usual curl-stuff.
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT,1000);

    // HTTP easily allows you to extend the protocol with your methods, as long
    // as it follows the BNF. Guess, you didn't know that, huh? :)
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PURGE");
    curl_setopt($curl, CURLOPT_HTTPHEADER, array ( "Host: " . $_SERVER['HTTP_HOST'] ) );

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

    return $status;
}

// This servers as a handy helper, to attach additional uris, 
// you'd like to include in each purge.
function varnish_notify($post_id) {

    $slug_array = array( sprintf('/%s', basename(get_permalink( $post_id ) ) ), '/' );

    foreach( $slug_array as $slug ) {
    	 varnish_post_stuff($slug);
    }
}

// Now we set that function up to execute when the admin_notices action is called
add_action( 'save_post', 'varnish_notify' );

?>
