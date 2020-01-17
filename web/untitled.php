<?php
session_start();

include("common/config.php");]
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $sql = "SELECT * FROM users WHERE token='$token' LIMIT 1";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        $query = "UPDATE users SET verified=1 WHERE token='$token'";

        if (mysqli_query($conn, $query)) {
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