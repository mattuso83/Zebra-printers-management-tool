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
	if(!empty($_GET["group_id"])){
		// querying the db to get the printers assigned to the group
		$group_idx = "%," . $_GET["group_id"] . "%";
		$stmt = $conn->prepare("SELECT * FROM printers WHERE groups LIKE ?");
		$stmt->bind_param("s", $group_idx); 
		$stmt->execute();
		$result_printers = $stmt->get_result();
		// checking the number of printers associated with the group
		$num_rows = mysqli_num_rows($result_printers);
		// getting group details
		$stmt = $conn->prepare("SELECT * FROM groups WHERE id = ?");
		$stmt->bind_param("s", $_GET["group_id"]); 
		$stmt->execute();
		$result_group = $stmt->get_result();
		$group = $result_group->fetch_assoc();
		// checking if the group exsists in the db
		if($group == NULL){
			echo "<p>This group doesn't exsist.</p>";
		}
		else{
			echo "<h2>Group " . $group["name"] . "</h2>";
			echo "<div class='grid_container'>";
				echo "<div class='grid_item' id='box_group_details_edit'>";
					echo "<h3>Edit group details</h3>";
					// form to edit the group details
					echo "<form action='group_actions.php' method='post'>";
						echo "<input type='hidden' name='action' value='edit_group'>";
						echo "<input type='hidden' name='group_id' value='" . $group["id"] . "'>";
						echo "<table>
								<tr>
									<td><label for='group_name'>Group name: </label></td>
									<td><input type='text' id='group_name' name='group_name' value='" . $group["name"] . "'></td>
								</tr>
								<tr>
									<td><label for='group_description'>Group description: </label></td>
									<td><input type='text' id='group_description' name='group_description' value='" . $group["description"] . "'></td>
								</tr>
								<tr>
									<td>Created on " . $group["date"] . "</td>
								</tr>
							</table>
							<p>
								<input type='submit' value='Submit'>
								<input type='reset' value='Reset'>
							</p>";
					echo "</form>";
				echo "</div>";
			echo "<div class='grid_item' id='box_group_details_printer_list'>"; 
			echo "<h3>Printers associated with group " . $group["name"] . "</h3>";
			// checking the number of printers assigned to the group
			if($num_rows == 0){
				echo "<p>No printer associated with group " . $group["name"] . "</p>";
			}
			else{
					echo "<p>" . $num_rows . " printer(s) associated.</p>";
					echo "<table>
							<th>Printer name</th>
							<th>Serial number</th>
							<th>IP address</th>
							<th>Status</th>";
					$port = 9100;
					$sec = 3;
					$usec = 0;
					// checking the printers online and offline
					while($printer = $result_printers->fetch_assoc()){
						$socket = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket\n");
						socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec'=>$sec, 'usec'=>$usec));
						socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec'=>$sec, 'usec'=>$usec));
						// and assigning a different background color to the table entries
						if(socket_connect($socket, $printer["ip"], $port)){
							$network = "online";
							echo "<tr style='background-color:green'>";
							echo "<td><a href='printer_details.php?printer_sn=" . $printer["sn"] . "'>" . $printer["name"] . "</a></td>";
						}
						else{
							$network = "offline";
							echo "<tr style='background-color:red'>";
							echo "<td>" . $printer["name"] . "</td>";
						}
						echo "<td>" . $printer["sn"] . "</td>
							<td>" . $printer["ip"] . "</td>
							<td>" . $network . "</td>";
						echo "</tr>";
					}
					echo "</table>";
				echo "</div>";
			}
			echo "</div>";
		}
	}
?>
</div>
</body>
</html>