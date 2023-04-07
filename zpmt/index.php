<!DOCTYPE html>
<html>
<head>
<title>ZPMT - Home</title>
<link href="css/layout.css" rel="stylesheet" type="text/css" />
<link href="css/nav_bar.css" rel="stylesheet" type="text/css" />
<link href="css/index.css" rel="stylesheet" type="text/css" />
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" type="text/css" />
<script src="http://code.jquery.com/jquery-latest.min.js"></script>
<script src="js/jquery.menu.js"></script>
</head>
<body>
<div class="content">
<?php
include "conn.php";
include "nav_bar.php";

?>
	<h2>Printer list</h2>
	<table>
		<th>Printer name</th>
		<th>Printer model</th>
		<th>Serial number</th>
		<th>IP address</th>
		<th>Network</th>
		<th>Status</th>
		<th>Firmware version</th>
		<th>Firmware date</th>
		<th>Battery status</th>
		<th>Groups</th>
<?php
	
	// setting the communication port and socket timeout
	// timeout can be lower, but you might have latency issues with wireless printers
	$port = 9100;
	$sec = 3;
	$usec = 0;
	$stmt = $conn->prepare("SELECT * FROM printers");
	$stmt->execute();
	$result = $stmt->get_result();
	$tr_background = "";
	while($row_printer = $result->fetch_assoc()){
		// open socket connection
		$socket = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket\n");
		socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec'=>$sec, 'usec'=>$usec));
		socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec'=>$sec, 'usec'=>$usec));
		// if connection is successful
		if(socket_connect($socket, $row_printer["ip"], $port)){
			$network = "online";
			// list of SGD commands to get info from the printer
			$command_status = '! U1 getvar "zpl.system_error" \r\n';
			$command_fw_version = '! U1 getvar "appl.name" \r\n';
			$command_fw_date = '! U1 getvar "appl.date" \r\n';
			$command_battery_option = '! U1 getvar "device.feature.battery" \r\n';
			$command_battery_status = '! U1 getvar "power.percent_full" \r\n';
			
			// printer is queried with previous SGD commands
			socket_write($socket, $command_status, strlen($command_status));
			$printer_status = str_replace('"', '', socket_read ($socket, 1024));
			socket_write($socket, $command_fw_version, strlen($command_fw_version));
			$fw_version = str_replace('"', '', socket_read ($socket, 1024));
			socket_write($socket, $command_fw_date, strlen($command_fw_date));
			$fw_date = str_replace('"', '', socket_read ($socket, 1024));
			socket_write($socket, $command_battery_option, strlen($command_battery_option));
			$battery_option = str_replace('"', '', socket_read ($socket, 1024));
			socket_write($socket, $command_battery_status, strlen($command_battery_status));
			$battery_status = str_replace('"', '', socket_read ($socket, 1024));
			socket_close($socket);
			
			// possible response of zpl.system_error command
			switch ($printer_status){
				case "0,0,00000000,00000000":
					$status = "Ready";
					$tr_background = "green";
					break;
				case "1,1,00000000,00010000":
					$status = "Printer paused";
					$tr_background = "yellow";
					break;
				case "1,1,00000000,00010001":
					$status = "Paper out";
					$tr_background = "red";
					break;
				case "1,1,00000000,00010002":
					$status = "Ribbon out";
					$tr_background = "red";
					break;
				case "1,1,00000000,00010004":
					$status = "Head open";
					$tr_background = "red";
					break;
				case "1,1,00000000,00010005":
					$status = "Head open, paper out";
					$tr_background = "red";
					break;
				case "1,1,00000000,00010006":
					$status = "Head open, ribbon out";
					$tr_background = "red";
					break;
				case "1,1,00000000,00010008":
					$status = "Cutter fault";
					$tr_background = "red";
					break;
			}
			
			if($battery_option == "not available"){
				$battery_status = "No battery";
			}
		}
		else{
			$network = "offline";
			$status = "";
			$last_alert = "";
			$fw_version = "";
			$fw_date = "";
			$battery_status = "";
		}
		
		// table row fields output
		if($network == "online"){
			echo "<tr style='background-color:" . $tr_background . "'>";
			echo "<td><a href='printer_details.php?printer_sn=" . $row_printer["sn"] . "'>" . $row_printer["name"] . "</a></td>";
		}
		else{
			echo "<tr>";
			echo "<td>" . $row_printer["name"] . "</td>";
		}
		
		$printer_groups = explode(",",$row_printer['groups']);
		//$printer_groups = array_filter($printer_groups);
		
		foreach ($printer_groups as $group_found){
			
			$stmt = $conn->prepare("SELECT * FROM groups WHERE id = ?");
			$stmt->bind_param("s", $group_found); 
			$stmt->execute();
			$query_group_list = $stmt->get_result();
			while($row_group = $query_group_list->fetch_assoc()){
				$array_groups[] = $row_group;
			}
		}
		echo "<td>" . $row_printer["model"] . "</td>
			  <td>" . $row_printer["sn"] . "</td>
			  <td>" . $row_printer["ip"] . "</td>
			  <td>" . $network . "</td>";
			if($tr_background == "red"){
				echo "<td><a href='printer_logs.php?printer_id=" . $row_printer["id"] . "'>" . $status . "</a>";
			}
			else{
				echo "<td>" . $status . "</td>";
			}
			echo "<td>" . $fw_version . "</td>
			  <td>" . $fw_date . "</td>
			  <td>" . $battery_status . "</td>
			  <td>";
		if(!empty($array_groups)){
			foreach($array_groups as $group){
				echo "<button class='button_group'><a href='group_details.php?group_id=" . $group["id"] . "'>" . $group["name"] . "</a></button>";
			}
		}
		echo "</td></tr>";
		unset($array_groups);
	}
?>
	</table>
</div>
</body>
</html>

