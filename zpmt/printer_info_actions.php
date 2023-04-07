<?php

if(!empty($_POST["new_printer_name"])){
	$printer_sn = $_POST["printer_sn"];
	$new_printer_name = $_POST["new_printer_name"];
	$action = "rename_printer";
}
else{
	$printer_sn = $_GET["printer_sn"];
	$action = $_GET["action"];
}

include "conn.php";
$stmt = $conn->prepare("SELECT * FROM printers WHERE sn = ?");
$stmt->bind_param("s", $printer_sn); 
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$printer_ip = $row["ip"];

$port = 9100;
$sec = 3;
$usec = 0;
$socket = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket\n");
socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec'=>$sec, 'usec'=>$usec));
socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec'=>$sec, 'usec'=>$usec));
socket_connect($socket, $printer_ip, $port);

function send_command($socket, $printer_sn, $command){
	socket_write($socket, $command, strlen($command)) or die ("Host unreachable");
	socket_close($socket);
	echo "<script>location.href='printer_details.php?printer_sn=" . $printer_sn . "';</script>";
}

switch ($action){
	case "rename_printer":
		$stmt = $conn->prepare("UPDATE printers SET name = ? WHERE sn = ?");
		$stmt->bind_param("ss", $new_printer_name, $printer_sn); 
		$update = $stmt->execute();
		echo "<script>location.href='printer_details.php?printer_sn=" . $printer_sn . "';</script>";
		break;
	case "reset_odometer.headclean":
		$command = '! U1 setvar "odometer.headclean" "0" \r\n ';
		send_command($socket, $printer_sn, $command);
		break;
	case "reset_odometer.headnew":
		$command = '! U1 setvar "odometer.headnew" "0" \r\n ';
		send_command($socket, $printer_sn, $command);
		break;
	case "reset_odometer.media_marker_count1":
		$command = '! U1 setvar "odometer.media_marker_count1" "0" \r\n ';
		send_command($socket, $printer_sn, $command);
		break;
	case "reset_odometer.media_marker_count2":
		$command = '! U1 setvar "odometer.media_marker_count2" "0" \r\n ';
		send_command($socket, $printer_sn, $command);
		break;
	case "reset_odometer.user_label_count1":
		$command = '! U1 setvar "odometer.user_label_count1" "0" \r\n ';
		send_command($socket, $printer_sn, $command);
		break;
	case "reset_odometer.user_label_count2":
		$command = '! U1 setvar "odometer.user_label_count2" "0" \r\n ';
		send_command($socket, $printer_sn, $command);
		break;
	case "reset_odometer.user_total_cuts":
		$command = '! U1 setvar "odometer.user_total_cuts" "0" \r\n ';
		send_command($socket, $printer_sn, $command);
		break;
	case "reset_odometer.latch_open_count":
		$command = '! U1 setvar "odometer.latch_open_count" "0" \r\n ';
		send_command($socket, $printer_sn, $command);
		break;
	case "download_file":
		$command = "^XA^HFE:" . $_GET["filename"] . " ^XZ";
		$result_socket = socket_connect($socket, $printer_ip, $port);
		socket_write($socket, $command, strlen($command));
		socket_recv($socket, $file_content, 102400, MSG_WAITALL);
		$filename = explode(":", $_GET["filename"]);
		$path = "files/printers/" . $printer_sn . "/other_files/" . $filename[0];
		$file = fopen($path, "w") or die("Unable to create file");
		fwrite($file,$file_content);
		header('Content-type: text/plain');
		header('Content-Disposition: attachment; filename="' . $filename[0] . '"');
		echo file_get_contents($path);
		break;
	case "delete_file":
		$command = '! U1 do "file.delete" "' . $_GET["filename"] . '"';
		send_command($socket, $printer_sn, $command);
		$filename = explode(":", $_GET["filename"]);
		$path = "files/printers/" . $printer_sn . "/other_files/" . $filename[0];
		$file = unlink($path) or die("Unable to delete file!");
		break;
	default:
		echo "No action selected.";
}
?>