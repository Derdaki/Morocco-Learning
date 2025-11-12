<?php
session_start();
require_once 'vendor/autoload.php';
require_once 'config.php'; // ملف يحتوي على إعدادات SMTP واتصال قاعدة البيانات

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// التحقق من أن الطلب POST ومن صلاحيات المدير
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['director_id'])) {
    header('HTTP/1.1 403 Forbidden');
    exit(json_encode(['success' => false, 'message' => 'غير مصرح']));
}

// التحقق من وجود بيانات الطالب والإجراء
$studentId = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
$action = filter_input(INPUT_POST, 'action');

if (!$studentId || !in_array($action, ['approve', 'reject'])) {
    exit(json_encode(['success' => false, 'message' => 'بيانات غير صالحة']));
}

try {
    // الاتصال بقاعدة البيانات باستخدام PDO
    $pdo = new PDO(
        "mysql:host={$config['db']['host']};port={$config['db']['port']};dbname={$config['db']['dbname']};charset=utf8mb4",
        $config['db']['username'],
        $config['db']['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // التحقق من أن الطالب تابع لمؤسسة المدير
    $stmt = $pdo->prepare("
        SELECT u.id, u.fullname, u.email 
        FROM users u
        JOIN directors_login d ON u.institution = d.institution_name
        WHERE u.id = ? AND d.id = ?
    ");
    $stmt->execute([$studentId, $_SESSION['director_id']]);
    $student = $stmt->fetch();

    if (!$student) {
        exit(json_encode(['success' => false, 'message' => 'الطالب غير موجود أو غير تابع لمؤسستك']));
    }

    // تحديث حالة الحساب
    $status = ($action === 'approve') ? 'active' : 'rejected';
    $stmt = $pdo->prepare("UPDATE users SET account_status = ? WHERE id = ?");
    $stmt->execute([$status, $studentId]);

    // إرسال بريد إلكتروني حسب الإجراء
    $mail = new PHPMailer(true);
    try {
        // إعدادات SMTP من ملف config
        $mail->isSMTP();
        $mail->Host       = $config['mail']['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['mail']['username'];
        $mail->Password   = $config['mail']['password'];
        $mail->SMTPSecure = $config['mail']['encryption'];
        $mail->Port       = $config['mail']['port'];
        $mail->CharSet    = 'UTF-8';

        // المرسل والمستقبل
        $mail->setFrom($config['mail']['from'], $config['mail']['from_name']);
        $mail->addAddress($student['email'], $student['fullname']);

        // محتوى البريد حسب الإجراء
        if ($action === 'approve') {
            $mail->Subject = 'تم قبول حسابك في ' . $config['app']['name'];
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; direction: rtl;'>
                    <h2 style='color: #4CAF50;'>مرحباً {$student['fullname']}</h2>
                    <p>نود إعلامك بأن حسابك في منصة {$config['app']['name']} قد تم <strong>قبوله</strong>.</p>
                    <p>يمكنك الآن تسجيل الدخول والاستفادة من جميع الخدمات.</p>
                    <p><a href='{$config['app']['url']}/login.php' style='background: #4CAF50; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>تسجيل الدخول الآن</a></p>
                    <p>شكراً لانضمامك إلينا!</p>
                </div>
            ";
        } else {
            $mail->Subject = 'حسابك في ' . $config['app']['name'] . ' مرفوض';
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; direction: rtl;'>
                    <h2 style='color: #f44336;'>مرحباً {$student['fullname']}</h2>
                    <p>نأسف لإعلامك بأن حسابك في منصة {$config['app']['name']} قد تم <strong>رفضه</strong>.</p>
                    <p>للحصول على مساعدة، يرجى التواصل مع إدارة المؤسسة.</p>
                    <p>بريد التواصل: {$config['mail']['contact']}</p>
                </div>
            ";
        }

        $mail->isHTML(true);
        $mail->send();
    } catch (Exception $e) {
        error_log('فشل إرسال البريد: ' . $e->getMessage());
    }

    // إرجاع رد ناجح
    echo json_encode([
        'success' => true,
        'message' => ($action === 'approve') ? 'تم قبول الطالب بنجاح' : 'تم رفض الطالب بنجاح'
    ]);

} catch (PDOException $e) {
    error_log('خطأ في قاعدة البيانات: ' . $e->getMessage());
    exit(json_encode(['success' => false, 'message' => 'حدث خطأ تقني']));
}