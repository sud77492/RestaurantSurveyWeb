<?php
/**
 * Database configuration
 */
//echo $_SERVER['HTTP_HOST'];
if (strcmp($_SERVER['HTTP_HOST'], 'restaurant-survey-sudhanshu77492652.c9users.io')  == 0 || strcmp($_SERVER['HTTP_HOST'], 'www.restaurant-survey-sudhanshu77492652.c9users.io')  == 0){
    define('DB_USERNAME', 'root');
    define('DB_PASSWORD', '');
    define('DB_HOST', '0.0.0.0');
    define('DB_NAME', 'survey');
} else {
    define('DB_USERNAME', 'root');
    define('DB_PASSWORD', '');
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'survey');
}
?>