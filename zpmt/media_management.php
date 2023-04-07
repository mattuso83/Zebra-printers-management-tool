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
	<h2>Media inventory</h2>
	<div class="grid_container">
		<div class="grid_item" id="box_media_insert">
			<div class="grid_item" id="box_media_insert_title">
				<h3>Insert media</h3>
			</div>
			<div class="grid_item" id="box_media_paper_insert">
				<h3>Insert a label\receipt in inventory</h2>
				<form action='media_actions.php' method='post'>
					<input type='hidden' name='action' value='insert_paper'>
					<table>
						<tr>
							<td>Select the type of paper</td>
							<td><select name="type" id="type" required>
									<option value="gaps">Label with gaps</option>
									<option value="mark">Label with black marks</option>
									<option value="receipt">Receipt</option>
								</select>
							</td>
						</tr>
						<tr>
							<td><label for='sku'>SKU: </label></td>
							<td><input type='text' id='sku' name='sku'></td>
						</tr>
						<tr>
							<td><label for='name'>Name: </label></td>
							<td><input type='text' id='name' name='name' required></td>
						</tr>
						<tr>
							<td><label for='width'>Label width (mm): </label></td>
							<td><input type='text' id='width' name='width' required></td>
						</tr>
						<tr>
							<td><label for='height'>Label height (mm): </label></td>
							<td><input type='text' id='height' name='height'></td>
						</tr>
						<tr>
							<td><label for='labels_per_roll'>Labels per roll (for receipt enter the total length in m): </label></td>
							<td><input type='text' id='labels_per_roll' name='labels_per_roll'></td>
						</tr>
						<tr>
							<td><label for='quantity'>Number of rolls in inventory: </label></td>
							<td><input type='text' id='rolls_quantity' name='quantity'></td>
						</tr>
						<tr>
							<td><label for='threshold'>Number of rolls for threshold alert: </label></td>
							<td><input type='text' id='threshold' name='threshold'></td>
						</tr>
					</table>	
					<p>
						<input type='submit' value='Submit'>
						<input type='reset' value='Reset'>
					</p>
				</form>
				<p>Click <a href="https://www.zebrazipship.com/filestore/Downloads/ZipShipSupplies_english_euro.pdf" target="_blank">here</a> for the supplies catalogue.</p>
			</div>
			<div class="grid_item box" id="media_ribbon_insert">
				<h3>Insert a ribbon in inventory</h2>
				<form action='media_actions.php' method='post'>
					<input type='hidden' name='action' value='insert_ribbon'>
					<table>
						<tr>
							<td><label for='sku'>SKU: </label></td>
							<td><input type='text' id='sku' name='sku'></td>
						</tr>
						<tr>
							<td><label for='name'>Name: </label></td>
							<td><input type='text' id='name' name='name' required></td>
						</tr>
						<tr>
							<td><label for='width'>Width (mm): </label></td>
							<td><input type='text' id='width' name='width' required></td>
						</tr>
						<tr>
							<td><label for='roll_length'>Ribbon length (m): </label></td>
							<td><input type='text' id='roll_length' name='roll_length' ></td>
						</tr>
						<tr>
							<td><label for='quantity'>Number of rolls in inventory: </label></td>
							<td><input type='text' id='quantity' name='quantity' ></td>
						</tr>
					</table>
					<p>
						<input type='submit' value='Submit'>
						<input type='reset' value='Reset'>
					</p>
				</form>
				<p>Click <a href="https://www.zebrazipship.com/filestore/Downloads/ZipShipSupplies_english_euro.pdf" target="_blank">here</a> for the supplies catalogue.</p>
			</div>
		</div>
		
		<div class="grid_item" id="box_media_lists">
			<div class="grid_item" id="box_media_manag_title">
				<h3>View\Delete media</h3>
			</div>
			<div class="grid_item" id="box_paper_list">
				<h3>Label\Receipt list</h3>
			<?php
				
				$stmt = $conn->prepare("SELECT * FROM paper");
				$stmt->execute();
				$result_paper = $stmt->get_result();
				if(mysqli_num_rows($result_paper) == 0){
					echo "No paper inserted. Please insert a paper above.";
				}
				else{
			?>
					<form action='media_actions.php' method='post'>
						<input type='hidden' name='action' value='delete_paper'>
						<table>
							<th>Select</th>
							<th>Name</th>
							<th>SKU</th>
							<th>Type</th>
							<th>Width</th>
							<th>Height</th>
							<th>Labels per roll</th>
							<th>Quantity</th>
						<?php
							while($row_paper = $result_paper->fetch_assoc()){
								echo "<tr>";
								echo "<td><input type='checkbox' name='paper_id[]' value='" . $row_paper["id"] . "'></td>";
								echo "<td><a href=media_details.php?paper_id=" . $row_paper["id"] . ">" . $row_paper["name"] . "</a></td>";
								echo "<td>" . $row_paper["sku"] . "</td>";
								echo "<td>" . $row_paper["type"] . "</td>";
								echo "<td>" . $row_paper["width"] . "</td>";
								echo "<td>" . $row_paper["height"] . "</td>";
								echo "<td>" . $row_paper["labels_per_roll"] . "</td>";
								echo "<td>" . round($row_paper["current_labels"] / intval($row_paper["labels_per_roll"]), 2) . "</td>";
								echo "</tr>";
							}
						echo "</table>";
						echo "<p>
								<input type='submit' value='Delete'>
								<input type='reset' value='Reset'>
							  </p>";
					echo "</form>";
				}
				?>
			</div>
			<div class="grid_item" id="box_ribbon_list">
				<h3>Ribbon list</h3>
			<?php
				$stmt = $conn->prepare("SELECT * FROM ribbon");
				$stmt->execute();
				$result_ribbon = $stmt->get_result();
				if(mysqli_num_rows($result_ribbon) == 0){
					echo "No ribbon inserted. Please insert a ribbon above.";
				}
				else{
			?>
					<form action='media_actions.php' method='post'>
						<input type='hidden' name='action' value='delete_ribbon'>
						<table>
							<th>Select</th>
							<th>Name</th>
							<th>SKU</th>
							<th>Width</th>
							<th>Roll length</th>
							<th>Quantity</th>			
					<?php
						while($row_ribbon = $result_ribbon->fetch_assoc()){
							echo "<tr>";
								echo "<td><input type='checkbox' name='ribbon_id[]' value='" . $row_ribbon["id"] . "'></td>";
								echo "<td><a href=media_details.php?ribbon_id=" . $row_ribbon["id"] . ">" . $row_ribbon["name"] . "</a></td>";
								echo "<td>" . $row_ribbon["sku"] . "</td>";
								echo "<td>" . $row_ribbon["width"] . "</td>";
								echo "<td>" . $row_ribbon["roll_length"] . "</td>";
								echo "<td>" . $row_ribbon["rolls_quantity"] . "</td>";
								echo "</tr>";
						}
						echo "</table>";
						echo "<p>
								<input type='submit' value='Delete'>
								<input type='reset' value='Reset'>
							  </p>";
					echo "</form>";
				}
				?>
			</div>
		</div>
	</div>
</div>
</body>
</html>