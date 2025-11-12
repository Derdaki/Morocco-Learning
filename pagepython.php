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
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>تفاصيل دورة Python | MOROCCO LEARNING</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet"/>
  <link rel="icon" type="image/png" href="ChatGPT Image 4 juin 2025, 14_53_49.png">
  <style>
    body {
      margin: 0;
      font-family: 'Cairo', sans-serif;
      background-color: #001f2e;
      color: #e0f7fa;
      overflow-x: hidden;
    }

    #particles-js {
      position: fixed;
      width: 100%;
      height: 100%;
      z-index: -1;
    }

    .container {
      max-width: 1200px;
      margin: auto;
      padding: 60px 20px;
      animation: fadeIn 2s ease;
    }

    h1, h2, h3 {
      color: #00ffe7;
      text-align: center;
    }

    p, li {
      font-size: 18px;
      line-height: 1.8;
      color: #d0faff;
    }

    section {
      margin: 60px 0;
      padding: 30px;
      background: rgba(255, 255, 255, 0.05);
      border-radius: 12px;
      box-shadow: 0 0 20px rgba(0, 255, 231, 0.1);
      transition: transform 0.3s ease;
    }

    section:hover {
      transform: scale(1.02);
    }

    .section1 {
      margin-bottom: 10px;
      background: rgba(255,255,255,0.05);
      padding: 30px;
      border-radius: 15px;
      border: 1px solid #00ffe7;
      box-shadow: 0 0 10px #00ffe777;
      text-align: center;
    }

    .instructor-img {
    border-radius: 50%;
    width: 120px;
    height: 120px;
    border: 3px solid #00ffe7;
    }

    .btn-group {
      display: flex;
      justify-content: center;
      gap: 20px;
      margin-top: 40px;
      flex-wrap: wrap;
    }

    .btn {
      background-color: #00ffe7;
      color: #001f2e;
      padding: 15px 30px;
      border-radius: 8px;
      font-size: 16px;
      font-weight: bold;
      text-decoration: none;
      transition: background 0.3s;
    }

    .btn:hover {
      background-color: #00cbbf;
    }

    .features-list, .skills-list {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 20px;
      margin-top: 20px;
    }

    .features-list div, .skills-list div {
      background: rgba(255, 255, 255, 0.07);
      padding: 15px;
      border-radius: 8px;
      transition: transform 0.3s ease;
    }

    .features-list div:hover, .skills-list div:hover {
      transform: translateY(-5px);
    }

    footer {
      text-align: center;
      padding: 30px;
      color: #888;
      font-size: 14px;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .info-grid {
      display: flex;
      flex-wrap: wrap;
      justify-content: space-around;
      gap: 20px;
      margin-top: 20px;
    }

    .info-box {
      flex: 1 1 250px;
      background: rgba(255,255,255,0.07);
      padding: 20px;
      border-radius: 8px;
      text-align: center;
    }

    ol {
      padding-right: 20px;
    }

    table {
      width: 100%;
      margin-top: 20px;
      border-collapse: collapse;
    }

    table, th, td {
      border: 1px solid rgba(0,255,231,0.3);
    }

    th, td {
      padding: 12px;
      text-align: center;
    }

    th {
      background-color: rgba(0,255,231,0.1);
      color: #00ffe7;
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
  <div class="container">

    <h1>تفاصيل دورة Python</h1>

    <section>
      <h2>معلومات عامة</h2>
      <div class="info-grid">
        <div class="info-box"><strong>المستوى:</strong> مبتدئ إلى متوسط</div>
        <div class="info-box"><strong>المدة:</strong> 6 أسابيع</div>
        <div class="info-box"><strong>اللغة:</strong> العربية</div>
        <div class="info-box"><strong>الشهادة:</strong> نعم - رقمية</div>
      </div>
    </section>

    <section>
      <h2>نظرة عامة</h2>
      <p>
        تعتبر دورة Python من أفضل الدورات لتعلم البرمجة. تبدأ بأساسيات اللغة ثم تتطور لتغطي مفاهيم البرمجة الشيئية، التعامل مع البيانات، وتحليل البيانات باستخدام مكتبات شهيرة مثل Pandas وMatplotlib.
      </p>
    </section>

    <section>
      <h2>المتطلبات الأساسية</h2>
      <ul>
        <li>جهاز كمبيوتر محمول أو مكتبي</li>
        <li>اتصال بالإنترنت</li>
        <li>لا حاجة لأي خبرة برمجية سابقة</li>
      </ul>
    </section>
    <section>
    <div class="section1">
      <h2>عن المدرب</h2>
      <img src="1728717809202.jfif" alt="المدرب" class="instructor-img" />
      <p ><strong>الاسم:</strong> أ. إلياس درداكي</p>
      <p ><strong>العمر:</strong> 20 سنة</p>
      <p ><strong>الخبرة:</strong>  مبرمج محترف بخبرة تزيد عن 15 سنة في الذكاء الاصطناعي وتطوير الأنظمة البرمجية في أوروبا والعالم العربي .</p>
    </div>
    </section>
    <section>
      <h2>ماذا ستتعلم؟</h2>
      <div class="features-list">
        <div>المتغيرات وأنواع البيانات</div>
        <div>الجمل الشرطية والتكرار</div>
        <div>الدوال والمكتبات</div>
        <div>البرمجة الكائنية (OOP)</div>
        <div>بايثون لتحليل البيانات</div>
        <div>التعامل مع الملفات والاستثناءات</div>
        <div>مكتبات Pandas وNumPy</div>
        <div>رسم البيانات باستخدام Matplotlib</div>
      </div>
    </section>

    <section>
      <h2>محتوى الدورة التفصيلي</h2>
      <table>
        <tr>
          <th>الأسبوع</th>
          <th>الموضوع</th>
          <th>الوصف</th>
        </tr>
        <tr>
          <td>1</td>
          <td>مقدمة في Python</td>
          <td>التثبيت، تشغيل البرامج، كتابة أول كود</td>
        </tr>
        <tr>
          <td>2</td>
          <td>التحكم بالتدفق</td>
          <td>if, else, for, while</td>
        </tr>
        <tr>
          <td>3</td>
          <td>الدوال والمكتبات</td>
          <td>تعريف الدوال، استدعاؤها، استخدام المكتبات</td>
        </tr>
        <tr>
          <td>4</td>
          <td>البرمجة الكائنية</td>
          <td>الكلاسات والكائنات</td>
        </tr>
        <tr>
          <td>5</td>
          <td>تحليل البيانات</td>
          <td>مقدمة في Pandas وNumPy</td>
        </tr>
        <tr>
          <td>6</td>
          <td>المشروع النهائي</td>
          <td>بناء مشروع عملي وتقديمه</td>
        </tr>
      </table>
    </section>

    <section>
      <h2>المهارات المكتسبة</h2>
      <div class="skills-list">
        <div>البرمجة بلغة Python</div>
        <div>تحليل البيانات</div>
        <div>حل المشكلات البرمجية</div>
        <div>بناء مشاريع برمجية</div>
        <div>استعمال أدوات حقيقية في السوق</div>
      </div>
    </section>

    <section>
      <h2>الشهادة</h2>
      <p>
        بنهاية الدورة، ستحصل على شهادة رقمية يمكن إضافتها إلى سيرتك الذاتية أو حساب LinkedIn، وتثبت أنك تمتلك المهارات الأساسية في Python وتحليل البيانات.
      </p>
    </section>

    <div class="btn-group">
      <a href="#" class="btn">ابدأ الدورة الآن</a>
      <a href="asistant virtuel ALX.jpeg" class="btn">عرض شهادة نموذجية</a>
      <a href="learning.php" class="btn">رجوع إلى الرئيسية</a>
    </div>
  </div>

  <footer>
    © 2025 MOROCCO LEARNING - جميع الحقوق محفوظة
  </footer>

  <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
  <script>
    particlesJS("particles-js", {
      "particles": {
        "number": { "value": 80 },
        "color": { "value": "#00ffe7" },
        "shape": { "type": "circle" },
        "opacity": { "value": 0.5 },
        "size": { "value": 3 },
        "line_linked": {
          "enable": true,
          "distance": 150,
          "color": "#00ffe7",
          "opacity": 0.4,
          "width": 1
        },
        "move": {
          "enable": true,
          "speed": 2
        }
      },
      "interactivity": {
        "detect_on": "canvas",
        "events": {
          "onhover": { "enable": true, "mode": "grab" },
          "onclick": { "enable": true, "mode": "push" }
        },
        "modes": {
          "grab": { "distance": 200, "line_linked": { "opacity": 0.5 } },
          "push": { "particles_nb": 4 }
        }
      }
    });
  </script>
</body>
</html>
