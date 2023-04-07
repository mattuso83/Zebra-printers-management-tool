<?php

function create_notification($title, $content){
	include "conn.php";
	// checking if the same notification has already been created
	$stmt = $conn->prepare("SELECT id FROM notifications WHERE title = ? AND content = ?");
	$stmt->bind_param("ss", $title, $content); 
	$stmt->execute();
	$stmt->bind_result($found);
	$stmt->fetch();
	if(is_null($found)){
		$checked = 0;
		$stmt = $conn->prepare("INSERT INTO notifications (title, content, checked) VALUES (?, ?, ?)");
		$stmt->bind_param("ssi", $title, $content, $checked); 
		$stmt->execute();
	}
}

include "conn.php";
$stmt = $conn->prepare("SELECT * FROM printers");
$stmt->execute();
$result_printer = $stmt->get_result();
while($row_printer = $result_printer->fetch_assoc()){
	$printer_ip = $row_printer["ip"];
	$path = "files/printers/" . $row_printer["sn"] . "/" . $row_printer["sn"] . "_golden_allcv.txt";
	$port = 9100;
	$sec = 1;
	$usec = 0;
	$socket = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket\n");
	socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec'=>$sec, 'usec'=>$usec));
	socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec'=>$sec, 'usec'=>$usec));
	if(socket_connect($socket, $row_printer["ip"], $port) == TRUE){
		$command_ph_health = '! U1 getvar "device.printhead.test.summary" \r\n';
		socket_write($socket, $command_ph_health, strlen($command_ph_health));
		socket_recv($socket, $response_ph_health, 51200, MSG_WAITALL);
		// checking printhead health
		$response_ph_health = str_replace('"', "", $response_ph_health);
		$printhead_test = explode("," , $response_ph_health);
		// if health test is not passed, a notification is created		
		if($printhead_test[0] == 1 AND $printhead_test[4] != 0000){
			$title = "Printhead health status";
			$content = "The printhead health test for printer " . $row_printer["name"] . " failed. Number of failed elements = " . $printhead_test[4] . ".";
			create_notification($title, $content);
		}
		// checking battery health
		$command_battery_health = '! U1 getvar "power.percent_health" \r\n';
		socket_write($socket, $command_battery_health, strlen($command_battery_health));
		socket_recv($socket, $response_battery_health, 51200, MSG_WAITALL);
		$response_battery_health = str_replace('"', "", $response_battery_health);
		if($response_battery_health != "?" AND $response_battery_health < 80){
			$title = "Battery health level";
			$content = "The battery health for printer " . $row_printer["name"] . " is lower than 80%.";
			create_notification($title, $content);
		}
	}
}

// updating the labels counter and checking the threshold 
$used_labels = 0;
$stmt = $conn->prepare("SELECT * FROM paper");
$stmt->execute();
$result_paper = $stmt->get_result();
while($row_paper = $result_paper->fetch_assoc()){
	$stmt = $conn->prepare("SELECT * FROM printers WHERE paper = ?");
	$stmt->bind_param("s", $row_paper["id"]); 
	$stmt->execute();
	$result_printer = $stmt->get_result();
	while($row_printer = $result_printer->fetch_assoc()){
		$command_label_odometer = '! U1 getvar "odometer.media_marker_count1" \r\n';
		socket_write($socket, $command_label_odometer, strlen($command_label_odometer));
		socket_recv($socket, $response_label_odometer, 51200, MSG_WAITALL);
		$response_label_odometer = explode(" ", $response_label_odometer);
		$used_labels += $response_label_odometer[2];
		
	}
	// updating label counter
	$current_labels = $row_paper["total_labels"] - $used_labels;
	$stmt = $conn->prepare("UPDATE paper SET current_labels = ? WHERE id = ?");
	$stmt->bind_param("ss", $current_labels, $row_paper["id"]); 
	$update = $stmt->execute();
	
	// checking label threshold
	if($row_paper["current_labels"] / $row_paper["labels_per_roll"] < $row_paper["threshold"]){
		// if the quantity is below the threshold, a notification is created
		$title = "Label quantity threshold";
		$content = "The inventory quantity of label " . $row_paper["name"] . " (sku -> " . $row_paper["sku"] . ") is below the threshold set (" . $row_paper["threshold"] . " rolls).";
		create_notification($title, $content);
	}
}

// cleaning alerts older than 30 days
$stmt = $conn->prepare("DELETE FROM alerts WHERE `date` <= DATE_SUB(SYSDATE(), INTERVAL 30 DAY)");
$stmt->execute();


// executing the tasks
$stmt = $conn->prepare("SELECT * FROM tasks WHERE completed IS NULL");
$stmt->execute();
$result_task = $stmt->get_result();
while($row = $result_task->fetch_assoc()){
	$tasks[] = $row;
}
foreach($tasks as $task){
	if($task["task_type"] == "reset_odometer"){
		$data = '! U1 setvar "odometer.media_marker_count1" "0" \r\n';
		$port = 9100;
		$sec = 3;
		$usec = 0;
	}
	elseif($task["task_type"] == "send_resource"){	
		$port = 9100;
		$sec = 60;
		$usec = 0;
		$stmt = $conn->prepare("SELECT type, filename FROM resources WHERE id = ?");
		$stmt->bind_param("s", $task["resource_id"]); 
		$stmt->execute();
		$result_resource = $stmt->get_result();
		$resource = $result_resource->fetch_assoc();
		$path = "files/resources/" . $resource["type"] . "/" . $resource["filename"];
		$data = file_get_contents($path);
	}

	$socket = socket_create(AF_INET, SOCK_STREAM, 0);
	socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array("sec"=>$sec, "usec"=>$usec));
	socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array("sec"=>$sec, "usec"=>$usec));
	if(socket_connect($socket, $task["target_ip"], $port)){
		socket_write($socket, $data, strlen($data));
		
		// updating the task in db to mark it as completed
		$stmt = $conn->prepare("UPDATE tasks SET completed = CURRENT_TIMESTAMP WHERE id = ?");
		$stmt->bind_param("s", $task["id"]); 
		$stmt->execute();
		
		socket_close($socket);
	}
}
?>