<!DOCTYPE html>
<html>
<head>
<title>Printer management</title>
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

echo "<h2> Save or send to printer connectivity configuration</h2>";
$printer_sn = $_POST["printer_sn"];
$stmt = $conn->prepare("SELECT * FROM printers WHERE sn = ?");
$stmt->bind_param("s", $_POST["printer_sn"]); 
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

$port = 9100;
$sec = 5;
$usec = 0;
$socket = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket\n");
socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec'=>$sec, 'usec'=>$usec));
socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec'=>$sec, 'usec'=>$usec));
socket_connect($socket, $row["ip"], $port);

$data = $_POST;

// function to build the list of SGD commands
function commands_list($data){
	unset($data["printer_sn"]);
	unset($data["config_type"]);
	unset($data["network_interface"]);
	unset($data["action"]);
	// initializing the SGD commands list
	$sgd_commands = "";
	foreach($data as $sgd => $value){
		// adding the SGD commands into the string
		$sgd_commands .= '! U1 setvar "' . str_replace("-", "." , $sgd) .'" "' . $value . '" \r\n ';	
	}
	// the following are some additional settings, specific for the network configurations
	if($_POST["config_type"] == "network_config"){
		// adding a command to reboot the printer because changing the network configuration requires it
		$sgd_commands .= '! U1 do "device.reset" "" \r\n';
	}
	return $sgd_commands;
}

function save_resource($filename, $sgd_commands){
	include "conn.php";
	if(!file_exists("files/resources/network_config")){
		mkdir("files/resources/network_config", 0777, true);
	}
	$store_path = "files/resources/network_config/" . $filename;
	// checking if the file already exsists
	if(file_exists($store_path) == FALSE){
		// if not, the file is saved into the resources folder
		$resource_file = fopen($store_path,"w") or die("Unable to create file");
		fwrite($resource_file, $sgd_commands);
		fclose($resource_file);
		$type = "network_config";
		// and the resource is added into the db
		$stmt = $conn->prepare("INSERT INTO resources (name, description, type, filename) VALUES (?, ?, ?, ?)");
		$stmt->bind_param("ssss", $filename, $filename, $type , $filename); 
		$stmt->execute();
		echo "<p>The file " . $filename . " has been saved as connectivity configuration.</p>";
		echo "<script>location.href='printer_details.php?printer_sn=" . $_POST["printer_sn"] . "';</script>";
	}
	else{
		echo "<p>The file already exsist. Please delete the file from the Resources menu.</p>";
	}
}

if (!empty($data["printer_sn"]) AND !empty($data["action"])){
	switch($data["action"]){
		case "send_config":
			$sgd_commands = commands_list($data);
			socket_write($socket, $sgd_commands, strlen($sgd_commands)) or die ("Host unreachable");
			socket_close($socket);
			// passing the new IP to the variable, according to the network interface in use
			if($_POST["network_interface"] == "wired"){
				$new_ip = str_replace("-", "." , $_POST["internal_wired-ip-addr"]);
			}
			elseif($_POST["network_interface"] == "wireless"){
				$new_ip = str_replace("-", "." , $_POST["wlan-ip-addr"]);
			}
			// updating the db with the new IP address
			$stmt = $conn->prepare("UPDATE printers SET ip = ? WHERE sn = ?");
			$stmt->bind_param("ss", $new_ip, $_POST["printer_sn"]); 
			$update = $stmt->execute();
			$stmt->close();
			if($update){
				echo "Database updated with the new IP";
			}
			else{
				echo "Error " . $stmt->error;
			}
			// the redirect command can't be used because the printer requires a reboot and it take a few seconds
			echo "<p>Configuration sent to the printer. The printer is now rebooting with the new network configuration.</p>";
			
		break;
		
		case "save_config":
			// recalling the commands_list function to create the list of SGD commands
			$sgd_commands = commands_list($data);
			// creating the filename
			$filename = $row["name"] . "_" . $data["config_type"] . "_" . date("d-m-Y") . ".zpl";
			// recalling the save_resource function to save the resource as a file and in the db
			save_resource($filename, $sgd_commands);
		break;

		case "save_full_config":
			// decoding the connectivity profile
			$connectivity_profile = json_decode($_POST['connectivity_profile'],true);
			// initializing the SGD commands list
			$sgd_commands = "";
			// adding the SGD commands into the string
			foreach ($connectivity_profile as $key => $value){
				$sgd_commands .= '! U1 setvar "' . $key . '" "' . $value . '" \r\n ';
			}
			// creating the filename
			$filename = $row["name"] . "_connectivity_profile_" . date("d-m-Y") . ".zpl";
			// adding a command to reboot the printer because changing the network configuration requires it
			$sgd_commands .= '! U1 do "device.reset" "" \r\n';
			// recalling the save_resource function to save the resource as a file and in the db
			save_resource($filename, $sgd_commands);
		break;
				
		default:
			echo "<p>No action has been selected.</p>";
	}
}
else{
	echo "No printer and/or no action has been selected";
}
?>
</div>
</body>
</html>