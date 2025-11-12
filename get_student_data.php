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

$studentId = (int)$_GET['id'];

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // جلب بيانات التلميذ
    $stmt = $pdo->prepare("
        SELECT u.*, s.massar, s.cne, s.birthdate, s.address, s.photo 
        FROM users u
        JOIN students s ON u.id = s.user_id
        WHERE u.id = ? AND s.institution_id = ?
    ");
    $stmt->execute([$studentId, $_SESSION['institution_id']]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        echo json_encode(['error' => 'لم يتم العثور على التلميذ']);
        exit();
    }

    // إرجاع البيانات كـ JSON
    echo json_encode([
        'fullname' => $student['fullname'],
        'email' => $student['email'],
        'massar' => $student['massar'],
        'cne' => $student['cne'],
        'education_level' => $student['education_level'],
        'birthdate' => $student['birthdate'],
        'address' => $student['address'],
        'photo' => $student['photo'] ? $student['photo'] : null
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
}
?>