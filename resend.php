<?php
session_start();

require 'src/PHPMailer.php';
require 'src/SMTP.php';
require 'src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "moroccolearning";
$port = 3307;

$message = "";
$success = false;
$show_code_input = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $message = "يرجى إدخال البريد الإلكتروني.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "البريد الإلكتروني غير صالح.";
    } else {
        $conn = new mysqli($servername, $username, $password, $dbname, $port);
        if ($conn->connect_error) {
            die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
        }
        $conn->set_charset("utf8mb4");

        $stmt = $conn->prepare("SELECT id, fullname, is_verified FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($user_id, $fullname, $is_verified);
            $stmt->fetch();

            if ($is_verified == 1) {
                $message = "الحساب مفعل مسبقاً.";
                $success = false;
            } else {
                $new_code = rand(100000, 999999);
                $update = $conn->prepare("UPDATE users SET verification_code = ? WHERE id = ?");
                $update->bind_param("ii", $new_code, $user_id);
                if ($update->execute()) {
                    $_SESSION['email_to_verify'] = $email;
                    $_SESSION['code_sent'] = true;
                    $show_code_input = true;

                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'moroccolearningnational@gmail.com';
                        $mail->Password = 'xkww kauk mslp isoi';
                        $mail->SMTPSecure = 'tls';
                        $mail->Port = 587;

                        $mail->setFrom('your_email@gmail.com', 'موقعنا');
                        $mail->addAddress($email, $fullname);

                        $mail->isHTML(true);
                        $mail->Subject = 'رمز تحقق جديد';
                        $mail->Body = "مرحباً $fullname،<br>رمز التحقق الجديد الخاص بك هو: <b>$new_code</b>";

                        $mail->send();
                        $message = "تم إرسال رمز تحقق جديد إلى بريدك الإلكتروني.";
                        $success = true;
                    } catch (Exception $e) {
                        $message = "حدث خطأ أثناء إرسال البريد: {$mail->ErrorInfo}";
                    }
                    $update->close();
                }
            }
        } else {
            $message = "هذا البريد غير مسجل.";
        }

        $stmt->close();
        $conn->close();
    }
}

if (isset($_SESSION['code_sent']) && $_SESSION['code_sent'] === true) {
    $show_code_input = true;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="ChatGPT Image 4 juin 2025, 14_53_49.png">
    <title>إعادة إرسال رمز التحقق</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo&display=swap');
        body, html {
            height: 100%;
            margin: 0;
            font-family: 'Cairo', sans-serif;
            background: #121212;
            color: #eee;
            display: flex;
            justify-content: center;
            align-items: center;
            direction: rtl;
            overflow: hidden;
        }
        #particles-js {
            position: fixed;
            width: 100%;
            height: 100%;
            top: 0; left: 0;
            z-index: -1;
        }
        .container {
            background: rgba(0,0,0,0.75);
            padding: 2.5rem 3rem;
            border-radius: 16px;
            box-shadow: 0 0 20px #0ff;
            width: 400px;
            max-width: 95vw;
            text-align: center;
        }
        h2 {
            color: #0ff;
            margin-bottom: 1.5rem;
        }
        input[type="email"], input[type="text"] {
            width: 100%;
            padding: 14px;
            margin: 12px 0;
            border-radius: 8px;
            border: none;
            font-size: 1.1rem;
            outline: none;
            box-sizing: border-box;
        }
        button {
            background: linear-gradient(90deg, #00fff7, #00cfcf);
            border: none;
            padding: 14px 0;
            border-radius: 8px;
            color: #000;
            font-weight: 700;
            font-size: 1.2rem;
            cursor: pointer;
            margin-top: 1rem;
            width: 100%;
            box-shadow: 0 0 15px #00fff7;
        }
        .message {
            margin-top: 15px;
            font-weight: bold;
            color: <?= $success ? '#0ff' : '#ff6b6b' ?>;
        }
    </style>
</head>
<body>

<div id="particles-js"></div>

<div class="container">
    <h2>إعادة إرسال رمز التحقق</h2>
    <?php if ($message): ?>
        <div class="message"><?= $message ?></div>
    <?php endif; ?>
    <form method="POST" action="">
        <input type="email" name="email" placeholder="أدخل بريدك الإلكتروني" required 
            value="<?= htmlspecialchars($_SESSION['email_to_verify'] ?? '') ?>" />
        
        <?php if ($show_code_input): ?>
            <input type="text" name="verification_code" placeholder="أدخل رمز التحقق" required />
            <button type="submit" formaction="confirm.php">تأكيد الرمز</button>
        <?php else: ?>
            <button type="submit">إرسال الرمز</button>
        <?php endif; ?>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
<script>
particlesJS('particles-js', {
  particles: {
    number: { value: 150, density: { enable: true, value_area: 1500 } },
    color: { value: '#00ffe7' },
    shape: { type: 'circle' },
    opacity: { value: 0.5 },
    size: { value: 3, random: true },
    line_linked: { enable: true, distance: 150, color: '#00ffe7', opacity: 0.3, width: 1 },
    move: { enable: true, speed: 2, out_mode: 'bounce' }
  },
  interactivity: {
    events: {
      onhover: { enable: true, mode: 'grab' },
      onclick: { enable: true, mode: 'push' },
      resize: true
    },
    modes: {
      grab: { distance: 140, line_linked: { opacity: 0.5 } },
      push: { particles_nb: 4 }
    }
  },
  retina_detect: true
});
</script>

</body>
</html>
