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
?>
	<h2>Fleet management</h2>
	<div class="grid_container">
		<div class="grid_item" id="box_delete">
			<form action="fleet_actions.php" method="post">
				<input type="hidden" name="action" value="remove_printer">
				<table>
					<th>Select</th>
					<th>Printer name</th>
					<th>Serial number</th>
					<th>IP address</th>
				<?php
					$stmt = $conn->prepare("SELECT * FROM printers");
					$stmt->execute();
					$result_printer = $stmt->get_result();
					while($row_printer = $result_printer->fetch_assoc()){
						echo "<tr>";
						echo "<td><input type='checkbox' name='printer_id[]' value='" . $row_printer["id"] . "'></td>";
						echo "<td>" . $row_printer["name"] . "</td>" . "<td>" . $row_printer["sn"] . "</td>" . "<td>" . $row_printer["ip"] . "</td>";
						echo "</tr>";
					}
				?>
				</table>
				<p>
					<input type="submit" value="Delete printer">
					<input type="reset" value="Reset">
				</p>
			</form>
		</div>
	</div>
</div>
</body>
</html>