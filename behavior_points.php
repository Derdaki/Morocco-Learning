<?php
session_start();

// تأكد من تسجيل دخول المستخدم
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// إعدادات الاتصال بقاعدة البيانات (عدّل حسب إعداداتك)
$host = 'localhost';
$port = 3307;
$dbname = 'moroccolearning';
$username = 'root';
$password = '';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8";
    $db = new PDO($dsn, $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // جلب نقاط السلوك من جدول user_behavior_points
    $stmt = $db->prepare("SELECT points FROM user_behavior_points WHERE user_id = ?");
    $stmt->execute([$userId]);
    $points = $stmt->fetchColumn();

    if ($points === false) {
        // إذا لم توجد نقاط (مستخدم جديد مثلا)، اعطيه نقطة 100 كبداية
        $points = 100;
    } else {
        $points = (int)$points;
    }
} catch (PDOException $e) {
    die("❌ خطأ في الاتصال بقاعدة البيانات: " . htmlspecialchars($e->getMessage()));
}

$stmt = $db->prepare("SELECT points FROM user_behavior_points WHERE user_id = ?");
$stmt->execute([$userId]);
$points = $stmt->fetchColumn();
if ($points === false) {
    $points = 100; // قيمة افتراضية لو ما لاقى
}

// دالة لتحديد لون الدائرة بناءً على النقاط
function getCircleColor($points) {
    if ($points >= 100 && $points <= 110) {
        return '#28a745'; // أخضر
    } elseif ($points >= 93 && $points < 100) {
        return '#ffc107'; // أصفر إنذار
    } elseif ($points >= 90 && $points < 93) {
        return '#fd7e14'; // برتقالي تحذير
    } else {
        return '#dc3545'; // أحمر خطير
    }
}

$circleColor = getCircleColor($points);

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <title>نقاط السلوك</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet" />
  <link rel="icon" type="image/png" href="ChatGPT Image 4 juin 2025, 14_53_49.png">
  <style>
    body, html {
      margin: 0; padding: 0; height: 100%;
      background-color: #001f2e;
      font-family: 'Cairo', sans-serif;
      color: #e0f7fa;
      overflow: hidden;
      display: flex;
      justify-content: center;
      align-items: center;
    }
    #particles-js {
      position: fixed;
      width: 100%;
      height: 100%;
      top: 0; left: 0;
      z-index: 0;
    }
    .card {
      position: relative;
      background: rgba(0, 0, 0, 0.7);
      border-radius: 20px;
      box-shadow: 0 0 25px #00ffe7;
      width: 90%;
      max-width: 900px;
      padding: 30px;
      text-align: center;
      z-index: 1;
      min-height: 280px;
      margin: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 40px;
      direction: rtl;
    }
    .circle-container {
      position: relative;
      width: 220px;
      height: 220px;
      background: <?= $circleColor ?>;
      border-radius: 50%;
      box-shadow: 0 0 25px <?= $circleColor ?>;
      overflow: visible;
      z-index: 2;
      transition: background 0.5s ease, box-shadow 0.5s ease;
      flex-shrink: 0;
    }
    .circle {
      position: relative;
      width: 100%;
      height: 100%;
      border-radius: 50%;
      background: #001f2e;
      box-shadow: inset 0 0 25px <?= $circleColor ?>;
      display: flex;
      justify-content: center;
      align-items: center;
      font-size: 56px;
      font-weight: 700;
      color: <?= $circleColor ?>;
      user-select: none;
      font-family: 'Cairo', sans-serif;
      letter-spacing: 2px;
    }
    .moving-dot {
      position: absolute;
      top: 50%;
      left: 50%;
      width: 20px;
      height: 20px;
      background-color: <?= $circleColor ?>;
      border-radius: 50%;
      box-shadow: 0 0 15px <?= $circleColor ?>;
      transform-origin: -70px 0;
      animation: rotateDot 6s linear infinite;
    }
    @keyframes rotateDot {
      from { transform: rotate(0deg) translateX(70px) rotate(0deg); }
      to { transform: rotate(360deg) translateX(70px) rotate(-360deg); }
    }
    .description {
      max-width: 650px;
      font-size: 16px;
      line-height: 1.6;
      color: #b0e0e6;
      text-align: right;
      direction: rtl;
    }
    .description h2 {
      color: #00ffe7;
      margin-bottom: 15px;
      font-weight: 700;
      text-align: right;
    }
    .description ul {
      padding-inline-start: 20px;
      margin-top: 0;
      margin-bottom: 15px;
    }
    .description ul li {
      margin-bottom: 10px;
    }
  </style>
</head>
<body>

<div id="particles-js"></div>

<div class="card">
  <div class="circle-container">
    <div class="circle">
      <?= htmlspecialchars($points) ?>
      <div class="moving-dot"></div>
    </div>
  </div>

  <div class="description">
    <h2>طريقة احتساب نقاط السلوك</h2>
    <p>
      تعتمد منصة MoroccoLearning على نظام نقاط السلوك لضمان بيئة تعليمية آمنة ومنظمة:
    </p>
    <ul>
      <li>نقاط 110: سلوك جيد جداً ويستفيد من مزايا كثيرة ومتعددة.</li>
      <li>نقطة 100: نقطة البداية عند إنشاء الحساب.</li>
      <li>تحت 95: يمنع المستخدم من الاستفادة من الميزات الجديدة.</li>
      <li>تحت 90: يتم إغلاق الحساب تماماً مع إخراج مذكرة رسمية باسم المستخدم، رقم بطاقته الوطنية، بريده الإلكتروني، ورقم مساره. ولا يُسمح له بالتسجيل إلا بعد الالتزام الصارم بالشروط.</li>
    </ul>
    <p>
      تهدف هذه النقاط إلى تحفيز السلوك الإيجابي ومراقبة الالتزام بقواعد المجتمع.
    </p>
      <div style="text-align: right; margin-top: 30px; width:200px">
  <a href="settings.php" style="
    display: block;
    padding: 10px 25px;
    background-color: #00ffe7;
    color: #001f2e;
    font-weight: bold;
    border-radius: 8px;
    text-decoration: none;
    transition: background-color 0.3s ease;
  " onmouseover="this.style.backgroundColor='#00cfc2';" onmouseout="this.style.backgroundColor='#00ffe7';">
    العودة إلى الإعدادات
  </a>
</div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
<script>
  particlesJS("particles-js", {
    "particles": {
      "number": { "value": 60, "density": { "enable": true, "value_area": 800 }},
      "color": { "value": "<?= $circleColor ?>" },
      "shape": { "type": "circle" },
      "opacity": { "value": 0.5, "random": true },
      "size": { "value": 3, "random": true },
      "line_linked": {
        "enable": true,
        "distance": 150,
        "color": "<?= $circleColor ?>",
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
