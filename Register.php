<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "config.php"; // الاتصال بقاعدة البيانات
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// قراءة البيانات المرسلة كـ JSON
$input = json_decode(file_get_contents("php://input"), true);

if (!$input) {
    echo json_encode(["success" => false, "message" => "البيانات غير صحيحة"]);
    exit;
}

// جلب الحقول من JSON
$email      = $input['email']      ?? '';
$password   = $input['password']   ?? '';
$fullname   = $input['fullname']   ?? '';
$phone      = $input['phone']      ?? '';
$cne        = $input['cne']        ?? '';
$massar     = $input['massar']     ?? '';
$gender     = $input['gender']     ?? '';
$birthdate  = $input['birthdate']  ?? '';
$level      = $input['level']      ?? '';
$speciality = $input['speciality'] ?? '';
$address    = $input['address']    ?? '';

// تحقق من الحقول الأساسية
if (empty($email) || empty($password)) {
    echo json_encode(["success" => false, "message" => "المرجو إدخال البريد وكلمة المرور"]);
    exit;
}

// تشفير كلمة المرور
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// التحقق إذا البريد مسجل من قبل
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "هذا البريد مسجل مسبقًا"]);
    exit;
}
$stmt->close();

// إنشاء رمز تحقق OTP
$otp = rand(100000, 999999);

// إدخال المستخدم الجديد
$stmt = $conn->prepare("INSERT INTO users (fullname, email, password, phone, cne, massar, gender, birthdate, level, speciality, address, otp_code, verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)");
$stmt->bind_param("ssssssssssss", $fullname, $email, $hashedPassword, $phone, $cne, $massar, $gender, $birthdate, $level, $speciality, $address, $otp);

if ($stmt->execute()) {
    // إرسال البريد الإلكتروني مع OTP
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = "smtp.gmail.com"; // غيّرها حسب SMTP
        $mail->SMTPAuth   = true;
        $mail->Username   = 'moroccolearningnational@gmail.com'; // بريدك
        $mail->Password   = 'vouh bnfr zacp duiq';   // كلمة مرور التطبيقات
        $mail->SMTPSecure = "tls";
        $mail->Port       = 587;

        $mail->setFrom("your-email@gmail.com", "اسم منصتك");
        $mail->addAddress($email, $fullname);

        $mail->isHTML(true);
        $mail->Subject = "رمز التحقق من الحساب";
        $mail->Body    = "<h3>مرحبًا $fullname</h3><p>رمز التحقق الخاص بك هو:</p><h2>$otp</h2><p>أدخله في صفحة تأكيد الحساب.</p>";

        $mail->send();

        echo json_encode(["success" => true, "message" => "تم التسجيل بنجاح، تحقق من بريدك الإلكتروني"]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => "تم التسجيل لكن فشل إرسال البريد: " . $mail->ErrorInfo]);
    }
} else {
    echo json_encode(["success" => false, "message" => "خطأ في إنشاء الحساب: " . $stmt->error]);
}

$stmt->close();
$conn->close();
