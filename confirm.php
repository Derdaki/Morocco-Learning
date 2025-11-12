<?php
session_start();

// نحاول أولاً من الجلسة ثم من الرابط
$email = $_SESSION['email_to_verify'] ?? $_GET['email'] ?? '';

if (!$email) {
    echo "<p style='color:red; font-weight:bold;'>❌ البريد الإلكتروني غير معروف. يرجى التسجيل أولاً.</p>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="ChatGPT Image 4 juin 2025, 14_53_49.png">
    
    <title>تأكيد الحساب</title>
    <style>
        body, html {
            height: 100%;
            margin: 0;
            font-family: 'Tajawal', sans-serif;
            background: #0f2027;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            background: rgba(0, 0, 0, 0.85);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 0 20px #00fff7;
            text-align: center;
            position: relative;
            z-index: 2;
        }

        input[type="text"], button {
            padding: 12px 20px;
            font-size: 18px;
            border-radius: 10px;
            border: none;
            margin-top: 10px;
            width: 100%;
            max-width: 300px;
        }

        input[type="text"] {
            text-align: center;
            background-color: #f0f0f0;
            color: #000;
        }

        button {
            background: linear-gradient(to right, #00fff7, #00cfcf);
            color: #000;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        button:hover {
            transform: scale(1.05);
        }

        a.resend-link {
            display: inline-block;
            margin-top: 20px;
            background: transparent;
            border: 2px solid #00fff7;
            color: #00fff7;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            transition: 0.3s;
        }

        a.resend-link:hover {
            background: #00fff7;
            color: #000;
        }

        #particles-js {
            position: absolute;
            width: 100%;
            height: 100%;
            z-index: 1;
            top: 0;
            left: 0;
        }
    </style>
</head>
<body>
<div id="particles-js"></div>

<div class="container">
    <h2>تأكيد الحساب</h2>
    <p>أدخل رمز التحقق المرسل إلى بريدك الإلكتروني:</p>

    <form method="POST" action="verify.php">
        <input type="text" name="verification_code" maxlength="6" placeholder="أدخل الرمز هنا" required pattern="\d{6}">
        <input type="hidden" name="email" value="<?php echo htmlspecialchars($_SESSION['email_to_verify'] ?? $_GET['email'] ?? ''); ?>">
        <button type="submit">تأكيد الحساب</button>
    </form>

    <a href="resend.php" class="resend-link">لم يصلك الرمز؟ أعد الإرسال</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
<script>
particlesJS("particles-js", {
  "particles": {
    "number": { "value": 100 },
    "color": { "value": "#00fff7" },
    "shape": { "type": "circle" },
    "opacity": { "value": 0.5 },
    "size": { "value": 3 },
    "move": { "enable": true, "speed": 2 }
  },
  "interactivity": {
    "events": {
      "onhover": { "enable": true, "mode": "repulse" }
    }
  }
});
</script>
</body>
</html>
