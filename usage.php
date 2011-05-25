<?php

/*
wrtbwmon_php
*/

// This should point to the database that wrtbwmon creates.
$WRTBWMON_DB = getcwd() . "/" . "usage.db";
// Points to aliases file.
$ALIASES = getcwd() . "/" . "alias.txt";

function human_size($size, $decimals = 1){
	// http://www.jonasjohn.de/snippets/php/readable-filesize.htm
	$mod = 1024;

	$units = explode(" ", "B KB MB GB TB PB");
	for ($i = 0; $size > $mod; $i++){
		$size /= $mod;
	}
	return round($size, 2) . ' ' . $units[$i];
}

class BWDatabase{

		public $usage_by_user;
		public $quota;

		private $usage;
		private $aliases;

		protected $db_file;
		protected $aliases_file;

		function __construct($DB_PATH, $ALIASES_PATH){
			if(!$DB_PATH && !$ALIASES_PATH) raise;

			if ($this->validate_db($DB_PATH) != True) raise;
			$this->db_file = fopen($DB_PATH, "r");
			// if aliases validates...
			$this->aliases_file = fopen($ALIASES_PATH, "r");

			$this->calculate_usage_by_user();

		}

		function validate_db($DB_PATH){
			// Database validation.
			if (!file_exists($DB_PATH)) {
				return False;
			}
			return True;
		}

		function output_lines($text_file){
			$lines = array();
			while(True){
				$line = fgets($text_file);
				if($line == Null) break;
				array_push($lines, $line);
			}
			return $lines;
		}

		function usage_array(){
			$usage = array();
			$lines = $this->output_lines($this->db_file);
			foreach($lines as $line){
				$line = explode(",", $line);
				$usage[$line[0]]	=	array(
											"down"	=> $line[1],
											"up"	=> $line[2],
											"odown"	=> $line[3],
											"oup"	=> $line[4],
											"last"	=> $line[5]
										);
			} //ends foreach
			$this->usage = $usage;
		}

		function aliases_array(){
			$aliases = array();
			$lines = $this->output_lines($this->aliases_file);
			foreach ($lines as $line){
				$line = explode(",", $line);
				if (array_key_exists($line[1], $aliases)) {
					array_push($aliases[$line[1]], $line[0]);
				} else {
					$aliases[$line[1]] = array($line[0]);
				}
			}
			$this->aliases = $aliases;
		}

		function calculate_usage_by_user(){
			
			if(!$this->aliases && !$this->usage) {
				$this->aliases_array();
				$this->usage_array();
			}

			$usage_by_user = array();

			$users = array_keys($this->aliases);

			foreach($users as $user){
				$machines = $this->aliases[$user];
				foreach($machines as $machine){
					// if our machine has been registered by dd-wrt
					// in the database, then add it to $usage_by_user
					if(array_key_exists($machine, $this->usage)){
						$usage_by_user[$user][$machine] = $this->usage[$machine];
					}
				}
			}
			$this->usage_by_user = $usage_by_user;
		}

		function user_total($username, $attribute){
			if(!$this->usage_by_user) {
				$this->calculate_usage_by_user();
			}

			$total = 0;

			foreach($this->usage_by_user[$username] as $machine){
				$total += $machine[$attribute];
			}

			return $total;

		}

		function total(){
			if(!$this->usage) {
				$this->usage_array();
			}

			$totals_array = array(
				"down" => 0,
				"up" => 0,
				"odown" => 0,
				"oup" => 0
			);

			foreach($this->usage as $machine){
				foreach($machine as $key => $value){
					if(array_key_exists($key, $totals_array)){
						$totals_array[$key] += $value;
					}
				}
			}

			return $totals_array;

		}

		function output_as_table(){
			if($this->usage_by_user == False){
				$this->calculate_usage_by_user();
			}
?>

<table>
	<tr>
		<th>User</th>
		<th>MAC Address</th>
		<th>Data Down</th>
		<th>Data Up</th>
		<th>Offpeak Data Down</th>
		<th>Offpeak Data Up</th>
		<th>Last Seen</th>
	</tr>
	
	<?php 

		foreach(array_keys($this->usage_by_user) as $username){
			$i = 1;
			foreach(array_keys($this->usage_by_user[$username]) as $machine){
				
				$username = $username;
				$mac = $machine;

				echo("<tr>");
				if($i == 1) {
					echo("<td>$username</td>");
				} else {
					echo("<td></td>");
				}
				echo("<td>$mac</td>");
				foreach($this->usage_by_user[$username][$machine] as $key => $value) {
					if(in_array($key, explode(" ", "up down oup odown"))){
						$value = human_size($value * 1024);
					}

					echo("<td>$value</td>");
				}
				echo("</tr>");

				if($i == count($this->usage_by_user[$username])) {
					echo("<tr class=\"user-totals-row\">");
					echo("<td></td><td></td>");

					$totals = array(
						$this->user_total($username, "down"),
						$this->user_total($username, "up"),
						$this->user_total($username, "odown"),
						$this->user_total($username, "oup")
					);

					foreach($totals as $total){
						$total = human_size($total * 1024);
						echo("<td>$total</td>");
					}

					echo("<td></td>");

					echo("</tr>");
				}

				$i++;
			}
		}
		
		echo("<tr class=\"totals-row\">");
		$totals = $this->total();

		echo("<td></td><td></td>");
		foreach($totals as $total){
			$total = human_size($total * 1024);
			echo("<td>$total</td>");
		}

		echo("</tr>");
	?>

</table>

<?php
		} // ends output_as_table

} // ends BWDatabase



$bwdb = new BWDatabase($WRTBWMON_DB, $ALIASES);

?>

<html>
<head><title></title></head>

<body>

	<h1></h1>

	<?php $bwdb->output_as_table(); ?>

</body>

</html>