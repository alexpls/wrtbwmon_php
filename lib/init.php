<?php

// Do you want to use fixtures? This should only ever be true in a testing environment.
$FIXTURES = True;

if(!$FIXTURES){
	// Define your filepaths here.
	define("WRTDB_PATH", 	"");
	define("ALIAS_PATH", 	"");
} else {
	// Load in fixtures.
	$WRTDB_PATH = getcwd() . "\\" . "fixtures\\usage.db";
	$ALIAS_PATH = getcwd() . "\\" . "fixtures\\alias.txt";
	define("WRTDB_PATH",	$WRTDB_PATH);
	define("ALIAS_PATH",	$ALIAS_PATH);
}

require_once("helpers.php");
require_once("exceptions.php");
require_once("wrtbwmon.php");

?>