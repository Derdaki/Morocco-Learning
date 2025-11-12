<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'src/PHPMailer.php';
require 'src/SMTP.php';
require 'src/Exception.php';

session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "moroccolearning";
$port = 3307;

$conn = new mysqli($servername, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $userEmail = trim($_POST['email']);

    // توليد توكن
    $token = bin2hex(random_bytes(16));

    // ** هاهنا تخزن التوكن في قاعدة البيانات **
    $stmt = $conn->prepare("INSERT INTO password_resets (email, token, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("ss", $userEmail, $token);
    $stmt->execute();
    $stmt->close();

    // إعداد و إرسال البريد
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'moroccolearningnational@gmail.com';
        $mail->Password   = 'xkww kauk mslp isoi';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('moroccolearningnational@gmail.com', 'دعم الموقع');
        $mail->addAddress($userEmail);

        $mail->isHTML(true);
        $mail->Subject = 'إعادة تعيين كلمة المرور';
        $mail->Body    = "
            <p>لقد طلبت إعادة تعيين كلمة المرور الخاصة بك.</p>
            <p>انقر على الرابط التالي لإعادة تعيينها:</p>
            <a href='http://localhost/projet/reset_password.php?token=" . urlencode($token) . "'>إعادة تعيين كلمة المرور</a>
            <p>الرابط صالح لمدة 30 دقيقة فقط.</p>
        ";

        $mail->send();
        $message = "✅ تم إرسال رابط إعادة تعيين كلمة المرور إلى بريدك الإلكتروني.";
    } catch (Exception $e) {
        $message = "❌ فشل في الإرسال: {$mail->ErrorInfo}";
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>نسيت كلمة المرور</title>
  <link rel="icon" type="image/png" href="ChatGPT Image 4 juin 2025, 14_53_49.png">
  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: url('https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=1470&q=80') no-repeat center center fixed;
      background-size: cover;
      position: relative;
      height: 100vh;
      color: #00ffe7;
      display: flex;
      justify-content: center;
      align-items: center;
      direction: rtl;
      overflow: hidden;
    }
    body::before {
      content: "";
      position: absolute;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0, 0, 0, 0.6);
      backdrop-filter: blur(6px);
      z-index: 0;
    }
    .container {
      position: relative;
      background: rgba(1, 33, 42, 0.85);
      padding: 40px 35px;
      border-radius: 12px;
      box-shadow: 0 0 30px #00ffe7;
      width: 380px;
      text-align: center;
      z-index: 1;
      animation: fadeIn 1s ease forwards;
    }
    h1 {
      margin-bottom: 22px;
      font-size: 30px;
      font-weight: 700;
      text-shadow: 0 0 10px #00ffe7;
    }
    p {
      font-size: 16px;
      margin-bottom: 28px;
      color: #a0e8e1;
      text-shadow: 0 0 7px #009999;
    }
    input[type="email"] {
      width: 100%;
      padding: 15px 20px;
      border-radius: 9px;
      border: none;
      font-size: 17px;
      outline: none;
      box-sizing: border-box;
      transition: box-shadow 0.35s ease;
      color: #01212a;
      font-weight: 600;
    }
    input[type="email"]::placeholder {
      color: #444;
    }
    input[type="email"]:focus {
      box-shadow: 0 0 15px #00ffe7;
      background: #defafa;
    }
    button {
      margin-top: 28px;
      width: 100%;
      padding: 15px 0;
      border-radius: 9px;
      border: none;
      background: #00ffe7;
      font-size: 18px;
      font-weight: 700;
      color: #01212a;
      cursor: pointer;
      transition: background-color 0.3s ease, box-shadow 0.3s ease;
      box-shadow: 0 0 20px #00ffe7;
    }
    button:hover {
      background-color: #00d4b3;
      box-shadow: 0 0 25px #00d4b3;
    }
    .back-link {
      margin-top: 22px;
      font-size: 15px;
      color: #00ffe7;
      text-decoration: none;
      display: inline-block;
      transition: color 0.3s ease;
    }
    .back-link:hover {
      color: #00d4b3;
      text-decoration: underline;
    }
    .message {
      margin-top: 15px;
      color: #00ffae;
      font-weight: bold;
    }
    @keyframes fadeIn {
      from {opacity: 0; transform: translateY(-20px);}
      to {opacity: 1; transform: translateY(0);}
    }
    ::-webkit-scrollbar {
      width: 10px;
    }
    ::-webkit-scrollbar-track {
      background: #0d1117;
    }
    ::-webkit-scrollbar-thumb {
      background: #00f2ff;
      border-radius: 10px;
      box-shadow: 0 0 10px #00f2ff;
    }
    ::-webkit-scrollbar-thumb:hover {
      background: #00f2ffcc;
    }
  </style>
</head>
<body>

  <div class="container" role="form" aria-label="نموذج استعادة كلمة المرور">
    <h1>نسيت كلمة المرور؟</h1>
    <p>أدخل بريدك الإلكتروني لاستعادة كلمة المرور الخاصة بك.</p>
    <?php if (isset($message)): ?>
      <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <br>
    <form method="POST">
      <input
        type="email"
        name="email"
        placeholder="البريد الإلكتروني"
        required
        aria-required="true"
        aria-label="البريد الإلكتروني"
      />
      <button type="submit" aria-label="إرسال طلب استعادة كلمة المرور">إرسال</button>
    </form>
    <a href="login.php" class="back-link" aria-label="العودة إلى صفحة تسجيل الدخول">العودة إلى تسجيل الدخول</a>
  </div>

</body>
</html>
