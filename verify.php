<?php
session_start();

// إعدادات الاتصال بقاعدة البيانات
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "moroccolearning";
$port = 3307;

// دالة لعرض صفحة النتيجة
function renderPage($message, $success = false) {
    $color = $success ? "#00fff7" : "#ff5555";
    $title = $success ? "تم تأكيد البريد الإلكتروني!" : "فشل التأكيد";
    ?>
    <!DOCTYPE html>
    <html lang="ar" dir="rtl">
    <head>
        <meta charset="UTF-8" />
        <link rel="icon" type="image/png" href="ChatGPT Image 4 juin 2025, 14_53_49.png">
        <title><?php echo $title; ?></title>
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Tajawal&display=swap');
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
                background: rgba(0,0,0,0.85);
                padding: 40px;
                border-radius: 20px;
                box-shadow: 0 0 20px <?php echo $color; ?>;
                text-align: center;
                max-width: 400px;
                position: relative;
                z-index: 2;
            }
            h2 {
                margin-bottom: 20px;
                color: <?php echo $color; ?>;
            }
            p {
                font-size: 18px;
                margin-bottom: 30px;
            }
            a {
                display: inline-block;
                padding: 10px 25px;
                background: <?php echo $color; ?>;
                color: #000;
                font-weight: bold;
                border-radius: 10px;
                text-decoration: none;
                transition: background 0.3s ease;
            }
            a:hover {
                background: #00cfcf;
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
        <h2><?php echo $title; ?></h2>
        <p><?php echo $message; ?></p>
        <a href="login.php">العودة إلى تسجيل الدخول</a>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
    <script>
    particlesJS("particles-js", {
      "particles": {
        "number": { "value": 100 },
        "color": { "value": "<?php echo $color; ?>" },
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
    <?php
    exit;
}

// التأكد من أن الطلب POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: confirm.php");
    exit;
}

// الحصول على البريد من الجلسة أو POST
$email = $_SESSION['email_to_verify'] ?? $_POST['email'] ?? '';
if (!$email) {
    renderPage("لا يوجد بريد إلكتروني في الجلسة أو الطلب. يرجى التسجيل أولاً.", false);
}

// رمز التحقق المُدخل من المستخدم
$input_code = trim($_POST['verification_code'] ?? '');
if (!preg_match('/^\d{6}$/', $input_code)) {
    renderPage("رمز التحقق يجب أن يكون مكوناً من 6 أرقام فقط.", false);
}

// الاتصال بقاعدة البيانات
$conn = new mysqli($servername, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    renderPage("فشل الاتصال بقاعدة البيانات. حاول لاحقاً.", false);
}
$conn->set_charset("utf8mb4");

// جلب معلومات المستخدم
$stmt = $conn->prepare("SELECT verification_code, is_verified FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    $stmt->close();
    $conn->close();
    renderPage("المستخدم غير موجود.", false);
}

$stmt->bind_result($verification_code_db, $is_verified);
$stmt->fetch();

// إذا كان الحساب مفعلاً مسبقًا
if ($is_verified) {
    $stmt->close();
    $conn->close();
    renderPage("الحساب مفعل بالفعل. يمكنك تسجيل الدخول.", true);
}

// التحقق من الرمز
if (trim((string)$input_code) !== trim((string)$verification_code_db)) {
    $stmt->close();
    $conn->close();
    renderPage("رمز التحقق غير صحيح. حاول مرة أخرى.", false);
}

// تحديث التحقق في قاعدة البيانات
$stmt->close();
$update = $conn->prepare("UPDATE users SET is_verified = 1, verification_code = NULL WHERE email = ?");
$update->bind_param("s", $email);

if ($update->execute()) {
    unset($_SESSION['email_to_verify']);
    $update->close();
    $conn->close();
    renderPage("تم تفعيل حسابك بنجاح!", true);
} else {
    $update->close();
    $conn->close();
    renderPage("حدث خطأ أثناء التفعيل، يرجى المحاولة لاحقاً.", false);
}
?>
