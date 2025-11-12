<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'غير مسموح.']);
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "moroccolearning";
$port = 3307;

$conn = new mysqli($servername, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'فشل الاتصال بقاعدة البيانات.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$currentPass = $_POST['currentPassword'] ?? '';
$newPass = $_POST['newPassword'] ?? '';

if (strlen($newPass) < 6) {
    echo json_encode(['success' => false, 'message' => 'كلمة المرور الجديدة يجب أن تكون 6 أحرف على الأقل.']);
    exit();
}

// جلب كلمة المرور المشفرة من DB
$stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user || !password_verify($currentPass, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'كلمة المرور الحالية غير صحيحة.']);
    exit();
}

// تحديث كلمة المرور الجديدة
$newPassHash = password_hash($newPass, PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
$stmt->bind_param("si", $newPassHash, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'تم تغيير كلمة المرور بنجاح.']);
} else {
    echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء تحديث كلمة المرور.']);
}
$stmt->close();
$conn->close();
