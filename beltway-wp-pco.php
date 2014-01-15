<?php
/*
Plugin Name: Planning Center Online Integration
Plugin URI: https://bitbucket.org/johnellmore/beltway-wp-pco
Description: Builds a supporting plugin for using the Planning Center Online API, and includes a few useful shortcodes and widgets.
Author: John Ellmore
Author URI: http://johnellmore.com
*/

// library connections
require_once('PCO-API.php');
require_once('access.php');
require_once('functions.php');

// admin management page
require_once(dirname(__FILE__).'/admin.php');

// scripts
require_once('scripts/recentsinging.php');

function beltway_plugin_action_links( $links, $file ) {
	$plugin_file = basename(__FILE__);
	if (basename($file) == $plugin_file) {
		$links[] = '<a href="' . admin_url( 'options-general.php?page=pco_connect' ) . '">'.__( 'Settings' ).'</a>';
	}
	return $links;
}

add_filter( 'plugin_action_links', 'beltway_plugin_action_links', 10, 2 );
