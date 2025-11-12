
<?php
// تأكد من تضمين مكتبة PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'src/PHPMailer.php';
require 'src/SMTP.php';
require 'src/Exception.php';

$host = 'localhost';
$port = 3307;   // عدّل حسب بورت MySQL عندك
$dbname = 'moroccolearning';
$username = 'root';
$password = '';  // كلمة المرور عندك

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ تم الاتصال بقاعدة البيانات بنجاح!";
} catch (PDOException $e) {
    die("❌ خطأ في الاتصال بقاعدة البيانات: " . htmlspecialchars($e->getMessage()));
}

// دالة إرسال البريد باستخدام PHPMailer و SMTP
function send_email($to, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'moroccolearningnational@gmail.com';
        $mail->Password   = 'xkww kauk mslp isoi';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('moroccolearningnational@gmail.com', 'نظام المقالات');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("خطأ في إرسال البريد: {$mail->ErrorInfo}");
        return false;
    }
}

// معالجة الموافقة أو الرفض
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $article_id = intval($_POST['article_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT author, email, title FROM articles WHERE id = :id");
    $stmt->execute([':id' => $article_id]);
    $article = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($article) {
        $author = htmlspecialchars($article['author']);
        $email = $article['email'] ?? '';
        $title = htmlspecialchars($article['title']);
        if ($action === 'approve') {
            $update = $pdo->prepare("UPDATE articles SET status = 'published' WHERE id = :id");
            $update->execute([':id' => $article_id]);
            $link = "https://yourdomain.com/article.php?id=$article_id";
            $subject = "تمت الموافقة على مقالك";
            $msg = "<p>مرحبًا $author،</p>
                    <p>تمت الموافقة على مقالك بعنوان <strong>$title</strong>، يمكنك الآن مشاهدته على الرابط التالي:</p>
                    <p><a href='$link'>$link</a></p>
                    <p>شكرًا لمساهمتك!</p>";
            send_email($email, $subject, $msg);
        } elseif ($action === 'reject') {
            $delete = $pdo->prepare("DELETE FROM articles WHERE id = :id");
            $delete->execute([':id' => $article_id]);
            $subject = "تم رفض مقالك";
            $msg = "<p>مرحبًا $author،</p>
                    <p>نأسف لإبلاغك بأنه تم رفض مقالك بعنوان <strong>$title</strong> بسبب عدم احترام معايير المجتمع أو نقاط السلوك.</p>
                    <p>يمكنك محاولة إرسال مقال آخر وفق الشروط.</p>";
            send_email($email, $subject, $msg);
        }
    }
    header("Location: admin_review.php");
    exit;
}

$stmt = $pdo->prepare("SELECT id, title, author, email, phone, created_at FROM articles WHERE status = 'pending' ORDER BY created_at DESC");
$stmt->execute();
$pending_articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>إدارة مراجعة المقالات</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Cairo&display=swap');
  * {
    margin: 0; padding: 0; box-sizing: border-box; font-family: 'Cairo', sans-serif;
  }
    body, html {
      margin: 0; padding: 0; min-height: 100vh;
      background-color: #001f2e;
      font-family: 'Cairo', sans-serif;
      color: #e0f7fa;
      direction: rtl;
    }
  .container {
    max-width: 1000px;
    margin: 60px auto 40px;
    background: rgba(0,31,46,0.95);
    padding: 30px 40px;
    border-radius: 15px;
    box-shadow: 0 0 40px #00ffe7;
  }
      #particles-js {
      position: fixed;
      width: 100%;
      height: 100%;
      top: 0; left: 0;
      z-index: 0;
      pointer-events: none;
    }
  h1 {
    font-size: 2.4rem;
    margin-bottom: 30px;
    text-align: center;
    color: #00ffe7;
  }
  table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 30px;
  }
  th, td {
    padding: 12px 15px;
    border-bottom: 1px solid #004d66;
    text-align: center;
    font-size: 1rem;
  }
  th { background-color: #00334d; }
  tr:hover { background-color: #004d66; }
  button {
    background: #00ffe7;
    border: none;
    color: #001f2e;
    font-weight: bold;
    padding: 8px 15px;
    font-size: 1rem;
    border-radius: 12px;
    cursor: pointer;
    margin: 0 5px;
    transition: background-color 0.3s ease;
  }
  button:hover { background: #00cfc2; }
  .approve-btn { background-color: #00ff9f; color: #004400; }
  .approve-btn:hover { background-color: #00cc7a; }
  .reject-btn { background-color: #ff5c5c; color: #440000; }
  .reject-btn:hover { background-color: #cc4949; }
  .no-articles {
    text-align: center;
    font-size: 1.2rem;
    margin-top: 40px;
    color: #00cfc2;
  }
  a.home-link {
    display: inline-block;
    margin-bottom: 25px;
    color: #00ffe7;
    text-decoration: none;
    font-size: 1.2rem;
    font-weight: 600;
  }
  a.home-link:hover { text-decoration: underline; }
  ::-webkit-scrollbar { width: 10px; }
  ::-webkit-scrollbar-track { background: #0d1117; }
  ::-webkit-scrollbar-thumb {
    background: #00f2ff;
    border-radius: 10px;
    box-shadow: 0 0 10px #00f2ff;
    transition: background 0.3s ease;
  }
  ::-webkit-scrollbar-thumb:hover { background: #00f2ffcc; }
  @keyframes fadeIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
  }

</style>
</head>
<body>
<div id="particles-js"></div>
<div class="container">
  <button onclick="window.location.href='articles.php';">← العودة إلى الصفحة الرئيسية للمقالات</button>
  <h1>مقالات في انتظار المراجعة</h1>
  <?php if (count($pending_articles) === 0): ?>
    <p class="no-articles">لا توجد مقالات في انتظار المراجعة حالياً.</p>
  <?php else: ?>
    <table>
<thead>
  <tr>
    <th>العنوان</th>
    <th>الكاتب</th>
    <th>البريد الإلكتروني</th>
    <th>رقم الهاتف</th>
    <th>تاريخ الإرسال</th>
    <th>التحكم</th>
  </tr>
</thead>
<tbody>
<?php foreach ($pending_articles as $art): ?>
  <tr>
    <td><?= htmlspecialchars($art['title']) ?></td>
    <td><?= htmlspecialchars($art['author']) ?></td>
    <td><?= htmlspecialchars($art['email']) ?></td>
    <td><?= htmlspecialchars($art['phone']) ?></td>
    <td><?= date('Y-m-d H:i', strtotime($art['created_at'])) ?></td>
    <td>
      <form method="post" style="display:inline-block;">
        <input type="hidden" name="article_id" value="<?= $art['id'] ?>" />
        <button type="submit" name="action" value="approve" class="approve-btn">موافقة</button>
      </form>
      <form method="post" style="display:inline-block;">
        <input type="hidden" name="article_id" value="<?= $art['id'] ?>" />
        <button type="submit" name="action" value="reject" class="reject-btn">رفض</button>
      </form>
      <button type="button" class="view-btn" data-id="<?= $art['id'] ?>">عرض</button>
    </td>
  </tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</div>

<!-- مودال جمالي -->
<div id="articleModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%;
  background:rgba(0,0,0,0.7); backdrop-filter: blur(4px); z-index:9999; justify-content:center; align-items:center;">
  <div style="background:#001f2e; color:#00ffe7; border-radius:20px; padding:30px 25px; width:90%; max-width:700px;
      max-height:80vh; overflow-y:auto; box-shadow: 0 0 25px #00ffe7; position:relative; animation: fadeIn 0.4s ease-in-out;">
    <button onclick="closeModal()" style="position:absolute; top:12px; left:12px; background:#00ffe7;
        color:#001f2e; border:none; padding:6px 12px; border-radius:8px; font-weight:bold; cursor:pointer; font-size:1rem;">
      ✖
    </button>
    <div id="modalContent" style="margin-top:30px; line-height:1.8; font-size:1.1rem;"></div>
  </div>
</div>

<script>
function closeModal() {
  document.getElementById('articleModal').style.display = 'none';
  document.getElementById('modalContent').innerHTML = '';
}

document.querySelectorAll('.view-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    const id = this.getAttribute('data-id');
    fetch('get_article.php?id=' + id)
      .then(res => res.text())
      .then(html => {
        document.getElementById('modalContent').innerHTML = html;
        document.getElementById('articleModal').style.display = 'flex';
      })
      .catch(err => {
        document.getElementById('modalContent').innerHTML = '<p style="color:red;">تعذر تحميل المقال.</p>';
        document.getElementById('articleModal').style.display = 'flex';
      });
  });
});
</script>
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
