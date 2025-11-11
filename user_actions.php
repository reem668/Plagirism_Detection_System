<?php
include 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: admin.php?page=user_management");
  exit;
}

$id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

if (isset($_POST['toggle_ban'])) {
  // current status
  $stmt = $conn->prepare("SELECT status FROM users WHERE id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($row = $res->fetch_assoc()) {
    $new = ($row['status'] === 'active') ? 'banned' : 'active';
    $u = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
    $u->bind_param("si", $new, $id);
    $u->execute();
  }
  $stmt->close();
  header("Location: admin.php?page=user_management");
  exit;
}

if (isset($_POST['change_role'])) {
  $new_role = $_POST['new_role'] ?? '';
  $allowed = ['student','instructor','admin'];
  if (!in_array($new_role, $allowed)) {
    header("Location: admin.php?page=user_management");
    exit;
  }
  $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
  $stmt->bind_param("si", $new_role, $id);
  $stmt->execute();
  $stmt->close();
  header("Location: admin.php?page=user_management");
  exit;
}
if (isset($_POST['delete_user'])) {
  $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $stmt->close();
  header("Location: admin.php?page=user_management&deleted=1");
  exit;
}


header("Location: admin.php?page=user_management");
exit;
