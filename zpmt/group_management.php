<!DOCTYPE html>
<html>
<head>
<title>Group management</title>
<link href="css/layout.css" rel="stylesheet" type="text/css" />
<link href="css/nav_bar.css" rel="stylesheet" type="text/css" />
<link href="css/group_management.css" rel="stylesheet" type="text/css" />
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
	<h2>Groups management</h2>
	<div class="grid_container">
		<div class ="grid_item" id="box_create_group">
			<h3>Create a group</h3>
			<form action="group_actions.php" method="post">
				<input type="hidden" name="action" value="create_group">
			<?php
				// checking the number of groups in the db
				$stmt = $conn->prepare("SELECT * FROM groups");
				$stmt->execute();
				$stmt->store_result();
				$group_number = $stmt->num_rows;
				if(is_null($group_number) OR ($group_number < 10)){
					if(is_null($group_number)){
						echo "<p>No group has been created. <br>Create a group:</p>";
					}
					elseif($group_number < 10){
						$group_remaining = 10 - $group_number;
						echo "<p>There are " . $group_number . " groups already, you can still create " . $group_remaining . " groups.</p>";
					}
					echo "<form action='group_actions.php' method='post'>
							<input type='hidden' name='action' value='create_group'>
							<table id='create'>
								<tr>
									<td><label for='group_name'>Group name: </label></td>
									<td><input type='text' id='group_name' name='group_name' required></td>
								</tr>
								<tr>
									<td><label for='group_description'>Group description: </label></td>
									<td><input type='text' id='group_description' name='group_description'></td>
								</tr>
							</table>
							<p>
								<input type='submit' value='Submit'>
								<input type='reset' value='Reset'>
							</p>
						</form>";
				}
				else{
					echo "You've reached the maximum number of groups.";
				}
			?>
		</div>
		<div class ="grid_item" id="box_manage_group">
			<h3>See group details or delete a group</h3>
			<form action="group_actions.php" method="post">
				<input type="hidden" name="action" value="delete_group">
				<table>
					<th>Select</th>
					<th>Group ID</th>
					<th>Group name</th>
					<th>Group description</th>
					<th>Creation date</th>
				<?php
					// listing the exsisting groups
					$stmt = $conn->prepare("SELECT * FROM groups");
					$stmt->execute();
					$result_groups = $stmt->get_result();
					while($row_group = $result_groups->fetch_assoc()){
						echo "<tr>";
						echo "<td><input type='checkbox' name='delete_group[]' value='" . $row_group["id"] . "'></td>";
						echo "<td>" . $row_group["id"] . "</td>";
						echo "<td><a href='group_details.php?group_id=" . $row_group["id"] . "'>" . $row_group["name"] . "</a></td>";
						echo "<td>" . $row_group["description"] . "</td>";
						echo "<td>" . $row_group["date"] . "</td>";
						echo "</tr>";
					}
				?>
				</table>
				<p>
					<input type='submit' value='Delete'>
					<input type='reset' value='Reset'>
				</p>
			</form>
		</div>
	</div>
</div>
</body>
</html>