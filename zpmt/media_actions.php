<!DOCTYPE html>
<html>
<head>
<title>ZPMT - Media management</title>
<link href="css/layout.css" rel="stylesheet" type="text/css" />
<link href="css/nav_bar.css" rel="stylesheet" type="text/css" />
<link href="css/media_management.css" rel="stylesheet" type="text/css" />
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" rel="stylesheet" type="text/css" />
<script src="http://code.jquery.com/jquery-latest.min.js"></script>
<script src="js/jquery.menu.js"></script>
</head>
<body>
<div class="content">
<?php
include "nav_bar.php";
include "conn.php";
$action = $_POST["action"];
// socket settings
$port = 9100;
$sec = 3;
$usec = 0;

switch ($action){
	case "insert_paper":
		echo "<h2>Insert a label\receipt in inventory</h2>";
		$stmt = $conn->prepare("SELECT * FROM paper WHERE name = ?");
		$stmt->bind_param("s", $_POST['name']);
		$stmt->execute();
		$row = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
		$total = $_POST["quantity"] * $_POST["labels_per_roll"];
		if($row != NULL){
			echo "<p>A media with the same ame already exsist. Edit the name and retry.</p>";
		}
		else{
			$stmt = $conn->prepare("INSERT INTO paper (sku, name, type, width, height, labels_per_roll, rolls_quantity, total_labels, current_labels, threshold) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
			$stmt->bind_param("sssiiiiiii", $_POST["sku"], $_POST["name"], $_POST["type"], $_POST["width"], $_POST["height"], $_POST["labels_per_roll"], $_POST["rolls_quantity"], $total, $total, $_POST["threshold"]); 
			$stmt->execute();
			echo "<p>The media <b>" . $_POST["name"] . "</b> has been inserted in the inventory.</p>";
		}	
	break;
	
	case "delete_paper":
		echo "<h2>Delete a paper from the inventory</h2>";
		if(!empty($_POST["paper_id"])){
			foreach($_POST["paper_id"] as $paper_id){
				// dissociate printers from paper
				$stmt = $conn->prepare("SELECT * FROM printers WHERE paper = ?");
				$stmt->bind_param("s", $paper_id); 
				$stmt->execute();
				$result_printers = $stmt->get_result();
				while($row_printer = $result_printers->fetch_assoc()){
					$new_paper_id = NULL;
					// updating the papers field in the database
					$stmt = $conn->prepare("UPDATE printers SET paper = ? WHERE id = ?");
					$stmt->bind_param("ss", $new_paper_id, $row_printer["id"]); 
					$update = $stmt->execute();
				}	
				// deleting the paper from db
				$stmt = $conn->prepare("DELETE FROM paper WHERE id = ?");
				$stmt->bind_param("s", $paper_id); 
				$delete = $stmt->execute();
				if($delete){
					echo "<p>The paper has been deleted and all the printers have been dissociated.</p>";
				}
				else{
					echo mysqli_error($conn);
					die();
				}
			}
		}
		else{
			echo "Select a paper from the list";
		}
		break;
		
	case "edit_paper":
		echo "<h2>Update details of a paper in the inventory</h2>";
		$stmt = $conn->prepare("UPDATE paper SET sku = ?, name = ?, type = ?, width = ?, height = ?, labels_per_roll = ?, threshold = ? WHERE id = ?");
			$stmt->bind_param("sssiiiii", $_POST["sku"], $_POST["name"], $_POST["type"], $_POST["width"], $_POST["height"], $_POST["labels_per_roll"], $_POST["threshold"], $_POST["id"]); 
		$update = $stmt->execute();
		if($update){
			echo "<p>Details for media " . $_POST["name"] . " have been updated.</p>";
		}
		else{
			echo "Error " . $stmt->error;
		}
	break;
	
	case "edit_paper_quantity":
		echo "<h2>Update roll quantity of a paper in the inventory</h2>";
		// getting paper details
		$stmt = $conn->prepare("SELECT * FROM paper WHERE id = ?");
		$stmt->bind_param("i", $_POST["paper_id"]); 
		$stmt->execute();
		$result_paper = $stmt->get_result();
		$paper = $result_paper->fetch_assoc();
		// calculating new total number of labels
		$total = $_POST["new_quantity"] * $paper["labels_per_roll"];
		// updating the db
		$stmt = $conn->prepare("UPDATE paper SET rolls_quantity = ?, total_labels = ?, current_labels = ? WHERE id = ?");
		$stmt->bind_param("iiii", $_POST["new_quantity"], $total, $total, $_POST["paper_id"]); 
		$update = $stmt->execute();
		if($update){
			echo "<p>Quantity for media " . $paper["name"] . " have been updated.</p>";
		}
		else{
			echo "Error " . $stmt->error;
		}
		// resetting the printers odometers
		$command = '! U1 setvar "odometer.media_marker_count1" "0" \r\n';
		$stmt = $conn->prepare("SELECT * FROM printers WHERE paper = ?");
		$stmt->bind_param("s", $_POST["paper_id"]); 
		$stmt->execute();
		$result_printer = $stmt->get_result();
		while($printer = $result_printer->fetch_assoc()){
			$socket = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket\n");
			socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec'=>$sec, 'usec'=>$usec));
			socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec'=>$sec, 'usec'=>$usec));
			if(socket_connect($socket, $printer["ip"], $port)){
				socket_write($socket, $command, strlen($command));
				socket_close($socket);
			}
			else{
				echo "<h4>Attention:</h4> The odometer <b>has not</b> been reset for printer " . $printer["sn"] . " because it is offline. The label roll counter may be affected. A task has been created and the odometer will be reset as soon as the printer will be online.";
				// creating a task to reset the odometer once the printer will be back online
				$task_type = "reset_odometer";
				$stmt = $conn->prepare("INSERT INTO tasks (task_type, target_ip) VALUES (?, ?)");
				$stmt->bind_param("ss", $task_type, $printer["ip"]); 
				$stmt->execute();
			}
		}
	break;
	
	case "associate_paper":
		echo "<h2>Associate a printer to a paper: </h2>";
		if(!empty($_POST["printer_id"]) AND !empty($_POST["paper_id"])){
			foreach($_POST["printer_id"] as $printer_id){
				// updating the database
				$stmt = $conn->prepare("SELECT * FROM printers WHERE id = ?");
				$stmt->bind_param("s", $printer_id); 
				$stmt->execute();
				$result_printers = $stmt->get_result();
				while($row_printer = $result_printers->fetch_assoc()){
					// checking if the printer is already assigned to the paper
					if($row_printer['paper'] == $_POST["paper_id"]){
						// if it is
						$stmt = $conn->prepare("SELECT * FROM paper WHERE id = ?");
						$stmt->bind_param("s", $_POST['paper_id']); 
						$stmt->execute();
						$result_paper = $stmt->get_result();
						$paper = $result_paper->fetch_assoc();
						echo "<p>Printer " . $row_printer["name"] . " already associated to paper " . $paper["name"] . ".</p>";
					}
					else{
						// if it is not the paper field in the database is updated
						$stmt = $conn->prepare("UPDATE printers SET paper = ? WHERE id = ?");
						$stmt->bind_param("ss", $_POST['paper_id'], $printer_id); 
						$update = $stmt->execute();
						$stmt = $conn->prepare("SELECT * FROM paper WHERE id = ?");
						$stmt->bind_param("s", $_POST['paper_id']); 
						$stmt->execute();
						$result_paper = $stmt->get_result();
						$paper = $result_paper->fetch_assoc();
						
						// resetting the printer odometer for the inventory
						$socket = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket\n");
						socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec'=>$sec, 'usec'=>$usec));
						socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec'=>$sec, 'usec'=>$usec));
						if(socket_connect($socket, $row_printer["ip"], $port)){
							$command = '! U1 setvar "odometer.user_label_count1" "0" \r\n';
							socket_write($socket, $command, strlen($command));
							socket_close($socket);
							echo "<p>Printer " . $row_printer["name"] . " is now associated with paper " . $paper["name"] . ".</p>";
						}
						else{
							echo "<h4>Attention:</h4> The odometer <b>has not</b> been reset for printer " . $row_printer["sn"] . " because it is offline. The label roll counter may be affected. A task has been created and the odometer will be reset as soon as the printer will be online.";
							// creating a task to reset the odometer once the printer will be back online
							$task_type = "reset_odometer";
							$stmt = $conn->prepare("INSERT INTO tasks (task_type, target_ip) VALUES (?, ?)");
							$stmt->bind_param("ss", $task_type, $row_printer["ip"]); 
							$stmt->execute();
							
						}
					}
				}
			}
		}
		else{
			echo "<p>Select at least a printer.</p>";
		}
	break;
	
	case "dissociate_paper":
		echo "<h2>Dissociate a printer from a paper: </h2>";
		if(!empty($_POST["printer_id"]) AND !empty($_POST["paper_id"]) AND !empty($action)){
			include "conn.php";
			foreach($_POST["printer_id"] as $printer_id){
				$stmt = $conn->prepare("SELECT * FROM printers WHERE id = ?");
				$stmt->bind_param("s", $printer_id); 
				$stmt->execute();
				$result_printers = $stmt->get_result();
				while($row_printer = $result_printers->fetch_assoc()){
					// checking if the printer is assigned to the paper
					if($row_printer['paper'] == $_POST["paper_id"]){
						$paper_id = NULL;
						// updating the papers field in the database
						$stmt = $conn->prepare("UPDATE printers SET paper = ? WHERE id = ?");
						$stmt->bind_param("ss", $paper_id, $printer_id); 
						$update = $stmt->execute();
						// getting paper info to prepare confirmation output
						$stmt = $conn->prepare("SELECT * FROM paper WHERE id = ?");
						$stmt->bind_param("s", $_POST['paper_id']); 
						$stmt->execute();
						$result_paper = $stmt->get_result();
						$paper = $result_paper->fetch_assoc();
						echo "<p>Printer " . $row_printer["name"] . " has been dissociated from paper " . $paper["name"] . ".</p>";
					}
					// if not found
					else{
						$stmt = $conn->prepare("SELECT * FROM paper WHERE id = ?");
						$stmt->bind_param("s", $_POST['paper_id']); 
						$stmt->execute();
						$result_printers = $stmt->get_result();
						$paper = $result_printers->fetch_assoc();
						echo "<p>Printer " . $row_printer["name"]	. " not associated with " . $paper["name"] . ".</p>";
					}
				}
			}
		}
		else{
			echo "<p>Select at least a printer.</p>";
		}
	break;
	
	case "insert_ribbon":
		echo "<h2>Insert a ribbon in inventory</h2>";
		$stmt = $conn->prepare("SELECT * FROM ribbon WHERE name = ?");
		$stmt->bind_param("s", $_POST['name']);
		$stmt->execute();
		$row = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
		$total = $_POST["quantity"] * $_POST["roll_length"];
		if($row != NULL){
			echo "<p>A ribbon with the same ame already exsist. Edit the name and retry.</p>";
		}
		else{
			$stmt = $conn->prepare("INSERT INTO ribbon (sku, name, width, roll_length, quantity, total) VALUES (?, ?, ?, ?, ?, ?)");
			$stmt->bind_param("ssiiii", $_POST["sku"], $_POST["name"], $_POST["width"], $_POST["roll_length"], $_POST["quantity"], $total); 
			$stmt->execute();
			echo "<p>The ribbon <b>" . $_POST["name"] . "</b> has been inserted in the inventory.</p>";
		}	
	break;
	
	case "delete_ribbon":
		echo "<h2>Delete a ribbon from the inventory</h2>";
		if(!empty($_POST["ribbon_id"])){
			foreach($_POST["ribbon_id"] as $ribbon_id){
				// dissociate printers from ribbon
				$stmt = $conn->prepare("SELECT * FROM printers WHERE ribbon = ?");
				$stmt->bind_param("s", $ribbon_id); 
				$stmt->execute();
				$result_printers = $stmt->get_result();
				while($row_printer = $result_printers->fetch_assoc()){
					$new_ribbon_id = NULL;
					// updating the groups field in the database
					$stmt = $conn->prepare("UPDATE printers SET ribbon = ? WHERE id = ?");
					$stmt->bind_param("ss", $new_ribbon_id, $row_printer["id"]); 
					$update = $stmt->execute();
				}				
				// deleting the ribbon from db
				$stmt = $conn->prepare("DELETE FROM ribbon WHERE id = ?");
				$stmt->bind_param("s", $ribbon_id); 
				$delete = $stmt->execute();
				if($delete){
					echo "<p>The ribbon has been deleted and all the printers have been dissociated.</p>";
				}
				else{
					echo mysqli_error($conn);
					die();
				}
			}
		}
		else{
			echo "Select a ribbon from the list";
		}
		break;
	
	case "edit_ribbon":
		echo "<h2>Update details of a ribbon in the inventory</h2>";
		$stmt = $conn->prepare("UPDATE ribbon SET sku = ?, name = ?, width = ?, roll_length = ?, quantity = ? WHERE id = ?");
			$stmt->bind_param("ssiiii", $_POST["sku"], $_POST["name"], $_POST["width"], $_POST["roll_length"], $_POST["quantity"], $_POST["id"]); 
		$update = $stmt->execute();
		if($update){
			echo "<p>Details for ribbon " . $_POST["name"] . " have been updated.</p>";
		}
		else{
			echo "Error " . $stmt->error;
		}
	break;
	
	case "edit_ribbon_quantity":
		echo "<h2>Update roll quantity of a ribbon in the inventory</h2>";
		// getting ribbon details
		$stmt = $conn->prepare("SELECT * FROM ribbon WHERE id = ?");
		$stmt->bind_param("i", $_POST["ribbon_id"]); 
		$stmt->execute();
		$result_ribbon = $stmt->get_result();
		$ribbon = $result_ribbon->fetch_assoc();
		// calculating total number of labels
		$total = $_POST["new_quantity"] * $ribbon["roll_length"];
		// updating the db
		$stmt = $conn->prepare("UPDATE ribbon SET quantity = ?, total = ?  WHERE id = ?");
		$stmt->bind_param("iii", $_POST["new_quantity"], $total, $_POST["ribbon_id"]); 
		$update = $stmt->execute();
		if($update){
			echo "<p>Quantity for media " . $ribbon["name"] . " have been updated.</p>";
		}
		else{
			echo "Error " . $stmt->error;
		}
		// resetting the printers ribbon starting point
		$ribbon_starting_point = 0;
		$stmt = $conn->prepare("SELECT * FROM printers WHERE ribbon = ?");
		$stmt->bind_param("s", $_POST["ribbon_id"]); 
		$stmt->execute();
		$result_printer = $stmt->get_result();
		while($printer = $result_printer->fetch_assoc()){
			$stmt = $conn->prepare("UPDATE printers SET ribbon_starting_point = ?  WHERE id = ?");
			$stmt->bind_param("ii", $ribbon_starting_point, $printer["id"]); 
			$update = $stmt->execute();
		}
	break;

	case "associate_ribbon":
		echo "<h2>Associate a printer to a ribbon: </h2>";
		if(!empty($_POST["printer_id"]) AND !empty($_POST["ribbon_id"])){
			foreach($_POST["printer_id"] as $printer_id){
				$stmt = $conn->prepare("SELECT * FROM printers WHERE id = ?");
				$stmt->bind_param("s", $printer_id); 
				$stmt->execute();
				$result_printers = $stmt->get_result();
				while($row_printer = $result_printers->fetch_assoc()){
					if($row_printer['ribbon'] == $_POST["ribbon_id"]){
						// if it is
						$stmt = $conn->prepare("SELECT * FROM ribbon WHERE id = ?");
						$stmt->bind_param("s", $_POST['ribbon_id']); 
						$stmt->execute();
						$result_ribbon = $stmt->get_result();
						$ribbon = $result_ribbon->fetch_assoc();
						echo "<p>Printer " . $row_printer["name"] . " already associated to ribbon " . $ribbon["name"] . ".</p>";
					}
					else{
						// checking if the printer is online
						$socket = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket\n");
						socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec'=>$sec, 'usec'=>$usec));
						socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec'=>$sec, 'usec'=>$usec));
						if(socket_connect($socket, $row_printer["ip"], $port)){
							$network_status = "online";
							// if online, the amount of ribbon used so far is retrieved from the printer
							$command = '! U1 getvar "odometer.net_ribbon_length" \r\n';
							socket_write($socket, $command, strlen($command));
							socket_recv($socket, $response, 1024, MSG_WAITALL);
							socket_close($socket);
							$response = explode(" ", $response);	
							$used_ribbon = $response[2];
						}
						else{
							$network_status = "offline";
							$path = "files/printers/" . $row_printer["sn"] . "/" . $row_printer["sn"] . "_golden_allcv.txt";
							// if the printer is offline, the amount of ribbon used so far is retrieved from the latest allcv report
							$allcv_content = file_get_contents($path);
							$command = "odometer.net_ribbon_length";
							$pattern = "/^.*$command .*\$/m";
							preg_match($pattern, $allcv_content, $match);
							$response = implode(" ", $match);
							$response = explode(" ", $response);
							// if the printer doesn't support TT printing, the value is set to 0
							if(empty($response)){
								$used_ribbon = $response[4];
							}
							else{
								$used_ribbon = 0;
							}
						}
						// if it is not the ribbon field and the ribbon starting point in the database are updated
						$stmt = $conn->prepare("UPDATE printers SET ribbon = ?, ribbon_starting_point = ? WHERE id = ?");
						$stmt->bind_param("sss", $_POST['ribbon_id'], $used_ribbon, $printer_id); 
						$update = $stmt->execute();
						$stmt = $conn->prepare("SELECT * FROM ribbon WHERE id = ?");
						$stmt->bind_param("s", $_POST['ribbon_id']); 
						$stmt->execute();
						$result_ribbon = $stmt->get_result();
						$ribbon = $result_ribbon->fetch_assoc();
						echo "<p>Printer " . $row_printer["name"] . " is now associated with ribbon " . $ribbon["name"] . ".</p>";
					}
				}
			}
		}
		else{
			echo "<p>Select at least a printer.</p>";
		}
	break;
	
	case "dissociate_ribbon":
		echo "<h2>Dissociate a printer from a ribbon: </h2>";
		if(!empty($_POST["printer_id"]) AND !empty($_POST["ribbon_id"]) AND !empty($action)){
			include "conn.php";
			foreach($_POST["printer_id"] as $printer_id){
				$stmt = $conn->prepare("SELECT * FROM printers WHERE id = ?");
				$stmt->bind_param("s", $printer_id); 
				$stmt->execute();
				$result_printers = $stmt->get_result();
				while($row_printer = $result_printers->fetch_assoc()){
					// checking if the printer is assigned to the paper
					if($row_printer['ribbon'] == $_POST["ribbon_id"]){
						$ribbon_id = NULL;
						// updating the ribbons field in the database
						$stmt = $conn->prepare("UPDATE printers SET ribbon = ? WHERE id = ?");
						$stmt->bind_param("ss", $ribbon_id, $printer_id); 
						$update = $stmt->execute();
						// getting ribbon info to prepare confirmation output
						$stmt = $conn->prepare("SELECT * FROM ribbon WHERE id = ?");
						$stmt->bind_param("s", $_POST['ribbon_id']); 
						$stmt->execute();
						$result_ribbon = $stmt->get_result();
						$ribbon = $result_ribbon->fetch_assoc();
						echo "<p>Printer " . $row_printer["name"] . " has been dissociated from ribbon " . $ribbon["name"] . ".</p>";
					}
					// if not found
					else{
						$stmt = $conn->prepare("SELECT * FROM ribbon WHERE id = ?");
						$stmt->bind_param("s", $_POST['ribbon_id']); 
						$stmt->execute();
						$result_printers = $stmt->get_result();
						$ribbon = $result_printers->fetch_assoc();
						echo "<p>Printer " . $row_printer["name"]	. " not associated with " . $ribbon["name"] . ".</p>";
					}
				}
			}
		}
		else{
			echo "<p>Select at least a printer.</p>";
		}
	break;
		
	default:
		echo "<p>No action has been selected.</p>";
}
?>
</div>
</body>
</html>