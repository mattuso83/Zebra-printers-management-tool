<?php

if(!empty($_FILES["file_to_send"])){
	$printer_sn = $_POST["printer_sn"];
	$action = "send_file";
}
elseif(!empty($_POST["raw_commands"])){
	$printer_sn = $_POST["printer_sn"];
	$action = "send_raw_commands";
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
$sec = 8;
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
	case "print_config_label":
		$command = '~WC';
		send_command($socket, $printer_sn, $command);
		break;
	case "print_network_config_label":
		$command = '~WL';
		send_command($socket, $printer_sn, $command);
		break;
	case "print_sensor_profile":
		$command = '~JG';
		send_command($socket, $printer_sn, $command);
		break;
	case "all":
		$command = '! U1 do "device.restore_defaults" "all" \r\n';
		send_command($socket, $printer_sn, $command);
		break;
	case "ip":
		$command = '! U1 do "device.restore_defaults" "ip" \r\n';
		send_command($socket, $printer_sn, $command);
		break;
	case "wlan":
		$command = '! U1 do "device.restore_defaults" "wlan" \r\n';
		send_command($socket, $printer_sn, $command);
		break;
	case "power":
		$command = '! U1 do "device.restore_defaults" "power" \r\n';
		send_command($socket, $printer_sn, $command);
		break;
	case "bt":
		$command = '! U1 do "device.restore_defaults" "bluetooth" \r\n';
		send_command($socket, $printer_sn, $command);
		break;
	case "display":
		$command = '! U1 do "device.restore_defaults" "display" \r\n';
		send_command($socket, $printer_sn, $command);
		break;
	case "calibration":
		$command = '! U1 do "zpl.calibrate" "" \r\n';
		send_command($socket, $printer_sn, $command);
		break;
	case "rtc_sync":
		$command = '! U1 setvar "rtc.date" "' . date("m-d-Y") . '" \r\n ! U1 setvar "rtc.time" "' . date("H:i:s") . '" \r\n';
		send_command($socket, $printer_sn, $command);
		break;
	case "clear_queue":
		$command = '~JA';
		send_command($socket, $printer_sn, $command);
		break;
	case "reboot":
		$command = '! U1 do "device.reset" ""  \r\n';
		send_command($socket, $printer_sn, $command);
		break;
	case "save_allcv_report":
		$command = '! U1 getvar "allcv" \r\n';
		socket_write($socket, $command, strlen($command));
		socket_recv($socket, $response, 76800, MSG_WAITALL);
		socket_close($socket);
		$allcv_file = fopen("files/printers/" . $printer_sn . "/allcv/allcv_" . date("d-m-Y") . "_" . date("H-i-s") . ".txt", "w") or die("Unable to create file");
		fwrite($allcv_file, $response);
		fclose($allcv_file);
		echo "<script>location.href='printer_details.php?printer_sn=" . $printer_sn . "';</script>";
		break;
	case "send_file":
		$file = file_get_contents($_FILES["file_to_send"]["tmp_name"]);
		socket_write($socket, $file, filesize($_FILES["file_to_send"]["tmp_name"])) or die ("Host unreachable");
		echo "<script>location.href='printer_details.php?printer_sn=" . $printer_sn . "';</script>";
		break;
	case "view_allcv_report":
		$path = "files/printers/" . $printer_sn . "/allcv/" . $_GET["allcv_filename"];
		$allcv = fopen($path, "r") or die("Unable to open file!");
		echo nl2br(file_get_contents($path));
		break;
	case "download_allcv_report":
		$path = "files/printers/" . $printer_sn . "/allcv/" . $_GET["allcv_filename"];
		$allcv = fopen($path, "r") or die("Unable to open file!");
		header('Content-type: text/plain');
		header('Content-Disposition: attachment; filename="' . $_GET["allcv_filename"] . '"');
		echo nl2br(file_get_contents($path));
		break;
	case "delete_allcv_report":
		$path = "files/printers/" . $printer_sn . "/allcv/" . $_GET["allcv_filename"];
		$allcv = unlink($path) or die("Unable to delete file!");
		echo "<script>location.href='printer_details.php?printer_sn=" . $printer_sn . "';</script>";
		break;
	case "send_raw_commands":
		$command = $_POST["raw_commands"];
		send_command($socket, $printer_sn, $command);
		break;
	case "input_capture_activate":
		$command = '! U1 setvar "input.capture" "run" \r\n';
		send_command($socket, $printer_sn, $command);
		break;
	case "input_capture_deactivate":
		$command = '! U1 setvar "input.capture" "off" \r\n';
		send_command($socket, $printer_sn, $command);
		break;
	case "download_dmp_file":
		$command = '! U1 do "file.type" "' . $_GET["dmp_filename"] . '" \r\n';
		$result_socket = @socket_connect($socket, $printer_ip, $port);
		socket_write($socket, $command, strlen($command));
		socket_recv($socket, $file_content, 1024, MSG_WAITALL);
		
		$filename = explode(":", $_GET["dmp_filename"]);
		$path = "files/printers/" . $printer_sn . "/dmp_files/" . $filename[0];
		$dmp_file = fopen($path, "w") or die("Unable to create file");
		fwrite($dmp_file,$file_content);
		header('Content-type: text/plain');
		header('Content-Disposition: attachment; filename="' . $filename[0] . '"');
		echo nl2br(file_get_contents($path));
		break;
	case "delete_dmp_file":
		$command = '! U1 do "file.delete" "' . $_GET["dmp_filename"] . '"';
		send_command($socket, $printer_sn, $command);
		$filename = explode(":", $_GET["dmp_filename"]);
		$path = "files/printers/" . $printer_sn . "/dmp_files/" . $filename[0];
		$dmp_file = unlink($path) or die("Unable to delete file!");
		break;
}
?>