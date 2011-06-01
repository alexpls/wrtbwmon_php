<?php
require_once("lib/init.php");
$bwmon = new WRTBWMON(WRTDB_PATH, ALIAS_PATH);
?>

<html>
<head>
	<title>WRTBWMON Stats</title>
	<link rel="stylesheet" type="text/css" href="css/base.css" />
</head>

<body>

<h1>WRTBWMON Stats</h1>

<?php $bwmon->output_as_table($display_offpeak = False); ?>
<?php // print_r($bwmon->usage_by_user); ?>

</body>

</html>