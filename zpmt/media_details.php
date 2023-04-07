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
	echo "<h2>Media details</h2>";
	echo "<div class='grid_container'>";
		if(!empty($_GET["paper_id"])){
			echo "<div class='grid_item' id='box_edit_media_details'>";
				$stmt = $conn->prepare("SELECT * FROM printers WHERE paper = ?");
				$stmt->bind_param("s", $_GET["paper_id"]); 
				$stmt->execute();
				$result_printer = $stmt->get_result();
				while($row = $result_printer->fetch_assoc()){
					$printer_row[] = $row;
				}
				// checking the number of printers associated with the paper
				$num_rows = mysqli_num_rows($result_printer);
				// getting paper details
				$stmt = $conn->prepare("SELECT * FROM paper WHERE id = ?");
				$stmt->bind_param("s", $_GET["paper_id"]); 
				$stmt->execute();
				$result_paper = $stmt->get_result();
				$paper = $result_paper->fetch_assoc();
				// checking if the paper exsists in the db
				if($paper == NULL){
					echo "<p>This paper doesn't exsist.</p>";
				}
				else{
				?>
					<h3>Edit paper details</h3>
					<form action='media_actions.php' method='post'>
						<input type='hidden' name='action' value='edit_paper'>
						<input type='hidden' name='id' value='<?php echo $paper["id"]; ?>'>
						<table>
							<tr>
								<td><label for='sku'>SKU: </label></td>
								<td><input type='text' id='sku' name='sku' value='<?php echo $paper["sku"]; ?>'></td>
							</tr>
							<tr>
								<td><label for='name'>Media name: </label></td>
								<td><input type='text' id='name' name='name' value='<?php echo $paper["name"]; ?>'></td>
							</tr>
							<tr>
								<td><label for='type'>Media type: </label></td>
								<td><select name="type" id="type" required>
										<option value="gaps">Label with gaps</option>
										<option value="mark">Label with black marks</option>
										<option value="receipt">Receipt</option>
									</select>
								</td>
							</tr>
							<tr>
								<td><label for='width'>Media width (mm): </label></td>
								<td><input type='text' id='width' name='width' value='<?php echo $paper["width"]; ?>'></td>
							</tr>
							<tr>
								<td><label for='height'>Media height (mm): </label></td>
								<td><input type='text' id='height' name='height' value='<?php echo $paper["height"]; ?>'></td>
							</tr>
							<tr>
								<td><label for='labels_per_roll'>Labels per roll: </label></td>
								<td><input type='text' id='labels_per_roll' name='labels_per_roll' value='<?php echo $paper["labels_per_roll"]; ?>'></td>
							</tr>
							<tr>
								<td><label for='threshold'>Number of rolls for threshold alert: </label></td>
								<td><input type='text' id='threshold' name='threshold' value='<?php echo $paper["threshold"]; ?>'></td>
							</tr>
						</table>
						<p>
							<input type='submit' value='Submit'>
							<input type='reset' value='Reset'>
						</p>
					</form>
				</div>
				<div class="grid_item" id="box_edit_media_quantity">
					<h3>Edit inventory quantity</h3>
					<form action='media_actions.php' method='post'>
						<input type='hidden' name='action' value='edit_paper_quantity'>
						<input type='hidden' name='paper_id' value='<?php echo $paper["id"]; ?>'>
						<table>
							<tr>
								<td>Current rolls quantity</td>
							<?php
								$port = 9100;
								$sec = 3;
								$usec = 0;
								// initializing label counters for printer online and offline
								$used_labels_online = 0;
								$used_labels_offline = 0;
								// checking the printers online and offline
								if(!empty($printer_row)){
									foreach ($printer_row as $printer){
										$socket = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket\n");
										socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec'=>$sec, 'usec'=>$usec));
										socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec'=>$sec, 'usec'=>$usec));
										if(socket_connect($socket, $printer["ip"], $port)){
											// checking if the printer is online
											$network_status = "online";
											// if online, the data is retrieved from the printer
											$command = '! U1 getvar "odometer.media_marker_count1" \r\n';
											socket_write($socket, $command, strlen($command));
											socket_recv($socket, $response, 1024, MSG_WAITALL);
											socket_close($socket);
											$response = str_replace('"', "", $response);
											$response = explode(" ", $response);
											$used_labels_online += $response[2];
										}
										else{
											$network_status = "offline";
											$path = "files/printers/" . $printer["sn"] . "/" . $printer["sn"] . "_golden_allcv.txt";
											// if the printer is offline, the data is retrieved from the latest allcv report
											$allcv_content = file_get_contents($path);
											$command = "odometer.media_marker_count1";
											$pattern = "/^.*$command .*\$/m";
											preg_match($pattern, $allcv_content, $match);
											$response = implode(" ", $match);
											$response = explode(" ", $response);
											$used_labels_offline += $response[4];
										}
										// creating an array for the associated printers tabel
										$associated_printers[] = array('name' => $printer["name"], 'sn' => $printer["sn"], 'ip' => $printer["ip"], 'network_status' => $network_status);
									}
									// calculating the number of labels used by the online and offline printers
									$used_labels = $used_labels_online + $used_labels_offline;
									// calculating the number of remaining rolls
									echo "<td>" . round(($paper["total_labels"] - $used_labels) / intval($paper["labels_per_roll"]), 2) . "</td>";
								}
								else{
									echo "<td>" . round(($paper["total_labels"]) / intval($paper["labels_per_roll"]), 2) . "</td>";
								} 
							?>
								</tr>
								<tr>
									<td>Enter new roll quantity</td>
									<td><input type="text" name="new_quantity"></td>
								</tr>
							</table>
							<p>
								<input type='submit' value='Submit'>
								<input type='reset' value='Reset'>
							</p>
						</form>	
					</div>
					<div class='grid_item' id='box_associated_printers'>
						<h3>Paper associated to the following printers</h3>
					<?php
						if($num_rows == 0){
							echo "<p>No printer associated with paper " . $paper["name"] . "</p>";
						}
						else{
							echo "<p>" . $num_rows . " printer(s) associated.</p>";
							echo "<table>
									<th>Printer name</th>
									<th>Serial number</th>
									<th>IP address</th>
									<th>Status</th>";
								foreach ($associated_printers as $associated_printer){
									if($associated_printer["network_status"] == "offline"){
										echo "<tr style='background-color:red'>";
										echo "<td>" . $associated_printer["name"] . "</td>";
									}
									elseif(($associated_printer["network_status"] == "online")){
										echo "<tr style='background-color:green'>";
										echo "<td><a href='printer_details.php?printer_sn=" . $associated_printer["sn"] . "'>" . $associated_printer["name"] . "</a></td>";
									}
									echo "<td>" . $associated_printer["sn"] . "</td>
										<td>" . $associated_printer["ip"] . "</td>
										<td>" . $associated_printer["network_status"] . "</td>";
									echo "</tr>";
								}
							}
							echo "</table>";
						echo "</div>";
						}
						echo "</div>";
	}
	elseif(!empty($_GET["ribbon_id"])){
		echo "<div class='grid_item' id='box_edit_media_details'>";
			$stmt = $conn->prepare("SELECT * FROM printers WHERE ribbon = ?");
			$stmt->bind_param("s", $_GET["ribbon_id"]); 
			$stmt->execute();
			$result_printer = $stmt->get_result();
			while($row = $result_printer->fetch_assoc()){
				$printer_row[] = $row;
			}
			// checking the number of printers associated with the ribbon
			$num_rows = mysqli_num_rows($result_printer);
			// getting ribbon details
			$stmt = $conn->prepare("SELECT * FROM ribbon WHERE id = ?");
			$stmt->bind_param("s", $_GET["ribbon_id"]); 
			$stmt->execute();
			$result_ribbon = $stmt->get_result();
			$ribbon = $result_ribbon->fetch_assoc();
			// checking if the ribbon exsists in the db
			if($ribbon == NULL){
				echo "<p>This ribbon doesn't exsist.</p>";
			}
			else{
		?>
				<h3>Edit Ribbon details</h3>
				<form action="media_actions.php" method="post">
						<input type="hidden" name="action" value="edit_ribbon">
						<input type="hidden" name="id" value="<?php echo $ribbon["id"]; ?>">
						<table>
							<tr>
								<td><label for="sku">SKU: </label></td>
								<td><input type="text" id="sku" name="sku" value="<?php echo $ribbon["sku"]; ?>"></td>
							</tr>
							<tr>
								<td><label for="name">Ribbon name: </label>
								<td><input type="text" id="name" name="name" value="<?php echo $ribbon["name"]; ?>"><br>
							</tr>
							<tr>
								<td><label for="width">Ribbon width (mm): </label>
								<td><input type="text" id="width" name="width" value="<?php echo $ribbon["width"]; ?>"><br>
							</tr>
							<tr>
								<td><label for="type">Roll length (m): </label>
								<td><input type="text" id="roll_length" name="roll_length" value=<?php echo $ribbon["roll_length"]; ?>><br>
							</tr>
						</table>
						<p>
							<input type="submit" value="Submit">
							<input type="reset" value="Reset">
						</p>
					</form>
				</div>
				<div class="grid_item" id="box_edit_media_quantity">
					<h3>Edit inventory quantity</h3>
					<form action='media_actions.php' method='post'>
						<input type='hidden' name='action' value='edit_ribbon_quantity'>
						<input type='hidden' name='ribbon_id' value='<?php echo $ribbon["id"]; ?>'>
						<table>
							<tr>
								<td>Current rolls quantity</td>
							<?php
								$port = 9100;
								$sec = 3;
								$usec = 0;
								// initializing label counters for printer online and offline
								$used_ribbon_online = 0;
								$used_ribbon_offline = 0;
								$sum_starting_points = 0;
								// checking the printers online and offline
								if(!empty($printer_row)){
									foreach ($printer_row as $printer){
										$socket = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket\n");
										socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec'=>$sec, 'usec'=>$usec));
										socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec'=>$sec, 'usec'=>$usec));
										if(socket_connect($socket, $printer["ip"], $port)){
											// checking if the printer is online
											$network_status = "online";
											// if online, the data is retrieved from the printer
											$command = '! U1 getvar "odometer.net_ribbon_length" \r\n';
											socket_write($socket, $command, strlen($command));
											socket_recv($socket, $response, 1024, MSG_WAITALL);
											socket_close($socket);
											$response = explode(" ", $response);	
											$used_ribbon_online += $response[2];
										}
										else{
											$network_status = "offline";
											$path = "files/printers/" . $printer["sn"] . "/" . $printer["sn"] . "_golden_allcv.txt";
											// if the printer is offline, the data is retrieved from the latest allcv report
											$allcv_content = file_get_contents($path);
											$command = "odometer.net_ribbon_length";
											$pattern = "/^.*$command .*\$/m";
											preg_match($pattern, $allcv_content, $match);
											$response = implode(" ", $match);
											$response = explode(" ", $response);
											$response_cleaned = array_filter($response);
											if(empty($response_cleaned)){
												
												$used_ribbon_offline += 0;
											}
											else{
												$used_ribbon_offline += $response[4];
											}
										}
										$sum_starting_points += $printer["ribbon_starting_point"];
										// creating an array for the associated printers tabel
										$associated_printers[] = array('name' => $printer["name"], 'sn' => $printer["sn"], 'ip' => $printer["ip"], 'network_status' => $network_status);
									}
									// calculating the centimeters of ribbon used by the online and offline printers
									$used_ribbon_odometers = $used_ribbon_online + $used_ribbon_offline;
									// calculating the meters of ribbon used by the online and offline printers
									$used_ribbon_m = ($used_ribbon_odometers - $sum_starting_points) / 100;
									// calculating the number of remaining rolls
									echo "<td>" . round(($ribbon["total_rolls"] - $used_ribbon_m) / intval($ribbon["roll_length"]), 2) . "</td>";
								}
								else{
									echo "<td>" . round(($ribbon["total_rolls"]) / intval($ribbon["roll_length"]), 2) . "</td>";
								} 
							?>
								</tr>
								<tr>
									<td>Enter new roll quantity</td>
									<td><input type="text" name="new_quantity"></td>
								</tr>
							</table>
							<p>
								<input type='submit' value='Submit'>
								<input type='reset' value='Reset'>
							</p>
						</form>	
					</div>
				<div class="grid_item" id="box_associated_printers">
					<h3>Ribbon associated to the following printers</h3>
				<?php
					if($num_rows == 0){
						echo "<p>No printer associated with ribbon " . $ribbon["name"] . "</p>";
					}
					else{
						echo "<p>" . $num_rows . " printer(s) associated.</p>";
						echo "<table>
								<th>Printer name</th>
								<th>Serial number</th>
								<th>IP address</th>
								<th>Status</th>";
						// checking the printers online and offline
						foreach ($associated_printers as $associated_printer){
							if($associated_printer["network_status"] == "offline"){
								echo "<tr style='background-color:red'>";
								echo "<td>" . $associated_printer["name"] . "</td>";
							}
							elseif(($associated_printer["network_status"] == "online")){
								echo "<tr style='background-color:green'>";
								echo "<td><a href='printer_details.php?printer_sn=" . $associated_printer["sn"] . "'>" . $associated_printer["name"] . "</a></td>";
							}
							echo "<td>" . $associated_printer["sn"] . "</td>
								<td>" . $associated_printer["ip"] . "</td>
								<td>" . $associated_printer["network_status"] . "</td>";
							echo "</tr>";
						}
						echo "</table>";
					echo "</div>";
					}
				echo "</div>";
			}
	}
	else{
		echo "This media doesn't exist.";
	}
?>
</div>
</body>
</html>