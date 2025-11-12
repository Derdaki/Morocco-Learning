<?php
require_once __DIR__ . '/vendor/autoload.php';

use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\Providers\Qr\QRServerProvider;

session_start();

// تأكد من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

$qrProvider = new QRServerProvider();
$tfa = new TwoFactorAuth($qrProvider, 'MoroccoLearning');

// إنشاء سر جديد إذا لم يكن موجودًا في الجلسة
if (!isset($_SESSION['secret'])) {
    $_SESSION['secret'] = $tfa->createSecret();
}

$secret = $_SESSION['secret'];
$qrCodeUrl = $tfa->getQRCodeImageAsDataUri('user@example.com', $secret);

$message = '';
$redirect = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['code'])) {
    $userCode = $_POST['code'];

    if ($tfa->verifyCode($secret, $userCode)) {
        // محاولة الاتصال بقاعدة البيانات
        try {
            $host = 'localhost';
            $port = 3307;
            $dbname = 'moroccolearning';
            $username = 'root';      // عدل اسم المستخدم حسب إعداداتك
            $password = '';          // عدل كلمة المرور حسب إعداداتك

            $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8";

            $db = new PDO($dsn, $username, $password);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // تحقق إذا سجل 2FA موجود
            $stmtCheck = $db->prepare("SELECT COUNT(*) FROM user_2fa WHERE user_id = ?");
            $stmtCheck->execute([$userId]);
            $exists = $stmtCheck->fetchColumn();

            if ($exists) {
                // حدث حالة التفعيل إلى 1
                $stmt = $db->prepare("UPDATE user_2fa SET is_enabled = 1 WHERE user_id = ?");
                $stmt->execute([$userId]);
            } else {
                // أنشئ سجل جديد مع التفعيل
                $stmt = $db->prepare("INSERT INTO user_2fa (user_id, is_enabled) VALUES (?, 1)");
                $stmt->execute([$userId]);
            }

            $message = '<span style="color:#00ff88;">✅ رمز صحيح! تم تفعيل المصادقة الثنائية.</span>';
            $redirect = true;

            // اجعل السر محفوظًا بقاعدة البيانات أو الجلسة إذا أردت
            // هنا يمكنك حفظ $secret في جدول user_2fa مثلاً
        } catch (PDOException $e) {
            $message = '<span style="color:#ff4d4d;">❌ خطأ في قاعدة البيانات: ' . htmlspecialchars($e->getMessage()) . '</span>';
        }
    } else {
        $message = '<span style="color:#ff4d4d;">❌ رمز غير صحيح. حاول مرة أخرى.</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>إعداد المصادقة الثنائية</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" href="ChatGPT Image 4 juin 2025, 14_53_49.png">
  <style>
    /* نفس التنسيقات السابقة */
    body {
      background-color: #001f2e;
      font-family: 'Cairo', sans-serif;
      color: #e0f7fa;
      margin: 0;
      height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      position: relative;
    }

    #particles-js {
      position: absolute;
      width: 100%;
      height: 100%;
      z-index: 0;
    }

    .container {
      background: rgba(0, 0, 0, 0.65);
      padding: 30px 25px;
      border-radius: 16px;
      text-align: center;
      box-shadow: 0 0 20px #00ffe7;
      z-index: 1;
      position: relative;
      width: 100%;
      max-width: 420px;
      margin: 0 20px;
    }

    h1 {
      color: #00ffe7;
      font-size: 22px;
      margin-bottom: 15px;
    }

    p {
      font-size: 15px;
      margin-bottom: 10px;
      color: #d0f0f0;
    }

    img {
      margin: 15px 0;
      width: 160px;
      height: 160px;
    }

    code {
      color: #ffea00;
      display: block;
      word-break: break-all;
      margin: 10px 0;
      font-size: 14px;
    }

    label {
      display: block;
      margin: 15px 0 5px;
      color: #e0f7fa;
      text-align: right;
    }

    input[type="text"] {
      width: 95%;
      padding: 10px;
      font-size: 16px;
      border: none;
      border-radius: 8px;
      margin-bottom: 10px;
      text-align: center;
      background-color: #002b40;
      color: #ffffff;
    }

    button {
      background-color: #00ffe7;
      color: #001f2e;
      border: none;
      padding: 10px 25px;
      font-size: 16px;
      border-radius: 8px;
      cursor: pointer;
      transition: 0.3s ease;
    }

    button:hover {
      background-color: #00cfc2;
    }

    .message {
      margin-top: 20px;
      font-weight: bold;
      font-size: 15px;
    }
  </style>
</head>
<body>
  <div id="particles-js"></div>

  <div class="container">
    <h1>إعداد المصادقة الثنائية (2FA)</h1>

    <p>امسح رمز QR باستخدام Google Authenticator أو أي تطبيق مشابه:</p>
    <img src="<?= $qrCodeUrl ?>" alt="QR Code" />

    <p><strong>أو أدخل المفتاح يدوياً:</strong></p>
    <code><?= htmlspecialchars($secret) ?></code>

    <form method="POST" novalidate>
      <label for="code">أدخل الرمز المؤقت من التطبيق:</label>
      <input type="text" id="code" name="code" required pattern="\d{6}" maxlength="6" autocomplete="off" />
      <button type="submit">تفعيل المصادقة الثنائية</button>
    </form>

    <div class="message"><?= $message ?></div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
  <script>
    particlesJS("particles-js", {
      "particles": {
        "number": { "value": 60, "density": { "enable": true, "value_area": 800 }},
        "color": { "value": "#00ffe7" },
        "shape": { "type": "circle" },
        "opacity": { "value": 0.5, "random": true },
        "size": { "value": 3, "random": true },
        "line_linked": {
          "enable": true,
          "distance": 150,
          "color": "#00ffe7",
          "opacity": 0.4,
          "width": 1
        },
        "move": { "enable": true, "speed": 2, "direction": "none", "out_mode": "bounce" }
      },
      "interactivity": {
        "detect_on": "canvas",
        "events": {
          "onhover": { "enable": true, "mode": "grab" },
          "onclick": { "enable": true, "mode": "push" },
          "resize": true
        },
        "modes": {
          "grab": { "distance": 200, "line_linked": { "opacity": 0.5 }},
          "push": { "particles_nb": 4 }
        }
      },
      "retina_detect": true
    });

    <?php if ($redirect): ?>
      setTimeout(() => {
        window.location.href = 'settings.php';
      }, 2000); // بعد ثانيتين يتحول
    <?php endif; ?>
  </script>
</body>
</html>
