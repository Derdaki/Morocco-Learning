
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>خطأ في تسجيل الدخول</title>
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
      box-shadow: 0 0 20px #ff5e57;
      z-index: 1;
      position: relative;
    }

    h1 {
      color: #ff5e57;
      font-size: 28px;
      margin-bottom: 20px;
    }

    p {
      font-size: 18px;
      margin-bottom: 20px;
    }

    .actions a {
      display: block;
      color: #00ffe7;
      text-decoration: none;
      font-weight: bold;
      margin: 10px 0;
      transition: color 0.3s;
    }

    .actions a:hover {
      color: #ffffff;
    }
  </style>
</head>
<body>
  <!-- خلفية Particles -->
  <div id="particles-js"></div>

  <!-- محتوى الرسالة -->
  <div class="container">
    <h1>❌ خطأ في تسجيل الدخول</h1>
    <p>تأكد من صحة البريد الإلكتروني وكلمة المرور.</p>
    <div class="actions">
      <a href="login.php">🔁 حاول مرة أخرى</a>
      <a href="forgetpassword.php">🔑 نسيت كلمة المرور؟</a>
      <a href="send_ForgetEmail.php">📧 نسيت البريد الإلكتروني؟ اتصل بنا</a>
    </div>
  </div>

  <!-- particles.js -->
  <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
  <script>
    particlesJS("particles-js", {
      "particles": {
        "number": {
          "value": 50,
          "density": { "enable": true, "value_area": 800 }
        },
        "color": { "value": "#00ffe7" },
        "shape": { "type": "circle" },
        "opacity": { "value": 0.4 },
        "size": { "value": 3, "random": true },
        "line_linked": {
          "enable": true,
          "distance": 150,
          "color": "#00ffe7",
          "opacity": 0.3,
          "width": 1
        },
        "move": {
          "enable": true,
          "speed": 2,
          "out_mode": "bounce"
        }
      },
      "interactivity": {
        "events": {
          "onhover": { "enable": true, "mode": "repulse" },
          "onclick": { "enable": true, "mode": "push" }
        },
        "modes": {
          "repulse": { "distance": 100 },
          "push": { "particles_nb": 4 }
        }
      },
      "retina_detect": true
    });
  </script>
</body>
</html>
