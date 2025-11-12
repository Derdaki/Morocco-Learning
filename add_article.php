<?php
session_start();

// تأكد من تسجيل دخول المستخدم
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// إعدادات الاتصال بقاعدة البيانات (عدّل حسب بياناتك)
$host = 'localhost';
$port = 3307;
$dbname = 'moroccolearning';
$username = 'root';
$password = '';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8";
    $db = new PDO($dsn, $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // جلب المقالات المنشورة فقط
    $stmt = $db->prepare("SELECT id, title, summary, image, author, category, views, created_at FROM articles WHERE status = 'published' ORDER BY created_at DESC");
    $stmt->execute();
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("❌ خطأ في الاتصال بقاعدة البيانات: " . htmlspecialchars($e->getMessage()));
}

// لون الخلفية الرئيسي
$mainColor = '#00ffe7';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <title>المقالات</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet" />
  <link rel="icon" type="image/png" href="ChatGPT Image 4 juin 2025, 14_53_49.png">
  <style>
    * {
      box-sizing: border-box;
    }
    body, html {
      margin: 0; padding: 0; height: 100%;
      background-color: #001f2e;
      font-family: 'Cairo', sans-serif;
      color: #e0f7fa;
      overflow-x: hidden;
      direction: rtl;
    }
    #particles-js {
      position: fixed;
      width: 100%;
      height: 100%;
      top: 0; left: 0;
      z-index: 0;
    }
    header {
      text-align: center;
      padding: 30px 20px 20px;
      font-size: 32px;
      font-weight: 700;
      color: <?= $mainColor ?>;
      text-shadow: 0 0 8px <?= $mainColor ?>;
      z-index: 1;
      position: relative;
    }
    .container {
      max-width: 1100px;
      margin: 20px auto 60px;
      padding: 0 20px;
      z-index: 1;
      position: relative;
    }
    .article-card {
      background: rgba(0, 0, 0, 0.7);
      border-radius: 20px;
      box-shadow: 0 0 25px <?= $mainColor ?>;
      margin-bottom: 30px;
      overflow: hidden;
      display: flex;
      gap: 20px;
      transition: transform 0.3s ease;
    }
    .article-card:hover {
      transform: scale(1.02);
      box-shadow: 0 0 40px <?= $mainColor ?>;
    }
    .article-image {
      flex-shrink: 0;
      width: 260px;
      height: 260px;
      object-fit: cover;
      border-radius: 20px 0 0 20px;
      filter: drop-shadow(0 0 6px <?= $mainColor ?>);
    }
    .article-info {
      padding: 20px;
      flex-grow: 1;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    .article-title {
      font-size: 24px;
      font-weight: 700;
      margin: 0 0 12px 0;
      color: <?= $mainColor ?>;
      cursor: default;
    }
    .article-summary {
      font-size: 16px;
      line-height: 1.5;
      color: #b0e0e6;
      margin-bottom: 16px;
      flex-grow: 1;
    }
    .article-meta {
      font-size: 14px;
      color: #89cfe4;
      margin-bottom: 10px;
    }
    .read-btn {
      align-self: flex-start;
      padding: 10px 25px;
      background-color: <?= $mainColor ?>;
      color: #001f2e;
      font-weight: 700;
      border-radius: 8px;
      text-decoration: none;
      box-shadow: 0 0 15px <?= $mainColor ?>;
      transition: background-color 0.3s ease;
    }
    .read-btn:hover {
      background-color: #00cfc2;
      box-shadow: 0 0 20px #00cfc2;
    }
    footer {
      text-align: center;
      color: #555;
      padding: 20px;
      font-size: 14px;
      user-select: none;
    }
    @media (max-width: 720px) {
      .article-card {
        flex-direction: column;
      }
      .article-image {
        width: 100%;
        height: 180px;
        border-radius: 20px 20px 0 0;
      }
      .article-info {
        padding: 15px 15px 25px;
      }
    }
      /* تخصيص شريط التمرير */
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
  transition: background 0.3s ease;
}

::-webkit-scrollbar-thumb:hover {
  background: #00f2ffcc;
}
  </style>
</head>
<body>

<div id="particles-js"></div>

<header>العالم كتاب من لا يسافر فيه لا يرى سوى صفحة واحدة</header>

<div class="container">
<?php if(count($articles) === 0): ?>
  <p style="text-align:center; font-size: 18px; color:#888;">لا توجد مقالات منشورة حالياً.</p>
<?php else: ?>
  <?php foreach ($articles as $article): ?>
    <div class="article-card">
      <img class="article-image" src="<?= htmlspecialchars($article['image']) ?>" alt="صورة <?= htmlspecialchars($article['title']) ?>" />
      <div class="article-info">
        <h2 class="article-title"><?= htmlspecialchars($article['title']) ?></h2>
        <p class="article-summary"><?= htmlspecialchars($article['summary']) ?></p>
        <div class="article-meta">
          <span>الكاتب: <?= htmlspecialchars($article['author']) ?></span> |
          <span>التصنيف: <?= htmlspecialchars($article['category']) ?></span> |
          <span>عدد المشاهدات: <?= (int)$article['views'] ?></span> |
          <span>تاريخ النشر: <?= date('d-m-Y', strtotime($article['created_at'])) ?></span>
        </div>
        <a class="read-btn" href="article.php?id=<?= (int)$article['id'] ?>">اقرأ الآن</a>
      </div>
      
    </div>
  <?php endforeach; ?>
<?php endif; ?>
</div>

<footer style="color:aliceblue;">© MoroccoLearning - جميع الحقوق محفوظة 2025</footer>

<script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
<script>
  particlesJS("particles-js", {
    "particles": {
      "number": { "value": 60, "density": { "enable": true, "value_area": 800 }},
      "color": { "value": "<?= $mainColor ?>" },
      "shape": { "type": "circle" },
      "opacity": { "value": 0.5, "random": true },
      "size": { "value": 3, "random": true },
      "line_linked": {
        "enable": true,
        "distance": 150,
        "color": "<?= $mainColor ?>",
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
