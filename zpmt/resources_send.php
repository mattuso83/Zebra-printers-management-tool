<!DOCTYPE html>
<html>
<head>
<title>Resources - Send</title>
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
	include "conn.php";
?>
	<h2>Send a resource</h2>
<?php
	// if no type is selected, show the resources type list
	if(!isset($_GET["type"])){
		echo "<div class='grid_item' id='box_send_type'>";
			echo "<h3>Select the type of file:</h3>";
			echo "<p><a href='resources_send.php?type=printer_config'>Printer configuration file</a><br>
				<a href='resources_send.php?type=network_config'>Network configuration file</a><br>
				<a href='resources_send.php?type=firmware'>Firmware</a></p>";
		echo "</div>";
	}
	// else select the resource and the group
	else{
		echo "<h3>Select the resource and the group</h3>";
		echo "<div class='grid_container'>";
			echo "<div class='grid_item' id='box_send'>";		
				echo "<div class='grid_item' id='box_send_filelist'>";
					echo "<h3>Select a file</h3>";
					echo "<form action='resources_actions.php' method='post' enctype='multipart/form-data'>";
					echo "<input type='hidden' name='action' value='send_resource'>";
					// quering the db to show only the resources of the requested type
					$stmt = $conn->prepare("SELECT * FROM resources WHERE type = ?");
					$stmt->bind_param("s", $_GET["type"]);
					$stmt->execute();
					$result = $stmt->get_result();
					while($row_resources = $result->fetch_assoc()) {
						echo "<input type='radio' name='resource_id' value='" . $row_resources["id"] . "' required>" . $row_resources["name"] . "<br>";
					}
?>
					</div>
					<div class="grid_item" id="box_send_destination">
						<h3>Select a group</h3>
					<?php
						//listing the groups
						$stmt = $conn->prepare("SELECT * FROM groups");
						$stmt->execute();
						$result_groups = $stmt->get_result();
						while($row_groups = $result_groups->fetch_assoc()){
							$group = "%," . $row_groups["id"] . "%";
							$stmt = $conn->prepare("SELECT * FROM printers WHERE groups LIKE ?");
							$stmt->bind_param("s", $group);
							$stmt->execute();
							$result = $stmt->get_result();
							// checking the number of printers assigned to the group
							$num_rows = mysqli_num_rows($result);
							// setting the counter for the offline printers to 0
							$counter_offline = 0;
							$port = 9100;
							$sec = 3;
							$usec = 0;
							// checking the printers online
							while($printer = $result->fetch_assoc()) {
								$socket = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket\n");
								socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec'=>$sec, 'usec'=>$usec));
								socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec'=>$sec, 'usec'=>$usec));
								// counting the printers offline
								if(socket_connect($socket, $printer["ip"], $port) == FALSE){
									$counter_offline++;
								}
							}
							// if no results from the db
							if($num_rows == 0){
								$output = " - (This group is empty)";
							}
							// else showing the number of printers assigned to the group and the number of printers offline
							else{
								$output = " - (" . $num_rows . " printers in this group; " . $counter_offline . "  offline)";
							}
							echo "<input type='radio' name='destination' value='" . $row_groups["id"] . "' required><a href='group_details.php?group_id=" . $row_groups["id"] . "'>" . $row_groups["name"] . "</a>" . $output . "<br>";
						}
				echo "</div>"; 
				echo "<div class='grid_item' id='box_send_buttons'>";
					echo "<p>
							<input type='submit' value='Send'>
							<input type='reset' value='Reset'>
						  </p>";
					echo "</form>";
				echo "</div>";
			echo "</div>";
		}
				?>
</div>
</body>
</html>