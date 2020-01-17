<?php
	global $con;
	$con = mysqli_connect("localhost","root","","survey");
    date_default_timezone_set('Asia/Kolkata');
    $date = date('d-m-Y H:i');
    // Check connection
    if (mysqli_connect_errno()){
      echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }

?>
