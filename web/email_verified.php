<?php
session_start();

include("common/config.php");
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $sql = "SELECT * FROM tbl_users WHERE usr_token='$token' LIMIT 1";
    $result = mysqli_query($con, $sql);

    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        $query = "UPDATE tbl_users SET usr_verified=1 WHERE usr_token='$token'";

        if (mysqli_query($con, $query)) {
            $_SESSION['user_id'] = $row['usr_id'];
            $_SESSION['user_name'] = $row['usr_name'];
            $_SESSION['user_restaurant_name'] = $row['usr_restaurant_name'];
            $_SESSION['verified'] = true;
            $_SESSION['user_email'] = $row['usr_email'];
            $_SESSION['user_mobile'] = $row['usr_mobile'];
            $_SESSION['type'] = 'alert-success';
            header('location: index.php');
            exit(0);
        }
    } else {
        echo "User not found!";
    }
} else {
    echo "No token provided!";
}
?>