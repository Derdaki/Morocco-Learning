<?php
// articles.php
// عرض قائمة المقالات

$host = 'localhost';
$port = 3307;
$dbname = 'moroccolearning';
$username = 'root';
$password = '';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8";
    $db = new PDO($dsn, $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // جلب المقالات المنشورة فقط (status = 'published')
    $stmt = $db->prepare("SELECT id, title, summary, image, author, category, views, created_at FROM articles WHERE status = 'published' ORDER BY created_at DESC");
    $stmt->execute();
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("❌ خطأ في الاتصال بقاعدة البيانات: " . htmlspecialchars($e->getMessage()));
}

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <title>المقالات</title>
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
      font-size: 32px;
      font-weight: 700;
      color: #00ffe7;
      text-shadow: 0 0 8px #00ffe7;
      z-index: 1;
      position: relative;
    }
    .container {
      position: relative;
      max-width: 1100px;
      margin: 0 auto 40px auto;
      padding: 20px;
      z-index: 1;
    }
    .article-card {
      background: rgba(0, 0, 0, 0.7);
      border-radius: 15px;
      box-shadow: 0 0 20px #00ffe7;
      display: flex;
      gap: 20px;
      margin-bottom: 25px;
      overflow: hidden;
      transition: box-shadow 0.3s ease;
    }
    .article-card:hover {
      box-shadow: 0 0 30px #00fff0;
    }
    .article-image {
      width: 250px;
      flex-shrink: 0;
    }
    .article-image img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-radius: 15px 0 0 15px;
    }
    .article-content {
      padding: 15px 20px;
      flex-grow: 1;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    .article-title {
      font-size: 24px;
      font-weight: 700;
      margin-bottom: 10px;
      color: #00ffe7;
      text-shadow: 0 0 6px #00ffe7;
    }
    .article-summary {
      font-size: 16px;
      color: #b0e0e6;
      flex-grow: 1;
      margin-bottom: 15px;
    }
    .article-meta {
      font-size: 14px;
      color: #66d9ff;
      margin-bottom: 10px;
    }
    .read-more {
      align-self: flex-start;
      padding: 10px 25px;
      background-color: #00ffe7;
      color: #001f2e;
      font-weight: bold;
      border-radius: 8px;
      text-decoration: none;
      transition: background-color 0.3s ease;
    }
    .read-more:hover {
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

<header>مكتبة المقالات والكتب
<p>العالم كتاب من لا يسافر فيه لا يرى سوى صفحة واحدة</p></header>
<div style="margin-left:30px ;">
  <div style="max-width:1100px; margin: 20px auto; direction: rtl; z-index:1; position: relative;">
  <div style="display: flex; justify-content: flex-end; gap: 10px; flex-wrap: wrap;">
    
    <!-- زر كن كاتبا -->
    <a href="submit_article.php" style="padding:10px 20px; background:#00ffe7; color:#001f2e; font-weight:bold; border-radius:8px; text-decoration:none; box-shadow:0 0 10px #00ffe7;" onmouseover="this.style.backgroundColor='#00cfc2';" onmouseout="this.style.backgroundColor='#00ffe7';">
    كن كاتبًا وأرسل لنا
    </a>

    <!-- أفضل كتاب الشهر -->
    <a href="top_book_month.php" style="padding:10px 20px; background:#7e57c2; color:#fff; font-weight:bold; border-radius:8px; text-decoration:none; box-shadow:0 0 10px #7e57c2;" onmouseover="this.style.backgroundColor='#5e35b1';" onmouseout="this.style.backgroundColor='#7e57c2';">
      أفضل كتاب لهذا الأسبوع
    </a>

    <!-- أفضل مقولة الشهر -->
    <a href="top_quote_month.php" style="padding:10px 20px; background:#ff9800; color:#fff; font-weight:bold; border-radius:8px; text-decoration:none; box-shadow:0 0 10px #ff9800;" onmouseover="this.style.backgroundColor='#f57c00';" onmouseout="this.style.backgroundColor='#ff9800';">
      أفضل مقولة لهذا الأسبوع
    </a>

    <!-- الترتيب الوطني للكتاب -->
    <a href="writer_ranking.php" style="padding:10px 20px; background:#ffdd57; color:#1a1a1a; font-weight:bold; border-radius:8px; text-decoration:none; box-shadow:0 0 10px #ffdd57;" onmouseover="this.style.backgroundColor='#e6c643';" onmouseout="this.style.backgroundColor='#ffdd57';">
      الترتيب الوطني للكتاب
    </a>

    <!-- زر العودة -->
    <a href="learning.php" style="padding:10px 20px; background:#ff5757; color:#fff; font-weight:bold; border-radius:8px; text-decoration:none; box-shadow:0 0 10px #ff5757;" onmouseover="this.style.backgroundColor='#e04a4a';" onmouseout="this.style.backgroundColor='#ff5757';">
     العودة إلى الصفحة الرئيسية
    </a>

  </div>
</div> 
</div>
<div class="container">
<?php if (empty($articles)): ?>
  <p style="text-align:center; font-size: 20px; color: #ff5555;">لا توجد مقالات منشورة حالياً.</p>
<?php else: ?>
  <?php foreach($articles as $article): ?>
    <div class="article-card">
      <div class="article-image">
        <img src="<?= htmlspecialchars($article['image']) ?>" alt="<?= htmlspecialchars($article['title']) ?>" />
      </div>
      <div class="article-content">
        <div>
          <h2 class="article-title"><?= htmlspecialchars($article['title']) ?></h2>
          <p class="article-summary"><?= htmlspecialchars($article['summary']) ?></p>
          <p class="article-meta">
            الكاتب: <?= htmlspecialchars($article['author']) ?> |
            التصنيف: <?= htmlspecialchars($article['category']) ?> |
            المشاهدات: <?= (int)$article['views'] ?> |
            تاريخ النشر: <?= date('Y-m-d', strtotime($article['created_at'])) ?>
          </p>
        </div>
        <a class="read-more" href="article.php?id=<?= (int)$article['id'] ?>">اقرأ الآن</a>
      </div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>
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
