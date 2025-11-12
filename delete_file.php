<?php
session_start();
require_once 'db.php';

// تسجيل تفاصيل الخطأ للتصحيح
ini_set('display_errors', 1);
error_reporting(E_ALL);

// التحقق من الصلاحيات
if (!isset($_SESSION['director_id'])) {
    die("ليس لديك صلاحية للوصول");
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('الطلب يجب أن يكون POST');
    }

    $file_id = filter_input(INPUT_POST, 'file_id', FILTER_VALIDATE_INT);
    if (!$file_id) {
        throw new Exception('معرف الملف غير صالح');
    }

    // 1. جلب بيانات الملف مع التحقق من ملكية المؤسسة
    $stmt = $pdo->prepare("SELECT file_path, file_name FROM institution_files 
                          WHERE id = ? AND institution_id = ?");
    $stmt->execute([$file_id, $_SESSION['institution_id']]);
    $file = $stmt->fetch();

    if (!$file) {
        throw new Exception('الملف غير موجود أو لا توجد صلاحية');
    }

    // تسجيل المسار للتصحيح
    error_log("Attempting to delete: " . $file['file_path']);

    // 2. حذف الملف فعلياً
    $deleted = false;
    if (file_exists($file['file_path'])) {
        if (!unlink($file['file_path'])) {
            error_log("Delete error: " . print_r(error_get_last(), true));
            throw new Exception('فشل حذف الملف: ' . error_get_last()['message']);
        }
        $deleted = true;
    }

    // 3. حذف السجل من قاعدة البيانات
    $stmt = $pdo->prepare("DELETE FROM institution_files WHERE id = ?");
    $stmt->execute([$file_id]);

    // تسجيل النجاح
    error_log("Successfully deleted file ID: $file_id");

    $_SESSION['success_message'] = "تم حذف الملف: " . htmlspecialchars($file['file_name']);

} catch (Exception $e) {
    error_log("Error deleting file: " . $e->getMessage());
    $_SESSION['error_message'] = $e->getMessage();
}

header("Location: directors.php#institutionFiles");
exit();
?>