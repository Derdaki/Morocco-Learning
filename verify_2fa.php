<?php
require_once __DIR__ . '/vendor/autoload.php';

use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\Providers\Qr\QRServerProvider;

session_start();

// تأكد من وجود جلسة المستخدم
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// تهيئة مزود رمز QR ومصادقة ثنائية
$qrProvider = new QRServerProvider();
$tfa = new TwoFactorAuth($qrProvider, 'MoroccoLearning');

try {
    $host = 'localhost';
    $port = 3307;
    $dbname = 'moroccolearning';
    $username = 'root';
    $password = '';

    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8";
    $db = new PDO($dsn, $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // جلب المفتاح السري من قاعدة البيانات
    $stmt = $db->prepare("SELECT secret FROM user_2fa WHERE user_id = ? AND is_enabled = 1");
    $stmt->execute([$userId]);
    $secret = $stmt->fetchColumn();

    if (!$secret) {
        // لم يتم تفعيل المصادقة الثنائية للمستخدم
        header('Location: login_success.php');
        exit;
    }
} catch (PDOException $e) {
    die("❌ خطأ في الاتصال بقاعدة البيانات: " . htmlspecialchars($e->getMessage()));
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['code'] ?? '';

    if ($tfa->verifyCode($secret, $code)) {
        $_SESSION['logged_in'] = true; // دخول رسمي
        header("Location: login_success.php");
        exit;
    } else {
        $message = '❌ رمز غير صحيح. حاول مرة أخرى.';
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>تأكيد رمز المصادقة الثنائية</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" href="ChatGPT Image 4 juin 2025, 14_53_49.png">
  <style>
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
      margin-top: 15px;
      font-size: 15px;
      font-weight: bold;
      color: #ff4d4d;
    }
  </style>
</head>
<body>
  <div id="particles-js"></div>
  <div class="container">
    <h1>تأكيد المصادقة الثنائية</h1>
    <p>يرجى إدخال الرمز من تطبيق Google Authenticator:</p>

    <form method="POST">
      <label for="code">الرمز المكون من 6 أرقام:</label>
      <input type="text" id="code" name="code" pattern="\d{6}" required maxlength="6" autocomplete="off" />
      <button type="submit">تأكيد</button>
    </form>

    <?php if ($message): ?>
      <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
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
  </script>
</body>
</html>
