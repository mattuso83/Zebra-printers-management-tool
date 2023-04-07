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
	echo "<h2>Resource details</h2>";
	if(!empty($_GET["resource_id"])){
		include "conn.php";
		// querying the db to get the resource details
		$stmt = $conn->prepare("SELECT * FROM resources WHERE id = ?");
		$stmt->bind_param("s", $_GET["resource_id"]);
		$stmt->execute();
		$result = $stmt->get_result();
		$row = $result->fetch_assoc();
		
		switch($row["type"]){
			case "printer_config":
				$type = "Printer configuration";
			break;
			case "network_config":
				$type = "Network configuration";
			break;
			case "firmware":
				$type = "Firmware";
			break;
		}
		
		echo "<div class='grid_container'>";
		echo "<div class='grid_item' id='box_details_edit'>";
			echo "<p><h3>Edit resource details</h3></p>";
			// creating a form to edit the resource details
			echo "<form action='resources_actions.php' method='post'>
					<input type='hidden' name='action' value='edit_resource'>
					<input type='hidden' name='resource_id' value='" . $row["id"] . "'>
					<table>
						<tr>
							<td><label for='name'>Name: </label>
							<td><input type='text' id='name' name='name' value='" . $row["name"] . " ' required>
						</tr>
						<tr>
							<td><label for='description'>Description: </label>
							<td><input type='text' id='description' name='description' value='" . $row["description"] . "'>
						</tr>
						<tr>
							<td>Resouce type:</td>
							<td>" . $type . "</td>
						</tr>
						<tr>
							<td>Filename: </td>
							<td>" . $row["filename"] . "</td>
						</tr>
					</table>
					<p>
						<input type='submit' value='Update'>
						<input type='reset' value='Reset'>
					</p>
				</form>";
		echo "</div>";
		echo "<div class='grid_item' id='box_details_content'>";
			echo "<h3>Content of the file</h3>"; 
			// getting the resource file path
			$path = "files/resources/" . $row["type"] . "/" . $row["filename"];
			// getting the file content
			$file_content = file($path);
			if(empty($file_content)){
				echo "The file is empty";
			}
			else{
				foreach($file_content as $line){
					echo str_replace('\r\n', "<br>", $line) . "<br>";
				}
			}
		echo "</div>";
	}
	else{
		echo "<p>No resource selected.</p>";
	}
?>
	</div>
</div>
</body>
</html>