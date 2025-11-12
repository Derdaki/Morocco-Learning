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
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>صفحة الاستقبال - تسجيل الدخول</title>
<link rel="icon" type="image/png" href="ChatGPT Image 4 juin 2025, 14_53_49.png">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
<style>
  * {
    margin: 0; padding: 0; box-sizing: border-box;
  }
  body, html {
    height: 100%;
    font-family: 'Cairo', sans-serif;
    background: #1e1e2f;
    overflow: hidden;
  }
  #particles-js {
    position: fixed;
    width: 100%; height: 100%;
    top: 0; left: 0;
    z-index: 0;
    pointer-events: none;
  }
  .container {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 30px;
    height: 100vh;
    padding: 20px;
    z-index: 1;
    position: relative;
    flex-wrap: wrap;
  }
  .card {
    background: rgba(255 255 255 / 0.05);
    border-radius: 20px;
    width: 330px;
    min-height: 240px;
    border-left: 6px solid transparent;
    backdrop-filter: blur(10px);
    box-shadow: 0 8px 20px rgb(0 0 0 / 0.3);
    color: #fff;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    padding: 25px 25px 15px;
    transition: all 0.35s ease;
    position: relative;
    user-select: none;
  }
  .card.academy { border-left-color: #506eff; }
  .card.teachers { border-left-color: #00adb5; }
  .card.directors { border-left-color: #ff5757; }

  .card:hover {
    background: rgba(255 255 255 / 0.12);
    box-shadow: 0 12px 30px rgb(0 0 0 / 0.5);
  }

  .icon {
    font-size: 38px;
    color: #00ffe7;
    margin-bottom: 15px;
    text-align: center;
  }
  .card-title {
    font-weight: 700;
    font-size: 1.1rem;
    text-align: center;
    line-height: 1.4;
    margin-bottom: 15px;
  }

  /* النموذج مخفي مبدئياً */
  .login-form {
    max-height: 0;
    opacity: 0;
    overflow: hidden;
    transition: max-height 0.5s ease, opacity 0.3s ease;
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  /* النموذج ظاهر عند الفتح */
  .card.active {
    min-height: 380px;
    cursor: default;
  }
  .card.active .login-form {
    max-height: 350px;
    opacity: 1;
    margin-top: 15px;
  }

  .login-form input {
    padding: 12px 15px;
    border-radius: 8px;
    border: none;
    outline: none;
    font-size: 14px;
    transition: background-color 0.25s;
  }
  .login-form input:focus {
    background-color: rgba(255 255 255 / 0.15);
  }
  .login-form button {
    background: #00adb5;
    border: none;
    padding: 12px 0;
    border-radius: 10px;
    font-weight: 700;
    color: #fff;
    cursor: pointer;
    font-size: 15px;
    transition: background 0.3s ease;
  }
  .login-form button:hover {
    background: #009fa6;
  }

  /* زر العودة */
  .back-btn {
    margin-top: 12px;
    text-align: center;
    color: #ccc;
    font-size: 14px;
    cursor: pointer;
    user-select: none;
    transition: color 0.3s;
  }
  .back-btn:hover {
    color: #fff;
  }
</style>
</head>
<body>

<div id="particles-js"></div>

<div class="container">
  <div class="card academy" tabindex="0">
    <div class="icon"><i class="fas fa-university"></i></div>
    <div class="card-title">تسجيل الدخول للمؤسسات الأكاديمية الجهوية للتربية والتكوين</div>

    <form class="login-form" method="POST" action="academy_login.php" onsubmit="return validateForm(this)">
      <input type="text" name="username" placeholder="اسم المستخدم" required />
      <input type="password" name="password" placeholder="كلمة المرور" required />
      <button type="submit">دخول</button>
      <div class="back-btn" onclick="closeForms(event)">عودة</div>
    </form>
  </div>

  <div class="card teachers" tabindex="0">
    <div class="icon"><i class="fas fa-chalkboard-teacher"></i></div>
    <div class="card-title">تسجيل الدخول للأساتذة / المدراء للمؤسسات التعليمية</div>

    <form class="login-form" method="POST" action="teachers_login.php" onsubmit="return validateForm(this)">
      <input type="email" name="email" placeholder="البريد الإلكتروني" required />
      <input type="password" name="password" placeholder="كلمة المرور" required />
      <button type="submit">دخول</button>
      <div class="back-btn" onclick="closeForms(event)">عودة</div>
    </form>
  </div>

  <div class="card directors" tabindex="0">
    <div class="icon"><i class="fas fa-user-tie"></i></div>
    <div class="card-title">تسجيل الدخول لمدراء المؤسسات التعليمية</div>

    <form class="login-form" method="POST" action="directors_login.php" onsubmit="return validateForm(this)">
      <input type="text" name="code" placeholder="رمز المؤسسة" required />
      <input type="password" name="password" placeholder="كلمة المرور" required />
      <button type="submit">دخول</button>
      <div class="back-btn" onclick="closeForms(event)">عودة</div>
    </form>
  </div>
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

  // فتح الاستمارة عند الضغط على البطاقة
  document.querySelectorAll('.card').forEach(card => {
    card.addEventListener('click', e => {
      if (!card.classList.contains('active')) {
        closeForms();
        card.classList.add('active');
      }
    });
  });

  // إغلاق كل الاستمارات
  function closeForms(event) {
    if(event) event.stopPropagation();
    document.querySelectorAll('.card.active').forEach(card => card.classList.remove('active'));
  }

  // منع إغلاق الاستمارة عند الضغط داخلها
  document.querySelectorAll('.login-form').forEach(form => {
    form.addEventListener('click', e => {
      e.stopPropagation();
    });
  });

  // إضافة دعم للوحة المفاتيح (Enter و Escape)
  document.addEventListener('keydown', e => {
    if (e.key === "Escape") {
      closeForms();
    }
  });

  // (اختياري) تحقق بسيط قبل الإرسال
  function validateForm(form) {
    // يمكن توسعته حسب الحاجة
    return true;
  }
</script>

</body>
</html>
