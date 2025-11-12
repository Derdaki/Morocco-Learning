<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'src/PHPMailer.php';
require 'src/SMTP.php';
require 'src/Exception.php';

$msg = '';
$msgClass = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // تنظيف وتحقق من المدخلات
    $name = htmlspecialchars(strip_tags(trim($_POST["name"] ?? '')));
    $email = filter_var(trim($_POST["email"] ?? ''), FILTER_SANITIZE_EMAIL);
    $phone = htmlspecialchars(strip_tags(trim($_POST["phone"] ?? '')));
    $message = htmlspecialchars(trim($_POST["message"] ?? ''));

    // تحقق من صحة البيانات
    $errors = [];
    if (empty($name)) $errors[] = "الاسم الكامل مطلوب";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "بريد إلكتروني غير صالح";
    if (empty($message)) $errors[] = "الرسالة مطلوبة";

    if (!empty($errors)) {
        $msg = implode("<br>", $errors);
        $msgClass = "error";
    } else {
        $mail = new PHPMailer(true);

        try {
            // الإعدادات الأساسية
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            
            // إعدادات SMTP
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'moroccolearningnational@gmail.com';
            $mail->Password = 'xkww kauk mslp isoi';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];

            // المرسل والمستلم
            $mail->setFrom($email, mb_encode_mimeheader($name, 'UTF-8', 'Q'));
            $mail->addAddress('moroccolearningnational@gmail.com');
            $mail->addReplyTo($email, $name);

            // محتوى الرسالة
            $mail->isHTML(true);
            $mail->Subject = mb_encode_mimeheader("رسالة جديدة من $name - موروكو ليرنينج", 'UTF-8', 'Q');
            
            $mail->Body = "
                <!DOCTYPE html>
                <html dir='rtl' lang='ar'>
                <head>
                    <meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { color: #0066cc; font-size: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
                        .content { margin-top: 20px; }
                        .field { margin-bottom: 15px; }
                        .field strong { display: inline-block; width: 120px; }
                        .message { background: #f5f5f5; padding: 15px; border-radius: 5px; margin-top: 10px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>رسالة جديدة من موقع موروكو ليرنينج</div>
                        <div class='content'>
                            <div class='field'><strong>الاسم الكامل:</strong> $name</div>
                            <div class='field'><strong>البريد الإلكتروني:</strong> $email</div>
                            <div class='field'><strong>رقم الهاتف:</strong> $phone</div>
                            <div><strong>الرسالة:</strong></div>
                            <div class='message'>" . nl2br($message) . "</div>
                        </div>
                    </div>
                </body>
                </html>
            ";

            $mail->AltBody = "الاسم: $name\nالبريد: $email\nالهاتف: $phone\nالرسالة:\n$message";

            if (!$mail->send()) {
                throw new Exception($mail->ErrorInfo);
            }
            
            $msg = 'تم إرسال الرسالة بنجاح! شكراً لتواصلك معنا.';
            $msgClass = "success";
            
        } catch (Exception $e) {
            $msg = "عذراً، حدث خطأ أثناء محاولة إرسال الرسالة. يرجى المحاولة لاحقاً.";
            $msgClass = "error";
            // يمكنك تسجيل الخطأ في ملف السجل هنا إذا لزم الأمر
            // error_log('Mailer Error: ' . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>اتصل بنا - موروكو ليرنينج</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet" />
    <link rel="icon" type="image/png" href="ChatGPT Image 4 juin 2025, 14_53_49.png" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #0b0c2a;
            color: #fff;
            overflow-x: hidden;
            min-height: 100vh;
            position: relative;
            padding: 20px;
        }
        #particles-js {
            position: fixed;
            width: 100%;
            height: 100%;
            z-index: -1;
            top: 0;
            left: 0;
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
            max-width: 700px;
            margin: 30px auto 60px;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid #00ffe7;
            border-radius: 20px;
            padding: 30px 40px;
            backdrop-filter: blur(12px);
            box-shadow: 0 0 30px #00ffe7;
        }
        .info-section {
            margin-bottom: 30px;
        }
        .info-section h2 {
            font-size: 1.5rem;
            color: #00ffe7;
            margin-bottom: 10px;
            text-shadow: 0 0 5px #00ffe7;
        }
        .info-section p {
            margin: 5px 0;
            font-size: 1.1rem;
        }
        .social-icons {
            margin-top: 10px;
        }
        .social-icons a {
            margin: 0 10px;
            color: #00ffe7;
            font-size: 1.7rem;
            text-decoration: none;
            transition: color 0.3s;
            display: inline-block;
            vertical-align: middle;
        }
        .social-icons a:hover {
            color: #00cccc;
        }
        label {
            display: block;
            margin-top: 20px;
            color: #e0f7fa;
            font-weight: bold;
            text-shadow: 0 0 3px #00ffe7;
        }
        input, textarea {
            width: 100%;
            padding: 12px 15px;
            margin-top: 8px;
            border-radius: 12px;
            border: none;
            background: #ffffff20;
            color: #fff;
            font-size: 1.1rem;
            resize: vertical;
            box-shadow: inset 0 0 8px #00ffe7;
            transition: background 0.3s, box-shadow 0.3s;
        }
        input::placeholder, textarea::placeholder {
            color: #aaa;
        }
        input:focus, textarea:focus {
            background: #00ffe7;
            color: #0b0c2a;
            box-shadow: 0 0 12px #00ffe7;
            outline: none;
        }
        button {
            margin-top: 25px;
            padding: 14px 25px;
            background-color: #00ffe7;
            border: none;
            color: #0b0c2a;
            font-size: 1.2rem;
            border-radius: 14px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s, box-shadow 0.3s;
            box-shadow: 0 0 15px #00ffe7;
            width: 100%;
        }
        button:hover {
            background-color: #00cccc;
            box-shadow: 0 0 25px #00cccc;
        }
        .back-button {
            text-align: center;
            margin: 40px 0 0;
        }
        .back-button a {
            text-decoration: none;
            background-color: #00ffe7;
            color: #0b0c2a;
            padding: 12px 30px;
            border-radius: 15px;
            font-weight: bold;
            font-size: 1.1rem;
            display: inline-block;
            box-shadow: 0 0 15px #00ffe7;
            transition: background-color 0.3s, box-shadow 0.3s;
        }
        .back-button a:hover {
            background-color: #00cccc;
            box-shadow: 0 0 25px #00cccc;
        }
        footer {
            text-align: center;
            padding: 20px 10px;
            font-size: 0.9rem;
            color: #ccc;
            position: relative;
            z-index: 1;
        }
        .msg {
            margin-bottom: 15px;
            padding: 12px 18px;
            border-radius: 12px;
            font-weight: bold;
            font-size: 1.1rem;
            text-align: center;
            box-shadow: 0 0 10px #00ffe7;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        .msg.success {
            background-color: #0e9f6e;
            color: #d3f9d8;
            box-shadow: 0 0 15px #0e9f6e;
        }
        .msg.error {
            background-color: #be3e3e;
            color: #f9d8d8;
            box-shadow: 0 0 15px #be3e3e;
        }
        ::-webkit-scrollbar { width: 10px; }
        ::-webkit-scrollbar-track { background: #0d1117; }
        ::-webkit-scrollbar-thumb {
            background: #00f2ff;
            border-radius: 10px;
            box-shadow: 0 0 10px #00f2ff;
            transition: background 0.3s ease;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #00f2ffcc;
        }
    </style>
</head>
<body>
    <div id="particles-js"></div>

    <header>
        <h1>📞 اتصل بنا</h1>
    </header>

    <div class="container">
        <div class="info-section">
            <h2>📍 العنوان:</h2>
            <p>شارع الحسن الثاني، الطابق 3، وجدة، المغرب</p>

            <h2>📧 البريد الإلكتروني:</h2>
            <p>moroccolearningnational@gmail.com</p>

            <h2>📱 رقم الهاتف:</h2>
            <p>+212 6 20 39 31 76</p>

            <h2>🌐 تابعنا على:</h2>
            <div class="social-icons">
                <a href="#" title="فيسبوك" target="_blank" aria-label="Facebook">
                    <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M22.675 0h-21.35C.597 0 0 .597 0 1.326v21.348C0 23.403.597 24 1.326 24h11.495v-9.294H9.691v-3.622h3.13V8.413c0-3.1 1.893-4.788 4.659-4.788 1.325 0 2.463.099 2.794.143v3.24h-1.917c-1.504 0-1.796.715-1.796 1.763v2.313h3.588l-.467 3.622h-3.121V24h6.116C23.403 24 24 23.403 24 22.674V1.326C24 .597 23.403 0 22.675 0z"/>
                    </svg>
                </a>
                <a href="#" title="تويتر" target="_blank" aria-label="Twitter">
                    <svg width="24" height="24" fill="#00ffe7" viewBox="0 0 24 24">
                        <path d="M23.954 4.569c-.885.389-1.83.654-2.825.775 1.014-.611 1.794-1.574 2.163-2.723-.949.555-2.005.959-3.127 1.184-.897-.959-2.178-1.559-3.594-1.559-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616v.061c0 2.385 1.693 4.374 3.946 4.827-.693.188-1.452.232-2.224.084.623 1.946 2.444 3.361 4.6 3.401-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.557 2.212 9.142 0 14.307-7.721 13.995-14.646.961-.695 1.8-1.562 2.46-2.549z"/>
                    </svg>
                </a>
                <a href="#" title="إنستغرام" target="_blank" aria-label="Instagram">
                    <svg width="24" height="24" fill="#00ffe7" viewBox="0 0 24 24">
                        <path d="M7.75 2h8.5C19.44 2 22 4.56 22 7.75v8.5c0 3.19-2.56 5.75-5.75 5.75h-8.5C4.56 22 2 19.44 2 16.25v-8.5C2 4.56 4.56 2 7.75 2zm4.25 3a5.75 5.75 0 1 0 0 11.5 5.75 5.75 0 0 0 0-11.5zm6.5 11.75a1.25 1.25 0 1 1-2.5 0 1.25 1.25 0 0 1 2.5 0zM12 8a4 4 0 1 1 0 8 4 4 0 0 1 0-8z"/>
                    </svg>
                </a>
            </div>
        </div>

        <?php if ($msg): ?>
            <div class="msg <?php echo $msgClass; ?>">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <label for="name">الاسم الكامل</label>
            <input type="text" id="name" name="name" placeholder="اكتب اسمك الكامل" required />

            <label for="email">البريد الإلكتروني</label>
            <input type="email" id="email" name="email" placeholder="example@mail.com" required />

            <label for="phone">رقم الهاتف</label>
            <input type="tel" id="phone" name="phone" placeholder="رقم الهاتف" required />

            <label for="message">رسالتك</label>
            <textarea id="message" name="message" rows="5" placeholder="اكتب رسالتك هنا..." required></textarea>

            <button type="submit">إرسال الرسالة</button>
        </form>

        <div class="back-button">
            <a href="Home.html">العودة للصفحة الرئيسية</a>
        </div>
    </div>

    <footer>
        &copy; <?php echo date('Y'); ?> Moroccolearning. جميع الحقوق محفوظة.
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
    <script>
        particlesJS('particles-js', {
            "particles": {
                "number": { "value": 80, "density": { "enable": true, "value_area": 800 } },
                "color": { "value": "#00ffe7" },
                "shape": { "type": "circle", "stroke": { "width": 0, "color": "#000000" }, "polygon": { "nb_sides": 5 } },
                "opacity": { "value": 0.5, "random": false, "anim": { "enable": false } },
                "size": { "value": 3, "random": true, "anim": { "enable": false } },
                "line_linked": { "enable": true, "distance": 150, "color": "#00ffe7", "opacity": 0.4, "width": 1 },
                "move": { "enable": true, "speed": 4, "direction": "none", "random": false, "straight": false, "out_mode": "out", "bounce": false, "attract": { "enable": false } }
            },
            "interactivity": {
                "detect_on": "canvas",
                "events": {
                    "onhover": { "enable": true, "mode": "grab" },
                    "onclick": { "enable": true, "mode": "push" },
                    "resize": true
                },
                "modes": {
                    "grab": { "distance": 140, "line_linked": { "opacity": 1 } },
                    "bubble": { "distance": 400, "size": 40, "duration": 2, "opacity": 8 },
                    "repulse": { "distance": 200, "duration": 0.4 },
                    "push": { "particles_nb": 4 },
                    "remove": { "particles_nb": 2 }
                }
            },
            "retina_detect": true
        });
    </script>
</body>
</html>