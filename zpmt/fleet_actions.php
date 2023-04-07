<!DOCTYPE html>
<html>
<head>
<title>Fleet management</title>
<link href="css/layout.css" rel="stylesheet" type="text/css" />
<link href="css/nav_bar.css" rel="stylesheet" type="text/css" />
<link href="css/fleet_management.css" rel="stylesheet" type="text/css" />
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" rel="stylesheet" type="text/css" />
<script src="http://code.jquery.com/jquery-latest.min.js"></script>
<script src="js/jquery.menu.js"></script>
</head>
<body>
<div class="content">
<?php
include "nav_bar.php";
set_time_limit(500);
switch ($_POST["action"]){
	case "scan":
		echo "<h2>Results of the network scan: </h2>";
		if(!empty($_POST["first_ip"]) AND !empty($_POST["last_ip"])){
			include "conn.php";
			$ip_start = $_POST["first_ip"];
			$ip_end =  $_POST["last_ip"];
			echo "<h3>IP range from " . $ip_start . " to " . $ip_end . "</h3>";
			for($ip = ip2long($ip_start); $ip<=ip2long($ip_end); $ip++) {
				$port = 9100;
				$ipx = long2ip($ip);
				// If the host doesn't respond..
				if(!(@fsockopen($ipx, $port, $errno, $errstr, 1))){
					$stmt = $conn->prepare("SELECT * FROM printers WHERE ip = ?");
					$stmt->bind_param("s", $ipx); 
					$stmt->execute();
					$result_printer = $stmt->get_result();
					if($row = $result_printer->fetch_assoc()){
						// ..but the IP is in database, status is updated to 0 (offline)
						$stmt = $conn->prepare("UPDATE printers SET online = 0 WHERE ip = ?");
						$stmt->bind_param("s", $ipx); 
						$stmt->execute();
						echo "<p>" . $ipx . " Host is a Zebra printer (sn: " . $row["sn"] . ") present in database. Status: offline.</p>";
					}
					else{
						// ..but the IP isn't in database, no action is performed
						echo "<p>" . $ipx . " Host is not in database or is not a Zebra printer.</p>";
					}
				}
				else{
					// if the host responds..
					$stmt = $conn->prepare("SELECT * FROM printers WHERE ip = ?");
					$stmt->bind_param("s", $ipx); 
					$stmt->execute();
					$result_printer = $stmt->get_result();
					if($row = $result_printer->fetch_assoc()){
						// and the IP is already in the database, status is updated to 1 (online)
						$stmt = $conn->prepare("UPDATE printers SET online = 1 WHERE sn = ?");
						$stmt->bind_param("s", $row['sn']); 
						$stmt->execute();
						echo "<p>" . $ipx . " already present in database and assigned to printer <b>" . $row["sn"] . "</b>. Status: online.</p>";
					}
					else{
						// set a low socket timeout just to check if the host is a Zebra printer						
						$sec = 1;
						$usec = 0;
						$socket = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket\n");
						socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec'=>$sec, 'usec'=>$usec));
						socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec'=>$sec, 'usec'=>$usec));
						socket_connect($socket, $ipx, $port);
						// if the IP isn't in the database, it's queried with an SGD command to understand if it is a Zebra printer
						$command_seek = '"! U1 getvar "device.company_name" \r\n';
						socket_write($socket, $command_seek, strlen($command_seek));
						$result = socket_read ($socket, 1024);
						
						// if the response is "Zebra Technologies", it means it's a Zebra printer
						if($result = "Zebra Technologies"){
							// list of SGD commands to get the printer details
							$command_sn = '! U1 getvar "device.unique_id" \r\n';
							$command_model = '! U1 getvar "device.product_name" \r\n';
							$command_resolution = '! U1 getvar "head.resolution.in_dpi" \r\n';
							$command_name = '! U1 getvar "device.host_identification" \r\n';
							$online = 1;
							// responses from the printer
							socket_write($socket, $command_sn, strlen($command_sn));
							$sn = str_replace('"', '', socket_read ($socket, 1024));
							socket_write($socket, $command_model, strlen($command_model));
							$model = str_replace('"', '', socket_read ($socket, 1024));
							socket_write($socket, $command_resolution, strlen($command_resolution));
							$resolution = str_replace('"', '', socket_read ($socket, 1024));
							socket_write($socket, $command_name, strlen($command_name));
							$name = str_replace('"', '', socket_read ($socket, 1024));
							socket_close($socket);
							// checking if a group has been selected or not
							if(isset($_POST["add_to_group"]) AND isset($_POST["group_id"])){
								$group = "," . $_POST["group_id"];
							}
							else{
								$group = "";
							}
							// adding the printer to the database or updating the IP address if the serial number is already in the database 
							// but associated with a different IP address
							$stmt = $conn->prepare("INSERT INTO printers (sn, ip, model, resolution, name, online, groups) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE ip = VALUES(ip), model = VALUES(model), resolution = VALUES(resolution), name = VALUES(name), online = VALUES(online), groups = VALUES(groups)");
							$stmt->bind_param("sssisis", $sn, $ipx, $model, $resolution, $name, $online, $group); 
							$stmt->execute();
							
							switch($stmt->affected_rows){
								case -1:
									$error = mysqli_error($conn);
									echo $error;
								break;
								case 1:
									echo "<b>" . $ipx . "</b> -> Printer <b>" . $sn . " inserted</b> into database. Status set to online";
									mkdir ("files/printers/" . $sn);
									mkdir ("files/printers/" . $sn . "/dmp_files");
									mkdir ("files/printers/" . $sn . "/other_files");
									mkdir ("files/printers/" . $sn . "/allcv");
									
								break;
								case 2:
									echo "<b>" . $ipx . "</b> -> Printer <b> " . $sn . " updated</b> with new IP. Status set to online";
								break;
							}
							$stmt->close();
							
							// creating the golden allcv file
							$path = "files/printers/" . $sn . "/" . $sn . "_golden_allcv.txt";
							// setting an higher socket timeout to get the goldern allcv file
							// this socket timeout can be lower, but you might have latency issues with wireless printers
							
							$sec = 8;
							$usec = 0;
							$socket = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket\n");
							socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec'=>$sec, 'usec'=>$usec));
							socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec'=>$sec, 'usec'=>$usec));
							socket_connect($socket, $ipx, $port);
							$command = '! U1 getvar "allcv" \r\n';
							socket_write($socket, $command, strlen($command));
							socket_recv($socket, $response, 76800, MSG_WAITALL);
							$golden_allcv = fopen($path,"w") or die("Unable to create file");
							fwrite($golden_allcv, $response);
							fclose($golden_allcv);
							socket_close($socket);
							
							// sending configuration command to send alerts to a POST listener page
							$sec = 1;
							$usec = 0;
							$socket = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket\n");
							socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec'=>$sec, 'usec'=>$usec));
							socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec'=>$sec, 'usec'=>$usec));
							socket_connect($socket, $ipx, $port);
							$command = '! U1 setvar "alerts.configured" "ALL MESSAGES,HTTP-POST,Y,Y,http://192.168.0.199/zpmt/alert_listener.php,0,N," \r\n
									! U1 setvar "device.reset" "" \r\n';
							
							socket_write($socket, $command, strlen($command));
							socket_close($socket);
						}
						
					}
				}
				
			}
		}
		else{
			echo "You should select a range of IPs to run a network scan..";
		}
	break;
	
	case "single":
		echo "<h2>Add a single printer</h2>";
		if(!empty($_POST["ip"])){
			include "conn.php";
			$ip = $_POST["ip"];
			$port = 9100;
			$sec = 8;
			$usec = 0;
			$ipx = $ip;
			
			// set socket timeout
			$socket = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket\n");
			socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec'=>$sec, 'usec'=>$usec));
			socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec'=>$sec, 'usec'=>$usec));
				
			// If the host doesn't respond..
			if(!($result = @socket_connect($socket, $_POST["ip"], $port))){
				$stmt = $conn->prepare("SELECT * FROM printers WHERE ip = ?");
				$stmt->bind_param("s", $_POST["ip"]); 
				$stmt->execute();
				$result_printer = $stmt->get_result();
				if($row = $result_printer->fetch_assoc()){
					// ..but the IP is in database, status is updated to 0 (offline)
					$stmt = $conn->prepare("UPDATE printers SET online = 0 WHERE ip = ?");
					$stmt->bind_param("s", $_POST["ip"]); 
					$stmt->execute();
					echo "<p>" . $_POST["ip"] . " Host is a Zebra printer (sn: " . $row["sn"] . ") present in database. Status: offline.</p>";
				}
				else{
					// ..but the IP isn't in database, no action is performed
					echo "<p>" . $_POST["ip"] . " Host is not in database or is not a Zebra printer.</p>";
				}
			}
			else{
				// if the host responds..
				$stmt = $conn->prepare("SELECT * FROM printers WHERE ip = ?");
				$stmt->bind_param("s", $_POST["ip"]); 
				$stmt->execute();
				$result_printer = $stmt->get_result();
				if($row = $result_printer->fetch_assoc()){
					// and the IP is already in the database, status is updated to 1 (online)
					$stmt = $conn->prepare("UPDATE printers SET online = 1 WHERE sn = ?");
					$stmt->bind_param("s", $row['sn']); 
					$stmt->execute();
					echo "<p>" . $_POST["ip"] . " already present in database and assigned to printer <b>" . $row["sn"] . "</b>. Status: online.</p>";
				}
				else{
					// if the IP isn't in the database, it's queried with an SGD command to understand if it is a Zebra printer
					$command_seek = '"! U1 getvar "device.company_name" \r';
					socket_write($socket, $command_seek, strlen($command_seek));
					$result = socket_read ($socket, 1024);
					
					// if the response is "Zebra Technologies", it means it's a Zebra printer
					if($result = "Zebra Technologies"){
						// list of SGD commands to get the printer details
						$command_sn = '! U1 getvar "device.unique_id" \r\n';
						$command_model = '! U1 getvar "device.product_name" \r\n';
						$command_resolution = '! U1 getvar "head.resolution.in_dpi" \r\n';
						$command_name = '! U1 getvar "device.host_identification" \r\n';
						$online = 1;
						
						$socket_add = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket\n");
						$result_add = socket_connect($socket_add, $_POST["ip"], $port);  
						
						// responses from the printer
						socket_write($socket_add, $command_sn, strlen($command_sn));
						$sn = str_replace('"', '', socket_read ($socket_add, 1024));
						socket_write($socket_add, $command_model, strlen($command_model));
						$model = str_replace('"', '', socket_read ($socket_add, 1024));
						socket_write($socket_add, $command_resolution, strlen($command_resolution));
						$resolution = str_replace('"', '', socket_read ($socket_add, 1024));
						socket_write($socket_add, $command_name, strlen($command_name));
						$name = str_replace('"', '', socket_read ($socket_add, 1024));
						socket_close($socket_add);
						
						if(isset($_POST["add_to_group"]) AND isset($_POST["group_id"])){
							$group = "," . $_POST["group_id"];
						}
						else{
							$group = "";
						}
						
						// adding the printer to the database or updating the IP address if the serial number is already in the database 
						// but associated with a different IP address
						$stmt = $conn->prepare("INSERT INTO printers (sn, ip, model, resolution, name, online, groups) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE ip = VALUES(ip), model = VALUES(model), resolution = VALUES(resolution), name = VALUES(name), online = VALUES(online), groups = VALUES(groups)");
						$stmt->bind_param("sssisis", $sn, $ipx, $model, $resolution, $name, $online, $group); 
						$stmt->execute();
						
						switch($stmt->affected_rows){
							case -1:
								$error = mysqli_error($conn);
								echo $error;
							break;
							case 1:
								echo "<p><br>" . $_POST["ip"] . "</b> -> Printer <b>" . $sn . " inserted</b> into database. Status set to online.</p>";
								mkdir ("files/printers/" . $sn);
								mkdir ("files/printers/" . $sn . "/dmp_files");
								mkdir ("files/printers/" . $sn . "/other_files");
								mkdir ("files/printers/" . $sn . "/allcv");
								
							break;
							case 2:
								echo "<b>" . $_POST["ip"] . "</b> -> Printer <b> " . $sn . " updated</b> with new IP. Status set to online.";
							break;
						}
						$stmt->close();
							
						// creating the golden allcv file
						$path = "files/printers/" . $sn . "/" . $sn . "_golden_allcv.txt";
						$command = '! U1 getvar "allcv" \r\n';
						socket_write($socket, $command, strlen($command));
						socket_recv($socket, $response, 76800, MSG_WAITALL);
						$golden_allcv = fopen($path,"w") or die("Unable to create file");
						fwrite($golden_allcv, $response);
						fclose($golden_allcv);
						socket_close($socket);
							
						// sending configuration command to send alerts to a POST listener page
						$sec = 1;
						$usec = 0;
						$socket = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket\n");
						socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec'=>$sec, 'usec'=>$usec));
						socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec'=>$sec, 'usec'=>$usec));
						socket_connect($socket, $ip, $port);
						$command = '! U1 setvar "alerts.configured" "ALL MESSAGES,HTTP-POST,Y,Y,http://192.168.0.199/zpmt/alert_listener.php,0,N," \r\n
								! U1 setvar "device.reset" "" \r\n';
						
						socket_write($socket, $command, strlen($command));
						socket_close($socket);
					}
				}
			}
		}
		else{
			echo "<p>You should enter an IP to to add a printer.</p>";
		}
	break;
	
	case "file":
		echo "<h2>Results of the file scan: </h2>";
		// if file size is bigger than 0
		if($_FILES['file']['size'] > 0) {
			include "conn.php";
			$mimetype = mime_content_type($_FILES['file']['tmp_name']);
			// if file type is correct
			if($mimetype == "text/plain" OR $mimetype == "application/csv"){
				// file is opened
				$file = fopen($_FILES['file']['tmp_name'], "r");
				// lines are passed into the $row_file array
				while ($row_file = fgetcsv($file,0,",")){
					$ip = $row_file[0];
					$port = 9100;						
					// If the host doesn't respond..
					if(!(@fsockopen($ip, $port, $errno, $errstr, 1))){
						$stmt = $conn->prepare("SELECT * FROM printers WHERE ip = ?");
						$stmt->bind_param("s", $ip); 
						$stmt->execute();
						$result_printer = $stmt->get_result();
						if($row = $result_printer->fetch_assoc()){
							// ..but the IP is in database, status is updated to 0 (offline)
							$stmt = $conn->prepare("UPDATE printers SET online = 0 WHERE ip = ?");
							$stmt->bind_param("s", $ip); 
							$stmt->execute();
							echo "<p>" . $ip . " Host is a Zebra printer (sn: " . $row["sn"] . ") present in database. Status: offline.</p>";
						}
						else{
							// ..but the IP isn't in database, no action is performed
							echo "<p>" . $ip . " Host is not in database or is not a Zebra printer.</p>";
						}
					}
					else{
						// if the host responds..
						$stmt = $conn->prepare("SELECT * FROM printers WHERE ip = ?");
						$stmt->bind_param("s", $ip); 
						$stmt->execute();
						$result_printer = $stmt->get_result();
						if($row = $result_printer->fetch_assoc()){
							// and the IP is already in the database, status is updated to 1 (online)
							$stmt = $conn->prepare("UPDATE printers SET online = 1 WHERE sn = ?");
							$stmt->bind_param("s", $row['sn']); 
							$stmt->execute();
							echo "<p>Printer " . $row["sn"] . " already present in database with IP = " . $ip . " Status: online.</p>";
						}
						else{
							$sec = 1;
							$usec = 0;
							$socket = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket\n");
							socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec'=>$sec, 'usec'=>$usec));
							socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec'=>$sec, 'usec'=>$usec));
							socket_connect($socket, $ip, $port);
							// if the IP isn't in the database, it's queried with an SGD command to understand if it is a Zebra printer
							$command_seek = '"! U1 getvar "device.company_name" \r';
							socket_write($socket, $command_seek, strlen($command_seek));
							$result = socket_read ($socket, 1024);
							
							// if the response is "Zebra Technologies", it means it's a Zebra printer
							if($result = "Zebra Technologies"){
								// list of SGD commands to get the printer details
								$command_sn = '! U1 getvar "device.unique_id" \r\n';
								$command_model = '! U1 getvar "device.product_name" \r\n';
								$command_resolution = '! U1 getvar "head.resolution.in_dpi" \r\n';
								$command_name = '! U1 getvar "device.host_identification" \r\n';
								$online = 1;
								// responses from the printer
								socket_write($socket, $command_sn, strlen($command_sn));
								$sn = str_replace('"', '', socket_read ($socket, 1024));
								socket_write($socket, $command_model, strlen($command_model));
								$model = str_replace('"', '', socket_read ($socket, 1024));
								socket_write($socket, $command_resolution, strlen($command_resolution));
								$resolution = str_replace('"', '', socket_read ($socket, 1024));
								socket_write($socket, $command_name, strlen($command_name));
								$name = str_replace('"', '', socket_read ($socket, 1024));
								
								if(empty($row_file[1])){
									socket_write($socket, $command_name, strlen($command_name));
									$name = str_replace('"', '', socket_read ($socket, 1024));
								}
								else{
									$name = $row_file[1];
								}
								socket_close($socket);
								
								if(empty($row_file[2])){
									$group = "";
								}
								else{
									$group = "," . $row_file[2];
								}
								
								// adding the printer to the database or updating the IP address if the serial number is already in the database 
								// but associated with a different IP address
								$stmt = $conn->prepare("INSERT INTO printers (sn, ip, model, resolution, name, online, groups) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE ip = VALUES(ip), model = VALUES(model), resolution = VALUES(resolution), name = VALUES(name), online = VALUES(online), groups = VALUES(groups)");
								$stmt->bind_param("sssisis", $sn, $ip, $model, $resolution, $name, $online, $group); 
								$stmt->execute();
								
								switch($stmt->affected_rows){
									case -1:
										$error = mysqli_error($conn);
										echo $error;
									break;
									case 1:
										echo "<b>" . $ip . "</b> -> Printer <b>" . $sn . " inserted</b> into database. Status set to online";
										mkdir ("files/printers/" . $sn);
										mkdir ("files/printers/" . $sn . "/dmp_files");
										mkdir ("files/printers/" . $sn . "/other_files");
										mkdir ("files/printers/" . $sn . "/allcv");
										
									break;
									case 2:
										echo "<b>" . $ip . "</b> -> Printer <b> " . $sn . " updated</b> with new IP. Status set to online";
									break;
								}
								$stmt->close();
							
								// creating the golden allcv file
								$path = "files/printers/" . $sn . "/" . $sn . "_golden_allcv.txt";
								$sec = 8;
								$usec = 0;
								$socket = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket\n");
								socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec'=>$sec, 'usec'=>$usec));
								socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec'=>$sec, 'usec'=>$usec));
								socket_connect($socket, $ip, $port);
								$command = '! U1 getvar "allcv" \r\n';
								socket_write($socket, $command, strlen($command));
								socket_recv($socket, $response, 76800, MSG_WAITALL);
								$golden_allcv = fopen($path,"w") or die("Unable to create file");
								fwrite($golden_allcv, $response);
								fclose($golden_allcv);
								socket_close($socket);
							
								// sending configuration command to send alerts to a POST listener page
								$sec = 1;
								$usec = 0;
								$socket = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket\n");
								socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec'=>$sec, 'usec'=>$usec));
								socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec'=>$sec, 'usec'=>$usec));
								socket_connect($socket, $ip, $port);
								$command = '! U1 setvar "alerts.configured" "ALL MESSAGES,HTTP-POST,Y,Y,http://192.168.0.199/zpmt/alert_listener.php,0,N," \r\n
										! U1 setvar "device.reset" "" \r\n';
								
								socket_write($socket, $command, strlen($command));
								socket_close($socket);
							}
						}
					}
				
				}
			}
			else{
				echo "<p><h3>This is not a text file!</h3></p>";
			}
		}
		else{
			echo "<p><h3>No file selected. Please select a file</h3></p>";
		}
	break;
	
	case "remove_printer":
		echo "<h2>Remove a printer</h2>";
		include "conn.php";
		// function to delete folders and files related to the printer
		function delete_files($path){
			foreach(glob($path . '/*') as $file){ 
				if(is_dir($file)){
					delete_files($file);
				}
				else{
					unlink($file);
				}
			}
			rmdir($path); 
		}
		// deleting the printer from the db and recalling the previously declared delete_files function
		foreach($_POST["printer_id"] as $printer_id){
			$stmt = $conn->prepare("SELECT * FROM printers WHERE id = ?");
			$stmt->bind_param("s", $printer_id); 
			$stmt->execute();
			$result = $stmt->get_result();
			while($printer = $result->fetch_assoc()){
				// resetting the alert messages configuration
				$sec = 1;
				$usec = 0;
				$port = 9100;
				$socket = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket\n");
				socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec'=>$sec, 'usec'=>$usec));
				socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec'=>$sec, 'usec'=>$usec));
				socket_connect($socket, $printer["ip"], $port);
				$command = '! U1 setvar "alerts.configured" "COLD START,SNMP,Y,N,255.255.255.255,162,N" \r\n
						! U1 setvar "device.reset" "" \r\n';
				
				socket_write($socket, $command, strlen($command));
				socket_close($socket);
				
				// removing the alerts log from the database
				$stmt = $conn->prepare("DELETE FROM alerts WHERE printer_id = ?");
				$stmt->bind_param("s", $printer["id"]); 
				$delete = $stmt->execute();
				
				// removing the printer from the database
				$stmt = $conn->prepare("DELETE FROM printers WHERE id = ?");
				$stmt->bind_param("s", $printer["id"]); 
				$delete = $stmt->execute();
				// removing the printer's folders and files
				$path = "files/printers/" . $printer["sn"];
				foreach(glob($path . '/*') as $file){ 
					if(is_dir($file)){
						delete_files($file);
					}
					else{
						unlink($file);
					}
				}
				rmdir($path); 
				echo "Printer with serial number " . $printer["sn"] . " has been deleted.<br>";
			}
		}
	break;
	
	default:
		echo "<p>No action has been selected.</p>";
}
?>