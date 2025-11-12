<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$db = 'moroccolearning';
$user = 'root';
$pass = '';
$port = 3307;

$dsn = "mysql:host=$host;dbname=$db;port=$port;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("فشل الاتصال بقاعدة البيانات: " . $e->getMessage());
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $code = trim($_POST['code'] ?? '');
    $password = $_POST['password'] ?? '';

    try {
        // استعلام معدل ليطابق هيكل الجدول
        $stmt = $pdo->prepare("SELECT id, institution_id, code, password FROM directors_login WHERE code = ?");
        $stmt->execute([$code]);
        $director = $stmt->fetch();

        if ($director && password_verify($password, $director['password'])) {
            // تخزين كل من id و institution_id في الجلسة
            $_SESSION['director_id'] = $director['id'];
            $_SESSION['institution_id'] = $director['institution_id'];
            
            $message = "✅ تم تسجيل الدخول بنجاح، سيتم تحويلك الآن...";
            $color = "#4caf50";
            $redirect = "directors.php";
        } else {
            $message = "❌ رمز المؤسسة أو كلمة المرور غير صحيحة، سيتم إعادتك...";
            $color = "#f44336";
            $redirect = "admin.php";
        }
    } catch (PDOException $e) {
        $message = "❌ حدث خطأ في النظام: " . $e->getMessage();
        $color = "#f44336";
        $redirect = "admin.php";
    }

    echo '
    <!DOCTYPE html>
    <html lang="ar" dir="rtl">
    <head>
        <meta charset="UTF-8" />
        <meta http-equiv="refresh" content="2;url=' . $redirect . '" />
        <title>تسجيل الدخول</title>
        <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
        <style>
            * {
                margin: 0; padding: 0; box-sizing: border-box;
            }
            body, html {
                height: 100%;
                font-family: "Cairo", sans-serif;
                background: #1e1e2f;
                overflow: hidden;
                direction: rtl;
                display: flex;
                justify-content: center;
                align-items: center;
                color: #eee;
            }
            #particles-js {
                position: fixed;
                width: 100%; height: 100%;
                top: 0; left: 0;
                z-index: 0;
                pointer-events: none;
            }
            .message-box {
                position: relative;
                z-index: 1;
                background: rgba(255 255 255 / 0.05);
                border-radius: 20px;
                padding: 30px 50px;
                box-shadow: 0 8px 20px rgb(0 0 0 / 0.5);
                font-size: 1.4rem;
                text-align: center;
                color: ' . $color . ';
                user-select: none;
                backdrop-filter: blur(10px);
                animation: fadeInScale 1s ease forwards;
                max-width: 400px;
            }
            @keyframes fadeInScale {
                0% {
                    opacity: 0;
                    transform: scale(0.9);
                }
                100% {
                    opacity: 1;
                    transform: scale(1);
                }
            }
        </style>
    </head>
    <body>
        <div id="particles-js"></div>
        <div class="message-box">' . $message . '</div>

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
        </script>
    </body>
    </html>
    ';
    exit;
}
?>
