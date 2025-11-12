<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$conn = new mysqli("localhost", "username", "password", "database");
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if ($new !== $confirm) {
        echo json_encode(['status' => 'error', 'message' => 'كلمة المرور الجديدة غير متطابقة']);
        exit;
    }

    $user_id = intval($_SESSION['user_id']);
    $result = $conn->query("SELECT password FROM users WHERE id=$user_id");
    $row = $result->fetch_assoc();

    if (password_verify($current, $row['password'])) {
        $new_hashed = password_hash($new, PASSWORD_DEFAULT);
        if ($conn->query("UPDATE users SET password='$new_hashed' WHERE id=$user_id") === TRUE) {
            echo json_encode(['status' => 'success', 'message' => 'تم تغيير كلمة المرور']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'فشل في تحديث كلمة المرور']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'كلمة المرور الحالية غير صحيحة']);
    }
}
$conn->close();
?>
