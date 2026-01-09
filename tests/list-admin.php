<?php
require_once __DIR__ . '/../config/Database.php';

$db = Database::getMysqli();

$result = mysqli_query($db, "DESCRIBE user");
echo "User table structure:\n";
while ($row = mysqli_fetch_assoc($result)) {
    echo "  {$row['Field']} ({$row['Type']})\n";
}

echo "\nAll users:\n";
$result = mysqli_query($db, "SELECT * FROM user LIMIT 10");
while ($row = mysqli_fetch_assoc($result)) {
    print_r($row);
}
