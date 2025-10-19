<?php
session_start();
include 'includes/db.php';

// --- Get inputs safely ---
$email = trim($_POST['email']);
$password = $_POST['password'];
$adminKey = isset($_POST['admin_key']) ? trim($_POST['admin_key']) : null;

// --- 1. Basic validation ---
if (empty($email) || empty($password)) {
    die("<script>alert('Please enter both email and password.'); window.history.back();</script>");
}

// --- 2. Check if user exists ---
$stmt = $conn->prepare("SELECT id, name, email, password, role, admin_key FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("<script>alert('No account found with this email.'); window.history.back();</script>");
}

$user = $result->fetch_assoc();

// --- 3. Handle Admin Logic ---
if ($user['role'] === 'admin') {
    // If it's an admin email but no key entered
    if (empty($adminKey)) {
        die("<script>alert('Admin access requires a secret key.'); window.history.back();</script>");
    }

    // If the key is incorrect
    if ($user['admin_key'] !== $adminKey) {
        die("<script>alert('Invalid admin key. Access denied.'); window.history.back();</script>");
    }
}

// --- 4. Verify password ---
if (!password_verify($password, $user['password'])) {
    die("<script>alert('Incorrect password. Please try again.'); window.history.back();</script>");
}

// --- 5. Set session variables ---
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_role'] = $user['role'];

// --- 6. Redirect based on role ---
if ($user['role'] === 'admin') {
    echo "<script>alert('Welcome Admin!'); window.location.href='index.php';</script>";
} elseif ($user['role'] === 'teacher') {
    echo "<script>alert('Welcome Teacher!'); window.location.href='welcome_page.php';</script>";
} else {
    echo "<script>alert('Welcome Student!'); window.location.href='welcome_page.php';</script>";
}

$stmt->close();
$conn->close();
?>
