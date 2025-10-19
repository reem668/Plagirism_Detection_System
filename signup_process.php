<?php
// signup_process.php
session_start();

include 'includes/db.php'; // Include your database connection file

// --- Get form inputs safely ---
$name = trim($_POST['name']);
$email = trim($_POST['email']);
$mobile = trim($_POST['mobile']);
$country = trim($_POST['country']);
$role = trim($_POST['role']);
$password = $_POST['password'];
$confirm_password = $_POST['confirm-password'];

// --- 1. Validate email provider ---
$allowed_providers = ["gmail.com", "yahoo.com", "outlook.com", "hotmail.com"];
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("<script>alert('Invalid email format.'); window.history.back();</script>");
}
$domain = substr(strrchr($email, "@"), 1);
if (!in_array($domain, $allowed_providers)) {
    die("<script>alert('Email must be Gmail, Yahoo, Outlook, or Hotmail.'); window.history.back();</script>");
}

// --- 2. Validate password strength ---
if (!preg_match("/^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*\/])[A-Za-z\d!@#$%^&*\/]{8,}$/", $password)) {
    die("<script>alert('Password must have at least 8 chars, an uppercase letter, a number, and a special symbol.'); window.history.back();</script>");
}

// --- 3. Confirm password match ---
if ($password !== $confirm_password) {
    die("<script>alert('Passwords do not match.'); window.history.back();</script>");
}

// --- 4. Validate phone number ---
if (!preg_match("/^\d{11}$/", $mobile)) {
    die("<script>alert('Please enter a valid 11-digit mobile number.'); window.history.back();</script>");
}

// --- 5. Ensure gender & country are chosen ---
if (empty($country) || empty($role)) {
    die("<script>alert('Please select your role and country.'); window.history.back();</script>");
}

// --- 6. Check if email already exists ---
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    die("<script>alert('This email is already registered.'); window.history.back();</script>");
}
$stmt->close();

// --- 7. Hash password ---
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// --- 8. Insert user ---
$stmt = $conn->prepare("INSERT INTO users (name, email, mobile, country, password,role) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssss", $name, $email, $mobile, $country, $hashed_password,$role);

if ($stmt->execute()) {
    header("Location: signup.php?signup=success");
    exit();
} else {
    echo "<script>alert('Signup failed. Please try again.'); window.history.back();</script>";
}


$stmt->close();
$conn->close();
?>
