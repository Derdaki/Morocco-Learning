<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== "admin") {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>تم تسجيل دخول المسؤول</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" href="ChatGPT Image 4 juin 2025, 14_53_49.png">

  <style>
    body {
      background-color: #001f2e;
      font-family: 'Cairo', sans-serif;
      color: #e0f7fa;
      margin: 0;
      height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      position: relative;
    }

    #particles-js {
      position: absolute;
      width: 100%;
      height: 100%;
      z-index: 0;
    }

    .container {
      background: rgba(0, 0, 0, 0.6);
      padding: 40px;
      border-radius: 12px;
      text-align: center;
      box-shadow: 0 0 20px #00ffe7;
      z-index: 1;
      position: relative;
    }

    h1 {
      color: #00ffe7;
      font-size: 28px;
      margin-bottom: 20px;
    }

    .countdown {
      font-size: 24px;
      font-weight: bold;
      color: #ffffff;
    }
  </style>
</head>
<body>
  <!-- خلفية particles -->
  <div id="particles-js"></div>

  <!-- محتوى الرسالة -->
  <div class="container">
    <h1>✅ تم تسجيل دخول المسؤول بنجاح</h1>
    <div class="countdown">
      سيتم توجيهك إلى لوحة التحكم خلال <span id="count">5</span> ثوانٍ...
    </div>
  </div>

  <!-- مؤقت التحويل -->
  <script>
    let count = 5;
    const countSpan = document.getElementById("count");

    const interval = setInterval(() => {
      count--;
      countSpan.textContent = count;
      if (count === 0) {
        clearInterval(interval);
        window.location.href = "institutions_list.php";
      }
    }, 1000);
  </script>

  <!-- إضافة مكتبة particles.js -->
  <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>

  <!-- إعداد particles -->
  <script>
    particlesJS("particles-js", {
      "particles": {
        "number": {
          "value": 60,
          "density": {
            "enable": true,
            "value_area": 800
          }
        },
        "color": { "value": "#00ffe7" },
        "shape": {
          "type": "circle",
          "stroke": { "width": 0, "color": "#000000" }
        },
        "opacity": {
          "value": 0.5,
          "random": true
        },
        "size": {
          "value": 3,
          "random": true
        },
        "line_linked": {
          "enable": true,
          "distance": 150,
          "color": "#00ffe7",
          "opacity": 0.4,
          "width": 1
        },
        "move": {
          "enable": true,
          "speed": 2,
          "direction": "none",
          "out_mode": "bounce"
        }
      },
      "interactivity": {
        "detect_on": "canvas",
        "events": {
          "onhover": { "enable": true, "mode": "grab" },
          "onclick": { "enable": true, "mode": "push" },
          "resize": true
        },
        "modes": {
          "grab": {
            "distance": 200,
            "line_linked": { "opacity": 0.5 }
          },
          "push": {
            "particles_nb": 4
          }
        }
      },
      "retina_detect": true
    });
  </script>
</body>
</html>