<?php
session_start();

// التحقق من تسجيل الدخول
if (!isset($_SESSION['director_id'])) {
    header("Location: admin.php");
    exit();
}

// إعدادات قاعدة البيانات
$host = 'localhost';
$port = 3307;
$dbname = 'moroccolearning';
$username = 'root';
$password = '';

try {
    // اتصال بقاعدة البيانات
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("فشل الاتصال بقاعدة البيانات: " . $e->getMessage());
}

// جلب معلومات المدير والمؤسسة
try {
    $stmt = $pdo->prepare("SELECT d.*, i.* FROM directors_login d 
                          JOIN institutions i ON d.institution_id = i.id 
                          WHERE d.id = ?");
    $stmt->execute([$_SESSION['director_id']]);
    $director = $stmt->fetch();

    if (!$director) {
        die("لم يتم العثور على معلومات المدير.");
    }

    $_SESSION['access_code'] = $director['access_code'];
    $_SESSION['user_id'] = $director['id'];
    $_SESSION['institution_id'] = $director['institution_id'];
    $_SESSION['institution_name'] = $director['institution_name']; // تم تعديله من name إلى institution_name

    $institution_id = $director['institution_id'];
    $institution_name = $director['institution_name']; // تم تعديله من name إلى institution_name

} catch (PDOException $e) {
    die("حدث خطأ في جلب بيانات المدير: " . $e->getMessage());
}

// جلب الأساتذة
try {
    $stmt = $pdo->prepare("SELECT t.*, u.fullname, u.email, u.phone 
                          FROM teachers t
                          JOIN users u ON t.user_id = u.id
                          WHERE t.institution_id = ?");
    $stmt->execute([$institution_id]);
    $teachers = $stmt->fetchAll();

    $total_teachers = count($teachers);

    // عدد الأساتذة الجدد هذا الشهر
    $month_start = date('Y-m-01');
    $month_end = date('Y-m-t');
    $stmt_new = $pdo->prepare("SELECT COUNT(*) FROM teachers WHERE institution_id = ? AND created_at BETWEEN ? AND ?");
    $stmt_new->execute([$institution_id, $month_start, $month_end]);
    $new_teachers_this_month = $stmt_new->fetchColumn();

} catch (PDOException $e) {
    die("حدث خطأ في جلب بيانات الأساتذة: " . $e->getMessage());
}

// جلب التلاميذ
try {
    $stmt = $pdo->prepare("
        SELECT u.*, ubp.points
        FROM users u
        LEFT JOIN user_behavior_points ubp ON u.id = ubp.user_id
        WHERE u.institution = ? AND u.user_type = 'student'
    ");
    $stmt->execute([$institution_name]);
    $students = $stmt->fetchAll();
} catch (PDOException $e) {
    die("حدث خطأ في جلب بيانات التلاميذ: " . $e->getMessage());
}

// معالجة إضافة مستخدم
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_type']) && $_POST['form_type'] === 'add_user') {
    try {
        $pdo->beginTransaction();
        
        $userType = $_POST['user_type'];
        $password = password_hash('default123', PASSWORD_DEFAULT);
        
        // إدخال بيانات المستخدم الأساسية
        $stmt = $pdo->prepare("INSERT INTO users 
            (fullname, email, password, phone, address, cne, massar, education_level, user_type, birthdate, institution, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        
        $stmt->execute([
            $_POST['fullname'],
            $_POST['email'] ?? null,
            $password,
            $_POST['phone'] ?? null,
            $_POST['address'] ?? null,
            $_POST['cne'] ?? null,
            $_POST['massar'] ?? null,
            $_POST['education_level'],
            $userType,
            $_POST['birthdate'] ?? null,
            $institution_name
        ]);
        
        $userId = $pdo->lastInsertId();
        
        // معالجة الصورة
        $photoPath = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/users/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $photoName = 'user_' . $userId . '.' . $ext;
            $photoPath = $uploadDir . $photoName;
            
            move_uploaded_file($_FILES['photo']['tmp_name'], $photoPath);
        }
        
        // إذا كان أستاذاً
        if ($userType === 'teacher') {
            $stmt = $pdo->prepare("INSERT INTO teachers 
                (user_id, institution_id, specialty, photo) 
                VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $userId,
                $institution_id,
                $_POST['specialty'],
                $photoPath
            ]);
        }
        
        $pdo->commit();
        $_SESSION['success'] = "تمت إضافة المستخدم بنجاح";
        header("Location: directors.php");
        exit();
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "خطأ في الإضافة: " . $e->getMessage();
        header("Location: directors.php");
        exit();
    }
}

if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success">'.$_SESSION['success'].'</div>';
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-danger">'.$_SESSION['error'].'</div>';
    unset($_SESSION['error']);
}

// معالجة حفظ الجدول الدراسي
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['class_id'])) {
    $class_id = intval($_POST['class_id'] ?? 0);
    $period = $_POST['period'] ?? 'morning';
    $schedule = $_POST['schedule'] ?? [];

    if ($class_id && !empty($schedule)) {
        try {
            $pdo->beginTransaction();

            // حذف الجدول السابق
            $stmtDelete = $pdo->prepare("DELETE FROM schedules WHERE class_id = ? AND period = ?");
            $stmtDelete->execute([$class_id, $period]);

            // إدخال الجدول الجديد
            $stmtInsert = $pdo->prepare("INSERT INTO schedules (class_id, day, period_number, subject_name, period) VALUES (?, ?, ?, ?, ?)");
            
            foreach ($schedule as $day => $periods) {
                foreach ($periods as $periodNum => $subject) {
                    if (!empty(trim($subject))) {
                        $stmtInsert->execute([$class_id, $day, $periodNum, $subject, $period]);
                    }
                }
            }

            $pdo->commit();
            header('Location: directors.php?success=schedule_saved');
            exit();

        } catch (PDOException $e) {
            $pdo->rollBack();
            die("حدث خطأ أثناء حفظ الجدول: " . $e->getMessage());
        }
    } else {
        die("يرجى تعبئة كافة الحقول المطلوبة.");
    }
}

// جلب الجداول الدراسية الحالية
$schedules = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM schedules WHERE class_id IN (SELECT id FROM classes WHERE institution_id = ?)");
    $stmt->execute([$institution_id]);
    $schedules = $stmt->fetchAll();
} catch (PDOException $e) {
    die("حدث خطأ في جلب الجداول الدراسية: " . $e->getMessage());
}

// تنظيم الجداول الدراسية
$organized_schedules = [];
foreach ($schedules as $schedule) {
    if (!isset($organized_schedules[$schedule['class_id']])) {
        $organized_schedules[$schedule['class_id']] = [];
    }
    if (!isset($organized_schedules[$schedule['class_id']][$schedule['period']])) {
        $organized_schedules[$schedule['class_id']][$schedule['period']] = [];
    }
    if (!isset($organized_schedules[$schedule['class_id']][$schedule['period']][$schedule['day']])) {
        $organized_schedules[$schedule['class_id']][$schedule['period']][$schedule['day']] = [];
    }
    $organized_schedules[$schedule['class_id']][$schedule['period']][$schedule['day']][$schedule['period_number']] = $schedule['subject_name'];
}

// جلب الملفات
try {
    $stmt = $pdo->prepare("SELECT * FROM institution_files WHERE institution_id = ? ORDER BY upload_date DESC");
    $stmt->execute([$institution_id]);
    $files = $stmt->fetchAll();
} catch (PDOException $e) {
    die("حدث خطأ في جلب ملفات المؤسسة: " . $e->getMessage());
}

// جلب المستخدمين
try {
    $stmt = $pdo->prepare("SELECT id, fullname, email, user_type FROM users WHERE institution = ? ORDER BY fullname ASC");
    $stmt->execute([$institution_name]);
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    die("حدث خطأ في جلب المستخدمين: " . $e->getMessage());
}

// معالجة الرسائل
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $recipient_type = $_POST['messageRecipient'];
    $recipient_id = null;
    $error = null;

    if (in_array($recipient_type, ['specificTeacher', 'specificStudent'])) {
        $email = trim($_POST['recipientEmail'] ?? '');
        if ($email) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $recipient_id = $stmt->fetchColumn();
            if (!$recipient_id) {
                $error = "البريد الإلكتروني للمستلم غير موجود.";
            }
        } else {
            $error = "يرجى إدخال البريد الإلكتروني للمستلم المحدد.";
        }
    }

    $subject = trim($_POST['messageSubject'] ?? '');
    $content = trim($_POST['messageContent'] ?? '');

    if (!$error) {
        try {
            $stmt = $pdo->prepare("INSERT INTO messages (sender_id, recipient_type, recipient_id, subject, content, sent_at) 
                                 VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$_SESSION['user_id'], $recipient_type, $recipient_id, $subject, $content]);
            $_SESSION['message_success'] = "تم إرسال الرسالة بنجاح.";
        } catch (PDOException $e) {
            $error = "حدث خطأ أثناء إرسال الرسالة: " . $e->getMessage();
        }
    }
}

// جلب الرسائل الواردة
try {
    $stmt = $pdo->prepare("
        SELECT m.*, u.fullname AS sender_name 
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE 
          (
            (m.recipient_type = 'allTeachers' AND ? IN (SELECT id FROM users WHERE user_type = 'teacher' AND institution = ?))
            OR (m.recipient_type = 'allStudents' AND ? IN (SELECT id FROM users WHERE user_type = 'student' AND institution = ?))
            OR (m.recipient_type IN ('specificTeacher','specificStudent') AND m.recipient_id = ?)
          )
          AND m.is_deleted_recipient = 0
        ORDER BY m.sent_at DESC
    ");
    $stmt->execute([$_SESSION['user_id'], $institution_name, $_SESSION['user_id'], $institution_name, $_SESSION['user_id']]);
    $inboxMessages = $stmt->fetchAll();
} catch (PDOException $e) {
    die("حدث خطأ في جلب الرسائل الواردة: " . $e->getMessage());
}

// جلب الرسائل المرسلة
try {
    $stmt = $pdo->prepare("
        SELECT m.*, u.fullname AS recipient_name
        FROM messages m
        LEFT JOIN users u ON m.recipient_id = u.id
        WHERE m.sender_id = ? AND m.is_deleted_sender = 0
        ORDER BY m.sent_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $sentMessages = $stmt->fetchAll();
} catch (PDOException $e) {
    die("حدث خطأ في جلب الرسائل المرسلة: " . $e->getMessage());
}

// جلب الفصول الدراسية
try {
    $stmt = $pdo->prepare("SELECT * FROM classes WHERE institution_id = ?");
    $stmt->execute([$institution_id]);
    $classes = $stmt->fetchAll();
} catch (PDOException $e) {
    die("حدث خطأ في جلب الفصول الدراسية: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة المدير | MOROCCO LEARNING</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        /* Reset */
        * {
            margin: 0; padding: 0; box-sizing: border-box;
        }

        body, html {
            height: 100%;
            font-family: "Cairo", sans-serif;
            background: #1e1e2f;
            color: #eee;
            direction: rtl;
            overflow: hidden;
        }

        /* الحاوية الرئيسية */
        .container {
            display: flex;
            height: 100vh;
            max-width: 100%;
            margin: 0 auto;
            backdrop-filter: blur(12px);
            background: rgba(30, 30, 47, 0.9);
            box-shadow: 0 0 30px rgb(0, 255, 231, 0.3);
            overflow: hidden;
        }

        /* الشريط الجانبي */
        nav.sidebar {
            width: 300px;
            background: #121226;
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
            gap: 25px;
            position: fixed;
            height: 100%;
            z-index: 100;
        }

        nav.sidebar h2 {
            font-weight: 700;
            font-size: 1.8rem;
            color: #00ffe7;
            margin-bottom: 20px;
            user-select: none;
            text-align: center;
        }

        nav.sidebar a {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: #ccc;
            font-weight: 600;
            font-size: 1.1rem;
            padding: 12px 15px;
            border-radius: 10px;
            transition: all 0.3s ease;
            user-select: none;
        }

        nav.sidebar a i {
            font-size: 1.3rem;
            color: #00ffe7;
            transition: all 0.3s ease;
        }

        nav.sidebar a:hover,
        nav.sidebar a.active {
            background: #00ffe7;
            color: #121226;
            transform: translateX(-5px);
        }

        nav.sidebar a:hover i,
        nav.sidebar a.active i {
            color: #121226;
        }

        /* المحتوى الرئيسي */
        main.content {
            flex-grow: 1;
            padding: 40px 60px;
            overflow-y: auto;
            margin-right: 300px;
            width: calc(100% - 300px);
        }

        main.content h1 {
            font-weight: 700;
            font-size: 2.2rem;
            margin-bottom: 30px;
            color: #00ffe7;
            user-select: none;
            border-bottom: 2px solid #00ffe7;
            padding-bottom: 10px;
            position: relative;
        }

        main.content h1::after {
            content: '';
            position: absolute;
            bottom: -5px;
            right: 0;
            width: 100px;
            height: 3px;
            background: linear-gradient(90deg, #00ffe7, transparent);
            border-radius: 3px;
        }

        /* أقسام المحتوى */
        section {
            background: rgba(255, 255, 255, 0.05);
            padding: 25px 30px;
            border-radius: 15px;
            box-shadow: 0 0 15px rgb(0, 255, 231, 0.2);
            min-height: 300px;
            margin-bottom: 30px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        section::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 5px;
            height: 100%;
            background: #00ffe7;
        }

        section:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgb(0, 255, 231, 0.3);
        }

        /* نماذج الإدخال */
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #00ffe7;
        }

        input[type="text"], 
        input[type="number"], 
        input[type="email"], 
        input[type="password"], 
        input[type="date"],
        input[type="file"],
        select, 
        textarea {
            width: 100%;
            padding: 12px 15px;
            margin: 5px 0 15px 0;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            background: rgba(255,255,255,0.05);
            color: #eee;
            font-family: 'Cairo', sans-serif;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        input:focus, 
        select:focus, 
        textarea:focus {
            outline: none;
            border-color: #00ffe7;
            box-shadow: 0 0 0 2px rgba(0, 255, 231, 0.2);
            background: rgba(255,255,255,0.1);
        }

        /* الأزرار */
        .btn {
            display: inline-block;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            font-family: 'Cairo', sans-serif;
        }

        .btn-primary {
            background: #00ffe7;
            color: #121226;
        }

        .btn-primary:hover {
            background: #00c5b9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 255, 231, 0.3);
        }

        .btn-secondary {
            background: rgba(255,255,255,0.1);
            color: #eee;
        }

        .btn-secondary:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-2px);
        }

        .btn-danger {
            background: #ff4757;
            color: white;
        }

        .btn-danger:hover {
            background: #ff6b81;
            transform: translateY(-2px);
        }

        /* الجداول */
        .table-responsive {
            overflow-x: auto;
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            min-width: 800px;
        }

        th, td {
            padding: 15px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        th {
            background: rgba(0, 255, 231, 0.1);
            color: #00ffe7;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 1px;
        }

        td {
            background: rgba(255, 255, 255, 0.03);
            color: #eee;
        }

        tr:hover td {
            background: rgba(0, 255, 231, 0.05);
            color: #00ffe7;
        }

        /* الرسائل */
        textarea {
            resize: vertical;
            min-height: 150px;
        }

        /* تخصيص شريط التمرير */
        ::-webkit-scrollbar {
            width: 12px;
            height: 12px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(#00ffe7, #00c5b9);
            border-radius: 10px;
            box-shadow: inset 0 0 6px rgba(0,0,0,0.3);
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(#00c5b9, #00ffe7);
        }

        /* راديو وبوكس */
        .radio-group, .checkbox-group {
            margin: 15px 0;
        }

        .radio-group label, .checkbox-group label {
            display: inline-flex;
            align-items: center;
            margin-left: 15px;
            cursor: pointer;
        }

        input[type="radio"], 
        input[type="checkbox"] {
            width: auto;
            margin-left: 8px;
        }

        /* كروت */
        .cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .card {
            background: rgba(255,255,255,0.05);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border-left: 3px solid #00ffe7;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 255, 231, 0.2);
        }

        .card-title {
            font-size: 1.2rem;
            margin-bottom: 10px;
            color: #00ffe7;
            font-weight: 700;
        }

        .card-value {
            font-size: 2rem;
            font-weight: 700;
            margin: 10px 0;
        }

        /* التنبيهات */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }

        .alert-success {
            background: rgba(46, 213, 115, 0.2);
            border-left: 4px solid #2ed573;
            color: #2ed573;
        }

        .alert-warning {
            background: rgba(255, 165, 0, 0.2);
            border-left: 4px solid #ffa500;
            color: #ffa500;
        }

        .alert-danger {
            background: rgba(255, 71, 87, 0.2);
            border-left: 4px solid #ff4757;
            color: #ff4757;
        }

        .alert i {
            margin-left: 10px;
            font-size: 1.2rem;
        }

        /* التاريخ */
        .date-picker {
            position: relative;
        }

        .date-picker i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #00ffe7;
            pointer-events: none;
        }

        /* التبويبات */
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .tab.active {
            border-bottom-color: #00ffe7;
            color: #00ffe7;
        }

        .tab:hover:not(.active) {
            color: #00ffe7;
            background: rgba(0, 255, 231, 0.05);
        }

        /* الأيقونات */
        .icon-box {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 255, 231, 0.1);
            color: #00ffe7;
            font-size: 1.5rem;
            margin-left: 15px;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .container {
                flex-direction: column;
            }
            
            nav.sidebar {
                width: 100%;
                height: auto;
                position: relative;
                padding: 15px;
            }
            
            main.content {
                margin-right: 0;
                width: 100%;
                padding: 20px;
            }
            
            nav.sidebar a {
                padding: 10px;
                font-size: 0.9rem;
            }
        }
            nav {
            max-height: 100vh; /* باش ما يفوتش ارتفاع الشاشة */
            overflow-y: auto;  /* يفعّل التمرير العمودي */
        }

  select:focus {
    outline: none;
    box-shadow: 0 0 5px #1e1e2f;
  }

  option {
    background-color: #1e1e2f; /* خلفية داكنة */
    color: white; /* كتابة بيضاء */
  }
  /* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(5px);
}

.modal-content {
    background-color: #1e1e2f;
    margin: 10% auto;
    padding: 25px;
    border: 1px solid #00ffe7;
    border-radius: 10px;
    width: 400px;
    box-shadow: 0 0 20px rgba(0, 255, 231, 0.3);
    text-align: center;
}

.modal-buttons {
    margin-top: 20px;
    display: flex;
    justify-content: center;
    gap: 15px;
}

.modal-buttons button {
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold;
}

.modal-buttons .confirm-btn {
    background-color: #ff4757;
    color: white;
    border: none;
}

.modal-buttons .cancel-btn {
    background-color: #2f3542;
    color: white;
    border: none;
}
    </style>
</head>
<body>
    <div class="container">
        <nav class="sidebar" aria-label="القائمة الرئيسية">
            <h2>لوحة المدير</h2>
            <a href="#" class="active" onclick="showSection('institutionInfo')"><i class="fa-solid fa-school"></i> معلومات المؤسسة</a>
            <a href="#" onclick="showSection('teachersList')"><i class="fa-solid fa-chalkboard-teacher"></i> الأساتذة</a>
            <a href="#" onclick="showSection('studentsList')"><i class="fa-solid fa-user-graduate"></i> التلاميذ</a>
            <a href="#" onclick="showSection('addUser')"><i class="fa-solid fa-user-plus"></i> إضافة مستخدم</a>
            <a href="#" onclick="showSection('classSchedule')"><i class="fa-solid fa-calendar-alt"></i> جدول الحصص</a>
            <a href="#" onclick="showSection('reports')"><i class="fa-solid fa-chart-line"></i> تقارير ونتائج</a>
            <a href="#" onclick="showSection('settings')"><i class="fa-solid fa-cog"></i> الإعدادات</a>
            <a href="#" onclick="showSection('institutionFiles')"><i class="fa-solid fa-folder"></i> ملفات المؤسسة</a>
            <a href="#" onclick="showSection('userPermissions')"><i class="fa-solid fa-user-shield"></i> صلاحيات المستخدمين</a>
            <a href="#" onclick="showSection('messages')"><i class="fa-solid fa-paper-plane"></i> رسائل</a>
            <a href="loginout_directors.php"><i class="fa-solid fa-sign-out-alt"></i> تسجيل الخروج</a>
        </nav>

        <main class="content">
<!-- معلومات المؤسسة -->
<section id="institutionInfo" style="display: block;">
    <h1>معلومات المؤسسة</h1>
    
    <div class="alert alert-success">
        <i class="fas fa-info-circle"></i>
        <span>يمكنك هنا تحديث معلومات مؤسستك التعليمية</span>
    </div>
    
    <form method="post" action="update_institution.php" enctype="multipart/form-data">
            
        <div class="form-group">
            <label for="name">اسم المدير :</label>
            <input type="text" id="name" name="nname" value="<?= htmlspecialchars($director['name'] ?? '') ?>" readonly>
        </div>

        <div class="form-group">
            <label for="institutionName">اسم المؤسسة :</label>
            <input type="text" id="institutionName" name="institution_name" value="<?= htmlspecialchars($director['institution_name'] ?? '') ?>" readonly>
        </div>

        <div class="form-group">
            <label for="type"> نوع المؤسسة :</label>
            <input type="text" id="type" name="type" value="<?= htmlspecialchars($director['type'] ?? '') ?>" readonly>
        </div>

        <div class="form-group">
            <label for="affiliation">الإنتماء :</label>
            <input type="text" id="affiliation" name="affiliation" value="<?= htmlspecialchars($director['affiliation'] ?? '') ?>" readonly>
        </div>


        <div class="form-group">
            <label for="city">المدينة :</label>
            <input type="text" id="city" name="city" value="<?= htmlspecialchars($director['city'] ?? '') ?>" readonly>
        </div>
        
        <div class="form-group">
            <label for="address">المقاطعة :</label>
            <input type="text" id="address" name="address" value="<?= htmlspecialchars($director['province'] ?? '') ?>" readonly>
        </div>
        
        <div class="form-group">
            <label for="identificationNumber">الرقم التعريفي :</label>
            <input type="text" id="identificationNumber" name="access_code" value="<?= htmlspecialchars($director['access_code'] ?? '') ?>" readonly>
        </div>
        
        <div class="form-group">
            <label for="institutionLogo">شعار المؤسسة :</label>
            <input type="file" id="institutionLogo" name="institution_logo" accept="image/*">
        </div>
        
        <button type="submit" class="btn btn-primary">حفظ التعديلات</button>
    </form>
</section>

            <!-- قائمة الأساتذة -->
            <section id="teachersList" style="display: none;">
                <h1>الأساتذة</h1>

                <div class="cards-container">
                    <div class="card">
                        <div class="card-title">عدد الأساتذة</div>
                        <div class="card-value"><?= $total_teachers ?></div>
                        <div>أستاذ نشط</div>
                    </div>

                    <div class="card">
                        <div class="card-title">أساتذة جدد</div>
                        <div class="card-value"><?= $new_teachers_this_month ?></div>
                        <div>هذا الشهر</div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>اسم الأستاذ</th>
                                <th>البريد الإلكتروني</th>
                                <th>رقم الهاتف</th>
                                <th>التخصص</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($teachers)): ?>
                                <?php foreach ($teachers as $index => $teacher): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($teacher['name']) ?></td>
                                        <td><?= htmlspecialchars($teacher['email']) ?></td>
                                        <td><?= htmlspecialchars($teacher['phone']) ?></td>
                                        <td><?= htmlspecialchars($teacher['specialty']) ?></td>
                                        <td>
                                            <button class="btn btn-primary btn-sm">تعديل</button>
                                            <button class="btn btn-danger btn-sm">حذف</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center;">لا يوجد أساتذة حالياً</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="form-group">
                    <button class="btn btn-primary" onclick="showSection('addUser')">
                        <i class="fas fa-plus"></i> إضافة أستاذ جديد
                    </button>
                </div>
            </section>

            <!-- قائمة التلاميذ -->
            <section id="studentsList" style="display: none;">
                <h1>التلاميذ</h1>

                <div class="tabs">
                    <div class="tab active" onclick="filterStudents('all')">الكل</div>
                    <div class="tab" onclick="filterStudents('primary')">ابتدائي</div>
                    <div class="tab" onclick="filterStudents('middle')">إعدادي</div>
                    <div class="tab" onclick="filterStudents('secondary')">ثانوي</div>
                </div>

                <div class="form-group">
                    <input type="text" placeholder="ابحث عن تلميذ..." id="studentSearch" onkeyup="searchStudents()">
                </div>

                <div class="table-responsive">
                    <table id="studentsTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>اسم التلميذ</th>
                                <th>رقم البطاقة الوطنية</th>
                                <th>رقم مسار</th>
                                <th>المستوى</th>
                                <th>نقاط السلوك</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($students)): ?>
                                <?php foreach ($students as $index => $student): ?>
                                    <?php
                                        // تصنيف المستوى عربي اختياري (حسب قيمتك)
                                        $level = htmlspecialchars($student['education_level']);
                                        
                                        // نقاط السلوك
                                        $points = isset($student['points']) ? (int)$student['points'] : 0;
                                        $badge_class = 'bg-secondary';
                                        if ($points >= 90) $badge_class = 'bg-primary';
                                        elseif ($points >= 75) $badge_class = 'bg-success';
                                        elseif ($points >= 50) $badge_class = 'bg-warning';
                                        elseif ($points > 0) $badge_class = 'bg-danger';

                                        // ترجمة نقاط إلى وصف
                                        if ($points >= 90) $points_text = 'ممتاز';
                                        elseif ($points >= 75) $points_text = 'جيد جدا';
                                        elseif ($points >= 50) $points_text = 'جيد';
                                        elseif ($points > 0) $points_text = 'مقبول';
                                        else $points_text = 'لا يوجد تقييم';
                                    ?>
                                    <tr class="student-row" data-level="<?= strtolower($level) ?>">
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($student['fullname']) ?></td>
                                        <td><?= htmlspecialchars($student['cne']) ?></td>
                                        <td><?= htmlspecialchars($student['massar']) ?></td>
                                        <td><?= $level ?></td>
                                        <td><span class="badge <?= $badge_class ?>"><?= $points_text ?></span></td>
                                        <td>
                                            <button class="btn btn-primary btn-sm">تعديل</button>
                                            <form method="post" action="delete_student.php" style="display:inline;">
                                                <input type="hidden" name="user_id" value="<?= $student['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('هل أنت متأكد من الحذف؟')">حذف</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="7" style="text-align: center;">لا يوجد تلاميذ حالياً</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="form-group">
                    <button class="btn btn-primary" onclick="showSection('addUser')">
                        <i class="fas fa-plus"></i> إضافة تلميذ جديد
                    </button>
                </div>
            </section>

            <!-- إضافة مستخدم -->
            <section id="addUser" style="display: none;">
                <h1>إضافة مستخدم</h1>

               <form method="post" enctype="multipart/form-data" id="userForm">
    <input type="hidden" name="form_type" value="add_user">
    
    <div class="form-group">
        <label for="userType">نوع المستخدم:</label>
        <select id="userType" name="user_type" class="form-control" required>
            <option value="teacher">أستاذ</option>
            <option value="student">تلميذ</option>
        </select>
    </div>

    <!-- حقول الأستاذ -->
    <div id="teacherFields">
        <div class="form-group">
            <label for="fullname">الاسم الكامل:</label>
            <input type="text" id="fullname" name="fullname" required>
        </div>

        <div class="form-group">
            <label for="email">البريد الإلكتروني:</label>
            <input type="email" id="email" name="email" required>
        </div>

        <div class="form-group">
            <label for="specialty">التخصص:</label>
            <input type="text" id="specialty" name="specialty" required>
        </div>

        <div class="form-group">
            <label for="education_level">المؤهل العلمي:</label>
            <select id="education_level" name="education_level" required>
                <option value="bachelor">إجازة</option>
                <option value="master">ماجستير</option>
                <option value="phd">دكتوراه</option>
            </select>
        </div>

        <div class="form-group">
            <label for="phone">الهاتف:</label>
            <input type="text" id="phone" name="phone" required>
        </div>
    </div>

    <!-- حقول التلميذ -->
    <div id="studentFields" style="display:none;">
        <div class="form-group">
            <label for="student_fullname">الاسم الكامل:</label>
            <input type="text" id="student_fullname" name="fullname" required>
        </div>

        <div class="form-group">
            <label for="student_email">البريد الإلكتروني:</label>
            <input type="email" id="student_email" name="email">
        </div>

        <div class="form-group">
            <label for="massar">رقم مسار:</label>
            <input type="text" id="massar" name="massar" required>
        </div>

        <div class="form-group">
            <label for="cne">رقم البطاقة:</label>
            <input type="text" id="cne" name="cne" required>
        </div>

        <div class="form-group">
            <label for="student_education_level">المستوى:</label>
            <select id="student_education_level" name="education_level" required>
                <option value="primary">ابتدائي</option>
                <option value="middle">إعدادي</option>
                <option value="secondary">ثانوي</option>
            </select>
        </div>

        <div class="form-group">
            <label for="birthdate">تاريخ الميلاد:</label>
            <input type="date" id="birthdate" name="birthdate" required>
        </div>

        <div class="form-group">
            <label for="address">العنوان:</label>
            <textarea id="address" name="address" required></textarea>
        </div>

        <div class="form-group">
            <label for="photo">الصورة:</label>
            <input type="file" id="photo" name="photo" accept="image/*">
        </div>
    </div>

    <button type="submit" class="btn btn-primary">حفظ</button>
</form>
            </section>

            <!-- جدول الحصص -->
            <section id="classSchedule" style="display: none;">
                <h1>جدول الحصص</h1>

                <form method="post" action="directors.php">
                    <div class="form-group">
                        <label for="classSelect">اختر الفصل:</label>
                        <select id="classSelect" name="class_id" class="form-control" required>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?= $class['id'] ?>"><?= htmlspecialchars($class['class_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="periodSelect">الفترة:</label>
                        <select id="periodSelect" name="period" class="form-control" required onchange="updateScheduleTimes()">
                            <option value="morning">صباحية (8:30 - 12:30)</option>
                            <option value="evening">مسائية (2:30 - 6:30)</option>
                        </select>
                    </div>

                    <div class="table-responsive">
                        <table id="scheduleTable">
                            <thead>
                                <tr>
                                    <th>اليوم</th>
                                    <th>الحصة 1<br><span id="period1Time">8:30 - 9:30</span></th>
                                    <th>الحصة 2<br><span id="period2Time">9:30 - 10:30</span></th>
                                    <th>الحصة 3<br><span id="period3Time">10:45 - 11:45</span></th>
                                    <th>الحصة 4<br><span id="period4Time">11:45 - 12:30</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $days = ['الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة','السبت'];
                                foreach ($days as $day) {
                                    echo "<tr>";
                                    echo "<td>$day</td>";
                                    for ($i = 1; $i <= 4; $i++) {
                                        $value = '';
                                        if (isset($organized_schedules[$_POST['class_id'] ?? $classes[0]['id']][$_POST['period'] ?? 'morning'][$day][$i])) {
                                            $value = htmlspecialchars($organized_schedules[$_POST['class_id'] ?? $classes[0]['id']][$_POST['period'] ?? 'morning'][$day][$i]);
                                        }
                                        echo "<td><input type='text' name='schedule[$day][$i]' placeholder='المادة' class='form-control' value='$value'></td>";
                                    }
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="form-group mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> حفظ الجدول
                        </button>
                        <button type="button" onclick="clearSchedule()" class="btn btn-secondary">
                            <i class="fas fa-plus"></i> إضافة جدول جديد
                        </button>
                    </div>
                </form>
            </section>

            <!-- تقارير ونتائج -->
            <section id="reports" style="display: none;">
                <h1>تقارير ونتائج</h1>

                <div class="cards-container">
                    <div class="card">
                        <div class="card-title">عدد التلاميذ</div>
                        <div class="card-value" id="studentsCount">0</div>
                        <div>تلميذ مسجل</div>
                    </div>

                    <div class="card">
                        <div class="card-title">النسبة المئوية</div>
                        <div class="card-value" id="successRate">0%</div>
                        <div>معدل النجاح</div>
                    </div>

                    <div class="card">
                        <div class="card-title">أعلى معدل</div>
                        <div class="card-value" id="topGrade">0.00</div>
                        <div>في الصف الثالث ثانوي</div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="reportType">نوع التقرير:</label>
                    <select id="reportType" class="form-control">
                        <option value="grades">النتائج الدراسية</option>
                        <option value="attendance">الحضور والغياب</option>
                        <option value="behavior">سلوك التلاميذ</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="reportPeriod">الفترة:</label>
                    <select id="reportPeriod" class="form-control">
                        <option value="monthly">شهري</option>
                        <option value="quarterly">ربع سنوي</option>
                        <option value="yearly">سنوي</option>
                    </select>
                </div>

                <div class="form-group">
                    <button class="btn btn-primary" onclick="exportReport()">
                        <i class="fas fa-download"></i> تصدير التقرير
                    </button>
                </div>
            </section>

            <!-- الإعدادات -->
            <section id="settings" style="display: none;">
                <h1>الإعدادات</h1>

                <div class="tabs">
                    <div class="tab active" onclick="showSettingsTab('account')">حسابي</div>
                    <div class="tab" onclick="showSettingsTab('security')">الأمان</div>
                    <div class="tab" onclick="showSettingsTab('notifications')">الإشعارات</div>
                </div>

                <!-- حسابي -->
                <div id="accountSettings">
                    <form method="post" enctype="multipart/form-data" action="update_account.php">
                        <div class="form-group">
                            <label for="username">اسم المستخدم:</label>
                            <input type="text" id="username" name="username" placeholder="اسم المستخدم" required>
                        </div>

                        <div class="form-group">
                            <label for="email">البريد الإلكتروني:</label>
                            <input type="email" id="email" name="email" placeholder="البريد الإلكتروني" required>
                        </div>

                        <div class="form-group">
                            <label for="profilePhoto">صورة الملف الشخصي:</label>
                            <input type="file" id="profilePhoto" name="profilePhoto" accept="image/*">
                        </div>

                        <div class="form-group">
                            <button type="submit" name="update_account" class="btn btn-primary">
                                <i class="fas fa-save"></i> حفظ التغييرات
                            </button>
                        </div>
                    </form>
                </div>

                <!-- الأمان -->
                <div id="securitySettings" style="display: none;">
                    <form method="post" action="update_password.php">
                        <div class="form-group">
                            <label for="currentPassword">كلمة المرور الحالية:</label>
                            <input type="password" id="currentPassword" name="currentPassword" placeholder="كلمة المرور الحالية" required>
                        </div>

                        <div class="form-group">
                            <label for="newPassword">كلمة المرور الجديدة:</label>
                            <input type="password" id="newPassword" name="newPassword" placeholder="كلمة المرور الجديدة" required>
                        </div>

                        <div class="form-group">
                            <label for="confirmNewPassword">تأكيد كلمة المرور الجديدة:</label>
                            <input type="password" id="confirmNewPassword" name="confirmNewPassword" placeholder="تأكيد كلمة المرور" required>
                        </div>

                        <div class="form-group">
                            <button type="submit" name="update_password" class="btn btn-primary">
                                <i class="fas fa-lock"></i> تحديث كلمة المرور
                            </button>
                        </div>
                    </form>
                </div>

                <!-- الإشعارات -->
                <div id="notificationsSettings" style="display: none;">
                    <form method="post" action="update_notifications.php">
                        <div class="form-group">
                            <label>إعدادات الإشعارات:</label>
                            <div class="checkbox-group">
                                <label>
                                    <input type="checkbox" name="notify_email" checked> إشعارات البريد الإلكتروني
                                </label>
                                <label>
                                    <input type="checkbox" name="notify_system" checked> إشعارات النظام
                                </label>
                                <label>
                                    <input type="checkbox" name="notify_phone"> إشعارات الهاتف
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" name="update_notifications" class="btn btn-primary">
                                <i class="fas fa-bell"></i> حفظ الإعدادات
                            </button>
                        </div>
                    </form>
                </div>
            </section>

<!-- ملفات المؤسسة -->
<section id="institutionFiles" style="display: block;">
    <h1>ملفات المؤسسة</h1>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?= $_SESSION['success_message'] ?>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <?= $_SESSION['error_message'] ?>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i>
        <span>يمكنك هنا رفع وإدارة ملفات المؤسسة التعليمية</span>
    </div>

    <form method="POST" action="upload_file.php" enctype="multipart/form-data">
        <div class="form-group">
            <label for="fileUpload">رفع ملف جديد:</label>
            <input type="file" name="fileUpload" id="fileUpload" required>
        </div>

        <div class="form-group">
            <label for="fileCategory">تصنيف الملف:</label>
            <select name="fileCategory" id="fileCategory" class="form-control" required>
                <option value="إداري">إداري</option>
                <option value="تعليمي">تعليمي</option>
                <option value="مالي">مالي</option>
                <option value="أخرى">أخرى</option>
            </select>
        </div>

        <div class="form-group">
            <label for="fileDescription">وصف الملف:</label>
            <textarea name="fileDescription" id="fileDescription" placeholder="أدخل وصفًا للملف"></textarea>
        </div>

        <div class="form-group">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-upload"></i> رفع الملف
            </button>
        </div>
    </form>

    <h2>الملفات المرفوعة:</h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>اسم الملف</th>
                    <th>النوع</th>
                    <th>الحجم (KB)</th>
                    <th>تاريخ الرفع</th>
                    <th>الوصف</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($files as $file): ?>
                <tr>
                    <td>
                        <?php
                        $ext = pathinfo($file['file_name'], PATHINFO_EXTENSION);
                        // أيقونة حسب نوع الملف
                        if (in_array(strtolower($ext), ['pdf'])) {
                            echo '<i class="fas fa-file-pdf"></i> ';
                        } elseif (in_array(strtolower($ext), ['doc', 'docx'])) {
                            echo '<i class="fas fa-file-word"></i> ';
                        } elseif (in_array(strtolower($ext), ['xls', 'xlsx'])) {
                            echo '<i class="fas fa-file-excel"></i> ';
                        } else {
                            echo '<i class="fas fa-file"></i> ';
                        }
                        echo htmlspecialchars($file['file_name']);
                        ?>
                    </td>
                    <td><?= htmlspecialchars($file['file_type']) ?></td>
                    <td><?= round($file['file_size'] / 1024, 2) ?></td>
                    <td><?= $file['upload_date'] ?></td>
                    <td><?= htmlspecialchars($file['description']) ?></td>
                    <td>
                        <a href="<?= htmlspecialchars($file['file_path']) ?>" download class="btn btn-primary btn-sm" title="تحميل">
                            <i class="fas fa-download"></i>
                        </a>
                        <button type="button" class="btn btn-danger btn-sm" title="حذف" 
                                onclick="showDeleteModal(<?= $file['id'] ?>, '<?= htmlspecialchars(addslashes($file['file_name'])) ?>')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($files)) : ?>
                <tr>
                    <td colspan="6" style="text-align:center;">لا توجد ملفات مرفوعة بعد.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- Modal Structure -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <h3 style="color: #00ffe7; margin-bottom: 20px;">تأكيد الحذف</h3>
        <p id="deleteConfirmationText">هل أنت متأكد من أنك تريد حذف هذا الملف؟</p>
        <div class="modal-buttons">
            <button class="cancel-btn" onclick="closeModal()">إلغاء</button>
            <form id="deleteForm" method="POST" action="delete_file.php" style="display: inline;">
                <input type="hidden" name="file_id" id="fileIdToDelete">
                <button type="submit" class="confirm-btn">حذف</button>
            </form>
        </div>
    </div>
</div>

<script>
function showDeleteModal(fileId, fileName) {
    document.getElementById('fileIdToDelete').value = fileId;
    document.getElementById('deleteConfirmationText').textContent = 
        `هل أنت متأكد من أنك تريد حذف الملف: ${fileName}؟`;
    document.getElementById('deleteModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    if (event.target === document.getElementById('deleteModal')) {
        closeModal();
    }
});
</script>
            <!-- صلاحيات المستخدمين -->
            <section id="userPermissions" style="display: block;">
                <h1>صلاحيات المستخدمين</h1>

                <div class="form-group">
                    <label for="userSearch">ابحث عن مستخدم:</label>
                    <input type="text" id="userSearch" placeholder="ابحث بالاسم أو البريد الإلكتروني" onkeyup="filterUsers()">
                </div>

                <div class="table-responsive">
                    <table id="usersTable">
                        <thead>
                            <tr>
                                <th>اسم المستخدم</th>
                                <th>البريد الإلكتروني</th>
                                <th>نوع المستخدم</th>
                                <th>الصلاحيات</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <form method="POST" action="update_permission.php">
                                    <td><?= htmlspecialchars($user['fullname']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td><?= htmlspecialchars($user['user_type']) ?></td>
                                    <td>
                                        <select name="permission_level" class="form-control" required>
                                            <option value="admin">مدير</option>
                                            <option value="teacher">أستاذ</option>
                                            <option value="student">تلميذ</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" class="btn btn-primary btn-sm">حفظ</button>
                                    </td>
                                </form>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="5" style="text-align:center;">لا توجد بيانات مستخدمين.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- قسم الرسائل -->
            <section id="messages">
                <h1>رسائل</h1>
                
                <div class="tabs">
                    <div class="tab active" onclick="showMessagesTab('inbox')">الوارد</div>
                    <div class="tab" onclick="showMessagesTab('sent')">المرسلة</div>
                    <div class="tab" onclick="showMessagesTab('compose')">رسالة جديدة</div>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php elseif (!empty($success)): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                
                <div id="inboxMessages">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>المرسل</th>
                                    <th>الموضوع</th>
                                    <th>التاريخ</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inboxMessages as $msg): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($msg['sender_name']) ?></td>
                                        <td><?= htmlspecialchars($msg['subject']) ?></td>
                                        <td><?= htmlspecialchars($msg['sent_at']) ?></td>
                                        <td>
                                            <a href="view_message.php?id=<?= $msg['id'] ?>" class="btn btn-primary btn-sm">عرض</a>
                                            <a href="delete_message.php?id=<?= $msg['id'] ?>&type=recipient" class="btn btn-danger btn-sm">حذف</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (count($inboxMessages) === 0): ?>
                                    <tr><td colspan="4" style="text-align:center;">لا توجد رسائل واردة</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div id="sentMessages" style="display: none;">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>المستلم</th>
                                    <th>الموضوع</th>
                                    <th>التاريخ</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sentMessages as $msg): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($msg['recipient_name'] ?: 'جميع المستلمين') ?></td>
                                        <td><?= htmlspecialchars($msg['subject']) ?></td>
                                        <td><?= htmlspecialchars($msg['sent_at']) ?></td>
                                        <td>
                                            <a href="view_message.php?id=<?= $msg['id'] ?>" class="btn btn-primary btn-sm">عرض</a>
                                            <a href="delete_message.php?id=<?= $msg['id'] ?>&type=sender" class="btn btn-danger btn-sm">حذف</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (count($sentMessages) === 0): ?>
                                    <tr><td colspan="4" style="text-align:center;">لا توجد رسائل مرسلة</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div id="composeMessage" style="display: none;">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="send_message" value="1">
                        <div class="form-group">
                            <label for="messageRecipient">إلى:</label>
                            <select id="messageRecipient" name="messageRecipient" class="form-control" onchange="toggleRecipientEmail()">
                                <option value="allTeachers">جميع الأساتذة</option>
                                <option value="allStudents">جميع التلاميذ</option>
                                <option value="specificTeacher">أستاذ محدد</option>
                                <option value="specificStudent">تلميذ محدد</option>
                            </select>
                        </div>
                        
                        <div class="form-group" id="specificRecipientGroup" style="display: none;">
                            <label for="recipientEmail">البريد الإلكتروني:</label>
                            <input type="email" id="recipientEmail" name="recipientEmail" placeholder="أدخل البريد الإلكتروني">
                        </div>
                        
                        <div class="form-group">
                            <label for="messageSubject">الموضوع:</label>
                            <input type="text" id="messageSubject" name="messageSubject" placeholder="أدخل موضوع الرسالة" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="messageContent">محتوى الرسالة:</label>
                            <textarea id="messageContent" name="messageContent" rows="8" placeholder="اكتب رسالتك هنا..." required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="messageAttachment">مرفقات:</label>
                            <input type="file" id="messageAttachment" name="messageAttachment">
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> إرسال
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // تفعيل منتقي التاريخ
        flatpickr("#birthDate", {
            dateFormat: "Y-m-d",
            allowInput: true,
            locale: "ar"
        });

        function showSection(sectionId) {
            // إخفاء جميع الأقسام
            const sections = document.querySelectorAll('main.content section');
            sections.forEach(section => {
                section.style.display = 'none';
            });

            // عرض القسم المطلوب
            document.getElementById(sectionId).style.display = 'block';

            // تحديث حالة الأزرار في الشريط الجانبي
            const links = document.querySelectorAll('nav.sidebar a');
            links.forEach(link => {
                link.classList.remove('active');
            });
            const activeLink = Array.from(links).find(link => link.textContent.includes(sectionId.charAt(0).toUpperCase() + sectionId.slice(1)));
            if (activeLink) {
                activeLink.classList.add('active');
            }
        }

        function toggleUserFields() {
            const userType = document.getElementById('userType').value;
            const teacherFields = document.getElementById('teacherFields');
            const studentFields = document.getElementById('studentFields');

            if (userType === 'teacher') {
                teacherFields.style.display = 'block';
                studentFields.style.display = 'none';
            } else {
                teacherFields.style.display = 'none';
                studentFields.style.display = 'block';
            }
        }

        function filterStudents(filter) {
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            event.target.classList.add('active');
            // هنا يمكنك إضافة كود تصفية التلاميذ حسب النوع
        }

        function showSettingsTab(tab) {
            document.getElementById('accountSettings').style.display = 'none';
            document.getElementById('securitySettings').style.display = 'none';
            document.getElementById('notificationsSettings').style.display = 'none';
            
            document.getElementById(tab + 'Settings').style.display = 'block';
            
            const tabs = document.querySelectorAll('#settings .tab');
            tabs.forEach(t => t.classList.remove('active'));
            event.target.classList.add('active');
        }

        function showMessagesTab(tab) {
            document.getElementById('inboxMessages').style.display = 'none';
            document.getElementById('sentMessages').style.display = 'none';
            document.getElementById('composeMessage').style.display = 'none';
            
            document.getElementById(tab + 'Messages').style.display = 'block';
            
            const tabs = document.querySelectorAll('#messages .tab');
            tabs.forEach(t => t.classList.remove('active'));
            event.target.classList.add('active');
        }

        // تغيير عرض حقول المستلم عند تغيير نوع المرسل إليه
        document.getElementById('messageRecipient').addEventListener('change', function() {
            const specificRecipientGroup = document.getElementById('specificRecipientGroup');
            if (this.value === 'specificTeacher' || this.value === 'specificStudent') {
                specificRecipientGroup.style.display = 'block';
            } else {
                specificRecipientGroup.style.display = 'none';
            }
        });

        // فلترة التلاميذ حسب المستوى
        function filterStudents(level) {
            const rows = document.querySelectorAll('#studentsTable tbody tr.student-row');
            rows.forEach(row => {
                if (level === 'all' || row.dataset.level === level) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });

            // تفعيل علامة التبويب المختارة
            document.querySelectorAll('.tabs .tab').forEach(tab => tab.classList.remove('active'));
            document.querySelector(`.tabs .tab[onclick="filterStudents('${level}')"]`).classList.add('active');
        }

        // بحث نصي في التلاميذ
        function searchStudents() {
            const input = document.getElementById('studentSearch').value.toLowerCase();
            const rows = document.querySelectorAll('#studentsTable tbody tr.student-row');
            rows.forEach(row => {
                const name = row.cells[1].textContent.toLowerCase();
                if (name.includes(input)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function clearSchedule() {
            document.querySelectorAll("input[type='text']").forEach(input => input.value = "");
        }

        function loadReport() {
            const type = document.getElementById('reportType').value;
            const period = document.getElementById('reportPeriod').value;

            fetch(`fetch_reports.php?type=${type}&period=${period}`)
                .then(res => res.json())
                .then(data => {
                    document.getElementById('studentsCount').innerText = data.students_count ?? '0';
                    document.getElementById('successRate').innerText = (data.success_rate ?? '0') + '%';
                    document.getElementById('topGrade').innerText = data.top_grade ?? '0.00';
                });
        }

        document.getElementById('reportType').addEventListener('change', loadReport);
        document.getElementById('reportPeriod').addEventListener('change', loadReport);

        function exportReport() {
            const type = document.getElementById('reportType').value;
            const period = document.getElementById('reportPeriod').value;
            window.location.href = `export_report.php?type=${type}&period=${period}`;
        }

        // تحميل البيانات افتراضياً عند فتح الصفحة
        loadReport();

        function filterUsers() {
            const input = document.getElementById("userSearch");
            const filter = input.value.toLowerCase();
            const table = document.getElementById("usersTable");
            const trs = table.getElementsByTagName("tr");

            for (let i = 1; i < trs.length; i++) { // تخطي رأس الجدول
                const tds = trs[i].getElementsByTagName("td");
                if (tds.length > 0) {
                    const username = tds[0].textContent.toLowerCase();
                    const email = tds[1].textContent.toLowerCase();
                    if (username.indexOf(filter) > -1 || email.indexOf(filter) > -1) {
                        trs[i].style.display = "";
                    } else {
                        trs[i].style.display = "none";
                    }
                }
            }
        }

        // تحديث أوقات الحصص حسب الفترة
        function updateScheduleTimes() {
            const period = document.getElementById('periodSelect').value;
            const period1 = document.getElementById('period1Time');
            const period2 = document.getElementById('period2Time');
            const period3 = document.getElementById('period3Time');
            const period4 = document.getElementById('period4Time');

            if (period === 'morning') {
                period1.textContent = '8:30 - 9:30';
                period2.textContent = '9:30 - 10:30';
                period3.textContent = '10:45 - 11:45';
                period4.textContent = '11:45 - 12:30';
            } else {
                period1.textContent = '2:30 - 3:30';
                period2.textContent = '3:30 - 4:30';
                period3.textContent = '4:45 - 5:45';
                period4.textContent = '5:45 - 6:30';
            }
        }

        // تحميل الجدول عند تغيير الفصل أو الفترة
        document.getElementById('classSelect').addEventListener('change', function() {
            document.querySelector('form').submit();
        });

        document.getElementById('periodSelect').addEventListener('change', function() {
            document.querySelector('form').submit();
        });
    </script>
</body>
</html>