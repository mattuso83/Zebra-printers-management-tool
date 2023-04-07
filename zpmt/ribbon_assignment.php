<!DOCTYPE html>
<html>
<head>
<title>Media management</title>
<link href="css/layout.css" rel="stylesheet" type="text/css" />
<link href="css/nav_bar.css" rel="stylesheet" type="text/css" />
<link href="css/media_management.css" rel="stylesheet" type="text/css" />
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
	<h2>Assign printers to ribbon</h2>
	<h3>Select the printer(s), the action and the ribbon you want to associate or dissociate the printers to.</h3>
	<div class="grid_container_assignment">
		<div class="grid_item" id="box_printer_list">
			<form action="media_actions.php" method="post">
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
						// getting the associated paper
						$stmt = $conn->prepare("SELECT * FROM paper WHERE id = ?");
						$stmt->bind_param("s", $row_printer["paper"]); 
						$stmt->execute();
						$paper_list = $stmt->get_result();
						$row_paper = $paper_list->fetch_assoc();
						
						// getting the associated ribbon
						$stmt = $conn->prepare("SELECT * FROM ribbon WHERE id = ?");
						$stmt->bind_param("s", $row_printer["ribbon"]); 
						$stmt->execute();
						$ribbon_list = $stmt->get_result();
						$row_ribbon = $ribbon_list->fetch_assoc();
						
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
						if(!empty($row_paper)){
							echo "<button class='button_associations'><a href='media_details.php?paper_id=" . $row_paper["id"] . "'>" . $row_paper["name"] . "</a></button>";
						}
						echo "</td><td>";
						if(!empty($row_ribbon)){
							echo "<button class='button_associations'><a href='media_details.php?ribbon_id=" . $row_ribbon["id"] . "'>" . $row_ribbon["name"] . "</a></button>";
						}
						echo "</td></tr>";
						unset($array_paper);
						unset($array_groups);
						unset($array_ribbon);
					}
				?>
				</table>
		</div>
		<div class="grid_item" id="box_media_selection">
				<p>
					<select name="action" id="action" required>
						<option value="" disabled selected>Select an action:</option>
						<option value="associate_ribbon">Associate ribbon to printer(s)</option>
						<option value="dissociate_ribbon">Dissociate ribbon to printer(s)</option>
					</select>
				</p>
				<p>
					<label for="paper"></label>
					<select name="ribbon_id" id="ribbon" required>
						<option value="" disabled selected>Select a ribbon:</option>
					<?php
						
						$stmt = $conn->prepare("SELECT * FROM ribbon");
						$stmt->execute();
						$result_ribbon = $stmt->get_result();
						while($row_ribbon = $result_ribbon->fetch_assoc()){
							echo "<option value='" . $row_ribbon["id"] . "' >" . $row_ribbon["name"] . "</option>";
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