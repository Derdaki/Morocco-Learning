<?php
session_start();
require_once 'db_config.php'; // تأكد من وجود هذا الملف للإعدادات

header('Content-Type: application/json');

if (!isset($_SESSION['director_id'])) {
    echo json_encode(['error' => 'غير مصرح بالوصول']);
    exit();
}

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'معرف المستخدم مطلوب']);
    exit();
}

$teacherId = (int)$_GET['id'];

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // جلب بيانات الأستاذ
    $stmt = $pdo->prepare("
        SELECT u.*, t.specialty, t.photo 
        FROM users u
        JOIN teachers t ON u.id = t.user_id
        WHERE u.id = ? AND t.institution_id = ?
    ");
    $stmt->execute([$teacherId, $_SESSION['institution_id']]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$teacher) {
        echo json_encode(['error' => 'لم يتم العثور على الأستاذ']);
        exit();
    }

    // إرجاع البيانات كـ JSON
    echo json_encode([
        'fullname' => $teacher['fullname'],
        'email' => $teacher['email'],
        'specialty' => $teacher['specialty'],
        'education_level' => $teacher['education_level'],
        'phone' => $teacher['phone'],
        'photo' => $teacher['photo'] ? $teacher['photo'] : null
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
}
?>