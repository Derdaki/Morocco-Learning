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
  <title>الأسئلة الشائعة</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" href="ChatGPT Image 4 juin 2025, 14_53_49.png">

  <style>
    * {
      box-sizing: border-box;
    }

    body {
      background-color: #001f2e;
      font-family: 'Cairo', sans-serif;
      color: #e0f7fa;
      margin: 0;
      padding: 0;
      position: relative;
      overflow-x: hidden;
    }

    #particles-js {
      position: fixed;
      width: 100%;
      height: 100%;
      z-index: -1;
    }

    .container {
      max-width: 1000px;
      margin: 50px auto;
      background: rgba(0, 0, 0, 0.6);
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 0 15px #00ffe7;
    }

    h1 {
      color: #00ffe7;
      margin-bottom: 30px;
      text-align: center;
    }

    .faq-item {
      border-bottom: 1px solid #00ffe7;
      padding: 15px 0;
    }

    .faq-question {
      font-weight: bold;
      font-size: 18px;
      cursor: pointer;
      position: relative;
      padding-right: 25px;
    }

    .faq-question::before {
      content: '➕';
      position: absolute;
      right: 0;
      top: 0;
      color: #00ffe7;
      font-size: 18px;
    }

    .faq-question.active::before {
      content: '➖';
    }

    .faq-answer {
      display: none;
      padding-top: 10px;
      color: #a0dede;
      font-size: 16px;
      line-height: 1.6;
    }

    .faq-answer.show {
      display: block;
    }

    .back-btn {
      display: block;
      margin: 30px auto 0;
      background-color: #2196f3;
      color: white;
      border: none;
      padding: 12px 24px;
      border-radius: 6px;
      cursor: pointer;
      font-weight: bold;
    }

    .back-btn:hover {
      background-color: #1976d2;
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
    <h1>الأسئلة الشائعة</h1>

    <div class="faq-item">
      <div class="faq-question">ما هو MOROCCO LEARNING؟</div>
      <div class="faq-answer">منصة تعليمية لتطوير المهارات في البرمجة، الذكاء الاصطناعي، والمهارات الشخصية.</div>
    </div>

    <div class="faq-item">
      <div class="faq-question">هل أحصل على شهادة؟</div>
      <div class="faq-answer">نعم، بعد إكمال الدورة يمكنك تحميل شهادة إلكترونية معتمدة.</div>
    </div>

    <div class="faq-item">
      <div class="faq-question">هل الدورات مجانية؟</div>
      <div class="faq-answer">بعضها مجاني وبعضها يتطلب اشتراك رمزي لدعم المحتوى.</div>
    </div>

    <div class="faq-item">
      <div class="faq-question">هل يمكن تعديل بياناتي؟</div>
      <div class="faq-answer">نعم، من صفحة "ملفي الشخصي" عبر زر تعديل الملف.</div>
    </div>

    <div class="faq-item">
      <div class="faq-question">هل يمكن حذف الحساب؟</div>
      <div class="faq-answer">نعم، هناك زر مخصص لذلك في صفحة الملف الشخصي.</div>
    </div>
    <button class="back-btn" onclick="window.location.href='learning.php'"> العودة إلى الصفحة الرئيسية</button>
  </div>

  <!-- Script: Accordion -->
  <script>
    document.querySelectorAll('.faq-question').forEach(item => {
      item.addEventListener('click', () => {
        item.classList.toggle('active');
        const answer = item.nextElementSibling;
        answer.classList.toggle('show');
      });
    });
  </script>

  <!-- Script: Particles.js -->
  <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
  <script>
    particlesJS("particles-js", {
      particles: {
        number: { value: 80, density: { enable: true, value_area: 800 }},
        color: { value: "#00ffe7" },
        shape: { type: "circle" },
        opacity: { value: 0.5 },
        size: { value: 3 },
        line_linked: {
          enable: true,
          distance: 150,
          color: "#00ffe7",
          opacity: 0.4,
          width: 1
        },
        move: {
          enable: true,
          speed: 3,
          direction: "none",
          out_mode: "out"
        }
      },
      interactivity: {
        detect_on: "canvas",
        events: {
          onhover: { enable: true, mode: "repulse" },
          onclick: { enable: true, mode: "push" }
        },
        modes: {
          repulse: { distance: 100 },
          push: { particles_nb: 4 }
        }
      },
      retina_detect: true
    });
  </script>
</body>
</html>
