<?php
/*
Plugin Name: Planning Center Online Integration
Plugin URI: https://github.com/johnellmore/wordpress-pco-api
GitHub Plugin URI: https://github.com/johnellmore/wordpress-pco-api
Description: A supporting plugin for using the Planning Center Online API. Includes a few useful shortcodes.
Author: John Ellmore
Author URI: http://johnellmore.com
Version: 1.0
*/

// library connections
require_once('PCO-API.php');
require_once('access.php');
require_once('functions.php');

// admin management page
require_once(dirname(__FILE__).'/admin.php');

// scripts
require_once('scripts/recentsinging.php');
