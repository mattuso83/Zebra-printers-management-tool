<!DOCTYPE html>
<html>
<head>
<title>Group management</title>
<link href="css/layout.css" rel="stylesheet" type="text/css" />
<link href="css/nav_bar.css" rel="stylesheet" type="text/css" />
<link href="css/group_management.css" rel="stylesheet" type="text/css" />
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" rel="stylesheet" type="text/css" />
<script src="http://code.jquery.com/jquery-latest.min.js"></script>
<script src="js/jquery.menu.js"></script>
</head>
<body>
<div class="content">
<?php
	include "nav_bar.php";
	include "conn.php";
	
	switch ($_POST["action"]){
		case "create_group":
			echo "<h2>Group creation</h2>";
			// check if a group with same name already exsist
			$stmt = $conn->prepare("SELECT * FROM groups WHERE name = ?");
			$stmt->bind_param("s", $_POST['group_name']);
			$stmt->execute();
			$stmt->store_result();
			$found = $stmt->num_rows;
			
			//$resource = $result_groups->fetch_assoc();
			if($found == 1){
					echo "<p>A group named " . $_POST['group_name'] . " already exsist.</p>";
			}
			else{
				// adding the group to the db		
				$stmt = $conn->prepare("INSERT INTO groups (name, description) VALUES (?, ?)");
				$stmt->bind_param("ss", $_POST['group_name'], $_POST['group_description']); 
				$insert = $stmt->execute();
				if($insert){
					echo "<p>Group " . $_POST['group_name'] . " has been created.</p>";
				}
				else{
					echo "Error " . $stmt->error;
				}
				$stmt->close();
			}
		break;
		
		case "delete_group":
			echo "<h2>Delete a group</h2>";
			if(!empty($_POST["delete_group"])){
				foreach($_POST["delete_group"] as $group_id){
					// dissociate printers from the group
					$group_idx = "%," . $group_id . "%";
					$stmt = $conn->prepare("SELECT * FROM printers WHERE groups LIKE ?");
					$stmt->bind_param("s", $group_idx); 
					$stmt->execute();
					$result_printers = $stmt->get_result();
					while($row_printer = $result_printers->fetch_assoc()){
						$group_list[] = $row_printer["groups"];
						// creating an array of groups for each printer
						foreach($group_list as $groups){
							// converting group list into array
							$printer_groups = explode(",",$groups);
						}
						// searching the array to find the key of the group to remove
						$key = array_search($group_id, $printer_groups);
						// and deleting the element from the array
						unset($printer_groups[$key]);
						// converting the array into a string
						$printer_groups_query = implode(",", $printer_groups);
						// updating the groups field in the database
						$stmt = $conn->prepare("UPDATE printers SET groups = ? WHERE id = ?");
						$stmt->bind_param("ss", $printer_groups_query, $row_printer["id"]); 
						$update = $stmt->execute();
					}				
					// deleting the group from db
					$stmt = $conn->prepare("DELETE FROM groups WHERE id = ?");
					$stmt->bind_param("s", $group_id); 
					$delete = $stmt->execute();
					if($delete){
						echo "<p>The group has been deleted.</p>";
					}
					else{
						echo mysqli_error($conn);
						die();
					}
				}
			}
			else{
				echo "Select the group you want to delete.";
			}
		break;
		
		case "edit_group":
		echo "<h2>Update group details</h2>";
			// updating the group details in the db
			$stmt = $conn->prepare("UPDATE groups SET name = ?, description = ? WHERE id = ?");
			$stmt->bind_param("sss", $_POST['group_name'], $_POST['group_description'], $_POST["group_id"]); 
			$update = $stmt->execute();
			if($update){
				echo "<p>Details for group " . $_POST['group_name'] . " have been updated.</p>";
			}
			else{
				echo "Error " . $stmt->error;
			}
			$stmt->close();
		break;
		
		case "add_to_group":
			echo "<h2>Add printer(s) to a group: </h2>";
			if(!empty($_POST["printer_id"]) AND !empty($_POST["group_id"])){
				foreach($_POST["printer_id"] as $printer_id){
					$stmt = $conn->prepare("SELECT * FROM printers WHERE id = ?");
					$stmt->bind_param("s", $printer_id); 
					$stmt->execute();
					$result_printers = $stmt->get_result();
					while($row_printer = $result_printers->fetch_assoc()){
						// converting group list into array
						$printer_groups = explode(",",$row_printer['groups']);
						// searching the array to find the group selected
						if(array_search($_POST["group_id"], $printer_groups) == TRUE){
							// if found
							$stmt = $conn->prepare("SELECT * FROM groups WHERE id = ?");
							$stmt->bind_param("s", $_POST['group_id']); 
							$stmt->execute();
							$result_groups = $stmt->get_result();
							$group = $result_groups->fetch_assoc();
							echo "<p>Printer " . $row_printer["name"] . " already associated to group " . $group["name"] . ".</p>";
						}
						else{
							// if not found but printer is already associated with 10 groups
							if(count($printer_groups)==10){
								echo "Printer " . $row_printer["name"] . " is already associated with the max number of groups (10).";
							}
							// if not found but printer is associated with less than 10 groups
							elseif(count($printer_groups)<10){
								// adding the group to the groups array
								$printer_groups[] = $_POST["group_id"];
								// converting the array into a string
								$groups_query = implode(",", $printer_groups);
								//$groups_query .= ",";
								// updating the groups field in the database
								$stmt = $conn->prepare("UPDATE printers SET groups = ? WHERE id = ?");
								$stmt->bind_param("ss", $groups_query, $printer_id); 
								$update = $stmt->execute();
								$stmt = $conn->prepare("SELECT * FROM groups WHERE id = ?");
								$stmt->bind_param("s", $_POST['group_id']); 
								$stmt->execute();
								$result_groups = $stmt->get_result();
								$group = $result_groups->fetch_assoc();
								echo "<p>Printer " . $row_printer["name"] . " is now associated with group " . $group["name"] . ".</p>";
							}
						}
					}
				}
			}
			else{
				echo "<p>Select at least a printer.</p>";
			}
		break;
	
		case "remove_from_group":
			echo "<h2>Remove printer(s) from a group: </h2>";
			if(!empty($_POST["printer_id"]) AND !empty($_POST["group_id"])){
				include "conn.php";
				foreach($_POST["printer_id"] as $printer_id){
					$stmt = $conn->prepare("SELECT * FROM printers WHERE id = ?");
					$stmt->bind_param("s", $printer_id); 
					$stmt->execute();
					$result_printers = $stmt->get_result();
					while($row_printer = $result_printers->fetch_assoc()){
					
						// converting group list into array
						$printer_groups = explode(",",$row_printer['groups']);
						// searching the array to find the group selected
						if(array_search($_POST["group_id"], $printer_groups) == TRUE){
							// if found, searching for group's key in the array
							$key = array_search($_POST["group_id"], $printer_groups);
							// and deleting the element from the array
							unset($printer_groups[$key]);
							// converting the array into a string
							$groups_query = implode(",", $printer_groups);
							// updating the groups field in the database
							$stmt = $conn->prepare("UPDATE printers SET groups = ? WHERE id = ?");
							$stmt->bind_param("ss", $groups_query, $printer_id); 
							$update = $stmt->execute();
							// getting group info to prepare confirmation output
							$stmt = $conn->prepare("SELECT * FROM groups WHERE id = ?");
							$stmt->bind_param("s", $_POST['group_id']); 
							$stmt->execute();
							$result_group = $stmt->get_result();
							$group = $result_group->fetch_assoc();
							echo "<p>Printer " . $row_printer["name"] . " has been removed from group " . $group["name"] . ".</p>";
						}
						// if not found
						else{
							$stmt = $conn->prepare("SELECT * FROM groups WHERE id = ?");
							$stmt->bind_param("s", $_POST['group_id']); 
							$stmt->execute();
							$result_printers = $stmt->get_result();
							$group = $result_printers->fetch_assoc();
							echo "<p>Printer " . $row_printer["name"]	. " not in group " . $group["name"] . ".</p>";
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
<div>
</body>
</html>