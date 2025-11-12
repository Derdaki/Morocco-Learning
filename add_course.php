<?php
session_start();

// التحقق من تسجيل الدخول
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

// جلب بيانات المستخدم
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT fullname FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $duration = trim($_POST['duration']);
    $level = trim($_POST['level']);
    $lessons = (int)$_POST['lessons'];
    $instructor_id = $user_id;
    $instructor_name = $user['fullname'];
    $has_certificate = isset($_POST['has_certificate']) ? 1 : 0;
    $image_url = trim($_POST['image_url']);
    $page_url = trim($_POST['page_url']);

    // التحقق من صحة البيانات
    if (empty($title)) $errors[] = "عنوان الدورة مطلوب";
    if (empty($description)) $errors[] = "وصف الدورة مطلوب";
    if (empty($category)) $errors[] = "تصنيف الدورة مطلوب";
    if (empty($image_url)) $errors[] = "رابط صورة الدورة مطلوب";
    if (empty($page_url)) $errors[] = "رابط صفحة الدورة مطلوب";

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO courses (title, description, category, duration, level, lessons, instructor, has_certificate, image_url, page_url, instructor_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssisissi", $title, $description, $category, $duration, $level, $lessons, $instructor_name, $has_certificate, $image_url, $page_url, $instructor_id);
        
        if ($stmt->execute()) {
            $success = true;
        } else {
            $errors[] = "حدث خطأ أثناء إضافة الدورة: " . $conn->error;
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>MOROCCO LEARNING - إضافة دورة</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&amp;display=swap" rel="stylesheet"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="ChatGPT Image 4 juin 2025, 14_53_49.png">
    <style>
        * {
            box-sizing: border-box;
            font-family: 'Cairo', sans-serif;
        }

        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            background-color: #001f2e;
            color: #e0f7fa;
            overflow-x: hidden;
        }

        #particles-js {
            position: fixed;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: 0;
            background: linear-gradient(135deg, #001f2e, #003e52);
        }

        header {
            z-index: 10;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 48px;
            background-color: rgba(0,0,0,0.6);
            border-bottom: 1px solid #00ffe7;
            flex-wrap: wrap;
            width: 100%;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
        }

        .logo {
            padding-top: 10px;
            margin: -10px;
            font-size: 1.8rem;
            font-weight: 900;
            color: #00ffe7;
            letter-spacing: 2px;
            text-transform: uppercase;
            user-select: none;
            cursor: pointer;
            filter: drop-shadow(0 0 6px #00ffe7);
        }

        nav a {
            color: #b2ebf2;
            margin: 0 12px;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        nav a:hover {
            color: #00ffe7;
        }

        .account-dropdown {
            position: relative;
        }

        .account-btn {
            background-color: #00ffe7;
            color: #001f2e;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
            min-width: 180px;
        }

        .account-btn:hover {
            background-color: #00cbbf;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #002f45;
            min-width: 180px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.3);
            border-radius: 6px;
            z-index: 20;
            right: 0;
            top: 120%;
        }

        .dropdown-content a {
            color: #e0f7fa;
            padding: 12px 16px;
            display: block;
            text-decoration: none;
            transition: background 0.2s;
        }

        .dropdown-content a:hover {
            background-color: #004d66;
        }

        .account-dropdown:hover .dropdown-content {
            display: block;
        }

        main.container {
            display: flex;
            position: relative;
            z-index: 5;
            max-width: 1000px;
            margin: 100px auto;
            padding: 30px;
            background: rgba(0,0,0,0.75);
            border-radius: 12px;
            box-shadow: 0 0 15px #00ffe7;
        }

        .form-container {
            width: 100%;
        }

        .form-title {
            font-size: 28px;
            color: #00ffe7;
            margin-bottom: 30px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #b2ebf2;
            font-weight: bold;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #00ffe7;
            background-color: #082c3d;
            color: #fff;
            font-size: 16px;
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .btn-submit {
            background-color: #00ffe7;
            color: #001f2e;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
            font-size: 16px;
            width: 100%;
            margin-top: 20px;
        }

        .btn-submit:hover {
            background-color: #00cbbf;
        }

        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
        }

        .alert-success {
            background-color: #004d40;
            color: #b2ebf2;
            border: 1px solid #00ffe7;
        }

        .alert-danger {
            background-color: #4d0000;
            color: #ffb2b2;
            border: 1px solid #ff5252;
        }

        .checkbox-container {
            display: flex;
            align-items: center;
            margin-top: 10px;
        }

        .checkbox-container input {
            width: auto;
            margin-left: 10px;
        }

        .subtitle {
            color: white;
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            opacity: 0;
            animation: fadeIn 1.5s forwards;
            margin: 0px;
        }

        @keyframes fadeIn {
            to {
                opacity: 1;
            }
        }

        .slide-up {
            opacity: 0;
            transform: translateY(10px);
            animation: slideUp 1s ease-out forwards;
        }

        @keyframes slideUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* تخصيص شريط التمرير */
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

        @media(max-width: 900px) {
            main.container {
                margin: 100px 20px;
                padding: 20px;
            }

            header {
                padding: 10px 20px;
            }

            nav a {
                margin: 0 8px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div id="particles-js"></div>
    <header>
        <div>
            <div class="logo" tabindex="0">MOROCCO learning</div>
            <p class="subtitle slide-up">Moroccan National Online Learning Platform</p>
        </div>
        <nav>
            <a href="learning.php">الرئيسية</a>
            <a href="Articles.php">مكتبة المقالات والكتب</a>
            <a href="faq.php">الأسئلة الشائعة</a>
            <a href="ContactHome.php">تواصل معنا</a>
            <a href="Game.php">بوابة التعليم الذكية</a>
        </nav>
        <div class="account-dropdown">
            <button class="account-btn">الحساب الشخصي</button>
            <div class="dropdown-content">
                <a href="profil.php">ملفي الشخصي</a>
                <a href="Certificate.php">شهاداتي</a>
                <a href="settings.php">الإعدادات</a>
                <a href="loginout.php">تسجيل الخروج</a>
            </div>
        </div>
    </header>
    
    <main class="container">
        <div class="form-container">
            <h1 class="form-title">إضافة دورة جديدة</h1>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    تم إضافة الدورة بنجاح!
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="add_course.php">
                <div class="form-group">
                    <label for="title">عنوان الدورة</label>
                    <input type="text" id="title" name="title" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="description">وصف الدورة</label>
                    <textarea id="description" name="description" class="form-control" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="category">تصنيف الدورة</label>
                    <select id="category" name="category" class="form-control" required>
                        <option value="">اختر التصنيف</option>
                        <option value="برمجة">برمجة</option>
                        <option value="تصميم">تصميم</option>
                        <option value="أمن معلومات">أمن معلومات</option>
                        <option value="ذكاء اصطناعي">ذكاء اصطناعي</option>
                        <option value="تطوير الذات">تطوير الذات</option>
                        <option value="التسويق الرقمي">التسويق الرقمي</option>
                        <option value="اللغة الإنجليزية">اللغة الإنجليزية</option>
                        <option value="إدارة الأعمال">إدارة الأعمال</option>
                        <option value="المالية">المالية</option>
                        <option value="الصحة واللياقة">الصحة واللياقة</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="duration">مدة الدورة (ساعات)</label>
                    <input type="text" id="duration" name="duration" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="level">مستوى الدورة</label>
                    <select id="level" name="level" class="form-control" required>
                        <option value="مبتدئ">مبتدئ</option>
                        <option value="متوسط">متوسط</option>
                        <option value="متقدم">متقدم</option>
                        <option value="جميع المستويات">جميع المستويات</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="lessons">عدد الدروس</label>
                    <input type="number" id="lessons" name="lessons" class="form-control" required min="1">
                </div>
                
                <div class="form-group">
                    <label for="image_url">رابط صورة الدورة (URL)</label>
                    <input type="url" id="image_url" name="image_url" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="page_url">رابط صفحة الدورة (اسم الملف)</label>
                    <input type="text" id="page_url" name="page_url" class="form-control" required placeholder="مثال: pagepython.php">
                </div>
                
                <div class="checkbox-container">
                    <label for="has_certificate">هل توجد شهادة بعد إكمال الدورة؟</label>
                    <input type="checkbox" id="has_certificate" name="has_certificate" checked>
                </div>
                
                <button type="submit" class="btn-submit">إضافة الدورة</button>
            </form>
        </div>
    </main>
    
    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <script>
        particlesJS("particles-js", {
            particles: {
                number: { value: 100, density: { enable: true, value_area: 1000 } },
                color: { value: "#00ffe7" },
                shape: { type: "circle" },
                opacity: {
                    value: 0.4,
                    random: false,
                    anim: { enable: true, speed: 1, opacity_min: 0.1, sync: false },
                },
                size: { value: 3, random: true },
                line_linked: {
                    enable: true,
                    distance: 150,
                    color: "#00ffe7",
                    opacity: 0.4,
                    width: 1,
                },
                move: {
                    enable: true,
                    speed: 2,
                    direction: "none",
                    out_mode: "bounce",
                    bounce: true,
                },
            },
            interactivity: {
                detect_on: "canvas",
                events: {
                    onhover: { enable: true, mode: "grab" },
                    onclick: { enable: true, mode: "push" },
                    resize: true,
                },
                modes: {
                    grab: { distance: 140, line_linked: { opacity: 0.5 } },
                    push: { particles_nb: 4 },
                },
            },
            retina_detect: true,
        });
    </script>
</body>
</html>