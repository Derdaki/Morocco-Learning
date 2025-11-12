<?php
// إعدادات قاعدة البيانات
$host = 'localhost';
$port = 3307;
$dbname = 'moroccolearning';
$username = 'root';
$password = '';

try {
    // إنشاء اتصال PDO مع قاعدة البيانات
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);

    // تعيين وضع ظهور الأخطاء إلى استثناءات
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // عرض رسالة الخطأ في حال فشل الاتصال
    echo "خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage();
    exit;
}
?>