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

echo "<h2>Save or send configuration files to the printer</h2>";
$printer_sn = $_POST["printer_sn"];
$stmt = $conn->prepare("SELECT * FROM printers WHERE sn = ?");
$stmt->bind_param("s", $_POST["printer_sn"]); 
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

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
	unset($data["action"]);
	// initializing the SGD commands list
	$sgd_commands = "";
	foreach($data as $sgd => $value){
		// adding the SGD commands into the string
		$sgd_commands .= '! U1 setvar "' . str_replace("*", "." , $sgd) .'" "' . $value . '" \r\n ';	
	}
	return $sgd_commands;
}

function save_resource($filename, $sgd_commands){
	include "conn.php";
	if(!file_exists("files/resources/printer_config")){
		mkdir("files/resources/printer_config", 0777, true);
	}
	$store_path = "files/resources/printer_config/" . $filename;
	// checking if the file already exsists
	if(file_exists($store_path) == FALSE){
		// if not, the file is saved into the resources folder
		$resource_file = fopen($store_path,"w") or die("Unable to create file");
		fwrite($resource_file, $sgd_commands);
		fclose($resource_file);
		$type = "printer_config";
		// and the resource is added into the db
		$stmt = $conn->prepare("INSERT INTO resources (name, description, type, filename) VALUES (?, ?, ?, ?)");
		$stmt->bind_param("ssss", $filename, $filename, $type , $filename); 
		$stmt->execute();
		echo "<p>The file " . $filename . " has been saved as configuration profile.</p>";
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
			echo "Configuration sent to the printer. The page will automatically reload with the new configuration";
			echo "<script>location.href='printer_details.php?printer_sn=" . $_POST["printer_sn"] . "';</script>";
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
			$configuration_profile = json_decode($_POST['configuration_profile'],true);
			// initializing the SGD commands list
			$sgd_commands = "";
			// adding the SGD commands into the string
			foreach ($configuration_profile as $key => $value){
				$sgd_commands .= '! U1 setvar "' . $key . '" "' . $value . '" \r\n ';
			}
			// creating the filename
			$filename = $row["name"] . "_configuration_profile_" . date("d-m-Y") . ".zpl";
			// adding a command to reboot the printer because changing the network configuration requires it
			$sgd_commands .= '! U1 do "device.reset" "" \r\n';
			// recalling the save_resource function to save the resource as a file and in the db
			save_resource($filename, $sgd_commands);
		break;
		}
}
else{
	echo "No printer and/or no action has been selected";
}

?>
</div>
</body>
</html>