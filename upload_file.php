<?php
session_start();

// التحقق من تسجيل الدخول
if (!isset($_SESSION['director_id'])) {
    header("Location: admin.php");
    exit();
}

// إعدادات قاعدة البيانات
$host = 'localhost';
$port = 3307;
$dbname = 'moroccolearning';
$username = 'root';
$password = '';

try {
    // اتصال بقاعدة البيانات مع استخدام الأرقام بدلاً من الثوابت إذا لزم الأمر
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // استخدام الأرقام المقابلة للثوابت إذا كانت غير معرّفة
    $pdo->setAttribute(2, 1); // PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    $pdo->setAttribute(3, 2); // PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    
} catch (PDOException $e) {
    die("فشل الاتصال بقاعدة البيانات: " . $e->getMessage());
}

// جلب institution_id من الجلسة
$institution_id = $_SESSION['institution_id'] ?? null;

if (!$institution_id) {
    die("لم يتم العثور على معرف المؤسسة. يرجى تسجيل الدخول مرة أخرى.");
}

// معالجة رفع الملف
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fileUpload'])) {
    $file = $_FILES['fileUpload'];
    
    // التحقق من عدم وجود أخطاء في الرفع
    if ($file['error'] !== UPLOAD_ERR_OK) {
        die("حدث خطأ في رفع الملف. كود الخطأ: " . $file['error']);
    }
    
    // معلومات الملف
    $fileName = basename($file['name']);
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileType = $file['type'];
    
    // أنواع الملفات المسموحة
    $allowedTypes = [
        'application/pdf',
        'application/msword',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ];
    
    if (!in_array($fileType, $allowedTypes)) {
        die("نوع الملف غير مسموح به. يسمح فقط بملفات PDF و Word و Excel.");
    }
    
    // الحد الأقصى لحجم الملف (5MB)
    $maxSize = 5 * 1024 * 1024;
    if ($fileSize > $maxSize) {
        die("حجم الملف كبير جداً. الحد الأقصى هو 5MB.");
    }
    
    // مجلد التحميل
    $uploadDir = 'uploads/institution_files/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // اسم فريد للملف
    $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
    $newFileName = uniqid() . '.' . $fileExt;
    $filePath = $uploadDir . $newFileName;
    
    // نقل الملف
    if (!move_uploaded_file($fileTmpName, $filePath)) {
        die("حدث خطأ أثناء حفظ الملف على الخادم.");
    }
    
    // بيانات النموذج
    $fileCategory = $_POST['fileCategory'] ?? 'أخرى';
    $fileDescription = $_POST['fileDescription'] ?? '';
    
    // التحقق من وجود المؤسسة
    try {
        $checkStmt = $pdo->prepare("SELECT id FROM institutions WHERE id = ?");
        $checkStmt->execute([$institution_id]);
        
        if (!$checkStmt->fetch()) {
            unlink($filePath);
            die("المؤسسة غير موجودة في قاعدة البيانات.");
        }
        
        // إدراج الملف في قاعدة البيانات
        $insertStmt = $pdo->prepare("INSERT INTO institution_files 
                                    (file_name, file_type, file_size, file_path, institution_id, description, upload_date) 
                                    VALUES (?, ?, ?, ?, ?, ?, NOW())");
        
        $insertStmt->execute([
            $fileName,
            $fileType,
            $fileSize,
            $filePath,
            $institution_id,
            $fileDescription
        ]);
        
        header("Location: directors.php?success=file_uploaded");
        exit();
        
    } catch (PDOException $e) {
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        die("حدث خطأ في قاعدة البيانات: " . $e->getMessage());
    }
} else {
    die("طلب غير صالح.");
}
?>