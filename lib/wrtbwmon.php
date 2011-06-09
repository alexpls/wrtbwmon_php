<?php
class WRTBWMON{

		public $usage_by_user;
		public $quota;
		public $errors;

		private $usage;
		private $aliases;
		private $stats;

		protected $db_file;
		protected $aliases_file;

		function __construct($DB_PATH, $ALIASES_PATH = Null, $BW_QUOTA = Null){
			try{
				$this->init_stats();
			} catch(MyException $e) {
				$this->errors[] = $e->getArray();
			}

			try{
				$this->init_db($DB_PATH);
			} catch(MyException $e) {
				$this->errors[] = $e->getArray();
			}

			try{
				if($ALIASES_PATH) {
					$this->init_aliases($ALIASES_PATH);
				}
			} catch(MyException $e) {
				$this->errors[] = $e->getArray();
			}

			if($this->errors){
				$this->render_errors();
			}

			// // TODO
			// $this->quota = $BW_QUOTA;
			// if($this->quota){
				
			// }

		}

		function render_errors($exit_on_fatal = True){
			// TODO is this ok ?
			if(!$this->errors){
				throw new MyException("Trying to render errors to HTML, but no errors exist!", $fatal = False);
			}

			$fatal = False;

			foreach($this->errors as $counter => $exception){
				foreach($exception as $message => $fatality){
					$output = "<strong>";
					if($fatality){
						$fatal = True;
						$output .= "Fatal ";
					}
					$output .= "Error:</strong> $message<br />";

					print($output);
				}
				unset($this->errors[$counter]);
			}
			if($exit_on_fatal){
				if($fatal){
					exit();
				}
			}
		}

		function init_stats(){
			/*
			Initialises a blank stats array.
			*/
			$stats = array(
				"num_known_users"	=> 0,
				"num_machines"  	=> 0,
				"total_down"		=> 0,
				"total_up"			=> 0
			);
			$this->stats = $stats;
		}

		function get_stats_array(){
			$this->stats = $stats;

			try{
				$counter = 0;
				while(!$this->stats["num_machines"]){
					if($counter == 1){
						throw new MyException("Stats error: It looks like you're trying to fetch the stats array, but no data could be extracted from your database!", $fatal = False);
					}
					$this->usage_array();
					$this->aliases_array();
					$counter++;
				}
			} catch (MyException $e) {
				$this->errors[] = $e->getArray();
				$this->render_errors();
				return False;
			}

			return $stats;
		}

		function init_db($PATH) {
			// Database validation.
			if(!$PATH) {
				throw new MyException("Database did not validate: You must specify a database path in init.php .", $fatal = True);
			}
			else if (!file_exists($PATH)) {
				throw new MyException("Database did not validate: $PATH does not exist.", $fatal = True);
			} else {
				$this->db_file = fopen($PATH, "r");
			}

			return True;
		}

		function init_aliases($PATH) {
			if(!file_exists($PATH)) {
				throw new MyException("Aliases file did not validate: $PATH does not exist.");
			} else {
				$this->aliases_file = fopen($PATH, "r");
			}

			return True;
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
				// Adding to stats...
				$this->stats["num_machines"] += 1;
				$this->stats["total_down"] += $line[1] * 1024 + $line[3] * 1024;
				$this->stats["total_up"] += $line[2] * 1024 + $line[4] * 1024;
			}

			$this->usage = $usage;
			return True;
		}

		function aliases_array(){
			/*
			Assigns $this->array variable as an array of processed data from the aliases text file.
			*/
			if(!$this->aliases_file){
				return False;
			}
			$aliases = array();
			$lines = $this->output_lines($this->aliases_file);
			foreach ($lines as $line){
				$line = explode(",", $line);
				$mac = $line[0];
				$user = $line[1];
				if (array_key_exists($user, $aliases)) {
					array_push($aliases[$user], $mac);
				} else {
					$aliases[$user] = array($mac);
				}
			}
			// Adding to stats.
			$this->stats["num_known_users"] = count($aliases);
			
			$this->aliases = $aliases;
			return True;
		}

		function calculate_usage_by_user(){
			/*
			Assigns $this->usage_by_user array with a tie-in of the usage 
			array and the aliases array (if one exists).

			EXAMPLE:
			array = (
				"steve" => array(
					"MAC #1" => array(
						"down"	=> 123
						"up"	=> 123
						"odown"	=> 123
						"oup"	=> 123
						"last"	=> "01-01-2001 01:01"
					)
				)
			)
			*/
			if(!$this->aliases && !$this->usage) {
				$this->aliases_array();
				$this->usage_array();
			}

			$usage_by_user = array();
			foreach($this->usage as $mac => $usage){
				$user = "";
				if($this->aliases){
					$user = recursive_array_search($mac, $this->aliases);
				}
				if($user){
					$usage_by_user[$user][$mac] = $usage;
				} else {
					// If the mac address does not match with one in
					// our aliases file, create the user: "unknown""
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

		function output_stats($output = "html"){

		}

		function output_as_table($display_offpeak = True){
			if($this->errors){
				$this->render_errors();
			}
			if(!$this->usage_by_user){
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