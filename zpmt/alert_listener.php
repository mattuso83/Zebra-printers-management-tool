<?php

$alertMsg = urldecode($_POST["alertMsg"]);
$sn = urldecode($_POST["uniqueId"]);

list($alert_type, $alert_message) = explode (": ", $alertMsg);

include "conn.php";
if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
}

$alert_type = ucfirst(strtolower($alert_type));
$alert_message = ucfirst(strtolower($alert_message));


$stmt = $conn->prepare("SELECT id FROM printers WHERE sn = ?");
$stmt->bind_param("s", $sn); 
$stmt->execute();
$stmt->bind_result($printer_id); 
$stmt->fetch();
$stmt->close();


$stmt = $conn->prepare("INSERT INTO alerts (printer_id, alert_type, alert_message) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $printer_id, $alert_type, $alert_message); 
$stmt->execute();
$stmt->close();

// if the alert is about the printhead, also a notification is triggered
if($alert_message == "HEAD ELEMENT BAD" OR $alert_message == "CLEAN PRINTHEAD" OR $alert_message == "REPLACE HEAD"){
	$title = "Printhead alert";
	$content = "Alert " . $alert_message . " for printer " . $printed_id . ".";
	$checked = 0;
	$stmt = $conn->prepare("INSERT INTO notifications (title, content, checked) VALUES (?, ?, ?)");
	$stmt->bind_param("ssi", $title, $content, $checked); 
	$stmt->execute();
	$stmt->close();
}


?>