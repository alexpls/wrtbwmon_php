<?php

$FIXTURES = True;

if($FIXTURES == True){
	$WRTDB_PATH = getcwd() . "\\" . "fixtures\\usage.db";
	$ALIAS_PATH = getcwd() . "\\" . "fixtures\\alias.txt";
	define("WRTDB_PATH",	$WRTDB_PATH);
	define("ALIAS_PATH",	$ALIAS_PATH);
}

/*
define("WRTDB_PATH", 	"");
define("ALIAS_PATH", 	"");
*/

require_once("wrtbwmon.php");

?>