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
	include "conn.php";
?>
	<h2>Resource Management</h2>
	<div class="grid_container">
		<div class="grid_item" id="box_manag_resource_upload">
			<h3>Upload a resource</h3>
			<form action='resources_actions.php' method='post' enctype="multipart/form-data">
				<input type='hidden' name='action' value='upload_resource'>
				<table>
					<tr>
						<td>Resouce type:</td>
						<td><select name="type" id="type" required>
								<option value="" disabled selected>Select</option>
								<option value="printer_config">Printer configuration file</option>
								<option value="network_config">Network configuration file</option>
								<option value="firmware">Firmware</option>
							</select>
						</td>
					</tr>
					<tr>
						<td><label for='resource_name'>Resource name: </label></td>
						<td><input type='text' id='resource_name' name='resource_name' required></td>
					</tr>
					<tr>
						<td><label for='resource_description'>Resource description: </label></td>
						<td><input type='text' id='resource_description' name='resource_description'></td>
					</tr>
					<tr>
						<td><input type="file" name="file" accept=".txt, text/plain, .zpl, text/plain"></td>
					</tr>
				</table>
				<p>
					<input type='submit' value='Upload'>
					<input type='reset' value='Reset'>
				</p>
			</form>
		</div>
		<?php
			//querying the db to get the resources list
			$stmt = $conn->prepare("SELECT * FROM resources");
			$stmt->execute();
			$result_resources = $stmt->get_result();
			while($row_resource = $result_resources->fetch_assoc()){
				// filtering the resources by type and creating an array for each type
				switch($row_resource["type"]){
					case "printer_config":
						$printer_config_array[] = $row_resource;
					break;
					case "network_config":
						$network_config_array[] = $row_resource;
					break;
					case "firmware":
						$firmware_array[] = $row_resource;
					break;
				}
			}
		?>
		<div class="grid_item" id="box_manag_file_lists">
			<div class="grid_item" id="box_manag_list_title">
				<h3>View\Delete a resource</h3>
			</div>
			<div class="grid_item" id="box_manag_printer_config_list">
				<h3>Printer configuration files</h3>
				<form action='resources_actions.php' method='post'>
					<input type='hidden' name='action' value='delete_resource'>
					<table id="list">
						<th>Select</th>
						<th>Name</th>
						<th>Description</th>
						<th>Filename</th>				
					<?php
						// listing the printer configurations
						foreach($printer_config_array as $printer_config){
							echo "<tr>";
							echo "<td><input type='checkbox' name='resource_id[]' value='" . $printer_config["id"] . "'></td>";
							echo "<td><a href='resource_details.php?resource_id=" . $printer_config["id"] . "'>" . $printer_config["name"] . "</a></td>";
							echo "<td>" . $printer_config["description"] . "</td>";
							echo "<td>" . $printer_config["filename"] . "</td>";
							echo "</tr>";
						}
				?>
					</table>
			</div>
			<div class="grid_item" id="box_manag_network_config_list">
				<h3>Network configuration files</h3>
				<table id="list">
					<th>Select</th>
					<th>Name</th>
					<th>Description</th>
					<th>Filename</th>				
				<?php
					// listing the network configurations
					foreach($network_config_array as $network_config){
						echo "<tr>";
						echo "<td><input type='checkbox' name='resource_id[]' value='" . $network_config["id"] . "'></td>";
						echo "<td><a href='resource_details.php?resource_id=" . $network_config["id"] . "'>" . $network_config["name"] . "</a></td>";
						echo "<td>" . $network_config["description"] . "</td>";
						echo "<td>" . $network_config["filename"] . "</td>";
						echo "</tr>";
					}
			?>
				</table>
			</div>
			<div class="grid_item" id="box_manag_firmware_list">
				<h3>Firmware</h3>
				<table id="list">
					<th>Select</th>
					<th>Name</th>
					<th>Description</th>
					<th>Filename</th>				
				<?php
					// listing the firmware
					foreach($firmware_array as $firmware){
						echo "<tr>";
						echo "<td><input type='checkbox' name='resource_id[]' value='" . $firmware["id"] . "'></td>";
						echo "<td>" . $firmware["name"] . "</td>";
						echo "<td>" . $firmware["description"] . "</td>";
						echo "<td>" . $firmware["filename"] . "</td>";
						echo "</tr>";
					}
			?>
				</table>
			</div>
			<div class="grid_item" id="box_manag_buttons">
				<p>
					<input type='submit' value='Delete'>
					<input type='reset' value='Reset'>
				</p>
				</form>
			</div>
		</div>
	</div>
</div>
</body>
</html>