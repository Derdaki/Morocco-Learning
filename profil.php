<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "moroccolearning";
$port = 3307;

$conn = new mysqli($servername, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT fullname, email, phone, address, cne, massar, gender, birthdate, education_level, specialty, institution, graduation_year FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    echo "المستخدم غير موجود.";
    exit();
}

$user = $result->fetch_assoc();

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" type="image/png" href="ChatGPT Image 4 juin 2025, 14_53_49.png" />
    <title>الملف الشخصي</title>
    <style>
        /* تصميم الصفحة */
        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: #222;
            color: #fff;
            position: relative;
            overflow-x: hidden;
        }
        #particles-js {
            position: fixed;
            width: 100%;
            height: 100%;
            z-index: 0;
            top: 0;
            left: 0;
        }
        .container {
            max-width: 700px;
            background: rgba(0, 0, 0, 0.75);
            margin: 70px auto 50px;
            padding: 30px 40px;
            border-radius: 12px;
            box-shadow: 0 0 20px rgba(240, 165, 0, 0.7);
            position: relative;
            z-index: 1;
        }
        h1 {
            text-align: center;
            margin-bottom: 30px;
            font-weight: 700;
            letter-spacing: 1.5px;
            color: #f0a500;
        }
        .profile-item {
            margin-bottom: 18px;
            font-size: 1.1rem;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            padding-bottom: 8px;
        }
        .label {
            font-weight: 700;
            color: #f0a500;
            display: inline-block;
            width: 140px;
        }
        .buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 40px;
            flex-wrap: wrap;
        }
        .btn {
            padding: 12px 25px;
            background-color: #f0a500;
            color: #222;
            font-weight: 700;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            box-shadow: 0 4px 10px rgba(240, 165, 0, 0.7);
            text-decoration: none;
            text-align: center;
            min-width: 140px;
            user-select: none;
        }
        .btn:hover {
            background-color: #cf8500;
            box-shadow: 0 6px 15px rgba(207, 133, 0, 0.9);
        }
        @media (max-width: 500px) {
            .container {
                margin: 30px 15px 50px;
                padding: 25px 20px;
            }
            .label {
                display: block;
                width: 100%;
                margin-bottom: 6px;
            }
            .buttons {
                flex-direction: column;
                gap: 15px;
            }
            .btn {
                min-width: 100%;
            }
        }
                  /* تخصيص شريط التمرير */
  ::-webkit-scrollbar {
    width: 10px;
  }

  ::-webkit-scrollbar-track {
    background: #0d1117; /* خلفية داكنة */
  }

  ::-webkit-scrollbar-thumb {
    background-color: #cf8500;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(240, 165, 0, 0.7);
    transition: background 0.3s ease;
  }

  ::-webkit-scrollbar-thumb:hover {
    background-color: #cf8500;
  }
    </style>
</head>
<body>
    <!-- خلفية particles.js -->
    <div id="particles-js"></div>

    <main class="container" role="main" aria-labelledby="profile-title">
        <h1 id="profile-title">الملف الشخصي</h1>
        
        <div class="profile-item"><span class="label">الاسم الكامل:</span> <?= htmlspecialchars($user['fullname']) ?></div>
        <div class="profile-item"><span class="label">البريد الإلكتروني:</span> <?= htmlspecialchars($user['email']) ?></div>
        <div class="profile-item"><span class="label">رقم الهاتف:</span> <?= htmlspecialchars($user['phone']) ?: 'غير متوفر' ?></div>
        <div class="profile-item"><span class="label">العنوان:</span> <?= htmlspecialchars($user['address']) ?: 'غير متوفر' ?></div>
        <div class="profile-item"><span class="label">CNE:</span> <?= htmlspecialchars($user['cne']) ?: 'غير متوفر' ?></div>
        <div class="profile-item"><span class="label">رقم مسار:</span> <?= htmlspecialchars($user['massar']) ?: 'غير متوفر' ?></div>
        <div class="profile-item"><span class="label">الجنس:</span> <?= htmlspecialchars($user['gender']) ?: 'غير متوفر' ?></div>
        <div class="profile-item"><span class="label">تاريخ الميلاد:</span> <?= htmlspecialchars($user['birthdate']) ?: 'غير متوفر' ?></div>
        <div class="profile-item"><span class="label">المستوى الدراسي:</span> <?= htmlspecialchars($user['education_level']) ?: 'غير متوفر' ?></div>
        <div class="profile-item"><span class="label">التخصص:</span> <?= htmlspecialchars($user['specialty']) ?: 'غير متوفر' ?></div>
        <div class="profile-item"><span class="label">المؤسسة:</span> <?= htmlspecialchars($user['institution']) ?: 'غير متوفر' ?></div>
        <div class="profile-item"><span class="label">سنة التخرج:</span> <?= htmlspecialchars($user['graduation_year']) ?: 'غير متوفر' ?></div>

        <div class="buttons" role="group" aria-label="أزرار التحكم بالملف الشخصي">
            <a href="learning.php" class="btn" aria-label="العودة إلى الصفحة الرئيسية">الصفحة الرئيسية</a>
            <a href="edit_profil.php" class="btn" aria-label="تعديل الملف الشخصي"> تعديل الملف</a>
            <a href="settings.php" class="btn" aria-label="الإعدادات">الإعدادات</a>
            <button class="btn" onclick="window.print()" aria-label="طباعة الملف الشخصي"> طباعة</button>
        </div>
    </main>

    <!-- particles.js library -->
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
    <script>
    /* إعدادات particles.js */
    particlesJS("particles-js", {
        "particles": {
            "number": {
                "value": 80,
                "density": {"enable": true, "value_area": 800}
            },
            "color": {"value": "#f0a500"},
            "shape": {
                "type": "circle",
                "stroke": {"width": 0, "color": "#000000"},
                "polygon": {"nb_sides": 5}
            },
            "opacity": {
                "value": 0.5,
                "random": false,
                "anim": {"enable": false}
            },
            "size": {
                "value": 4,
                "random": true,
                "anim": {"enable": false}
            },
            "line_linked": {
                "enable": true,
                "distance": 150,
                "color": "#f0a500",
                "opacity": 0.4,
                "width": 1
            },
            "move": {
                "enable": true,
                "speed": 3,
                "direction": "none",
                "random": false,
                "straight": false,
                "out_mode": "out",
                "bounce": false
            }
        },
        "interactivity": {
            "detect_on": "canvas",
            "events": {
                "onhover": {"enable": true, "mode": "grab"},
                "onclick": {"enable": true, "mode": "push"},
                "resize": true
            },
            "modes": {
                "grab": {"distance": 140, "line_linked": {"opacity": 1}},
                "push": {"particles_nb": 4}
            }
        },
        "retina_detect": true
    });
    </script>
</body>
</html>
