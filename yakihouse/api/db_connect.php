<?php
// Cấu hình kết nối cơ sở dữ liệu
define('DB_SERVER', 'localhost'); 
define('DB_USERNAME', 'root');   
define('DB_PASSWORD', '');       
define('DB_NAME', 'yakihouse');  

// Tạo kết nối đến cơ sở dữ liệu
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);


if ($conn->connect_error) {

    error_log("Connection failed: " . $conn->connect_error);
    die("Connection failed: " . $conn->connect_error); 
}


$conn->set_charset("utf8mb4");

function close_db_connection($conn) {
    $conn->close();
}
?>