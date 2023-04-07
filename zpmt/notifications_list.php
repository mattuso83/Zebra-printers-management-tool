<!DOCTYPE html>
<html>
<head>
<title>ZPMT - Notifications</title>
<link href="css/layout.css" rel="stylesheet" type="text/css" />
<link href="css/nav_bar.css" rel="stylesheet" type="text/css" />
<link href="css/notifications.css" rel="stylesheet" type="text/css" />
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" type="text/css" />
<script src="http://code.jquery.com/jquery-latest.min.js"></script>
<script src="js/jquery.menu.js"></script>
</head>
<body>
<div class="content">
<?php
	include "conn.php";
	include "nav_bar.php";
?>
	<h2>Notifications</h2>
	<form action="notifications_actions.php" method="post">
		<input type="hidden" name="action" value="delete_notification">
		<table>
			<th>Select</th>
			<th>Title</th>
			<th>Content</th>
			<th>Date</th>
<?php
	$stmt = $conn->prepare("SELECT * FROM notifications ORDER BY `date` DESC");
	$stmt->execute();
	$result_notifications = $stmt->get_result();
	while($row_notification = $result_notifications->fetch_assoc()){
		echo "<tr>";
			echo "<td><input type='checkbox' name='notification_id[]' value='" . $row_notification["id"] . "'></td>";
			if($row_notification["checked"] == 0){
				echo "<td class='title'><a href='notifications_actions.php?notification_id=" . $row_notification["id"] . "'><b>" . $row_notification["title"] . "</b></a></td>";
				echo "<td><b>" .$row_notification["content"] . "</b></td>";
				//echo "<td><b>" . substr($row_notification["content"], 0, 100) . "...</b></td>";
				echo "<td class='date'><b>" . $row_notification["date"] . "</b></td>";
			}
			else{
				echo "<td class='title'><a href='notifications_actions.php?notification_id=" . $row_notification["id"] . "'>" . $row_notification["title"] . "</a></td>";
				echo "<td>" .$row_notification["content"] . "</td>";
				//echo "<td>" . substr($row_notification["content"], 0, 100) . "...</td>";
				echo "<td class='date'>" . $row_notification["date"] . "</td>";
			}
		echo "</tr>";
	}
?>
		</table>
		<p>
			<input type="submit" value="Delete">
			<input type="reset" value="Reset">
		</p>
	</form>
</div>
</body>
</html>