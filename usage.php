<?php

/*

*/

?>

<?php

$DB_FILENAME = "usage.db";
$ALIASES_FILENAME = "alias.txt";

$db_filepath = getcwd() . '/' . $DB_FILENAME;
$db = fopen($db_filepath, "r");

$aliases_filepath = getcwd() . '/' . $ALIASES_FILENAME;
$al = fopen($aliases_filepath, "r");

$aliases = array();
while(true){
	$line = fgets($al);
	if($line == null) break;

	$alias = explode(",", $line);
	$aliases[$alias[0]] = $alias[1];
}

function human_size($size, $decimals = 1){

	$suffix = array("KB", "MB", "GB", "TB");
	$i = 0;

	while ($size >= 1024 && ($i < count($suffix) - 1)){
		$size /= 1024;
		$i++;
	}
	
	return round($size, $decmials). ' ' . $suffix[$i];

}

?>

<html>
<head>
	<title>Shakespeare Rd USAGE</title>
	<link rel="stylesheet" type="text/css" href="style.css" />
</head>

<body>

<table>
<tr>
<th>Alias</th>
<th>MAC Address</th>
<th>Data Down</th>
<th>Data Up</th>
<th>Last Seen</th>
</tr>
<?php

$total_users = 0;
$total_down  = 0;
$total_up    = 0;

while(true)
{
	$line = fgets($db);
	echo("<tr>");
	if($line == null) break;
	
	$entry = explode(",", $line);

	$alias = "";
	$mac   = $entry[0];
	$down  = $entry[1];
	$up    = $entry[2];
	$last  = $entry[5];

	## Adding to totals

	$total_users++;
	$total_down += $down;
	$total_up += $up;
	
	## Parsing
	
	### Known alias?
	if($aliases[$mac] == True) { $alias = $aliases[$mac]; };

	$down = human_size($down);
	$up   = human_size($up);

	$entry = array($alias, $mac, $down, $up, $last);

	## Output	
	foreach($entry as $td){
		echo("<td>" . $td . "</td>");
	}

	echo("</tr>");
}

?>

<tr>
	<td></td>
	<td></td>
	<td><strong><?php echo(human_size($total_down)); ?></strong></td>
	<td><strong><?php echo(human_size($total_up)); ?></strong></td>
</tr>
	
</table>

</body>

</html>

<?php
fclose($db);
fclose($al);
?>
