<?php
// article.php
session_start();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("رابط المقال غير صالح.");
}

$id = (int)$_GET['id'];

$host = 'localhost';
$port = 3307;
$dbname = 'moroccolearning';
$username = 'root';
$password = '';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8";
    $db = new PDO($dsn, $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // زيادة المشاهدات أولاً
    $update = $db->prepare("UPDATE articles SET views = views + 1 WHERE id = ? AND status = 'published'");
    $update->execute([$id]);

    // جلب بيانات المقال
    $stmt = $db->prepare("SELECT * FROM articles WHERE id = ? AND status = 'published'");
    $stmt->execute([$id]);
    $article = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$article) {
        die("المقال غير موجود.");
    }

} catch (PDOException $e) {
    die("❌ خطأ في الاتصال بقاعدة البيانات: " . htmlspecialchars($e->getMessage()));
}

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <title><?= htmlspecialchars($article['title']) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet" />
  <style>
    body, html {
      margin: 0; padding: 0; min-height: 100vh;
      background-color: #001f2e;
      font-family: 'Cairo', sans-serif;
      color: #e0f7fa;
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
      padding: 20px;
      text-align: center;
      font-size: 28px;
      font-weight: 700;
      color: #00ffe7;
      text-shadow: 0 0 8px #00ffe7;
      position: relative;
      z-index: 1;
    }
    .container {
      position: relative;
      max-width: 1000px;
      margin: 20px auto 40px auto;
      background: rgba(0,0,0,0.7);
      border-radius: 15px;
      padding: 25px 35px;
      box-shadow: 0 0 20px #00ffe7;
      color: #b0e0e6;
      z-index: 1;
    }
    .article-image {
      width: 100%;
      max-height: 400px;
      object-fit: cover;
      border-radius: 15px;
      margin-bottom: 20px;
      box-shadow: 0 0 15px #00ffe7;
    }
    .article-meta {
      font-size: 14px;
      color: #66d9ff;
      margin-bottom: 25px;
    }
    .article-content {
      font-size: 18px;
      line-height: 1.8;
      white-space: pre-wrap;
    }
    a.back-link {
      display: inline-block;
      margin-top: 30px;
      padding: 10px 20px;
      background-color: #00ffe7;
      color: #001f2e;
      font-weight: bold;
      border-radius: 10px;
      text-decoration: none;
      transition: background-color 0.3s ease;
    }
    a.back-link:hover {
      background-color: #00cfc2;
    }
            /* تخصيص شريط التمرير */
    ::-webkit-scrollbar {
      width: 10px;
    }

    ::-webkit-scrollbar-track {
      background: #0d1117; /* خلفية داكنة */
    }

    ::-webkit-scrollbar-thumb {
      background: #00f2ff; /* لون ساطع */
      border-radius: 10px;
      box-shadow: 0 0 10px #00f2ff; /* تأثير مضيء */
      transition: background 0.3s ease;
    }

    ::-webkit-scrollbar-thumb:hover {
      background: #00f2ffcc; /* عند التمرير يصبح أكثر سطوعًا */
    }
  </style>
</head>
<body>

<div id="particles-js"></div>

<header><?= htmlspecialchars($article['title']) ?></header>

<div class="container">
  <img src="<?= htmlspecialchars($article['image']) ?>" alt="<?= htmlspecialchars($article['title']) ?>" class="article-image" />

  <p class="article-meta">
    الكاتب: <?= htmlspecialchars($article['author']) ?> | 
    التصنيف: <?= htmlspecialchars($article['category']) ?> | 
    الكلمات المفتاحية: <?= htmlspecialchars($article['tags']) ?> | 
    المشاهدات: <?= (int)$article['views'] ?> | 
    تاريخ النشر: <?= date('Y-m-d', strtotime($article['created_at'])) ?> | 
    آخر تعديل: <?= date('Y-m-d', strtotime($article['updated_at'])) ?>
  </p>

  <div class="article-content"><?= nl2br(htmlspecialchars($article['content'])) ?></div>

  <a href="articles.php" class="back-link">عودة إلى صفحة المقالات</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
<script>
  particlesJS("particles-js", {
    "particles": {
      "number": { "value": 50, "density": { "enable": true, "value_area": 700 }},
      "color": { "value": "#00ffe7" },
      "shape": { "type": "circle" },
      "opacity": { "value": 0.5, "random": true },
      "size": { "value": 4, "random": true },
      "line_linked": {
        "enable": true,
        "distance": 140,
        "color": "#00ffe7",
        "opacity": 0.35,
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
