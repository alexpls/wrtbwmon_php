<?php
function human_size($input, $desired_output = Null, $decimals = 2){
	/*
	TODO: Make it work with fractions eg: .25GB.
	TODO: Add support for unabbreviated unit types, eg: gigabytes, megabytes. etc.
	*/
	$mod = 1024;
	$units = explode(" ", "B KB MB GB TB PB");
	$size = 0;

	$pattern = "/(?P<size>\d+)(?P<prefix>" . implode("|", $units) . ")/i";
	preg_match($pattern, $input, $match);
	if(!$match OR $match['prefix'] == "B"){
		// No unit specified, so assume we're using bytes.
		// OR... Bytes already specified, so no additional processing needs to be done.
		// Easy does it!
		$size = (int)$input;
	} else {
		// Unit specified. Convert it into bytes for further processing.
		$place_in_array = array_search($match['prefix'], $units);
		$size = $match['size'] * pow($mod, $place_in_array);
	}

	if($desired_output == Null){
		// Assume that desired output is best fit.
		for($i = 0; $size > $mod; $i++){
		$size /= $mod;
		}
		return round($size, $decimals) . ' ' . $units[$i];
	} else if (in_array($desired_output, $units)) {
		// If we know the unit that was specified.
		$place_in_array = array_search($desired_output, $units);
		$new_size = $size / pow($mod, $place_in_array);
		return round($new_size, $decimals) . ' ' . $desired_output;
	} else {
		throw new Exception("Type error: Unknown unit \"$desired_output\" specified for conversion.");
	}
}

function recursive_array_search($needle,$haystack) {
	// Thanks buddel & tony @ http://php.net/manual/en/function.array-search.php
	foreach($haystack as $key=>$value) {
		$current_key=$key;
		if($needle===$value OR (is_array($value) && recursive_array_search($needle,$value) !== false)) {
			return $current_key;
		}
	}
	return false;
}
?>