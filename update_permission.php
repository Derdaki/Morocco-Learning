<?php
session_start();
require 'db.php';

// تحقق أن المستخدم مسجل دخول (يمكنك تعديل هذا حسب نظامك)
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// جلب بيانات POST
$userId = $_POST['user_id'] ?? null;
$newPermission = $_POST['permission_level'] ?? null;

// تحقق من وجود البيانات المطلوبة
if ($userId === null || $newPermission === null) {
    echo "خطأ: بيانات غير كاملة.";
    exit;
}

// تحقق من صلاحية القيمة (اختياري لكن مستحسن)
$validPermissions = ['محدود', 'عادي', 'متقدم'];
if (!in_array($newPermission, $validPermissions)) {
    echo "خطأ: صلاحية غير صالحة.";
    exit;
}

// ...
try {
    $stmt = $pdo->prepare("UPDATE users SET permission_level = ? WHERE id = ?");
    $stmt->execute([$newPermission, $userId]);

    header("Location: permissions_page.php?success=1");
    exit;
} catch (PDOException $e) {
    // في حال الخطأ، نرسل خطأ للصفحة
    header("Location: permissions_page.php?error=1");
    exit;
}

?>
