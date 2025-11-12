<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
date_default_timezone_set('Africa/Casablanca');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "moroccolearning";
$port = 3307;

$conn = new mysqli($servername, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = trim($_POST['token'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($token) || empty($newPassword) || empty($confirmPassword)) {
        $_SESSION['error'] = "يرجى ملء جميع الحقول.";
        header("Location: reset_password.php?token=$token");
        exit();
    }

    if ($newPassword !== $confirmPassword) {
        $_SESSION['error'] = "كلمتا المرور غير متطابقتين.";
        header("Location: reset_password.php?token=$token");
        exit();
    }

    // جلب بيانات البريد وتاريخ الإنشاء من جدول password_resets
    $stmt = $conn->prepare("SELECT email, created_at FROM password_resets WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $resetData = $result->fetch_assoc();
    $stmt->close();

    if (!$resetData) {
        $_SESSION['error'] = "رمز غير صالح أو منتهي.";
        header("Location: reset_password.php");
        exit();
    }

    $createdAt = strtotime($resetData['created_at']);
    if (time() - $createdAt > 1800) { // 30 دقيقة
        $_SESSION['error'] = "انتهت صلاحية الرابط. حاول مرة أخرى.";
        header("Location: reset_password.php");
        exit();
    }

    // تحديث كلمة المرور المشفرة في جدول users
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    $updateStmt->bind_param("ss", $hashedPassword, $resetData['email']);
    $updateStmt->execute();
    $updateStmt->close();

    // حذف التوكن بعد الاستخدام
    $deleteStmt = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
    $deleteStmt->bind_param("s", $token);
    $deleteStmt->execute();
    $deleteStmt->close();

    $_SESSION['success'] = "تم تحديث كلمة المرور بنجاح. يمكنك الآن تسجيل الدخول.";
    header("Location: login.php");
    exit();

} else {
    header("Location: index.php");
    exit();
}

$conn->close();
?>
