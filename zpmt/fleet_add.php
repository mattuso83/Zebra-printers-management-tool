<!DOCTYPE html>
<html>
<head>
<title>Fleet management</title>
<link href="css/layout.css" rel="stylesheet" type="text/css" />
<link href="css/nav_bar.css" rel="stylesheet" type="text/css" />
<link href="css/fleet_management.css" rel="stylesheet" type="text/css" />
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" rel="stylesheet" type="text/css" />
<script src="http://code.jquery.com/jquery-latest.min.js"></script>
<script src="js/jquery.menu.js"></script>
</head>
<body>
<div class="content">
<?php
	include "nav_bar.php";
	include "conn.php";
	
	$stmt = $conn->prepare("SELECT * FROM groups");
	$stmt->execute();
	$result_groups = $stmt->get_result();
	while($row_group = $result_groups->fetch_assoc()){
		$array_groups[] = $row_group;
	}
?>
	<h2>Fleet management - Add printers</h2>
	<div class="grid_container">
		<div class="grid_item" id="box_scan">
			<h3>Scan network</h3>
			<form action="fleet_actions.php" method="post">
				<p><input type="hidden" name="action" value="scan">
				<label for="first_ip">First IP address: </label>
				<input type="text" id="first_ip" name="first_ip" minlength="7" maxlength="15" size="15" pattern="^((\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.){3}(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])$"><br>
				<label for="last_ip">Last IP address:</label>
				<input type="text" id="last_ip" name="last_ip" minlength="7" maxlength="15" size="15" pattern="^((\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.){3}(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])$"></p>
				<p>
				<input type="checkbox" name="add_to_group" value="yes">
				Add the printers to this group:
				<label for="group"></label>
					<select name="group_id" id="group">
					<option value="" disabled selected>No group</option>
				<?php
					foreach($array_groups as $group){
						echo "<option value='" . $group["id"] . "' >" . $group["name"] . "</option>";
					}
				?>
					</select>
				</p>
				<p>
					<input type="submit" value="Submit">
					<input type="reset" value="Reset">
				</p>
			</form>
		</div>
		<div class="grid_item" id="box_single">
			<h3>Add a single printer by IP</h3>
			<form action="fleet_actions.php" id="ip" method="post">
				<p><input type="hidden" name="action" value="single">
				<label for="ip">IP address: </label>
				<input type="text" id="ip" name="ip" minlength="7" maxlength="15" size="15" pattern="^((\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.){3}(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])$"></p>
				<p>
				<input type="checkbox" name="add_to_group" value="yes">
				Add the printer to this group:
				<label for="group"></label>
					<select name="group_id" id="group">
					<option value="" disabled selected>No group</option>
				<?php
					foreach($array_groups as $group){
						echo "<option value='" . $group["id"] . "' >" . $group["name"] . "</option>";
					}
				?>
					</select>
				</p>
				<p>
					<input type="submit" value="Submit">
					<input type="reset" value="Reset">
				</p>
			</form>
		</div>
		<div class="grid_item" id="box_file">
			<h3>Add a list of printers from a file</h3>
			<p>File types accepted are .txt and .csv. <br>
				The file can contain only printer IPs (the printer name will be the default one), duplets of IP and Hostname (for custom printer names) or triplets of IP, Hostname and a single group ID (check <a href='group_management.php'>here </a> for the group IDs).<br> 
				One IP, duplets of IP and Hostname or triplets of IP, Hostname and group ID for each line without enclosures. Comma is the only accepted separator between the fields.<br>
				Example:<br>
				192.168.0.10,Printer1<br>
				192.168.0.11,Printer2<br>
				192.168.0.12,Printer3,12<br>
				192.168.0.13,Printer4,13<br>
			</p>
			<form action="fleet_actions.php" method="post" enctype="multipart/form-data">
				<p><input type="hidden" name="action" value="file">
				<label for="ip">File: </label>
				<input type="file" name="file" accept=".txt, text/plain, .csv, application/csv" required></p>
				<input type="submit" value="Submit">
				<input type="reset" value="Reset">
			</form>
		</div>
	</div>
</div>
</body>
</html>