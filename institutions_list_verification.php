<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require 'src/PHPMailer.php';
require 'src/SMTP.php';
require 'src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// الاتصال بقاعدة البيانات
$host = 'localhost';
$db = 'moroccolearning';
$user = 'root';
$pass = '';
$port = 3307;

$dsn = "mysql:host=$host;dbname=$db;port=$port;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("فشل الاتصال بقاعدة البيانات: " . $e->getMessage());
}

$message = "";
$filter = 'all'; // الفلترة الافتراضية

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['institution_id'], $_POST['action'])) {
    $institutionId = $_POST['institution_id'];
    $action = $_POST['action'];

    // الحصول على البريد الإلكتروني وحالة المؤسسة
    $stmt = $pdo->prepare("SELECT email, status FROM institutions WHERE id = ?");
    $stmt->execute([$institutionId]);
    $institution = $stmt->fetch();
    $email = $institution['email'] ?? null;
    $status = $institution['status'] ?? null;


    if ($email && $status !== 'accepted') {
        if ($action === 'accept') {
            // تحديث حالة المؤسسة
            $stmt = $pdo->prepare("UPDATE institutions SET status = 'accepted' WHERE id = ?");
            $stmt->execute([$institutionId]);

            // توليد رمز مؤسسة مكون من 7 أرقام فريد
            do {
                $code = str_pad(random_int(0, 9999999), 7, '0', STR_PAD_LEFT);
                $check = $pdo->prepare("SELECT id FROM directors_login WHERE code = ?");
                $check->execute([$code]);
            } while ($check->rowCount() > 0);

            // توليد كلمة مرور مؤقتة (8 أحرف)
            $rawPassword = bin2hex(random_bytes(4)); // كلمة سر مثل: a3b4f1d2
            $hashedPassword = password_hash($rawPassword, PASSWORD_DEFAULT);

            // حفظ في جدول تسجيل المدير
            $insert = $pdo->prepare("INSERT INTO directors_login (institution_id, code, password) VALUES (?, ?, ?)");
            $insert->execute([$institutionId, $code, $hashedPassword]);
            
            // تحديث جدول institutions لحفظ الرمز في عمود access_code
            $updateCode = $pdo->prepare("UPDATE institutions SET access_code = ? WHERE id = ?");
            $updateCode->execute([$code, $institutionId]);

            $message = "✅ تم قبول المؤسسة.";
          
            // إرسال البريد
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'moroccolearningnational@gmail.com'; // بريدك الإلكتروني
                $mail->Password = 'xkww kauk mslp isoi';        // كلمة مرور التطبيق
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('moroccolearningnational@gmail.com', 'منصة Morocco Learning');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = 'تم قبول المؤسسة - بيانات الدخول';
                $mail->Body = "
                    <p>شكرًا لتسجيلكم في منصة <strong>Morocco Learning</strong>.</p>
                    <p>لقد تم <strong>قبول</strong> مؤسستكم بنجاح.</p>
                    <hr>
                    <p><strong>رمز المؤسسة:</strong> $code</p>
                    <p><strong>كلمة المرور المؤقتة:</strong> $rawPassword</p>
                    <p>يرجى استخدام هذه البيانات لتسجيل الدخول كمدير في لوحة التحكم، ويمكنك تغيير كلمة المرور لاحقًا.</p>
                ";

                $mail->send();
            } catch (Exception $e) {
                error_log("❌ فشل في إرسال بريد القبول: {$mail->ErrorInfo}");
            }

        } elseif ($action === 'reject') {
            $stmt = $pdo->prepare("UPDATE institutions SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$institutionId]);
            $message = "❌ تم رفض المؤسسة.";

            // إرسال بريد الرفض
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'moroccolearningnational@gmail.com'; // بريدك الإلكتروني
                $mail->Password = 'xkww kauk mslp isoi';        // كلمة مرور التطبيق
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('moroccolearningnational@gmail.com', 'منصة Morocco Learning');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = 'رفض المؤسسة';
                $mail->Body = "نأسف، لكن تم رفض مؤسستك لعدم ملء الاستمارة بشكل صحيح. الرجاء مراجعة البيانات والمحاولة مجددًا.";

                $mail->send();
            } catch (Exception $e) {
                error_log("❌ فشل في إرسال بريد الرفض: {$mail->ErrorInfo}");
            }
        }
    } else {
        error_log("❌ لم يتم العثور على البريد الإلكتروني للمؤسسة بالرقم: $institutionId");
    }
}

// التحقق من الفلترة
if (isset($_GET['filter'])) {
    $filter = $_GET['filter'];
}

// جلب المؤسسات حسب الفلترة
if ($filter === 'accepted') {
    $stmt = $pdo->prepare("SELECT * FROM institutions WHERE status = 'accepted' ORDER BY id DESC");
} elseif ($filter === 'rejected') {
    $stmt = $pdo->prepare("SELECT * FROM institutions WHERE status = 'rejected' ORDER BY id DESC");
} elseif ($filter === 'pending') {
    $stmt = $pdo->prepare("SELECT * FROM institutions WHERE status IS NULL ORDER BY id DESC");
} else {
    $stmt = $pdo->prepare("SELECT * FROM institutions ORDER BY id DESC");
}
$stmt->execute();
$institutions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>قائمة المؤسسات التعليمية</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Cairo&display=swap');
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Cairo', sans-serif; }
    body, html {
      background-color: #001f2e; color: #e0f7fa; min-height: 100vh; direction: rtl;
    }
    .container {
      max-width: 1300px; margin: 60px auto; background: rgba(0,31,46,0.95);
      padding: 30px 40px; border-radius: 15px;
      box-shadow: 0 0 40px #00ffe7;
    }
    h1 { font-size: 2.4rem; margin-bottom: 30px; text-align: center; color: #00ffe7; }
    .search-box { margin-bottom: 20px; text-align: center; }
    .search-box input {
      padding: 10px 15px; width: 80%;
      border-radius: 10px; border: 1px solid #00ffe7;
      background-color: #001f2e; color: #e0f7fa; font-size: 1rem;
    }
    table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
    th, td {
      padding: 12px 15px; border-bottom: 1px solid #004d66;
      text-align: center; font-size: 1rem;
    }
    #particles-js {
      position: fixed;
      width: 100%;
      height: 100%;
      top: 0; left: 0;
      z-index: 0;
      pointer-events: none;
    }
    th { background-color: #00334d; }
    tr:hover { background-color: #004d66; }
    button {
      background: #00ffe7; border: none; color: #001f2e;
      font-weight: bold; padding: 8px 15px;
      font-size: 1rem; border-radius: 12px; cursor: pointer; margin: 0 5px;
      transition: background-color 0.3s ease;
    }
    button:hover { background: #00cfc2; }
    ::-webkit-scrollbar { width: 10px; }
    ::-webkit-scrollbar-track { background: #0d1117; }
    ::-webkit-scrollbar-thumb {
      background: #00f2ff; border-radius: 10px;
      box-shadow: 0 0 10px #00f2ff;
    }
    ::-webkit-scrollbar-thumb:hover { background: #00f2ffcc; }
    .message-box {
      background-color: #00ffe766; color: #001f2e;
      padding: 10px 20px; border-radius: 8px;
      text-align: center; margin-bottom: 20px; font-weight: bold;
    }
    .filter-buttons {
      display: flex;
      justify-content: center;
      margin-bottom: 20px;
    }
    .filter-button {
      background-color: #00ffe7;
      border: none;
      color: #001f2e;
      font-weight: bold;
      padding: 10px 20px;
      font-size: 1rem;
      border-radius: 12px;
      cursor: pointer;
      margin: 0 5px;
      transition: background-color 0.3s ease;
    }
    .filter-button:hover {
      background-color: #00cfc2;
    }
    .filter-button.active {
      background-color: #00cfc2;
      color: #ffffff;
    }
  </style>
</head>
<body>
  <div id="particles-js"></div>
  <div class="container">
    <h1>قائمة حالات المؤسسات التعليمية</h1>

    <?php if (!empty($message)): ?>
      <div class="message-box"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="filter-buttons">
      <button class="filter-button <?= $filter === 'all' ? 'active' : '' ?>" onclick="filterInstitutions('all')">الكل</button>
      <button class="filter-button <?= $filter === 'accepted' ? 'active' : '' ?>" onclick="filterInstitutions('accepted')">المؤسسات المقبولة</button>
      <button class="filter-button <?= $filter === 'rejected' ? 'active' : '' ?>" onclick="filterInstitutions('rejected')">المؤسسات المرفوضة</button>
      <button class="filter-button <?= $filter === 'pending' ? 'active' : '' ?>" onclick="filterInstitutions('pending')">المؤسسات المعلقة</button>
    </div>

    <div class="search-box">
      <input type="text" id="searchInput" placeholder="ابحث بإسم المؤسسة...">
    </div>

    <table id="schoolTable">
      <thead>
        <tr>
          <th>اسم المدير</th>
          <th>اسم المؤسسة</th>
          <th>الهاتف</th>
          <th>البريد الإلكتروني</th>
          <th>الجهة</th>
          <th>المدينة</th>
          <th>عدد التلاميذ</th>
          <th>نوع المؤسسة</th>
          <th>الحالة</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($institutions as $inst): ?>
          <tr>
            <td><?= htmlspecialchars($inst['name']) ?></td>
            <td><?= htmlspecialchars($inst['institution_name']) ?></td>
            <td><?= htmlspecialchars($inst['phone']) ?></td>
            <td><?= htmlspecialchars($inst['email']) ?></td>
            <td><?= htmlspecialchars($inst['province']) ?></td>
            <td><?= htmlspecialchars($inst['city']) ?></td>
            <td><?= htmlspecialchars($inst['students']) ?></td>
            <td><?= htmlspecialchars($inst['type']) ?></td>
            <td>
              <?php if ($inst['status'] === 'accepted'): ?>
                <span style="color: #00ff99;">مقبولة ✅</span>
              <?php elseif ($inst['status'] === 'rejected'): ?>
                <span style="color: #ff5555;">مرفوضة ❌</span>
              <?php else: ?>
<form method="post" action="" style="display:inline;">
  <input type="hidden" name="institution_id" value="<?= $inst['id'] ?>">
  <button type="submit" name="action" value="accept" style="background-color: #28a745; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer;">✔️</button>
  <button type="submit" name="action" value="reject" style="background-color: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer;">❌</button>
</form>

              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
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
          "enable": true, "distance": 150,
          "color": "#00ffe7", "opacity": 0.4, "width": 1
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

    document.getElementById('searchInput').addEventListener('keyup', function() {
      var value = this.value.toLowerCase();
      var rows = document.querySelectorAll('#schoolTable tbody tr');
      rows.forEach(function(row) {
        var name = row.cells[0].textContent.toLowerCase();
        row.style.display = name.includes(value) ? '' : 'none';
      });
    });

    function filterInstitutions(status) {
      window.location.href = "?filter=" + status; // إعادة توجيه مع الفلترة
    }

    setTimeout(function() {
      const msg = document.querySelector('.message-box');
      if (msg) msg.style.display = 'none';
    }, 4000);
  </script>
</body>
</html>
