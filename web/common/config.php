<?php

/*if (strcmp($_SERVER['HTTP_HOST'], 'test-sudhanshu77492652.c9users.io')  == 0 || strcmp($_SERVER['HTTP_HOST'], 'www.test-sudhanshu77492652.c9users.io')  == 0){
      $username = "root"; 
      $password = "";  
      $hostname = "0.0.0.0"; 
      $databasename = "survey";
    } else {
      $username = "root"; 
      $password = "";  
      $hostname = "localhost"; 
      $databasename = "survey";
    }
    $dbhandle = mysqli_connect($hostname, $username, $password, $databasename) or die("Unable to connect to MySQL");*/
    //$selected = mysqli_select_db($dbhandle) or die("Could not select examples");
  global $con;
  $con = mysqli_connect("localhost","root","","survey");
  date_default_timezone_set('Asia/Kolkata');
  $date = date('d-m-Y H:i');
  // Check connection
  if (mysqli_connect_errno()){
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
  }
?>