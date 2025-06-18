<?php
require_once 'config.php';

if (!is_logged_in()) {
    redirect('login.php', 'Please login first.', 'error');
}

$user_id = $_SESSION['user_id'];

// حذف المستخدم من قاعدة البيانات
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
if ($stmt->execute()) {
    session_destroy();
    redirect('login.php', 'Your account has been deleted successfully.', 'success');
} else {
    redirect('profile.php', 'Failed to delete your account.', 'error');
}
?>

