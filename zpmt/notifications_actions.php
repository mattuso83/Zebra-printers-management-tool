<?php
include "conn.php";
// updating the notification as viewed
if(isset($_GET["notification_id"])){
	$stmt = $conn->prepare("UPDATE notifications SET checked = 1 WHERE id = ?");
	$stmt->bind_param("s", $_GET["notification_id"]); 
	$update = $stmt->execute();
}

// deleting the notification
elseif($_POST["action"] == "delete_notification"){
	foreach($_POST["notification_id"] as $notification_id){
		$stmt = $conn->prepare("DELETE FROM notifications WHERE id = ?");
		$stmt->bind_param("s", $notification_id); 
		$stmt->execute();
	}
}

echo "<script>location.href='notifications_list.php';</script>";
?>