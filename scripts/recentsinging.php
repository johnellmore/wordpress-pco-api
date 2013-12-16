<?php

class PCOWhatWeAreSinging {
	const TRANSIENT_CACHE_PREFIX = 'pco_singing_service_';
	
	function __construct() {
		add_shortcode('whatwearesinging', array(__CLASS__, 'whatwearesinging'));
	}
	
	static public function whatwearesinging($atts, $content = null) {
		extract(shortcode_atts(array(
			'servicetype' => '',
			'showauthor' => 'true',
			'showcopyright' => 'true',
			'showmedialinks' => 'true'
			
		), $atts));
		
		// verify service type
		if (empty($servicetype) || !is_numeric($servicetype))
			return 'No servicetype attribute given. Please specify a service type ID.';
		
		$no = array('false', 'no', 'hide');
		
		$showauthor = !in_array($showauthor, $no);
		$showcopyright = !in_array($showcopyright, $no);
		$showmedialinks = !in_array($showmedialinks, $no);
		
		// get song list
		$songs = self::getSongs($servicetype);
		$output = '<div class="whatwesing">'."\n";
		foreach ($songs as $i => $day) {
			$output .= '<div class="col col'.$i.' count2 width1">'."\n";
			$output .= '<h3 class="servicetitle">'.$day->date.'</h3>'."\n";
			$output .= '<ul class="songlist">'."\n";
			foreach ($day->songs as $song) {
				$output .= '<li class="song">'."\n";
				$output .= '<h4 class="songtitle">'.$song->title.'</h4>'."\n";
				if ($showauthor && $song->author) {
					$output .= '<span class="byline">by '.$song->author.'</span>'."\n";
				}
				if ($showcopyright && $song->copyright) {
					$output .= '<span class="copyright">(&copy; '.$song->copyright.')</span>'."\n";
				}
				// disable media links for now--links currently require PCO access
				if (false && $showmedialinks && ($song->spotify || $song->amazon || $song->itunes)) {
					$output .= '<span class="medialinks">'."\n";
					$output .= 'view on:'."\n";
					if ($song->spotify) {
						$output .= '<a class="spotify" href="'.$song->spotify.'">Spotify</a>'."\n";
					}
					if ($song->amazon) {
						$output .= '<a class="amazon" href="'.$song->amazon.'">Amazon</a>'."\n";
					}
					if ($song->itunes) {
						$output .= '<a class="itunes" href="'.$song->itunes.'">iTunes</a>'."\n";
					}
					$output .= '</span>'."\n";
				}
				$output .= '</li>'."\n";
			}
			$output .= '</ul>'."\n";
			$output .= '</div>'."\n";
		}
		$output .= '</div>'."\n";
		return $output;
	}
	
	static public function getSongs($serviceType) {
		$transient = self::TRANSIENT_CACHE_PREFIX.$serviceType;
		$cache = get_transient($transient);
		if (!$cache) {
			// cache doesn't exist, let's try to re-query PCO
			$pco = new PCOAccess();
			if ($pco->okay()) {
				// we've got PCO access
				$songs = self::queryForSongs($pco->api, $serviceType);
				// cache the song list again
				set_transient($transient, serialize($songs), 60*60*24);
				return $songs;
			} else {
				// we're not connected to PCO yet
				return false;
			}
		} else {
			$cache = maybe_unserialize($cache);
			if (!is_array($cache)) return false;
			return $cache;
		}
	}
	
	static private function queryForSongs(&$api, $serviceType) {
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
			if ($today == 0)
				$sundaysToCheck[] = strtotime('today');
			else
				$sundaysToCheck[] = strtotime('next Sunday');
		}
		
		// get all adult service plans from PCO
		$plans = $api->getPlansByServiceId($serviceType, true);
		
		// find plans matching the chosen Sundays
		$sundayPlans = array();
		foreach ($sundaysToCheck as $s) {
			$foundIndex = binaryArraySearch($plans, $s, array(__CLASS__, 'matchDateInPlanSearch'));
			if ($foundIndex !== false) $sundayPlans[] = $plans[$foundIndex];
		}
		
		// get songs from the Sundays
		$songs = array();
		foreach ($sundayPlans as $plan) {
			$plan = $api->getPlanById($plan->id);
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
						$song->spotify = $a->public_url;
					} else if ($a->type == 'AttachmentAmazon') {
						$song->amazon = $a->public_url;
					} else if ($a->type == 'AttachmentItunes') {
						$song->itunes = $a->public_url;
					}
				}
				
				$day->songs[] = $song;
			}
			$songs[] = $day;
		}
		return $songs;
	}
	
	static function matchDateInPlanSearch($needle, $plan) {
		$time = strtotime($plan->dates);
		if ($needle < $time) return -1;
		else if ($needle > $time) return 1;
		else return 0;
	}
}
new PCOWhatWeAreSinging;
