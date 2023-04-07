<!DOCTYPE html>
<html>
<head>
<title>Printer details</title>
<link href="css/layout.css" rel="stylesheet" type="text/css" />
<link href="css/nav_bar.css" rel="stylesheet" type="text/css" />
<link href="css/printer_details.css" rel="stylesheet" type="text/css" />
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" rel="stylesheet" type="text/css" />
<script src="http://code.jquery.com/jquery-latest.min.js"></script>
<script src="js/jquery.menu.js"></script>
</head>
<body>
<div class="content">
<?php
	include "nav_bar.php";
	include "conn.php";
	session_start();
	$printer_sn = $_GET["printer_sn"];
	$stmt = $conn->prepare("SELECT * FROM printers WHERE sn = ?");
	$stmt->bind_param("s", $printer_sn); 
	$stmt->execute();
	$result = $stmt->get_result();
	$row_printer = $result->fetch_assoc();
	$stmt->close();
	$printer_ip = $row_printer["ip"];
	// path for the "golden" allcv file
	$path = "files/printers/" . $row_printer["sn"] . "/" . $row_printer["sn"] . "_golden_allcv.txt";

	$port = 9100;
	$sec = 8;
	$usec = 0;
	$socket = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket\n");
	socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec'=>$sec, 'usec'=>$usec));
	socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec'=>$sec, 'usec'=>$usec));
	//check if printer is reachable
	if(@socket_connect($socket, $printer_ip, $port) == TRUE){
		$connection = "on";
	}
	else{
		$connection = "off";
	}
	// creating an allcv report each time the page is loaded
	$command = '! U1 getvar "allcv" \r\n';
	@socket_write($socket, $command, strlen($command));
	@socket_recv($socket, $response, 76800, MSG_WAITALL);
	
	$golden_allcv = fopen($path,"w") or die("Unable to create file");
	fwrite($golden_allcv, $response);
	fclose($golden_allcv);
	$allcv_content = file_get_contents($path);
	
	// function to parse the allcv file, searching for the sgd commands required to build the page
	function results($path, $names, $commands){
		$allcv_content = file_get_contents($path);
		foreach ($commands as $command){
			$pattern = "/^.*$command .*\$/m";
			if (preg_match($pattern, $allcv_content, $matches)){
				$result[] = $matches;
			}
		}		
		$result_array = array_column($result,"0");
		
		foreach ($result_array as $sgd_line){
			//filtering the allcv line by line assigning the values to variables
			//in a string like the following, the assignment will be
			//internal_wired.ip.protocol : permanent , Choices: all,bootp,dhcp,dhcp and bootp,gleaning only,rarp,permanent
			//|		$sgd			   |   | $set   ||$changable| $choices                                               |     
			sscanf($sgd_line, "%s : %[^\,] , %s %[^\t\n]", $sgd, $set, $changable, $choices);
			if(in_array($sgd, $commands)){
				$sgd_array[] = array('sgd_command' => trim($sgd), 'current' => trim($set), 'changable' => $changable, 'choices' => trim($choices));
			}
			$changable = NULL;
			$choices = NULL;
		}
		$i = 0;
		// returning only the required lines
		foreach($sgd_array as $sgd_filtered){
			$sgd_filtered["name"] = $names[$i];
			$final_array[$i] = $sgd_filtered;
			$i++;
		}
		return $final_array;
	}
	// function to extract the cm value only from the odometers
	function glory_to_the_metric_system($path, $duplets_name_command){
		foreach ($duplets_name_command as $duplet_name_command){
			$allcv_content = file_get_contents($path);
			$pattern = "/^.*" . $duplet_name_command["command"] . ".*\$/m";
			preg_match($pattern, $allcv_content, $match);
			$response = implode(" ", $match);
			$response = explode(" ", $response);
			$response_array[] = array($duplet_name_command["name"], $duplet_name_command["command"], $response[4] . " " . str_replace("CENTIMETERS", "cm", $response[5]));
		}
		return $response_array;
	}
	
	function checkboxes($final_array, $array_element){
		echo "<tr><td>" . $final_array[$array_element]["name"] . "</td><td>" . $final_array[$array_element]["current"] . "</td><td>";
		$options = explode(",", $final_array[$array_element]["choices"]);
		foreach ($options as $option){
			if($option == $final_array[$array_element]["current"]){
				$selected = "checked";
			}
			else{
				$selected = "";
			}	
			echo "<input type='radio' name='" . str_replace(".", "*", $final_array[$array_element]["sgd_command"]) . "' value='" . $option . "' " . $selected . "  required>" . $option;
		}
		echo "</td></tr>";
	}
	
	function select($final_array, $array_element){
		echo "<tr><td class='option'>" . $final_array[$array_element]["name"] . "</td><td class='current'>" . $final_array[$array_element]["current"] . "</td>";
		echo "<td><select name='" . str_replace(".", "*" ,$final_array[$array_element]["sgd_command"]) . "' id='type' required>";
		$options = explode(",", $final_array[$array_element]["choices"]);
		foreach ($options as $option){
			if($option == $final_array[$array_element]["current"]){
				$selected = "selected";
			}
			else{
				$selected = "";
			}
			echo "<option value='" . $option . "' " . $selected . ">" . $option . "</option>";
		}
		echo "</select>";
		echo "</td></tr>";
	}
	
	function minmax($final_array, $array_element){
		echo "<tr><td class='option'>" . $final_array[$array_element]["name"] . "</td><td class='current'>" . $final_array[$array_element]["current"] . "</td>";
		$values = explode("-", $final_array[$array_element]["choices"]);
		if(count($values) == 3){
			// removing empty elements
			$values = array_filter($values);
			// reindexing the array
			$values = array_values($values);
			// readding the - to the first value
			$values[0] = "-" . $values[0];
		}
		echo "<td><input type='number'  name='" . str_replace(".", "*" ,$final_array[$array_element]["sgd_command"]) . "' min='" . $values[0] . "' max='" . $values[1] . "' value ='" . $final_array[$array_element]["current"] . "' required> Min: " . $values[0] . ", Max: " . $values[1] . "</td></tr>";	
	}
	
	if($connection == "on"){
?>
		<div class="grid_container_main">
			<div id= "box_printer_tabs">
				<ul class="printer_menu">
					<li><button class="tablink" onclick="openPage('Info', this)" id="defaultOpen"><img src="img/info.png"></button></li>
					<li><button class="tablink" onclick="openPage('Configuration', this)" ><img src="img/configuration.png"></button></li>
					<li><button class="tablink" onclick="openPage('Connectivity', this)" ><img src="img/connectivity.png"></button></li>
					<li><button class="tablink" onclick="openPage('Troubleshooting', this)" ><img src="img/troubleshooting.png"></button></li>
				</ul>
			</div>

			<div id="box_data">
				<div id="Info" class="tabcontent">
					<h2><?php echo $row_printer["name"]; ?> - General info</h2>
					<div class="grid_container">
						<div class="grid_item" id="box_general_info">
							<h3>General information </h3>
							<table>
						<?php
								echo "<form action='printer_info_actions.php' method='post'>";
								echo "<tr><td>Printer name <input type='text' name='new_printer_name' value='" . $row_printer["name"] . "'></td>";
								echo "<input type='hidden' name='printer_sn' value='" . $row_printer["sn"] . "'>";
								echo "<td><input type='submit' value='Change name'></td>";
								echo "</tr></form>";
								echo "<tr><td>IP address</td><td>" . $printer_ip . "</td></tr>";
								
								echo "<tr><td>Associated groups</td>";
								$printer_groups = explode(",",$row_printer['groups']);
								foreach ($printer_groups as $group_found){
									$stmt = $conn->prepare("SELECT * FROM groups WHERE id = ?");
									$stmt->bind_param("s", $group_found); 
									$stmt->execute();
									$result_groups = $stmt->get_result();
									while($row_group = $result_groups->fetch_assoc()){
										$array_groups[] = $row_group;
									}
								}
								echo "<td>";
								if(!empty($array_groups)){
									foreach($array_groups as $group){
										echo "<button class='button_associations'><a href='group_details.php?group_id=" . $group["id"] . "'>" . $group["name"] . "</a></button>";
									}
								}
								echo "</td></tr>";
				
								echo "<tr><td>Associated paper</td>";
								echo "<td>";
								$stmt = $conn->prepare("SELECT * FROM paper WHERE id = ?");
								$stmt->bind_param("s", $row_printer['paper']); 
								$stmt->execute();
								$result_paper = $stmt->get_result();
								$paper = $result_paper->fetch_assoc();
								if(!is_null($paper)){
									echo "<button class='button_associations'><a href='media_details.php?paper_id=" . $paper["id"] . "'>" . $paper["name"] . "</a></button>";
								}
								echo "</td></tr>";
								
								echo "<tr><td>Associated ribbon</td>";
								echo "<td>";
								$stmt = $conn->prepare("SELECT * FROM ribbon WHERE id = ?");
								$stmt->bind_param("s", $row_printer['ribbon']); 
								$stmt->execute();
								$result_ribbon = $stmt->get_result();
								$ribbon = $result_ribbon->fetch_assoc();
								if(!is_null($ribbon)){
									echo "<button class='button_associations'><a href='media_details.php?paper_id=" . $ribbon["id"] . "'>" . $ribbon["name"] . "</a></button></td></tr>";
								}
								echo "</td></tr>";
								
								// creating an array with information names
								$names = array("Firmware version", "Firmware date", "LinkOS version", "Emulator enabled", "RTC date (US format)", "RTC time");
								// list of SGD commands to query the printer about general information
								$commands = array("appl.name", "appl.date", "appl.link_os_version", "apl.enable", "rtc.date", "rtc.time");
								$result_info = results($path, $names, $commands);
								foreach($result_info as $sgd_info){
									echo "<tr><td>" . $sgd_info["name"] . "</td><td>" . $sgd_info["current"] . "</td></tr>";
								}
							?>
							</table>
						</div>
						<div class="grid_item" id="box_info_logs">
							<h3>Last 5 entries in <a href='printer_logs.php?printer_id=<?php echo $row_printer['id'];?>'>printer log</a></h3>
							<table>
								<th>Type</th>
								<th>Message</th>
								<th>Date</th>
							<?php
								$stmt = $conn->prepare("SELECT * FROM alerts WHERE printer_id = ? ORDER BY `date` DESC LIMIT 5");
								$stmt->bind_param("s", $row_printer['id']); 
								$stmt->execute();
								$result_alerts = $stmt->get_result();
								while($alert = $result_alerts->fetch_assoc()){
									echo "<tr><td>" . $alert["alert_type"] . "</td>";
									echo "<td>" . $alert["alert_message"] . "</td>";
									echo "<td>" . $alert["date"] . "</td></tr>";
								}
							?>
							</table>
						</div>
						<div class="grid_item" id="box_info_odometer">
							<h3>Non resettable Odometers</h3>
						<?php
							if(str_starts_with($row_printer["model"], "ZQ") OR str_starts_with($row_printer["model"], "QLn")){
								$nonreset_odometers_length = array(
									array("name" => "Length of labels passed through the printer (calibration and feed included)", "command" => "odometer.total_print_length"),
									array("name" => "Length of actually consumed labels (subtract backward motion)", "command" => "odometer.net_media_length"));
							}
							else{
								$nonreset_odometers_length = array(
									array("name" => "Length of labels passed through the printer (calibration and feed included)", "command" => "odometer.total_print_length"),
									array("name" => "Length of actually consumed labels (subtract backward motion)", "command" => "odometer.net_media_length"), 
									array("name" => "Length of ribbon passed through the printer", "command" => "odometer.net_ribbon_length"));
							}
							$results_nonreset_odometers_length = glory_to_the_metric_system($path, $nonreset_odometers_length);
							echo "<table>";
							foreach($results_nonreset_odometers_length as $sgd_nonreset_odometers_length){
								echo "<tr><td>" . $sgd_nonreset_odometers_length[0] . "</td><td>" . $sgd_nonreset_odometers_length[2] . "</td></tr>";
							}
							$names = array("Number of labels passed through the printer", "Number of labels passed through the printer (calibration and feed excluded)",);
							$commands = array("odometer.media_marker_count", "odometer.total_label_count");
							$result_nonreset_odometer = results($path, $names, $commands);
							foreach($result_nonreset_odometer as $sgd_nonreset_odometer){
								echo "<tr><td>" . $sgd_nonreset_odometer["name"] . "</td><td>" . $sgd_nonreset_odometer["current"] . "</td></tr>";
							}
							echo "</table>";
						?>
							<h3>Resettable Odometers</h3>
						<?php
							$reset_odometers_count = array(
								array("name" => "Length since last printhead clean", "command" => "odometer.headclean"), 
								array("name" => "Length since last printhead replacement", "command" => "odometer.headnew"), 
								array("name" => "Length passed through the printer (1)", "command" => "odometer.media_marker_count1"), 
								array("name" => "Length passed through the printer (2)", "command" => "odometer.media_marker_count2"));
							$result_reset_odometers_count = glory_to_the_metric_system($path, $reset_odometers_count);
							echo "<table>";
							foreach($result_reset_odometers_count as $sgd_reset_odometers_count){
								echo "<tr><td>" . $sgd_reset_odometers_count[0] . "</td><td  class='odometers'>" . $sgd_reset_odometers_count[2] . "</td><td><a href='printer_info_actions.php?printer_sn=" . $row_printer["sn"] . "&action=reset_" . $sgd_reset_odometers_count[1] . "'><button class='button_options'>Click to reset</button></a></td></tr>";
							}
							if(str_starts_with($row_printer["model"], "ZQ") OR str_starts_with($row_printer["model"], "QLn")){
								$names = array(
									"Label passed through the printer <b>(used for inventory)</b>", 
									"Label passed through the printer", 
									"Number of times the printer lid has been opened");
								$commands = array(
									"odometer.user_label_count1", 
									"odometer.user_label_count2", 
									"odometer.latch_open_count");
							}
							else{
								$names = array(
									"Labels passed through the printer (1)<b> (used for inventory)</b>", 
									"Labels passed through the printer (2)", 
									"Number of cuts", 
									"Number of times the printhead (or printer lid) has been opened");
								$commands = array(
									"odometer.user_label_count1", 
									"odometer.user_label_count2", 
									"odometer.user_total_cuts", 
									"odometer.latch_open_count");	
							}
							$result_reset_odometer = results($path, $names, $commands);
							foreach($result_reset_odometer as $sgd_reset_odometer){
								echo "<tr><td>" . $sgd_reset_odometer["name"] . "</td><td>" . strtolower($sgd_reset_odometer["current"]) . "</td><td><a href='printer_info_actions.php?printer_sn=" . $row_printer["sn"] . "&action=reset_" . $sgd_reset_odometer["sgd_command"] . "'><button class='button_options'>Click to reset</button></a></td></tr>";
							}
							echo "</table>";
						?>
						</div>
						<div class="grid_item" id="box_info_filelist">
								<h3>Memory</h3>
							<?php
								$names = array("Total flash memory size" , "Free flash memory");
								$commands = array("memory.flash_size" , "memory.flash_free");
								$result_memory = results($path, $names, $commands);
								foreach($result_memory as $sgd_memory){
									echo "<p>" . $sgd_memory["name"] . ": " . intval($sgd_memory["current"]) / 1024 . " Kb</p>";
								}
								?>
							
							<h3>Drive E: Filelist</h3> 
							<h5><i>(DMP files are listed in the troubleshooting tab)</i></h5>
							<?php
								$file_types = array("ZPL" => "Formats/Label Template (.ZPL)", "TTF" => "True Type Font (.TTF)" , "FNT" => "Font (.FNT)", "DAT" => "Font encoding table (.DAT)", "GRF" => "Raw bitmap graphic (.GRF)", "PNG" => "Compressed graphic (.PNG)", "WML" => "User defined menu (.WML)", "NRD" => "Non readable files (.NRD - Network certificates)" );
								foreach($file_types as $ext => $type){
									$pattern = "/\* E:.*." . $ext . "  /m"; 
									if (preg_match_all($pattern, $allcv_content, $matches)){
										$array_files = array_merge(...$matches);
										echo "<p class='filetype'>" . $type . "</p>";
										echo "<table>";
										foreach ($array_files as $single_file){
											$filename = substr($single_file,4);
											echo "<tr><td class=filelist>" . $filename . "</td>";
											if($ext == "FNT" OR $ext == "TTF" OR $ext =="NRD" OR $ext =="DAT"){
												$hidden = "style='display: none'";
											}
											else{
												$hidden = "";
											}
											echo "<td class=filelist><a href='file_content.php?printer_sn=" . $row_printer["sn"] . "&filename=" . $filename . "' target='view_file'><button class ='file_actions' " . $hidden . ">View</button></a></td>";
											echo "<td class=filelist><a href='printer_info_actions.php?printer_sn=" . $row_printer["sn"] . "&action=download_file" . "&filename=" . $filename . "'><button class ='file_actions' " . $hidden . ">Download</button></a></td>";
											echo "<td class=filelist><a href='printer_info_actions.php?printer_sn=" . $row_printer["sn"] . "&action=delete_file" . "&filename=" . $filename . "'><button class ='file_actions'>Delete</button></a></td></tr>";
											
										}
										echo "</table>";
									}
								}
							?>
							</div>
						<div class="grid_item" id="box_info_view_file">
							<iframe id='all_files' src='file_content.php' name='view_file'></iframe>
						</div>
					</div>
				</div>
				<div id="Configuration" class="tabcontent">
					<h2><?php echo $row_printer["name"]; ?> - Configuration</h2>
					<div class="grid_container">
						<div class="grid_item" id="box_configuration_printing">
							<h3>Printing configuration</h3>
							<?php
							$names = array("Darkness", "Speed" , "Media type", "Media width",  "Media height", "Print orientation", "Vertical adjust", "Horizontal adjust", "Backfeed", "Media handling", "Print mode");
							$commands = array("print.tone_zpl", "media.speed", "ezpl.media_type", "ezpl.print_width", "zpl.label_length", "zpl.print_orientation", "zpl.label_top", "zpl.left_position", "media.backfeed", "media.printmode", "media.thermal_mode");
							$final_array = results($path, $names, $commands);
							// creating an array to export the configuration as a profile
							foreach($final_array as $sgd_config){
								$profile_config[$sgd_config['sgd_command']] = $sgd_config["current"];
							}
							echo "<form action='printer_configuration_actions.php' method='post'>";
							echo "<input type='hidden' name='printer_sn' value='" . $row_printer["sn"] . "'>";
							echo "<input type='hidden' name='config_type' value='printing_config'>";
							echo "<table>
									<th>Setting</th>
									<th>Current status</th>
									<th>Options</th>";
							// darkness
							minmax($final_array,0);
							// speed
							minmax($final_array,1);
							// media type
							checkboxes($final_array, 2);
							// media width
							minmax($final_array,3);
							// media length
							minmax($final_array,4);
							// orientation
							checkboxes($final_array, 5);
							// vertical adjust
							minmax($final_array,6);
							// horizontal adjust
							minmax($final_array,7);
							// backfeed
							select($final_array, 8);
							// print mode
							checkboxes($final_array, 9);
							if(isset($final_array[10])){
								// media handling
								select($final_array, 10);
							}
						?>
							</table>
							<p>
								<input type='radio' name='action' value='send_config' required>Send to printer
								<input type='radio' name='action' value='save_config'>Save as resource (filename: <i> <?php echo $row_printer["name"] . "_printing_" . date("d-m-Y") . ".zpl"; ?> </i>)
							</p>
							<p>
								<input type="submit" value="Submit">
								<input type="reset" value="Reset">
							</p>
							</form>
						</div>
						<div class="grid_item" id="box_configuration_device">
							<h3>Device configuration</h3>
						<?php
							$names = array("Head closure action", "Power on action" , "Display printing counter", "Device language",  "Plug and play option", "XML");
							$commands = array("ezpl.head_close_action", "ezpl.power_up_action", "display.batch_counter", "device.languages", "device.pnp_option", "device.xml.enable");
							$final_array = results($path, $names, $commands);
							echo "<form action='printer_configuration_actions.php' method='post'>";
							echo "<input type='hidden' name='printer_sn' value='" . $row_printer["sn"] . "'>";
							echo "<input type='hidden' name='config_type' value='device_config'>";
							echo "<table>
									<th>Setting</th>
									<th>Current status</th>
									<th>Options</th>";
							foreach($final_array as $sgd_config){
								// creating an array to export the configuration as a profile
								$device_config[$sgd_config['sgd_command']] = $sgd_config["current"];
								// printing the current configuration
								echo "<tr><td class='option'>" . $sgd_config["name"] . "</td><td class='current'>" . $sgd_config["current"] . "</td><td>";
								$options = explode(",", $sgd_config["choices"]);
								foreach ($options as $option){
									if($option == $sgd_config["current"]){
										$selected = "checked";
									}
									else{
										$selected = "";
									}
									echo "<input type='radio' name='" . str_replace(".", "*", $sgd_config["sgd_command"]) . "' value='" . $option . "' " . $selected . " required>" . $option;
								}
								echo "</td></tr>";
							}
						?>
							</table>
							<p>
								<input type='radio' name='action' value='send_config' required>Send to printer
								<input type='radio' name='action' value='save_config'>Save as resource (filename: <i> <?php echo $row_printer["name"] . "_config_" . date("d-m-Y") . ".zpl"; ?> </i>)
							</p>
							<p>
								<input type="submit" value="Submit">
								<input type="reset" value="Reset">
							</p>
							</form>
						</div>
						<div class="grid_item" id="box_configuration_power">
							<h3>Power management configuration</h3>
						<?php
							//checking if printer has battery
							$names = array("Battery");
							$commands = array("device.feature.battery");
							$battery_check = results($path, $names, $commands);
							if($battery_check[0]["current"] == "not available"){
								$battery = "no";
								$names = array("Energy star", "Energy star timeout");
								$commands = array("power.energy_star.enable", "power.energy_star.timeout");
							}
							else{
								$battery = "yes";
								$names = array("Power sleep", "Power sleep timeout",  "Power sleep cradle", "Power sleep unassociated");
								$commands = array("power.sleep.enable", "power.sleep.timeout", "power.sleep.cradle", "power.sleep.unassociated");
							}
							$final_array = results($path, $names, $commands);
							// creating an array to export the configuration as a profile
							foreach($final_array as $sgd_config){
								$power_config[$sgd_config['sgd_command']] = $sgd_config["current"];
							}
							echo "<form action='printer_configuration_actions.php' method='post'>";
							echo "<input type='hidden' name='printer_sn' value='" . $row_printer["sn"] . "'>";
							echo "<input type='hidden' name='config_type' value='power_config'>";
							echo "<table>
									<th>Setting</th>
									<th>Current status</th>
									<th>Options</th>";
							if($battery == "no"){
								// energy star
								checkboxes($final_array, 0);
								// energy star timeout
								minmax($final_array, 1);
							}
							elseif($battery == "yes"){
								// power sleep
								checkboxes($final_array, 0);
								// power sleep timeout
								minmax($final_array, 1);
								// power sleep cradle
								checkboxes($final_array, 2);
								// power sleep unassociated
								checkboxes($final_array, 3);
							}
						?>
							</table>
							<p>
								<input type='radio' name='action' value='send_config' required>Send to printer
								<input type='radio' name='action' value='save_config'>Save as resource (filename: <i> <?php echo $row_printer["name"] . "_printing_" . date("d-m-Y") . ".zpl"; ?> </i>)
							</p>
							<p>
								<input type="submit" value="Submit">
								<input type="reset" value="Reset">
							</p>
							</form>
						</div>
						<div class="grid_item" id="box_save_configuration_profile">
							<h3>Save the current printer profile as resource</h3>
							<form action='printer_configuration_actions.php' method='post'>
							<?php
								$printer_profile = array_merge($profile_config, $device_config, $power_config);
								echo "<input type='hidden' name='printer_sn' value='" . $row_printer["sn"] . "'>
										<input type='hidden' name='action' value='save_full_config'>
										<input type='hidden' name='configuration_profile' value='" . json_encode($printer_profile) . "'>";
							?>
								<input type="submit" value="Submit">
							</form>
						</div>
					</div>
				</div>
				<div id="Connectivity" class="tabcontent">
					<h2><?php echo $row_printer["name"]; ?> - Connectivity info</h2>
					<div class="grid_container">
						<div class="grid_item" id="box_connectivity_info">
							<h3>Network status</h3>
						<?php 
							$names = array("Active connection", "IP address", "Subnet mask", "IP Gateway", "IP assignment");
							$commands = array("interface.network.active.printserver", "interface.network.active.ip_addr", "interface.network.active.netmask", "interface.network.active.gateway", "wlan.ip.protocol");
							$final_array = results($path, $names, $commands);
							// creating an array to export the configuration as a profile
							foreach($final_array as $sgd_network){
								$profile_network_wired[$sgd_network['sgd_command']] = $sgd_network["current"];
							}
							echo "<form action='printer_connectivity_actions.php' method='post'>";
							echo "<input type='hidden' name='printer_sn' value='" . $row_printer["sn"] . "'>";
							echo "<input type='hidden' name='config_type' value='network_config'>";
							echo "<table>
									<th>Option</th>
									<th>Current status</th>
									<th>Options</th>";
							// setting different sgd command accordingly to the network interface in use
							if($final_array[0]["current"] == "internal wired"){
								$network_interface = "wired";
								$new_ip = "internal_wired.ip.addr";
								$new_netmask = "internal_wired.ip.netmask";
								$new_gateway = "internal_wired.ip.gateway";
							}
							
							if($final_array[0]["current"] == "wireless"){
								$network_interface = "wireless";
								$new_ip = "wlan.ip.addr";
								$new_netmask = "wlan.ip.netmask";
								$new_gateway = "wlan.ip.gateway";
							}
							echo "<input type='hidden' name='network_interface' value='" . $network_interface . "'>";
							// printing the current configuration
							// active connection
							echo "<tr><td class='option'>" . $final_array[0]["name"] . "</td><td class='current'>" . $final_array[0]["current"] . "</td><td></td></tr>";
							// IP address
							echo "<tr><td>" . $final_array[1]["name"] . "</td><td>" . $final_array[1]["current"] . "</td><td><input type='text' name='" . str_replace(".", "-", $new_ip) . "' value='" . $final_array[1]["current"] . "'></td></tr>";
							// subnet mask
							echo "<tr><td>" . $final_array[2]["name"] . "</td><td>" . $final_array[2]["current"] . "</td><td><input type='text' name='" . str_replace(".", "-", $new_netmask) . "' value='" . $final_array[2]["current"] . "'></td></tr>";
							// gateway IP
							echo "<tr><td>" . $final_array[3]["name"] . "</td><td>" . $final_array[3]["current"] . "</td><td><input type='text' name='" . str_replace(".", "-", $new_gateway) ."' value='" . $final_array[3]["current"] . "'></td></tr>";
							// type of IP assignment
							checkboxes($final_array, 4);
							
							// checking if printer has wireless capabilities
							$filter_wifi_capability = "device.feature.802_11ac : present";
							// if so, some additional rows are added to the table
							if(str_contains($allcv_content, $filter_wifi_capability)){
								$names = array("WiFi Associated" , "Current SSID", "Encryption protocol", "Country code");
								// list of SGD commands to query the printer about wireless information
								$commands = array("wlan.associated", "wlan.current_essid", "wlan.wpa.authentication", "wlan.country_code");
								$final_array = results($path, $names, $commands);	
								// creating an array to export the configuration as a profile
								foreach($final_array as $sgd_network_wireless){
									$profile_network_wireless[$sgd_network_wireless['sgd_command']] = $sgd_network_wireless["current"];
								}
								// printing the current configuration
								// wifi associated
								echo "<tr><td class='option'>" . $final_array[0]["name"] . "</td><td class='current'>" . $final_array[0]["current"] . "</td><td></td></tr>";
								// SSID associated
								echo "<tr><td class='option'>" . $final_array[1]["name"] . "</td><td class='current'>" . $final_array[1]["current"] . "</td><td></td></tr>";
								// wifi encryption
								checkboxes($final_array, 2);
								// country code
								select($final_array, 3);
								
								echo "</table>";	
							}
							else{
								echo "No WiFi capability";
							}
							?>
							<p>
								<input type='radio' name='action' value='send_config' required>Send to printer
								<input type='radio' name='action' value='save_config'>Save as resource (filename: <i> <?php echo $row_printer["name"] . "_network_config_" . date("d-m-Y") . ".zpl"; ?> </i>)
							</p>
							<p>
								<input type="submit" value="Submit">
								<input type="reset" value="Reset">
							</p>
							</form>
						</div>
						<div class="grid_item" id="box_connectivity_bt">
							<h3>Bluetooth status</h3>
						<?php 
							$names = array("Discoverable" , "BT Friendly name", "Enabled", "Connected", "Type (LE or Standard)", "Security level");
							$commands = array("bluetooth.discoverable" ,"bluetooth.friendly_name" , "bluetooth.enable", "bluetooth.connected", "bluetooth.le.controller_mode", "bluetooth.minimum_security_mode");
							$final_array = results($path, $names, $commands);
							echo "<form action='printer_connectivity_actions.php' method='post'>";
							echo "<input type='hidden' name='printer_sn' value='" . $row_printer["sn"] . "'>";
							echo "<input type='hidden' name='config_type' value='bt_config'>";
							echo "<table>
									<th>Option</th>
									<th>Current status</th>
									<th>Options</th>";
							foreach($final_array as $sgd_bt){
								// creating an array to export the configuration as a profile
								$profile_bt[$sgd_bt['sgd_command']] = $sgd_bt["current"];
								// printing the current configuration
								echo "<tr><td class='option'>" . $sgd_bt["name"] . "</td><td class='current'>" . $sgd_bt["current"] . "</td><td>";
								$options = explode(",", $sgd_bt["choices"]);
								foreach ($options as $option){
									if(trim($option) == $sgd_bt["current"]){
										$selected = "checked";
									}
									else{
										$selected = "";
									}
									if($sgd_bt["name"] == "Connected"){
										echo "";
									}
									elseif($sgd_bt["name"] == "BT Friendly name"){
										echo "<input type='text' name='bluetooth-friendly_name' value='" . $sgd_bt["current"] . "'>";
									}
									else{
										echo "<input type='radio' name='" . str_replace(".", "-", $sgd_bt["sgd_command"]) . "' value='" . $option . "' " . $selected . "  required>" . $option;
									}
								}
								echo "</td></tr>";
							}
						?>
							</table>
							<p>
								<input type='radio' name='action' value='send_config' required>Send to printer
								<input type='radio' name='action' value='save_config'>Save as resource (filename: <i> <?php echo $row_printer["name"] . "_bt_config_" . date("d-m-Y") . ".zpl"; ?> </i>)
							</p>
							<p>
								<input type="submit" value="Submit">
								<input type="reset" value="Reset">
							</p>
							</form>
						</div>
						<div class ="grid_item" id="box_connectivity_other">
							<h3>Other protocols</h3>
						<?php

							$names = array("FTP", "LPD" , "HTTP (Web page)", "SMTP", "POP3", "SNMP", "Mirror");
							$commands = array("ip.ftp.enable", "ip.lpd.enable", "ip.http.enable", "ip.smtp.enable", "ip.pop3.enable", "ip.snmp.enable", "ip.mirror.auto");
							$final_array = results($path, $names, $commands);
							echo "<form action='printer_connectivity_actions.php' method='post'>";
							echo "<input type='hidden' name='printer_sn' value='" . $row_printer["sn"] . "'>";
							echo "<input type='hidden' name='config_type' value='other_protocols'>";
							echo "<table>
									<th>Protocol</th>
									<th>Current status</th>
									<th>Options</th>";
							foreach($final_array as $sgd_other_protocols){
								// creating an array to export the configuration as a profile
								$profile_other_protocols[$sgd_other_protocols['sgd_command']] = $sgd_other_protocols["current"];
								// printing the current configuration
								echo "<tr><td class='option'>" . $sgd_other_protocols["name"] . "</td><td class='current'>" . $sgd_other_protocols["current"] . "</td><td>";
								$options = explode(",", $sgd_other_protocols["choices"]);
								foreach ($options as $option){
									if($option == $sgd_other_protocols["current"]){
										$selected = "checked";
									}
									else{
										$selected = "";
									}
									echo "<input type='radio' name='" . str_replace(".", "-", $sgd_other_protocols["sgd_command"]) . "' value='" . $option . "' " . $selected . " required>" . $option;
								}
								echo "</td></tr>";
							}
						?>
							</table>
							<p>
								<input type='radio' name='action' value='send_config' required>Send to printer
								<input type='radio' name='action' value='save_config'>Save as resource (filename: <i> <?php echo $row_printer["name"] . "_other_protocols_" . date("d-m-Y") . ".zpl"; ?> </i>)
							</p>
							<p>
								<input type="submit" value="Submit">
								<input type="reset" value="Reset">
							</p>
							</form>
						</div>
						<div class="grid_item" id="box_connectivity_serial">
							<h3>Serial port</h3>
						<?php
							$names = array("Baud rate", "Bits" , "Parity bit", "Stop bits", "Handshake", "Serial communication type");
							$commands = array("comm.baud", "comm.data_bits", "comm.parity", "comm.stop_bits", "comm.handshake", "comm.type");
							$final_array = results($path, $names, $commands);
							echo "<form action='printer_connectivity_actions.php' method='post'>";
							echo "<input type='hidden' name='printer_sn' value='" . $row_printer["sn"] . "'>";
							echo "<input type='hidden' name='config_type' value='serial_port'>";
							echo "<table>
									<th>Option</th>
									<th>Current status</th>
									<th>Options</th>";
							foreach($final_array as $sgd_serial_port){
								// creating an array to export the configuration as a profile
								$profile_serial[$sgd_serial_port['sgd_command']] = $sgd_serial_port["current"];
								// printing the current configuration
								echo "<tr><td class='option'>" . $sgd_serial_port["name"] . "</td><td class='current'>" . $sgd_serial_port["current"] . "</td><td>";
								$options = explode(",", $sgd_serial_port["choices"]);
								foreach ($options as $option){
									if($option == $sgd_serial_port["current"]){
										$selected = "checked";
									}
									else{
										$selected = "";
									}
									echo "<input type='radio' name='" . str_replace(".", "-", $sgd_serial_port["sgd_command"]) . "' value='" . $option . "' " . $selected . " required>" . $option;
								}
								echo "</td></tr>";
							}
						?>
							</table>
							<p>
								<input type='radio' name='action' value='send_config' required>Send to printer
								<input type='radio' name='action' value='save_config'>Save as resource (filename: <i> <?php echo $row_printer["name"] . "_serial_port_" . date("d-m-Y") . ".zpl"; ?> </i>)
							</p>
							<p>
								<input type="submit" value="Submit">
								<input type="reset" value="Reset">
							</p>
							</form>
						</div>
					
						<div class="grid_item" id="box_save_connectivity_profile">
							<h3>Save the current connectivity profile as resource</h3>
							<form action='printer_connectivity_actions.php' method='post'>
							<?php
								$connectivity_profile = array_merge($profile_network_wired, $profile_network_wireless, $profile_bt, $profile_other_protocols, $profile_serial);
								echo "<input type='hidden' name='printer_sn' value='" . $row_printer["sn"] . "'>
										<input type='hidden' name='action' value='save_full_config'>
										<input type='hidden' name='connectivity_profile' value='" . json_encode($connectivity_profile) . "'>";
							?>
								<input type="submit" value="Submit">
							</form>
						</div>
					</div>
				</div>
				<div id="Troubleshooting" class="tabcontent">
					<h2><?php echo $row_printer["name"]; ?> - Troubleshooting</h2>
					<div class="grid_container">
						<div class="grid_item" id="box_troubleshooting_actions">
							<table>
					<?php
							$text_area_text = "Enter commands here..";
							$submit_visible = "submit";
							//Troubleshooting printouts
							echo "<tr><td>Print</td><td>";
							$troubleshooting_printouts = array("print_config_label","print_network_config_label","print_sensor_profile");
							foreach ($troubleshooting_printouts as $troubleshooting_printout){
								echo "<a href='printer_troubleshooting_actions.php?printer_sn=" . $row_printer["sn"] . "&action=" . $troubleshooting_printout . "'><button class='button_options'>" . str_replace("_", " ", ucfirst($troubleshooting_printout)) . "</button></a>";
							}
							echo "</td></tr>";
							
							//Factory default
							echo "<tr><td>Factory default</td><td>";
							$factory_defaults = array("all","ip","wlan","power","bt","display");
							foreach ($factory_defaults as $factory_default){
								echo "<a href='printer_troubleshooting_actions.php?printer_sn=" . $row_printer["sn"] . "&action=" . $factory_default . "'><button class='button_options'>" . str_replace("_", " ", ucfirst($factory_default)) . "</button></a>";
							}
							echo "</td></tr>";
							//Printer control
							echo "<tr><td>Execute</td><td>";
							$printer_controls = array("calibration", "rtc_sync","clear_queue","reboot","save_allcv_report");
							foreach ($printer_controls as $printer_control){
								echo "<a href='printer_troubleshooting_actions.php?printer_sn=" . $row_printer["sn"] . "&action=" . $printer_control . "'><button class='button_options'>" . str_replace("_", " ", ucfirst($printer_control)) . "</button></a>";
							}
							echo "</td></tr>";
							?>
							</table>
							<!-- Send file -->
							<p>
								<form action='printer_troubleshooting_actions.php' method='post' enctype='multipart/form-data'>
									<input type='hidden' name='printer_sn' value='<?php echo $row_printer["sn"]; ?>'>
									<label for='file'>Send a file to the printer </label>
									<input type='file' name='file_to_send'>
									<input type='submit' value='Send file'>
								</form>
							</p>
						</div>
						<div class="grid_item" id="box_troubleshooting_allcv">
							<h3>List of allcv files</h3>
							<?php
							$allcv_dir = "files/printers/" . $row_printer["sn"] . "/allcv/";
							// removing parent folders from scandir
							$allcv_file_list = array_slice(scandir($allcv_dir), 2);
							// allcv file list with options to view, download or delete the files
							foreach($allcv_file_list as $allcv_file){
								echo $allcv_file . " ";
								echo "<a href='printer_troubleshooting_actions.php?printer_sn=" . $row_printer["sn"] . "&action=view_allcv_report" . "&allcv_filename=" . $allcv_file . "'><button>View</button></a> ";
								echo "<a href='printer_troubleshooting_actions.php?printer_sn=" . $row_printer["sn"] . "&action=download_allcv_report" . "&allcv_filename=" . $allcv_file . "'><button>Download</button></a> ";
								echo "<a href='printer_troubleshooting_actions.php?printer_sn=" . $row_printer["sn"] . "&action=delete_allcv_report" . "&allcv_filename=" . $allcv_file . "'><button>Delete</button></a><br>";
							}
							?>
						</div>
						<div class="grid_item" id="box_troubleshooting_send_commands">
							<h3>Send commands</h3>
							<form action='printer_troubleshooting_actions.php' method='post'>
							<input type='hidden' name='printer_sn' value='<?php echo $row_printer["sn"]; ?>'>
							<textarea name='raw_commands' placeholder=" <?php echo $text_area_text; ?> "></textarea><br>
							<input type="<?php echo $submit_visible; ?>" value="Submit">
						</div>
						<div class="grid_item" id="box_troubleshooting_input_capture_filelist">
							<h3>Input Capture</h3>
						<?php
							$filter_input_capture = "input.capture : run";
							if(strpos($allcv_content, $filter_input_capture) !== FALSE){
								$input_capture_switch = "on";
							}	
							else{
								$input_capture_switch = "off";
							}
							echo "Input capture is " . $input_capture_switch . "<br>";
							// input capture switch
							echo "<form action='printer_troubleshooting_actions.php' method='get'>";	
							if($input_capture_switch == "on"){
								$action = "input_capture_deactivate";
								echo "<p>Click <a href='printer_troubleshooting_actions.php?printer_sn=" . $row_printer["sn"] . "&action=" . $action . "'>here</a> to deactivate input capture</p>";
							}
							elseif($input_capture_switch == "off"){
								$action = "input_capture_activate";
								echo "<p>Click <a href='printer_troubleshooting_actions.php?printer_sn=" . $row_printer["sn"] . "&action=" . $action . "'>here</a> to activate input capture</p>";
							}
							echo "</form>";
							// getting file list from printer
							$pattern = "/^.*.DMP  /m"; 
							if (preg_match_all($pattern, $allcv_content, $matches)){
								$array_files = array_merge(...$matches);
								echo "<p><i>DMP Filelist</i></p>";
								foreach ($array_files as $single_file){
									$filename = substr($single_file,4);
									echo $filename;
									echo "<a href='file_content.php?printer_sn=" . $row_printer["sn"] . "&filename=" . $filename . "' target='view_dmp_file'><button>View</button></a> ";
									echo "<a href='printer_troubleshooting_actions.php?printer_sn=" . $row_printer["sn"] . "&action=download_dmp_file" . "&dmp_filename=" . $filename . "'><button>Download</button></a> ";
									echo "<a href='printer_troubleshooting_actions.php?printer_sn=" . $row_printer["sn"] . "&action=delete_dmp_file" . "&dmp_filename=" . $filename . "'><button>Delete</button></a><br>";
								}	
							}
						?>
						</div>
						<div class="grid_item" id="box_troubleshooting_input_capture_content">
							<iframe id='dmp_files' src='file_content.php' name='view_dmp_file'></iframe>
						</div>
					</div>
				</div>
			</div>
		</div>
	<?php
	}
	else{
		echo "<h2>" .  $row_printer["name"] . " - General info</h2>";
		echo "<h3>The printer is offline</h3>";
	}
	?>
</div>

<script>
function openPage(pageName,elmnt,color) {
  var i, tabcontent, tablinks;
  tabcontent = document.getElementsByClassName("tabcontent");
  for (i = 0; i < tabcontent.length; i++) {
    tabcontent[i].style.display = "none";
  }
  tablinks = document.getElementsByClassName("tablink");
  for (i = 0; i < tablinks.length; i++) {
    tablinks[i].style.backgroundColor = "";
  }
  document.getElementById(pageName).style.display = "block";
  elmnt.style.backgroundColor = color;
}

// Get the element with id="defaultOpen" and click on it
document.getElementById("defaultOpen").click();
</script>
</body>
</html>