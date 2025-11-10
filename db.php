<?php

// --- Database connection ---
$host = "localhost";
$user = "root"; // change if different
$pass = "";     // change if you have a password
$db   = "pal"; // change to your actual database name

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>