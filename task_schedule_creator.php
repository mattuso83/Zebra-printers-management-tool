<?php



$starting_time = date('H:i', strtotime('1 hour'));



$task = "schtasks /CREATE /SC minute /mo 60 /ST " . $starting_time . " /TN Cron_test /TR \"C:\\xampp\\php\\php.exe ..\\htdocs\\cron.php \" /np ";




exec($task);


?>