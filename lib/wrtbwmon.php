<?php

/*
wrtbwmon_php
*/

function human_size($size, $decimals = 1){
	// http://www.jonasjohn.de/snippets/php/readable-filesize.htm
	$mod = 1024;

	$units = explode(" ", "B KB MB GB TB PB");
	for ($i = 0; $size > $mod; $i++){
		$size /= $mod;
	}
	return round($size, 2) . ' ' . $units[$i];
}

class WRTBWMON{

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
			// **Really needs to be worked on...
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
				$usage[$line[0]] = array(
					"down"	=> $line[1] * 1024,
					"up"	=> $line[2] * 1024,
					"odown"	=> $line[3] * 1024,
					"oup"	=> $line[4] * 1024,
					"last"	=> $line[5]
				);
			}
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

		function array_values_as_cells($array, $hidden_keys = ""){
			$output = "";
			if(!$hidden_keys){
				foreach($array as $key => $value) {
					$output .= "<td>$value</td>";
				}
			} else {
				$hidden_keys = explode(" ", $hidden_keys);
				foreach($array as $key => $value) {
					if(!in_array($key, $hidden_keys)){
						$output .= "<td>$value</td>";
					} else {
						$output .= "<td style=\"display: none;\">$value</td>";
					}
				}
			}
			return $output;
		}

		function output_as_table($display_offpeak = True){
			if($this->usage_by_user == False){
				$this->calculate_usage_by_user();
			}
?>

<table id="usage-by-user-table">
	<tr>
		<th>User</th>
		<th>MAC Address</th>
		<th>Data Down</th>
		<th>Data Up</th>
		<th<?php if ($display_offpeak == False) {?> style="display: none;"<?php } ?>>Offpeak Data Down</th>
		<th<?php if ($display_offpeak == False) {?> style="display: none;"<?php } ?>></th>
		<th>Last Seen</th>
	</tr>
	
	<?php 

		foreach($this->usage_by_user as $username => $machines){
			$i = 1;
			foreach($machines as $mac => $usage_by_machine){

				echo("<tr>");
				// Only print the username if this is the first row
				// the user appears in.
				if($i == 1) {
					echo("<td>$username</td>");
				} else {
					echo("<td></td>");
				}
				echo("<td>$mac</td>");
				foreach($this->usage_by_user[$username][$mac] as $key => $value) {
					if(in_array($key, explode(" ", "up down oup odown"))){
						$value = human_size($value);
						if($display_offpeak == False){
							if($key == "oup" || $key == "odown"){
								echo("<td style=\"display: none;\">$value</td>");
							} else {
								echo("<td>$value</td>");
							}
						} else {
							echo("<td>$value</td>");
						}
					} else if ($key == "last") {
						echo("<td>$value</td>");
					}
				}
				echo("</tr>");

				// Output a user totals row if user has more than one machine
				// and if users last machine has already been printed.
				if($i == count($this->usage_by_user[$username]) && count($this->usage_by_user[$username]) > 1) {
					echo("<tr class=\"user-totals-row\">");
					echo("<td></td><td></td>");

					$totals = array(
						"down" => human_size($this->user_total($username, "down")),
						"up" => human_size($this->user_total($username, "up")),
						"odown" => human_size($this->user_total($username, "odown")),
						"oup" => human_size($this->user_total($username, "oup"))
					);

					if($display_offpeak == True){
						echo($this->array_values_as_cells($totals));
					} else {
						echo($this->array_values_as_cells($totals, "oup odown"));
					}

					echo("<td></td>");

					echo("</tr>");
				}

				$i++;
			}
		}
		
		// Output grand totals row.
		echo("<tr class=\"totals-row\">");
		$totals = $this->total();
		array_walk($totals, function(&$size) {
			$size = human_size($size);
		});

		echo("<td></td><td></td>");

		if($display_offpeak == True){
			echo($this->array_values_as_cells($totals));
		} else {
			echo($this->array_values_as_cells($totals, "oup odown"));
		}

		echo("</tr>");

	?>

</table>

<?php
		} // ends output_as_table
} // ends WRTBWMON class
?>