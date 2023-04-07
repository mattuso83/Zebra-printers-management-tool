<!DOCTYPE html>
<html>
<head>
<title>ZPMT - Printer log</title>
<link href="css/layout.css" rel="stylesheet" type="text/css" />
<link href="css/nav_bar.css" rel="stylesheet" type="text/css" />
<link href="css/index.css" rel="stylesheet" type="text/css" />
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" type="text/css" />
<script src="http://code.jquery.com/jquery-latest.min.js"></script>
<script src="js/jquery.menu.js"></script>
</head>
<body>
<div class="content">
<?php
	include "conn.php";
	include "nav_bar.php";
	$stmt = $conn->prepare("SELECT * FROM printers WHERE id = ?");
	$stmt->bind_param("s", $_GET["printer_id"]); 
	$stmt->execute();
	$result_printer = $stmt->get_result();
	$row_printer = $result_printer->fetch_assoc();
	echo "<h2>Log for printer " . $row_printer["name"] . "</h2>";
	echo "<b>Attention: logs older than 30 days are automatically deleted</b>";
	
	$stmt = $conn->prepare("SELECT * FROM alerts WHERE printer_id = ? ORDER BY `date` DESC");
	$stmt->bind_param("s", $_GET["printer_id"]); 
	$stmt->execute();
	$result = $stmt->get_result();
	
	echo "<table>
			<th>Date</th>
			<th>Alert type</th>
			<th>Alert message</th>";
	while($row_alerts = $result->fetch_assoc()){
		if($row_alerts["alert_type"] == "Error condition"){
			$tr_background = "red";
		}
		elseif($row_alerts["alert_type"] == "Alert" AND $row_alerts["alert_message"] == "Printer paused"){
			$tr_background = "yellow";
		}
		else{
			$tr_background = "";
		}
		
		echo "<tr style='background-color:" . $tr_background . "'>";
		echo "<td>" . $row_alerts["date"] . "</td><td>" . $row_alerts["alert_type"] . "</td><td>" . $row_alerts["alert_message"] . "</td>"; 
		echo "</tr>";
	}
	echo "</table>";
?>
</div>
</body>
</html>