<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$host = 'localhost';
$port = 3307;
$dbname = 'moroccolearning';
$username = 'root';
$password = '';

$acceptedInstitutions = [];
$message = '';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $institutionId = $_POST['institution_id'];
        $action = $_POST['action'];

        if ($action === 'accept') {
            $stmt = $pdo->prepare("UPDATE institutions SET status = 'accepted' WHERE id = ?");
            $stmt->execute([$institutionId]);
            $message = "✅ تم قبول المؤسسة بنجاح.";
        } elseif ($action === 'reject') {
            $stmt = $pdo->prepare("UPDATE institutions SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$institutionId]);
            $message = "❌ تم رفض المؤسسة.";
        }
    }

    // جلب فقط المؤسسات المقبولة
    $stmt = $pdo->prepare("SELECT * FROM institutions WHERE status = 'accepted' ORDER BY id DESC");
    $stmt->execute();
    $acceptedInstitutions = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "فشل الاتصال بقاعدة البيانات: " . htmlspecialchars($e->getMessage());
}
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
      max-width: 1200px; margin: 60px auto; background: rgba(0,31,46,0.95);
      padding: 30px 40px; border-radius: 15px;
      box-shadow: 0 0 40px #00ffe7;
    }
    h1 { font-size: 2.4rem; margin-bottom: 30px; text-align: center; color: #00ffe7; }
    .search-box { margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
    .search-box input {
      padding: 10px 15px; width: 80%;
      border-radius: 10px; border: 1px solid #00ffe7;
      background-color: #001f2e; color: #e0f7fa; font-size: 1rem;
    }
    .btn-add, .btn-login {
      background: #00ffe7; 
      border: none; 
      color: #001f2e;
      font-weight: bold; 
      padding: 8px 15px;
      font-size: 1rem; 
      border-radius: 12px; 
      cursor: pointer; 
      margin-left: 10px;
      height: 40px;
      transition: background-color 0.3s ease;
    }
    .btn-add:hover, .btn-login:hover { 
      background: #00cfc2; 
    }
     .btn-add {
        margin-left: 20px;
        width: 180px;
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
  </style>
</head>
<body>
  <div id="particles-js"></div>
  
  <div class="container">
    <h1>قائمة المؤسسات التعليمية</h1>

    <?php if (!empty($message)): ?>
      <div class="message-box"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="search-box">
      <input type="text" id="searchInput" placeholder="ابحث بإسم المؤسسة...">
      <a href="add_institution.php" class="btn btn-success btn-add">إضافة مؤسسة</a>
    </div>

    <table id="schoolTable">
      <thead>
        <tr>
          <th>اسم المؤسسة</th>
          <th>المنطقة</th>
          <th>الجهة</th>
          <th>المدينة</th>
          <th>عدد التلاميذ</th>
          <th>نوع المؤسسة</th>
          <th>تسجيل الدخول</th> <!-- إضافة عمود تسجيل الدخول -->
        </tr>
      </thead>
      <tbody>
        <?php foreach ($acceptedInstitutions as $inst): ?>
          <tr>
            <td><?= htmlspecialchars($inst['institution_name']) ?></td>
            <td><?= htmlspecialchars($inst['region']) ?></td>
            <td><?= htmlspecialchars($inst['province']) ?></td>
            <td><?= htmlspecialchars($inst['city']) ?></td>
            <td><?= htmlspecialchars($inst['students']) ?></td>
            <td><?= htmlspecialchars($inst['type']) ?></td>
            <td>
                <a href="admin.php" class="btn btn-primary btn-login">تسجيل الدخول</a>
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

    setTimeout(function() {
      const msg = document.querySelector('.message-box');
      if (msg) msg.style.display = 'none';
    }, 4000);
  </script>
</body>
</html>
