<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'src/PHPMailer.php';
require 'src/SMTP.php';
require 'src/Exception.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header('Content-Type: application/json; charset=utf-8');

    // تنظيف المدخلات لتفادي حقن الأكواد
    $fullname = strip_tags(trim($_POST["fullname"] ?? ''));
    $email = filter_var(trim($_POST["email"] ?? ''), FILTER_VALIDATE_EMAIL);
    $phone = strip_tags(trim($_POST["phone"] ?? ''));
    $subject = strip_tags(trim($_POST["subject"] ?? ''));
    $message = strip_tags(trim($_POST["message"] ?? ''));

    // تحقق من صحة البيانات
    if (!$fullname || !$email || !$phone || !$subject || !$message) {
        echo json_encode([
            "status" => "error",
            "message" => "يرجى تعبئة جميع الحقول بشكل صحيح."
        ]);
        exit;
    }

    $mail = new PHPMailer(true);

    try {
        // إعدادات SMTP لجيميل
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'moroccolearningnational@gmail.com';
        $mail->Password = 'xkww kauk mslp isoi'; // ضع كلمة المرور هنا بحذر (كلمة تطبيق App Password)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // المرسل والمستقبل
        $mail->setFrom('moroccolearningnational@gmail.com', 'نظام استرجاع الحساب');
        $mail->addAddress('moroccolearningnational@gmail.com');

        // محتوى الإيميل
        $mail->isHTML(false);
        $mail->Subject = "رسالة من: $fullname - $subject";
        $mail->Body = "الاسم الكامل: $fullname\nالبريد الإلكتروني: $email\nرقم الهاتف: $phone\n\nالرسالة:\n$message";

        $mail->send();

        echo json_encode([
            "status" => "success",
            "message" => "📩 تم إرسال طلبك بنجاح! سيتم التواصل معك قريباً."
        ]);
    } catch (Exception $e) {
        echo json_encode([
            "status" => "error",
            "message" => "حدث خطأ أثناء إرسال الطلب. حاول مرة أخرى.",
            "error" => $mail->ErrorInfo
        ]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>تواصل معنا</title>
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
            color: #00ffff;
            font-size: 2.5rem;
            text-shadow: 0 0 10px #00f2ff;
        }
        .container {
            max-width: 600px;
            margin: 30px auto;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid #00ffe7;
            border-radius: 20px;
            padding: 30px;
            backdrop-filter: blur(10px);
            box-shadow: 0 0 25px #00ffe7;
        }
        label {
            display: block;
            margin-top: 15px;
            color: #e0f7fa;
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
            background-color: #00ffe7;
            border: none;
            color: #0b0c2a;
            font-size: 1rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #00cccc;
        }
        .back-button {
            display: block;
            text-align: center;
            margin: 30px auto 0;
        }
        .back-button a {
            text-decoration: none;
            background-color: #00ffe7;
            color: #0b0c2a;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        .back-button a:hover {
            background-color: #00cccc;
        }
        footer {
            text-align: center;
            padding: 20px;
            font-size: 0.9rem;
            color: #ccc;
        }
        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 10px;
        }
        ::-webkit-scrollbar-track {
            background: #0d1117;
        }
        ::-webkit-scrollbar-thumb {
            background: #00f2ff;
            border-radius: 10px;
            box-shadow: 0 0 10px #00f2ff;
            transition: background 0.3s ease;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #00f2ffcc;
        }
        .form-card {
            padding: 25px;
            border-radius: 15px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid #00ffe7;
            box-shadow: 0 0 20px rgba(0, 255, 231, 0.4);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        .status-message {
            margin-top: 15px;
            font-weight: bold;
            text-align: center;
        }
        .status-success {
            color: #4CAF50;
        }
        .status-error {
            color: #FF5252;
        }
    </style>
</head>
<body>
    <div id="particles-js"></div>

    <header>
        <h1>📬 تواصل معنا</h1>
    </header>

    <div class="container form-card">
        <form id="contactForm" method="POST" novalidate>
            <label for="fullname">الاسم الكامل</label>
            <input type="text" id="fullname" name="fullname" placeholder="أدخل اسمك" required />

            <label for="email">البريد الإلكتروني</label>
            <input type="email" id="email" name="email" placeholder="example@email.com" required />

            <label for="phone">رقم الهاتف</label>
            <input type="tel" id="phone" name="phone" placeholder="05XXXXXXXX" required />

            <label for="subject">الموضوع</label>
            <input type="text" id="subject" name="subject" placeholder="موضوع الرسالة" required />

            <label for="message">الرسالة</label>
            <textarea id="message" name="message" rows="5" placeholder="اكتب رسالتك هنا..." required></textarea>

            <button type="submit">📤 إرسال</button>
        </form>
        <div id="statusMessage" class="status-message"></div>
    </div>

    <div class="back-button">
        <a href="learning.php">العودة إلى الصفحة الرئيسية</a>
    </div>
    <br />
    <footer>
        &copy; 2025 جميع الحقوق محفوظة - MOROCCO LEARNING
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
    <script>
        particlesJS("particles-js", {
            particles: {
                number: { value: 60 },
                color: { value: "#00ffe7" },
                shape: { type: "circle" },
                opacity: { value: 0.4 },
                size: { value: 3 },
                line_linked: {
                    enable: true,
                    distance: 120,
                    color: "#00ffe7",
                    opacity: 0.4,
                    width: 1,
                },
                move: { enable: true, speed: 2 },
            },
            interactivity: {
                events: {
                    onhover: { enable: true, mode: "repulse" },
                    onclick: { enable: true, mode: "push" },
                },
            },
            retina_detect: true,
        });

        // AJAX إرسال النموذج بدون إعادة تحميل الصفحة
        document.getElementById('contactForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const form = e.target;
            const statusDiv = document.getElementById('statusMessage');
            statusDiv.textContent = '';
            statusDiv.className = 'status-message';

            // جمع بيانات النموذج
            const formData = new FormData(form);

            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData,
                });
                const result = await response.json();

                if (result.status === 'success') {
                    statusDiv.textContent = result.message;
                    statusDiv.classList.add('status-success');
                    form.reset();
                } else {
                    statusDiv.textContent = result.message || 'حدث خطأ غير معروف.';
                    statusDiv.classList.add('status-error');
                }
            } catch (error) {
                statusDiv.textContent = 'حدث خطأ أثناء الإرسال، يرجى المحاولة لاحقاً.';
                statusDiv.classList.add('status-error');
            }
        });
    </script>
</body>
</html>
