<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>بوابة التعليم الذكية</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" href="ChatGPT Image 4 juin 2025, 14_53_49.png">

  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    body {
      font-family: 'Cairo', sans-serif;
      background-color: #0b0c2a;
      color: #fff;
      overflow-x: hidden;
    }
    #particles-js {
      position: fixed;
      width: 100%;
      height: 100%;
      z-index: -1;
    }
    header {
      text-align: center;
      padding: 40px 20px 10px;
    }
    header h1 {
      color: #00ffff;
      font-size: 2.5rem;
      margin-bottom: 10px;
      text-shadow: 0 0 10px #00f2ff;
    }
    .grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 30px;
      padding: 40px;
      max-width: 1200px;
      margin: auto;
    }
    .card {
      background: rgba(255, 255, 255, 0.05);
      border: 2px solid #00ffe7;
      border-radius: 20px;
      padding: 30px;
      text-align: center;
      backdrop-filter: blur(10px);
      transition: transform 0.4s ease, box-shadow 0.3s;
      cursor: pointer;
      position: relative;
    }
    .card:hover {
      transform: translateY(-10px) scale(1.03);
      box-shadow: 0 0 25px #00ffe7;
    }
    .card h2 {
      font-size: 1.4rem;
      color: #00ffe7;
      margin-bottom: 10px;
    }
    .card p {
      font-size: 0.95rem;
      color: #e0f7fa;
    }
    .card::before {
      content: "";
      position: absolute;
      top: -5px;
      right: -5px;
      bottom: -5px;
      left: -5px;
      border-radius: 25px;
      z-index: -1;
      background: linear-gradient(45deg, #00ffe7, #ff00e6);
      opacity: 0.1;
      transition: opacity 0.3s;
    }
    .card:hover::before {
      opacity: 0.25;
    }
    .back-button {
      display: block;
      margin: 30px auto;
      padding: 12px 25px;
      font-size: 1rem;
      color: #0b0c2a;
      background-color: #00ffe7;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      font-weight: bold;
      transition: background-color 0.3s;
    }
    .back-button:hover {
      background-color: #00cccc;
    }
    footer {
      text-align: center;
      padding: 20px;
      font-size: 0.9rem;
      color: #ccc;
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
  <header>
    <h1>🎓 بوابة التعليم الذكية</h1>
    <p style="color:#ccc">اختر أحد المسارات التعليمية الشيقة وابدأ رحلتك المعرفية</p>
  </header>
  <section class="grid">
    <div class="card" onclick="alert('🧠 ألعاب العقل')">
      <h2>🧠 ألعاب العقل</h2>
      <p>اختبر سرعة البديهة والمنطق بطرق ممتعة وتفاعلية</p>
    </div>
    <div class="card" onclick="alert('🌍 ثقافة عامة')">
      <h2>🌍 ثقافة عامة</h2>
      <p>تعرف على معلومات مفيدة من مختلف المجالات حول العالم</p>
    </div>
    <div class="card" onclick="alert('💡 اختبار ذكاء')">
      <h2>💡 اختبار ذكاء</h2>
      <p>قم بتحدي نفسك في مجموعة من أسئلة الذكاء المميزة</p>
    </div>
    <div class="card" onclick="alert('🔬 حقائق علمية')">
      <h2>🔬 حقائق علمية</h2>
      <p>اكتشف المدهش والمثير في عالم العلوم</p>
    </div>
    <div class="card" onclick="alert('🏳️ لعبة الأعلام')">
      <h2>🏳️ لعبة الأعلام</h2>
      <p>هل يمكنك التعرف على أعلام الدول؟</p>
    </div>
    <div class="card" onclick="alert('📘 معلومات مهمة')">
      <h2>📘 معلومات مهمة</h2>
      <p>نصائح ومعلومات حياتية يجب أن يعرفها كل متعلم</p>
    </div>
  </section>

  <button class="back-button" onclick="window.location.href='learning.php'">العودة إلى الصفحة الرئيسية</button>

  <footer>
    &copy; 2025 جميع الحقوق محفوظة - صفحة تعليمية مبتكرة
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
  <script>
    particlesJS("particles-js", {
      "particles": {
        "number": { "value": 70 },
        "color": { "value": "#00ffe7" },
        "shape": { "type": "circle" },
        "opacity": { "value": 0.4 },
        "size": { "value": 3 },
        "line_linked": {
          "enable": true,
          "distance": 120,
          "color": "#00ffe7",
          "opacity": 0.4,
          "width": 1
        },
        "move": { "enable": true, "speed": 2 }
      },
      "interactivity": {
        "events": {
          "onhover": { "enable": true, "mode": "repulse" },
          "onclick": { "enable": true, "mode": "push" }
        }
      },
      "retina_detect": true
    });
  </script>
</body>
</html>
