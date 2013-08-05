<?php
/*
Plugin Name: Planning Center Online Integration
Plugin URI: https://bitbucket.org/johnellmore/beltway-wp-pco
Description: Builds a supporting plugin for using the Planning Center Online API, and includes a few useful shortcodes and widgets.
Author: John Ellmore
Author URI: http://johnellmore.com
*/

// library connections
require_once('PlanningCenterOnline-API-Helper/PlanningCenterOnline.php');
require_once('access.php');
require_once('functions.php');

// admin management page
require_once(dirname(__FILE__).'/admin.php');

// scripts
require_once('scripts/recentsinging.php');