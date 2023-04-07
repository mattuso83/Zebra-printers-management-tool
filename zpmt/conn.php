<?php
$servername = "localhost";
$username = "root";
$password = "password";
$nomedb = "zpmt";
$conn = new mysqli($servername, $username, $password, $nomedb);
mysqli_query($conn,"set names 'utf8'"); 
?>

