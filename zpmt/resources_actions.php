<!DOCTYPE html>
<html>
<head>
<title>Resources management</title>
<link href="css/layout.css" rel="stylesheet" type="text/css" />
<link href="css/nav_bar.css" rel="stylesheet" type="text/css" />
<link href="css/resources_management.css" rel="stylesheet" type="text/css" />
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" rel="stylesheet" type="text/css" />
<script src="http://code.jquery.com/jquery-latest.min.js"></script>
<script src="js/jquery.menu.js"></script>
</head>
<body>
<div class="content">
<?php
include "nav_bar.php";

switch ($_POST["action"]){
	case "upload_resource":
		include "conn.php";
		echo "<h2>Upload a resource</h2>";
		// checking if the filename is already present
		$stmt = $conn->prepare("SELECT * FROM resources WHERE name = ?");
		$stmt->bind_param("s", $_POST['resource_name']);
		$stmt->execute();
		$result = $stmt->get_result();
		if($row = $result->fetch_assoc()){
			echo "<p>A resource with the same filename already exsist. Edit the file name and retry.</p>";
		}
		// if not, the resource is added to the db
		else{
			$stmt = $conn->prepare("INSERT INTO resources (name, description, type, filename) VALUES (?, ?, ?, ?)");
			$stmt->bind_param("ssss", $_POST['resource_name'], $_POST['resource_description'], $_POST['type'], $_FILES['file']['name']); 
			$stmt->execute();
			// checking if the folder exsists
			if(!file_exists("files/resources/" . $_POST['type'])){
				mkdir("files/resources/" . $_POST['type'], 0777, true);
			}
			// and the file is stored in the appropriate directory
			$store_path = "files/resources/" . $_POST['type'] . "/" . $_FILES['file']['name'];
			move_uploaded_file($_FILES["file"]["tmp_name"], $store_path);
			switch($_POST['type']){
				case "printer_config":
					$type = "printer configuration";
				break;
				case "network_config":
					$type = "network configuration";
				break;
				case "firmware":
					$type = "firmware";
				break;
			} 
			echo "<p>File " . $_POST['resource_name'] . " has been stored as " . $type . ".</p>";
		}	
	break;
	
	case "edit_resource":
		include "conn.php";
		echo "<h2>Edit resource details</h2>";
		// updating the resource details
		$stmt = $conn->prepare("UPDATE resources SET name = ?, description = ? WHERE id = ?");
		$stmt->bind_param("sss", $_POST["name"], $_POST["description"], $_POST["resource_id"]); 
		$update = $stmt->execute();
		if($update){
			echo "<p>Details for resource " . $_POST["name"] . " have been updated.</p>";
		}
		else{
			echo "Error " . $stmt->error;
		}
	break;
	
	case "delete_resource":
		echo "<h2>Delete a resource</h2>";
		if(!empty($_POST["resource_id"])){
			include "conn.php";
			// checking if the resource is in the db
			foreach($_POST["resource_id"] as $resource_id){
				$stmt = $conn->prepare("SELECT * FROM resources WHERE id = ?");
				$stmt->bind_param("s", $resource_id); 
				$stmt->execute();
				$result = $stmt->get_result();
				if($row_check_type = $result->fetch_assoc()){
					// deleting the file
					unlink("files/resources/" . $row_check_type["type"] . "/" . $row_check_type["filename"]);
					// deleting the resource from the db
					$stmt = $conn->prepare("DELETE FROM resources WHERE id = ?");
					$stmt->bind_param("s", $resource_id); 
					$stmt->execute();
					echo "<p>The resource " . $row_check_type["filename"] . " has been deleted.</p>";
				}
				else{
					echo "Resource not found.";
				}
			}
		}
		else{
			echo "Select a resource to delete.";
		}
	break;
	
	case "send_resource":
		include "conn.php";
		echo "<h2>Send resource to group of printers</h2>";
		// getting the resource info
		$stmt = $conn->prepare("SELECT * FROM resources WHERE id = ?");
		$stmt->bind_param("s", $_POST["resource_id"]); 
		$stmt->execute();
		$result_resources = $stmt->get_result();
		$resource = $result_resources->fetch_assoc();
		// creating the resource path
		$path = "files/resources/" . $resource["type"] . "/" . $resource["filename"];
		// getting the file content
		$file = file_get_contents($path);
		// querying the db to find the printer assigned to the selected group
		$group_idx = "%," . $_POST["destination"] . "%";
		$stmt = $conn->prepare("SELECT * FROM printers WHERE groups LIKE ?");
		$stmt->bind_param("s", $group_idx); 
		$stmt->execute();
		$result_printers = $stmt->get_result();
		// querying the db to get the group details
		$stmt = $conn->prepare("SELECT * FROM groups WHERE id = ?");
		$stmt->bind_param("s", $_POST["destination"]); 
		$stmt->execute();
		$result_groups = $stmt->get_result();
		$group = $result_groups->fetch_assoc();
		// if the resource is a firmware, the timeout is longer
		if($resource["type"] == "firmware"){
			$sec = 60;
		}
		else{
			$sec = 5;
		}
		$port = 9100;
		$usec = 0;
		
		echo "<h3>" . $resource["filename"] . " sent to group " . $group["name"] . ":</h3>";
		// sending the file to each printer of the group
		while($printer = $result_printers->fetch_assoc()){
			$ip = $printer["ip"];
			$socket = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket\n");
			socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec'=>$sec, 'usec'=>$usec));
			socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec'=>$sec, 'usec'=>$usec));
			if(@socket_connect($socket, $ip, $port)){
				socket_write($socket, $file) or die ("Host unreachable");
				echo "<p>File sent to " . $printer["name"] . " - " . $printer["ip"] . ".</p>";
			}
			else{
				echo "<p><h4>Attention: </h4>Printer " . $printer["name"] . " - " . $printer["ip"] . " is offline. A task has been created and the file will be sent as soon as the printer will be online.";
				
				$task_type = "send_resource";
				$stmt = $conn->prepare("INSERT INTO tasks (task_type, resource_id, target_ip) VALUES (?, ?, ?)");
				$stmt->bind_param("sss", $task_type, $_POST["resource_id"], $ip); 
				$stmt->execute();
				}
		}
	break;
	
	default:
		echo "<p>No action has been selected.</p>";
}
?>
</div>
</body>
</html>