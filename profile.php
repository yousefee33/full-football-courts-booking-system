<?php
require_once 'config.php';

// تحقق من تسجيل الدخول
if (!is_logged_in()) {
    redirect('login.php', 'Please login first.', 'error');
}

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// قراءة بيانات المستخدم من قاعدة البيانات
$stmt = $conn->prepare("SELECT name, email, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// تحديث البيانات إذا تم الإرسال
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);

    // تحقق من صحة الحقول
    if (!empty($name) && !empty($email)) {
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $email, $user_id);
        if ($stmt->execute()) {
            $_SESSION['name'] = $name;
            $_SESSION['user_email'] = $email;
            $message = "Profile updated successfully.";
            $message_type = "success";
            // تحديث البيانات المعروضة
            $user['name'] = $name;
            $user['email'] = $email;
        } else {
            $message = "Error updating profile.";
            $message_type = "error";
        }
    } else {
        $message = "All fields are required.";
        $message_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile</title>
    <link rel="stylesheet" href="../css/common.css">
    <style>
        .container { max-width: 600px; margin: 40px auto; background: #fff; padding: 20px; border-radius: 10px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        input[type="text"], input[type="email"] { width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ccc; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .btn-primary { background-color: #28a745; color: #fff; }
        .btn-danger { background-color: #dc3545; color: #fff; }
        .alert { padding: 10px; margin-bottom: 15px; border-radius: 5px; }
        .alert.success { background-color: #d4edda; color: #155724; }
        .alert.error { background-color: #f8d7da; color: #721c24; }
        form { margin-top: 20px; }
    </style>
</head>
<body>

<div class="container">
    <h2>My Profile</h2>

    <?php if ($message): ?>
        <div class="alert <?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST" action="profile.php">
        <input type="hidden" name="update_profile" value="1">
        <div class="form-group">
            <label>Name</label>
            <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
        </div>
        <div class="form-group">
            <label>Role</label>
            <input type="text" value="<?= htmlspecialchars($user['role']) ?>" disabled>
        </div>
        <button type="submit" class="btn btn-primary">Update Profile</button>
    </form>

    <form method="POST" action="delete_account.php" onsubmit="return confirm('Are you sure you want to delete your account permanently?');" style="margin-top: 20px;">
        <button type="submit" class="btn btn-danger">Delete My Account</button>
    </form>
</div>

</body>
</html>
