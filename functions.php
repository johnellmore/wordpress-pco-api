<?php

// performs binary search on numerically indexed array
// returning the index that matches (or false if not found)
function binaryArraySearch($haystack, $needle, $function) {
	$upperBound = count($haystack);
	$lowerBound = -1;
	while (abs($upperBound-$lowerBound) > 1) {
		// pick the next element to check
		$pick = floor($lowerBound + ($upperBound-$lowerBound)/2);
		
		// check element
		$result = call_user_func($function, $needle, $haystack[$pick]);
		
		// choose next slice based on result
		if ($result == -1) { // needle is before check
			$upperBound = $pick;
		} else if ($result == 1) { // needle is after check
			$lowerBound = $pick;
		} else {
			return $pick;
		}
		//echo 'trying again, range '.$lowerBound.':'.$upperBound."\n";
	}
	return false;
}