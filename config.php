<?php
// Database credentials
$db_host = '148.222.53.5';   // Use Hostinger IPv4 to avoid IPv6 issues
$db_name = 'u997536977_moodtracker_db';
$db_user = 'u997536977_moodtracker_us';
$db_pass = 'Simpletracker123';

try {
    // Force PDO to use TCP/IP instead of socket
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
