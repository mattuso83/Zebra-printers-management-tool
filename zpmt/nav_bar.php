<ul class="main-menu">
	<li><a href="index.php"><button class="nav_bar_button">Home</button></a></li>
	<li><button class="nav_bar_button">Fleet <i class="fa fa-angle-down"></i></button>
		<ul>
			<li><a href="fleet_add.php"><button class="nav_bar_submenu_button">Add printers</button></a></li>
			<li><a href="fleet_delete.php"><button class="nav_bar_submenu_button">Delete printers</button></a></li>
		</ul>
	</li>
	<li><button class="nav_bar_button">Groups <i class="fa fa-angle-down"></i></button>
		<ul>
			<li><a href="group_management.php"><button class="nav_bar_submenu_button">Manage groups</button></a></li>
			<li><a href="group_assignment.php"><button class="nav_bar_submenu_button">Printers assignment to groups</button></a></li>
		</ul>
	</li>
	<li><button class="nav_bar_button">Media <i class="fa fa-angle-down"></i></button>
		<ul>
			<li><a href="media_management.php"><button class="nav_bar_submenu_button">Manage media</button></a></li>
			<li><a href="paper_assignment.php"><button class="nav_bar_submenu_button">Assign paper to printer</button></a></li>
			<li><a href="ribbon_assignment.php"><button class="nav_bar_submenu_button">Assign ribbon to printer</button></a></li>
		</ul>
	<li><button class="nav_bar_button">Resources <i class="fa fa-angle-down"></i></button>
		<ul>
			<li><a href="resources_management.php"><button class="nav_bar_submenu_button">Manage resources</button></a></li>
			<li><a href="resources_send.php"><button class="nav_bar_submenu_button">Send resources</button></a></li>
		</ul>
	</li>
	<?php
		include "conn.php";
		$stmt = $conn->prepare("SELECT * FROM notifications WHERE checked = 0");
		$stmt->execute();
		$result_notification = $stmt->get_result();
		$notification = $result_notification->fetch_assoc();
		if(mysqli_num_rows($result_notification) > 0){
			echo "<a href='notifications_list.php'><img src='img/bell_notification.png'></a>";
		}
		else{
			echo "<a href='notifications_list.php'><img src='img/bell.png'></a>";
		}
	?>
</ul>