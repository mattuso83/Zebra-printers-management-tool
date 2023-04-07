<?php

if(!isset($_GET["filename"])){
	echo "Select a file to view its content";
}
else{
	include "conn.php";
	$stmt = $conn->prepare("SELECT * FROM printers WHERE sn = ?");
	$stmt->bind_param("s", $_GET["printer_sn"]); 
	$stmt->execute();
	$result = $stmt->get_result();
	$row = $result->fetch_assoc();
	if(str_contains($_GET["filename"], ".DMP")){
		$message_file_type = '! U1 do "file.type" "' . $_GET["filename"] . '" \r\n';
	}
	else{
		$message_file_type = '^XA^HFE:' . $_GET["filename"] . ' ^XZ';
	}
	
	$port = 9100;
	$sec = 2;
	$usec = 0;
	$socket = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket\n");
	socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec'=>$sec, 'usec'=>$usec));
	socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec'=>$sec, 'usec'=>$usec));
	$result_socket = @socket_connect($socket, $row["ip"], $port);
	socket_write($socket, $message_file_type, strlen($message_file_type));
	socket_recv($socket, $file_type, 102400, MSG_WAITALL);
	echo "<h4>" . $_GET["filename"] . "</h4><hr>";
	echo $file_type;
}
?>