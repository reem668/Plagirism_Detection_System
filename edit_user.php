<?php
include 'includes/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
  header("Location: /Plagirism_Detection_System/admin.php?page=user_management");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name']);
  $email = trim($_POST['email']);
  $role = trim($_POST['role']);
  $status = trim($_POST['status']);

  if ($name && $email && $role && $status) {
    $stmt = $conn->prepare("UPDATE users SET name=?, email=?, role=?, status=? WHERE id=?");
    $stmt->bind_param("ssssi", $name, $email, $role, $status, $id);
    $stmt->execute();
    $stmt->close();
    header("Location: /Plagirism_Detection_System/admin.php?page=user_management&updated=1");
    exit;
  }
}

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit User</title>
  <link rel="stylesheet" href="/Plagirism_Detection_System/assets/css/style.css">
</head>
<body>
  <div class="edit-container">
    <h2>Edit User ✏️</h2>
    <form method="POST" class="edit-form">
      <label>Name</label>
      <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>

      <label>Email</label>
      <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>

      <label>Role</label>
      <select name="role" required>
        <?php
          $roles = ['student','instructor','admin'];
          foreach ($roles as $r) {
            $sel = ($r == $user['role']) ? 'selected' : '';
            echo "<option value='$r' $sel>".ucfirst($r)."</option>";
          }
        ?>
      </select>

      <label>Status</label>
      <select name="status" required>
        <option value="active" <?= $user['status']=='active'?'selected':'' ?>>Active</option>
        <option value="banned" <?= $user['status']=='banned'?'selected':'' ?>>Banned</option>
      </select>

      <button type="submit" class="btn primary">Save Changes</button>
      <a href="/Plagirism_Detection_System/admin.php?page=user_management" class="btn">Cancel</a>
    </form>
  </div>
</body>
</html>