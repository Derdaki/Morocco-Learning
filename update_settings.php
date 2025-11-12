<?php
session_start();
require 'db.php';

// تحقق أن المستخدم مسجل دخول
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // جلب القيم من الفورم
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $notify_email = isset($_POST['notify_email']) ? 1 : 0;
    $notify_system = isset($_POST['notify_system']) ? 1 : 0;
    $notify_phone = isset($_POST['notify_phone']) ? 1 : 0;

    // تحديث الحساب (مثال تحديث الاسم والبريد والإشعارات)
    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, notify_email = ?, notify_system = ?, notify_phone = ? WHERE id = ?");
    $stmt->execute([$username, $email, $notify_email, $notify_system, $notify_phone, $userId]);

    echo "تم تحديث الإعدادات بنجاح";
}
?>
