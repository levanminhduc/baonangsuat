<?php
$config = require 'C:/xampp/config/db.php';

$connect = mysqli_connect(
    $config['host'] ?? 'localhost',
    $config['username'] ?? 'root',
    $config['password'] ?? '',
    $config['database'] ?? 'mysqli'
);
?>
