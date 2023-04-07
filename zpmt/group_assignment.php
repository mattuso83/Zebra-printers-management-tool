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
	<h2>Assign printers to groups</h2>
	<h3>Select the printer(s), the action and the group you want to associate or dissociate the printers to. Max 10 groups per printer.</h3>
	<div class="grid_container">
		<div class="grid_item" id="box_printer_list">
			<form action="group_actions.php" method="post">
				<table>
					<th>Select</th>
					<th>Printer name</th>
					<th>Serial number</th>
					<th>IP address</th>
					<th>Associated groups</th>
					<th>Associated papers</th>
					<th>Associated ribbons</th>
				<?php
					$stmt = $conn->prepare("SELECT * FROM printers");
					$stmt->execute();
					$result_printer = $stmt->get_result();
					while($row_printer = $result_printer->fetch_assoc()){
						$printer_groups = explode(",",$row_printer['groups']);
						$printer_groups = array_filter($printer_groups);
						// getting the associated groups
						foreach ($printer_groups as $group_found){
							$stmt = $conn->prepare("SELECT * FROM groups WHERE id = ?");
							$stmt->bind_param("s", $group_found); 
							$stmt->execute();
							$group_list = $stmt->get_result();
							while($row_group = $group_list->fetch_assoc()){
								$array_groups[] = $row_group;
							}
						}
						// getting the associated papers
						$printer_paper = explode(",",$row_printer['paper']);
						$printer_paper = array_filter($printer_paper);
						foreach ($printer_paper as $paper_found){
							$stmt = $conn->prepare("SELECT * FROM paper WHERE id = ?");
							$stmt->bind_param("s", $paper_found); 
							$stmt->execute();
							$paper_list = $stmt->get_result();
							while($row_paper = $paper_list->fetch_assoc()){
								$array_paper[] = $row_paper;
							}
						}
						// getting the associated ribbons
						$printer_ribbon = explode(",",$row_printer['ribbon']);
						$printer_ribbon = array_filter($printer_ribbon);
						foreach ($printer_ribbon as $ribbon_found){
							$stmt = $conn->prepare("SELECT * FROM ribbon WHERE id = ?");
							$stmt->bind_param("s", $ribbon_found); 
							$stmt->execute();
							$ribbon_list = $stmt->get_result();
							while($row_ribbon = $ribbon_list->fetch_assoc()){
								$array_ribbon[] = $row_ribbon;
							}
						}
						
						echo "<tr>";
						echo "<td><input type='checkbox' name='printer_id[]' value='" . $row_printer["id"] . "'></td>";
						echo "<td>" . $row_printer["name"] . "</td>
								<td>" . $row_printer["sn"] . "</td>
								<td>" . $row_printer["ip"] . "</td>";
						echo "<td>";
						if(!empty($array_groups)){
							foreach($array_groups as $group){
								echo "<button class='button_associations'><a href='group_details.php?group_id=" . $group["id"] . "'>" . $group["name"] . "</a></button>";
							}
						}
						echo "</td><td>";
						if(!empty($array_paper)){
							foreach($array_paper as $paper){
								echo "<button class='button_associations'><a href='media_details.php?paper_id=" . $paper["id"] . "'>" . $paper["name"] . "</a></button>";
							}
							
						}
						echo "</td><td>";
						if(!empty($array_ribbon)){
							foreach($array_ribbon as $ribbon){
								echo "<button class='button_associations'><a href='media_details.php?ribbon_id=" . $ribbon["id"] . "'>" . $ribbon["name"] . "</a></button>";
							}
						}
						echo "</td></tr>";
						unset($array_paper);
						unset($array_groups);
						unset($array_ribbon);
					}
				?>
				</table>
		</div>
		<div class="grid_item" id="box_group_selection">
				<p>
					<select name="action" id="action" required>
						<option value="" disabled selected>Select an action:</option>
						<option value="add_to_group">Add printer(s) to group</option>
						<option value="remove_from_group">Remove printer(s) from group</option>
					</select>
				</p>
				<p>
					<label for="group"></label>
					<select name="group_id" id="group" required>
						<option value="" disabled selected>Select a group:</option>
					<?php
						$stmt = $conn->prepare("SELECT * FROM groups");
						$stmt->execute();
						$result_groups = $stmt->get_result();
						while($row_group = $result_groups->fetch_assoc()){
							echo "<option value='" . $row_group["id"] . "' >" . $row_group["name"] . "</option>";
						}
						?>
					</select>
				</p>
				<p>
					<input type="submit" value="Submit">
					<input type="reset" value="Reset">
				</p>
			</form>
		</div>
	</div>
</div>
</body>
</html>