<?php
session_start();

// تفعيل عرض الأخطاء للتطوير
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// التحقق من أن الطريقة المستخدمة هي POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("يجب إرسال البيانات بطريقة POST");
}

// التحقق من وجود البيانات
if (empty($_POST['username']) || empty($_POST['password'])) {
    $_SESSION['error'] = "اسم المستخدم وكلمة المرور مطلوبان";
    header("Location: admin.php");
    exit();
}

// إعدادات الاتصال بقاعدة البيانات
$host = 'localhost';
$port = 3307;
$dbname = 'moroccolearning';
$username_db = 'root';
$password_db = '';

try {
    // الاتصال بقاعدة البيانات
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // استعلام التحقق من المستخدم
    $stmt = $pdo->prepare("SELECT * FROM academies WHERE username = :username LIMIT 1");
    $stmt->bindParam(':username', $_POST['username']);
    $stmt->execute();
    $user = $stmt->fetch();

    if ($user && password_verify($_POST['password'], $user['password'])) {
        // تخزين بيانات الجلسة
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_type'] = 'academy';
        $_SESSION['logged_in'] = true;

        // التوجيه إلى academia.php
        header("Location: academia.php");
        exit();
    } else {
        $_SESSION['error'] = "بيانات الدخول غير صحيحة";
        header("Location: admin.php");
        exit();
    }
} catch (PDOException $e) {
    // تسجيل الخطأ
    error_log("Login error: " . $e->getMessage());
    $_SESSION['error'] = "حدث خطأ في النظام";
    header("Location: admin.php");
    exit();
}
?>