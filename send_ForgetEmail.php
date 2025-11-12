<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'src/PHPMailer.php';
require 'src/SMTP.php';
require 'src/Exception.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = strip_tags(trim($_POST["fullname"]));
    $phone = strip_tags(trim($_POST["phone"]));
    $CNE = strip_tags(trim($_POST["CNE"]));
    $description = strip_tags(trim($_POST["description"]));

    $mail = new PHPMailer(true);
    try {
        // إعدادات SMTP لجيميل
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'moroccolearningnational@gmail.com'; // إيميل جيميل ديالك
        $mail->Password = 'xkww kauk mslp isoi'; // App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // المرسل والمستقبل
        $mail->setFrom('moroccolearningnational@gmail.com', 'نظام استرجاع الحساب');
        $mail->addAddress('moroccolearningnational@gmail.com');

        // محتوى الإيميل
        $mail->isHTML(false);
        $mail->Subject = "طلب استرجاع الحساب من: $fullname";
        $mail->Body = "الاسم الكامل: $fullname\nرقم الهاتف: $phone\nرقم البطاقة الوطنية: $CNE\nمعلومات إضافية:\n$description";

        $mail->send();
        echo json_encode(["status" => "success", "message" => "📩 تم إرسال طلبك بنجاح! سيتم التواصل معك قريباً."]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "حدث خطأ أثناء إرسال الطلب. حاول مرة أخرى.", "error" => $mail->ErrorInfo]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>استرجاع الحساب</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet" />
  <link rel="icon" type="image/png" href="ChatGPT Image 4 juin 2025, 14_53_49.png" />
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
      padding: 50px 20px 20px;
    }
    header h1 {
      color: #ff9800;
      font-size: 2.5rem;
      text-shadow: 0 0 10px #ffc107;
    }
    .container {
      max-width: 600px;
      margin: 30px auto;
      background: rgba(255, 255, 255, 0.05);
      border: 2px solid #ffc107;
      border-radius: 20px;
      padding: 30px;
      backdrop-filter: blur(10px);
      box-shadow: 0 0 25px #ffc107;
    }
    label {
      display: block;
      margin-top: 15px;
      color: #ffeb3b;
      font-weight: bold;
    }
    input, textarea {
      width: 100%;
      padding: 10px;
      margin-top: 5px;
      border-radius: 8px;
      border: none;
      background: #ffffff10;
      color: #fff;
      font-size: 1rem;
    }
    input::placeholder, textarea::placeholder {
      color: #aaa;
    }
    button {
      margin-top: 20px;
      padding: 12px 20px;
      background-color: #ffc107;
      border: none;
      color: #0b0c2a;
      font-size: 1rem;
      border-radius: 8px;
      cursor: pointer;
      font-weight: bold;
      transition: background-color 0.3s;
    }
    button:hover {
      background-color: #ffb300;
    }
    .back-button {
      display: block;
      text-align: center;
      margin: 30px auto 0;
    }
    .back-button a {
      text-decoration: none;
      background-color: #ffc107;
      color: #0b0c2a;
      padding: 10px 20px;
      border-radius: 10px;
      font-weight: bold;
      transition: background-color 0.3s;
    }
    .back-button a:hover {
      background-color: #ffb300;
    }
    footer {
      text-align: center;
      padding: 20px;
      font-size: 0.9rem;
      color: #ccc;
    }

    /* رسالة الإرسال */
    #messageBox {
      display: none;
      margin-top: 20px;
      padding: 15px;
      background-color: #4caf50;
      color: white;
      border-radius: 8px;
      font-weight: bold;
      text-align: center;
      opacity: 0;
      transition: opacity 0.5s;
    }
  </style>
</head>
<body>
  <div id="particles-js"></div>

  <header>
    <h1>🔒 استرجاع الحساب بدون بريد إلكتروني</h1>
  </header>

  <div class="container">
    <form id="recoveryForm" action="<?= $_SERVER['PHP_SELF'] ?>" method="post">
      <label for="fullname">الاسم الكامل</label>
      <input type="text" id="fullname" name="fullname" placeholder="أدخل اسمك الكامل" required />

      <label for="phone">رقم الهاتف</label>
      <input type="tel" id="phone" name="phone" placeholder="05XXXXXXXX" required />

      <label for="CNE">رقم البطاقة الوطنية</label>
      <input type="text" id="CNE" name="CNE" placeholder="رقم البطاقة الوطنية" required />

      <label for="description">معلومات إضافية تساعدنا في التحقق</label>
      <textarea id="description" name="description" rows="5" placeholder="اكتب أي معلومات تتذكرها عن الحساب، مثل تاريخ الإنشاء، أو آخر نشاط..." required></textarea>

      <button type="submit">🔐 إرسال طلب الاسترجاع</button>
    </form>

    <!-- رسالة الإرسال تظهر هنا -->
    <div id="messageBox"></div>
  </div>

  <div class="back-button">
    <a href="login.php">العودة إلى صفحة التسجيل الدخول</a>
  </div>

  <footer>
    &copy; 2025 جميع الحقوق محفوظة - استرجاع الحساب
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
  <script>
    particlesJS("particles-js", {
      "particles": {
        "number": { "value": 60 },
        "color": { "value": "#ffc107" },
        "shape": { "type": "circle" },
        "opacity": { "value": 0.4 },
        "size": { "value": 3 },
        "line_linked": {
          "enable": true,
          "distance": 120,
          "color": "#ffc107",
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

    document.getElementById("recoveryForm").addEventListener("submit", function(e) {
      e.preventDefault();

      const form = this;
      const formData = new FormData(form);
      const messageBox = document.getElementById("messageBox");

      fetch(form.action, {
        method: "POST",
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        messageBox.textContent = data.message;
        messageBox.style.backgroundColor = (data.status === "success") ? "#4caf50" : "#f44336";
        messageBox.style.display = "block";
        messageBox.style.opacity = "1";

        if (data.status === "success") {
          form.reset();
        }
      })
      .catch(() => {
        messageBox.textContent = "حدث خطأ أثناء إرسال الطلب. حاول مرة أخرى.";
        messageBox.style.backgroundColor = "#f44336";
        messageBox.style.display = "block";
        messageBox.style.opacity = "1";
      });
    });
  </script>
</body>
</html>
