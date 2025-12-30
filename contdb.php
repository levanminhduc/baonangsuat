<?php
$configFile = 'C:/xampp/config/db.php';
if (file_exists($configFile)) {
    $config = require $configFile;
} else {
    $config = [
        'host' => 'localhost',
        'username' => 'root',
        'password' => '',
        'database' => 'mysqli'
    ];
}

$connect = mysqli_connect(
    $config['host'] ?? 'localhost',
    $config['username'] ?? 'root',
    $config['password'] ?? '',
    $config['database'] ?? 'mysqli'
);

if (!$connect) {
    die('Database connection failed: ' . mysqli_connect_error());
}

mysqli_set_charset($connect, 'utf8mb4');
?>
