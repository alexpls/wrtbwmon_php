<?php
class WRTBWMON{
		/*
		TODO: Operation enabled without aliases textfile.
		*/

		public $usage_by_user;
		public $quota;

		private $usage;
		private $aliases;

		protected $db_file;
		protected $aliases_file;

		function __construct($DB_PATH, $ALIASES_PATH = Null, $BW_QUOTA = Null){
			if(!$DB_PATH && !$ALIASES_PATH){
				throw new Exception("Missing argument: This class requires $DB_PATH and $ALIASES_PATH to be set upon instatiation.");
			}
			
			$this->validate_db($DB_PATH);
			$this->db_file = fopen($DB_PATH, "r");
			// TODO: if aliases validates...
			$this->aliases_file = fopen($ALIASES_PATH, "r");
			$this->calculate_usage_by_user();
            
			// TODO
            $this->quota = $BW_QUOTA;
            if($this->quota){
                
            }

		}

		function validate_db($DB_PATH){
			// Database validation.
			// TODO: Needs to be worked on...
			if (!file_exists($DB_PATH)) {
				throw new Exception("Database did not validate: $DB_PATH does not exist.");
			}
		}

		function output_lines($text_file){
			/*
			Returns a zero-indexed array of all the lines in a supplied text file.

			RETURN EXAMPLE:
			$lines = array(
				0 => "this is the first line of your text file.",
				1 => "this is the second line of your text file.",
				2 => "this is the third line of your text file."
			);
			*/
			$lines = array();
			while(True){
				$line = fgets($text_file);
				if($line == Null) break;
				array_push($lines, $line);
			}
			return $lines;
		}

		function usage_array(){
			/*
			Asigns $this->usage variable as an array of processed data from the wrtbwmon usage database.

			So far the only processing going on is converting the original WRTBWMON's output of file sizes as KB to file sizes as bytes.

			EXAMPLE:
			$this->usage = array(
				"MAC ADDRESS" => array(
					"down" =>	"DOWNLOADED",
					"up =>		"UPLOADED",
					"odown" =>	"OFFPEAK DOWNLOADED",
					"oup" =>	"OFFPEAK UPLOADED",
					"last" =>	"LAST SEEN")
			);
			*/
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
			/*
			Assigns $this->array variable as an array of processed data from the aliases text file.
			*/
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

			foreach($this->usage as $mac => $usage){
				$user = recursive_array_search($mac, $this->aliases);
				if($user != false){
					$usage_by_user[$user][$mac] = $usage;
				} else {
					$usage_by_user["unknown"][$mac] = $usage;
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

		function output_html_tag($tag, $contents, $attributes = ""){
			$output = "<" . $tag;
			if($attributes != ""){
				$output .= " " . $attributes;
			}
			$output .= ">" . $contents . "</" . $tag . ">";

			return $output;
		}

		function array_values_as_cells($array, $hidden_keys = "", $attributes = ""){
			# TODO
			# there should be a separate function that handles td behaviour, its opts,
			# classes, etc.
			$output = "";
			if(!$hidden_keys){
				foreach($array as $key => $value) {
					$output .= $this->output_html_tag("td", $value, $attributes);
				}
			} else {
				$hidden_keys = explode(" ", $hidden_keys);
				foreach($array as $key => $value) {
					if(!in_array($key, $hidden_keys)){
						$output .= $this->output_html_tag("td", $value, $attributes);
					} else {
						$output .= $this->output_html_tag("td", $value, "style=\"display: none;\" " . $attributes);
					}
				}
			}
			return $output;
		}
        
		// TODO
        function stats_array(){
            /*
            Returns an array of statistics.
            */
            if($this->quota){
                
            }
        }

		function output_as_table($display_offpeak = True){
			if($this->usage_by_user == False){
				$this->calculate_usage_by_user();
			}
?>

<table id="usage-by-user-table">
	<tr class="yellow">
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
			echo($this->array_values_as_cells($totals, $class = "strong"));
		} else {
			echo($this->array_values_as_cells($totals, $hidden_keys = "oup odown", $attributes = "class=\"strong\""));
		}

		echo("<td></td></tr>");

	?>

</table>

<?php
		} // ends output_as_table
        
} // ends WRTBWMON class
?>