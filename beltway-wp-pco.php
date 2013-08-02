<?php
/*
Plugin Name: Planning Center Online Integration
Plugin URI: https://bitbucket.org/johnellmore/beltway-wp-pco
Description: Builds a supporting plugin for using the Planning Center Online API, and includes a few useful shortcodes and widgets.
Author: John Ellmore
Author URI: http://johnellmore.com
*/

require_once('functions.php');

class BeltwayPCO {
	function __construct() {
		
	}
}

session_start();
require_once('PlanningCenterOnline-API-Helper/src/com.rapiddigitalllc/PlanningCenterOnline.php');

// build PCO connection object
$pco = new PlanningCenterOnline(array(
	'key' => 'UWOjfizDtHMNwzRHqyHI',
	'secret' => 'e9b2D5jEoktAoUDzBi6cO3SVtbRv1YEOO11QUZcq',
	'debug' => true
));

// login to PCO
$callbackUrl = "http://{$_SERVER['HTTP_HOST']}{$_SERVER['SCRIPT_NAME']}";
$r = $pco->login($callbackUrl, PlanningCenterOnline::TOKEN_CACHE_FILE);
if (!$r) die('Login failed.');

// pick the Sundays to check
$sundaysToCheck = array();
$today = date('w');
$hour = date('G');
if ((1 <= $today && $today <= 3)
|| ($today == 4 && $hour < 13)) { // if today is Monday through Thursday @ 1pm
	// get previous two Sundays
	$sundaysToCheck[] = strtotime('-2 Sunday');
	$sundaysToCheck[] = strtotime('last Sunday');
} else { // today is Thursday @ 1pm through Sunday
	// get previous Sunday and this Sunday
	$sundaysToCheck[] = strtotime('last Sunday');
	$sundaysToCheck[] = strtotime('next Sunday');
}

// get all adult service plans from PCO
$plans = $pco->getPlansByServiceId(41853, true);

// find plans matching the chosen Sundays
function matchDateInPlanSearch($needle, $plan) {
	$time = strtotime($plan->dates);
	if ($needle < $time) return -1;
	else if ($needle > $time) return 1;
	else return 0;
}
$sundayPlans = array();
foreach ($sundaysToCheck as $s) {
	$foundIndex = binaryArraySearch($plans, $s, 'matchDateInPlanSearch');
	if ($foundIndex !== false) $sundayPlans[] = $plans[$foundIndex];
}

// get songs from the Sundays
$songs = array();
foreach ($sundayPlans as $plan) {
	$plan = $pco->getPlanById($plan->id);
	$day = (object)array('date' => $plan->dates, 'songs' => array());
	foreach ($plan->items as $item) {
		if ($item->type != 'PlanSong') continue;
		
		// specify defaults
		$song = (object)array(
			'title' => '',
			'author' => false,
			'copyright' => false,
			'spotify' => false,
			'amazon' => false,
			'itunes' => false
		);
		
		// fill in main info
		$song->title = $item->title;
		$song->author = $item->song->author;
		if (!empty($item->song->copyright)) {
			$song->copyright = $item->song->copyright;
		}
		
		// fill in media links
		foreach ($item->attachments as $a) {
			if (!isset($a->type)) continue;
			if ($a->type == 'AttachmentSpotify') {
				$song->spotify = $a->url;
			} else if ($a->type == 'AttachmentAmazon') {
				$song->amazon = $a->url;
			} else if ($a->type == 'AttachmentItunes') {
				$song->itunes = $a->url;
			}
		}
		
		$day->songs[] = $song;
	}
	$songs[] = $day;
}

// get organization details
$o = $pco->organization;
?>
<h1><?php echo $o->name; ?>: What We're Singing</h1>
<hr />
<?php foreach ($songs as $day) { ?>
<h2><?php echo $day->date; ?></h2>
<ul>
	<?php foreach($day->songs as $song) { ?>
	<li>
		<strong><?php echo $song->title; ?></strong><br />
		by <?php echo $song->author; ?>
		<?php if ($song->copyright) { ?>
		(<em>&copy; <?php echo $song->copyright; ?></em>)
		<?php } ?>
		<?php if ($song->spotify || $song->amazon || $song->itunes) { ?>
		<br />
		view on: <?php if ($song->spotify) { ?>
		<a href="<?php echo $song->spotify; ?>">Spotify</a>
		<?php } ?>
		<?php if ($song->amazon) { ?>
		<a href="<?php echo $song->amazon; ?>">Amazon</a>
		<?php } ?>
		<?php if ($song->itunes) { ?>
		<a href="<?php echo $song->itunes; ?>">iTunes</a>
		<?php } ?>
		<?php } ?>
	</li>
	<?php } ?>
</ul>
<?php } ?>