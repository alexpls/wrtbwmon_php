<?php

// Should we use fixtues? This variable should only ever be true in a testing environment.
$FIXTURES = True;

if(!$FIXTURES){
	// Define your filepaths here.
	define("WRTDB_PATH", 	"");
	define("ALIAS_PATH", 	"");
} else {
	$WRTDB_PATH = getcwd() . "\\" . "fixtures\\usage.db";
	$ALIAS_PATH = getcwd() . "\\" . "fixtures\\alias.txt";
	define("WRTDB_PATH",	$WRTDB_PATH);
	define("ALIAS_PATH",	$ALIAS_PATH);
}

require_once("wrtbwmon.php");

?>