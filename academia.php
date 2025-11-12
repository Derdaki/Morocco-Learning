<?php
session_start();

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'academy') {
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
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // جلب معلومات الأكاديمية الجهوية
    $stmt = $pdo->prepare("SELECT * FROM academies WHERE id = ?");
    $stmt->execute([$_SESSION['academy_id']]); // يجب استخدام academy_id بدلاً من user_id
    $academy = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$academy) {
        die("<div style='text-align:center; padding:20px;'>
                <h3 style='color:red'>خطأ في تحميل البيانات</h3>
                <p>لم يتم العثور على معلومات الأكاديمية الجهوية المسجلة لحسابك.</p>
                <p>الرجاء التأكد من أنك مسجل كمسؤول أكاديمية جهوية.</p>
                <a href='logout.php' style='color:blue'>تسجيل الخروج</a>
            </div>");
    }

    // جلب جميع المؤسسات التابعة للأكاديمية
    $stmt = $pdo->prepare("SELECT * FROM institutions WHERE academy_id = ?"); // تغيير regional_academy_id إلى academy_id
    $stmt->execute([$academy['id']]);
    $institutions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // جلب جميع الأساتذة التابعين للمؤسسات في هذه الأكاديمية
    $institution_ids = array_column($institutions, 'id');
    if (!empty($institution_ids)) {
        $placeholders = implode(',', array_fill(0, count($institution_ids), '?'));
        
        $stmt = $pdo->prepare("SELECT t.*, u.fullname, u.email, u.phone 
                              FROM teachers t
                              JOIN users u ON t.user_id = u.id
                              WHERE t.institution_id IN ($placeholders)");
        $stmt->execute($institution_ids);
        $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $teachers = [];
    }

    // جلب جميع التلاميذ التابعين للمؤسسات في هذه الأكاديمية
    $stmt = $pdo->prepare("
        SELECT s.*, u.fullname, u.email, u.phone, i.name as institution_name,
               sbp.points as behavior_points
        FROM students s
        JOIN users u ON s.user_id = u.id
        JOIN institutions i ON s.institution_id = i.id
        LEFT JOIN student_behavior_points sbp ON s.id = sbp.student_id
        WHERE i.academy_id = ?
    ");
    $stmt->execute([$academy['id']]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("<div style='text-align:center; padding:20px;'>
            <h3 style='color:red'>خطأ في النظام</h3>
            <p>حدث خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage() . "</p>
            <p>الرجاء المحاولة لاحقاً أو التواصل مع الدعم الفني.</p>
        </div>");
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة الأكاديمية الجهوية | MOROCCO LEARNING</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        /* نفس التصميم السابق مع تعديلات بسيطة */
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

        textarea {
            resize: vertical;
            min-height: 150px;
        }

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
            max-height: 100vh;
            overflow-y: auto;
        }

        select:focus {
            outline: none;
            box-shadow: 0 0 5px #1e1e2f;
        }

        option {
            background-color: #1e1e2f;
            color: white;
        }
        
        /* إضافة جديدة لعرض المؤسسات */
        .institution-card {
            background: rgba(0, 255, 231, 0.05);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #00ffe7;
        }
        
        .institution-name {
            font-size: 1.3rem;
            color: #00ffe7;
            margin-bottom: 10px;
        }
        
        .institution-details {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .institution-detail {
            flex: 1;
            min-width: 200px;
        }
        
        .detail-label {
            font-size: 0.9rem;
            color: #aaa;
        }
        
        .detail-value {
            font-size: 1.1rem;
            color: #eee;
        }
    </style>
</head>
<body>
    <div class="container">
        <nav class="sidebar" aria-label="القائمة الرئيسية">
            <h2>لوحة الأكاديمية الجهوية</h2>
            <a href="#" class="active" onclick="showSection('academyInfo')"><i class="fa-solid fa-school"></i> معلومات الأكاديمية</a>
            <a href="#" onclick="showSection('institutionsList')"><i class="fa-solid fa-building"></i> المؤسسات التعليمية</a>
            <a href="#" onclick="showSection('teachersList')"><i class="fa-solid fa-chalkboard-teacher"></i> الأساتذة</a>
            <a href="#" onclick="showSection('studentsList')"><i class="fa-solid fa-user-graduate"></i> التلاميذ</a>
            <a href="#" onclick="showSection('statistics')"><i class="fa-solid fa-chart-pie"></i> الإحصائيات</a>
            <a href="#" onclick="showSection('reports')"><i class="fa-solid fa-file-alt"></i> التقارير</a>
            <a href="#" onclick="showSection('settings')"><i class="fa-solid fa-cog"></i> الإعدادات</a>
            <a href="logout.php"><i class="fa-solid fa-sign-out-alt"></i> تسجيل الخروج</a>
        </nav>

        <main class="content">
            <!-- معلومات الأكاديمية -->
            <section id="academyInfo" style="display: block;">
                <h1>معلومات الأكاديمية الجهوية</h1>
                
                <div class="alert alert-success">
                    <i class="fas fa-info-circle"></i>
                    <span>معلومات الأكاديمية الجهوية للتربية والتكوين</span>
                </div>
                
                <div class="institution-card">
                    <div class="institution-name"><?= htmlspecialchars($academy['name']) ?></div>
                    <div class="institution-details">
                        <div class="institution-detail">
                            <div class="detail-label">الجهة:</div>
                            <div class="detail-value"><?= htmlspecialchars($academy['region']) ?></div>
                        </div>
                        <div class="institution-detail">
                            <div class="detail-label">المدير:</div>
                            <div class="detail-value"><?= htmlspecialchars($academy['director_name']) ?></div>
                        </div>
                        <div class="institution-detail">
                            <div class="detail-label">البريد الإلكتروني:</div>
                            <div class="detail-value"><?= htmlspecialchars($academy['email']) ?></div>
                        </div>
                        <div class="institution-detail">
                            <div class="detail-label">رقم الهاتف:</div>
                            <div class="detail-value"><?= htmlspecialchars($academy['phone']) ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="cards-container">
                    <div class="card">
                        <div class="card-title">عدد المؤسسات</div>
                        <div class="card-value"><?= count($institutions) ?></div>
                        <div>مؤسسة تعليمية</div>
                    </div>
                    
                    <div class="card">
                        <div class="card-title">عدد الأساتذة</div>
                        <div class="card-value"><?= count($teachers) ?></div>
                        <div>أستاذ</div>
                    </div>
                    
                    <div class="card">
                        <div class="card-title">عدد التلاميذ</div>
                        <div class="card-value"><?= count($students) ?></div>
                        <div>تلميذ</div>
                    </div>
                </div>
            </section>

            <!-- قائمة المؤسسات التعليمية -->
            <section id="institutionsList" style="display: none;">
                <h1>المؤسسات التعليمية</h1>
                
                <div class="form-group">
                    <input type="text" id="institutionSearch" placeholder="ابحث عن مؤسسة..." onkeyup="searchInstitutions()">
                </div>
                
                <div class="table-responsive">
                    <table id="institutionsTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>اسم المؤسسة</th>
                                <th>النوع</th>
                                <th>المدينة</th>
                                <th>عدد الأساتذة</th>
                                <th>عدد التلاميذ</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($institutions)): ?>
                                <?php foreach ($institutions as $index => $institution): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($institution['name']) ?></td>
                                        <td>
                                            <?php 
                                            $type = htmlspecialchars($institution['type']);
                                            echo $type == 'primary' ? 'ابتدائي' : 
                                                 ($type == 'middle' ? 'إعدادي' : 
                                                 ($type == 'secondary' ? 'ثانوي' : 'جامعي'));
                                            ?>
                                        </td>
                                        <td><?= htmlspecialchars($institution['city']) ?></td>
                                        <td>
                                            <?php 
                                            $teacher_count = array_reduce($teachers, function($carry, $teacher) use ($institution) {
                                                return $carry + ($teacher['institution_id'] == $institution['id'] ? 1 : 0);
                                            }, 0);
                                            echo $teacher_count;
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $student_count = array_reduce($students, function($carry, $student) use ($institution) {
                                                return $carry + ($student['institution_name'] == $institution['name'] ? 1 : 0);
                                            }, 0);
                                            echo $student_count;
                                            ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-primary btn-sm" onclick="viewInstitution(<?= $institution['id'] ?>)">عرض</button>
                                            <button class="btn btn-secondary btn-sm" onclick="editInstitution(<?= $institution['id'] ?>)">تعديل</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center;">لا توجد مؤسسات مسجلة</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- قائمة الأساتذة -->
            <section id="teachersList" style="display: none;">
                <h1>الأساتذة</h1>
                
                <div class="tabs">
                    <div class="tab active" onclick="filterTeachers('all')">الكل</div>
                    <div class="tab" onclick="filterTeachers('primary')">ابتدائي</div>
                    <div class="tab" onclick="filterTeachers('middle')">إعدادي</div>
                    <div class="tab" onclick="filterTeachers('secondary')">ثانوي</div>
                </div>
                
                <div class="form-group">
                    <input type="text" id="teacherSearch" placeholder="ابحث عن أستاذ..." onkeyup="searchTeachers()">
                </div>
                
                <div class="table-responsive">
                    <table id="teachersTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>اسم الأستاذ</th>
                                <th>البريد الإلكتروني</th>
                                <th>رقم الهاتف</th>
                                <th>التخصص</th>
                                <th>المؤسسة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($teachers)): ?>
                                <?php foreach ($teachers as $index => $teacher): ?>
                                    <?php
                                    $institution_name = '';
                                    foreach ($institutions as $inst) {
                                        if ($inst['id'] == $teacher['institution_id']) {
                                            $institution_name = $inst['name'];
                                            break;
                                        }
                                    }
                                    ?>
                                    <tr class="teacher-row" data-level="<?= htmlspecialchars($teacher['education_level']) ?>">
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($teacher['name']) ?></td>
                                        <td><?= htmlspecialchars($teacher['email']) ?></td>
                                        <td><?= htmlspecialchars($teacher['phone']) ?></td>
                                        <td><?= htmlspecialchars($teacher['specialty']) ?></td>
                                        <td><?= htmlspecialchars($institution_name) ?></td>
                                        <td>
                                            <button class="btn btn-primary btn-sm">تعديل</button>
                                            <button class="btn btn-danger btn-sm">حذف</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center;">لا يوجد أساتذة مسجلين</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
                    <input type="text" id="studentSearch" placeholder="ابحث عن تلميذ..." onkeyup="searchStudents()">
                </div>
                
                <div class="table-responsive">
                    <table id="studentsTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>اسم التلميذ</th>
                                <th>رقم البطاقة الوطنية</th>
                                <th>رقم مسار</th>
                                <th>المؤسسة</th>
                                <th>المستوى</th>
                                <th>نقاط السلوك</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($students)): ?>
                                <?php foreach ($students as $index => $student): ?>
                                    <?php
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
                                    <tr class="student-row" data-level="<?= strtolower($student['education_level']) ?>">
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($student['fullname']) ?></td>
                                        <td><?= htmlspecialchars($student['cne']) ?></td>
                                        <td><?= htmlspecialchars($student['massar']) ?></td>
                                        <td><?= htmlspecialchars($student['institution_name']) ?></td>
                                        <td><?= htmlspecialchars($student['education_level']) ?></td>
                                        <td><span class="badge <?= $badge_class ?>"><?= $points_text ?></span></td>
                                        <td>
                                            <button class="btn btn-primary btn-sm">تعديل</button>
                                            <button class="btn btn-danger btn-sm">حذف</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align: center;">لا يوجد تلاميذ مسجلين</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- الإحصائيات -->
            <section id="statistics" style="display: none;">
                <h1>الإحصائيات</h1>
                
                <div class="cards-container">
                    <div class="card">
                        <div class="card-title">عدد المؤسسات حسب النوع</div>
                        <canvas id="institutionsChart" height="200"></canvas>
                    </div>
                    
                    <div class="card">
                        <div class="card-title">توزيع التلاميذ حسب المستوى</div>
                        <canvas id="studentsChart" height="200"></canvas>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-title">توزيع الأساتذة حسب التخصص</div>
                    <canvas id="teachersChart" height="150"></canvas>
                </div>
            </section>

            <!-- التقارير -->
            <section id="reports" style="display: none;">
                <h1>التقارير</h1>
                
                <div class="form-group">
                    <label for="reportType">نوع التقرير:</label>
                    <select id="reportType" class="form-control">
                        <option value="institutions">تقرير المؤسسات</option>
                        <option value="teachers">تقرير الأساتذة</option>
                        <option value="students">تقرير التلاميذ</option>
                        <option value="results">النتائج الدراسية</option>
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
                    <button class="btn btn-primary" onclick="generateReport()">
                        <i class="fas fa-download"></i> إنشاء التقرير
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
                
                <div id="accountSettings">
                    <form method="post" action="update_account.php">
                        <div class="form-group">
                            <label for="academyName">اسم الأكاديمية:</label>
                            <input type="text" id="academyName" name="name" value="<?= htmlspecialchars($academy['name']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="directorName">اسم المدير:</label>
                            <input type="text" id="directorName" name="director_name" value="<?= htmlspecialchars($academy['director_name']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="academyEmail">البريد الإلكتروني:</label>
                            <input type="email" id="academyEmail" name="email" value="<?= htmlspecialchars($academy['email']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="academyPhone">رقم الهاتف:</label>
                            <input type="text" id="academyPhone" name="phone" value="<?= htmlspecialchars($academy['phone']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> حفظ التغييرات
                            </button>
                        </div>
                    </form>
                </div>
                
                <div id="securitySettings" style="display: none;">
                    <form method="post" action="update_password.php">
                        <div class="form-group">
                            <label for="currentPassword">كلمة المرور الحالية:</label>
                            <input type="password" id="currentPassword" name="current_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="newPassword">كلمة المرور الجديدة:</label>
                            <input type="password" id="newPassword" name="new_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirmPassword">تأكيد كلمة المرور:</label>
                            <input type="password" id="confirmPassword" name="confirm_password" required>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-lock"></i> تحديث كلمة المرور
                            </button>
                        </div>
                    </form>
                </div>
                
                <div id="notificationsSettings" style="display: none;">
                    <form method="post" action="update_notifications.php">
                        <div class="form-group">
                            <label>إعدادات الإشعارات:</label>
                            <div class="checkbox-group">
                                <label>
                                    <input type="checkbox" name="new_institutions" checked> إشعارات بالمؤسسات الجديدة
                                </label>
                                <label>
                                    <input type="checkbox" name="reports_ready" checked> إشعارات بجاهزية التقارير
                                </label>
                                <label>
                                    <input type="checkbox" name="important_updates"> إشعارات بالتحديثات المهمة
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-bell"></i> حفظ الإعدادات
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // تفعيل منتقي التاريخ
        flatpickr("#birthDate", {
            dateFormat: "Y-m-d",
            allowInput: true,
            locale: "ar"
        });

        // عرض الأقسام
        function showSection(sectionId) {
            const sections = document.querySelectorAll('main.content section');
            sections.forEach(section => {
                section.style.display = 'none';
            });

            document.getElementById(sectionId).style.display = 'block';

            const links = document.querySelectorAll('nav.sidebar a');
            links.forEach(link => {
                link.classList.remove('active');
            });
            
            const activeLink = Array.from(links).find(link => {
                return link.getAttribute('onclick')?.includes(sectionId);
            });
            
            if (activeLink) {
                activeLink.classList.add('active');
            }
            
            // إذا كان القسم هو الإحصائيات، نرسم المخططات
            if (sectionId === 'statistics') {
                drawCharts();
            }
        }

        // فلترة المؤسسات
        function searchInstitutions() {
            const input = document.getElementById('institutionSearch').value.toLowerCase();
            const rows = document.querySelectorAll('#institutionsTable tbody tr');
            
            rows.forEach(row => {
                const name = row.cells[1].textContent.toLowerCase();
                const city = row.cells[3].textContent.toLowerCase();
                
                if (name.includes(input) || city.includes(input)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // فلترة الأساتذة
        function filterTeachers(level) {
            const rows = document.querySelectorAll('#teachersTable tbody tr.teacher-row');
            
            rows.forEach(row => {
                if (level === 'all' || row.dataset.level === level) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
            
            document.querySelectorAll('#teachersList .tabs .tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            event.target.classList.add('active');
        }

        function searchTeachers() {
            const input = document.getElementById('teacherSearch').value.toLowerCase();
            const rows = document.querySelectorAll('#teachersTable tbody tr.teacher-row');
            
            rows.forEach(row => {
                const name = row.cells[1].textContent.toLowerCase();
                const specialty = row.cells[4].textContent.toLowerCase();
                const institution = row.cells[5].textContent.toLowerCase();
                
                if (name.includes(input) || specialty.includes(input) || institution.includes(input)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // فلترة التلاميذ
        function filterStudents(level) {
            const rows = document.querySelectorAll('#studentsTable tbody tr.student-row');
            
            rows.forEach(row => {
                if (level === 'all' || row.dataset.level === level) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
            
            document.querySelectorAll('#studentsList .tabs .tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            event.target.classList.add('active');
        }

        function searchStudents() {
            const input = document.getElementById('studentSearch').value.toLowerCase();
            const rows = document.querySelectorAll('#studentsTable tbody tr.student-row');
            
            rows.forEach(row => {
                const name = row.cells[1].textContent.toLowerCase();
                const cne = row.cells[2].textContent.toLowerCase();
                const massar = row.cells[3].textContent.toLowerCase();
                const institution = row.cells[4].textContent.toLowerCase();
                
                if (name.includes(input) || cne.includes(input) || massar.includes(input) || institution.includes(input)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // عرض تبويبات الإعدادات
        function showSettingsTab(tab) {
            document.getElementById('accountSettings').style.display = 'none';
            document.getElementById('securitySettings').style.display = 'none';
            document.getElementById('notificationsSettings').style.display = 'none';
            
            document.getElementById(tab + 'Settings').style.display = 'block';
            
            document.querySelectorAll('#settings .tabs .tab').forEach(t => {
                t.classList.remove('active');
            });
            
            event.target.classList.add('active');
        }

        // إنشاء التقارير
        function generateReport() {
            const type = document.getElementById('reportType').value;
            const period = document.getElementById('reportPeriod').value;
            
            alert(`سيتم إنشاء تقرير ${type} للفترة ${period}`);
            // هنا يمكنك إضافة كود لإنشاء التقرير فعلياً
        }

        // عرض تفاصيل المؤسسة
        function viewInstitution(id) {
            alert(`عرض تفاصيل المؤسسة ذات المعرف ${id}`);
            // هنا يمكنك إضافة كود لعرض تفاصيل المؤسسة
        }

        // تعديل المؤسسة
        function editInstitution(id) {
            alert(`تعديل المؤسسة ذات المعرف ${id}`);
            // هنا يمكنك إضافة كود لتعديل المؤسسة
        }

        // رسم المخططات الإحصائية
        function drawCharts() {
            // بيانات المؤسسات حسب النوع
            const institutionsData = {
                primary: 0,
                middle: 0,
                secondary: 0,
                university: 0
            };
            
            <?php foreach ($institutions as $institution): ?>
                institutionsData['<?= $institution['type'] ?>']++;
            <?php endforeach; ?>
            
            // رسم مخطط المؤسسات
            const institutionsCtx = document.getElementById('institutionsChart').getContext('2d');
            new Chart(institutionsCtx, {
                type: 'doughnut',
                data: {
                    labels: ['ابتدائي', 'إعدادي', 'ثانوي', 'جامعي'],
                    datasets: [{
                        data: [
                            institutionsData.primary,
                            institutionsData.middle,
                            institutionsData.secondary,
                            institutionsData.university
                        ],
                        backgroundColor: [
                            '#00ffe7',
                            '#2ed573',
                            '#ffa500',
                            '#ff4757'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
            
            // بيانات التلاميذ حسب المستوى
            const studentsData = {
                primary: 0,
                middle: 0,
                secondary: 0
            };
            
            <?php foreach ($students as $student): ?>
                studentsData['<?= strtolower($student['education_level']) ?>']++;
            <?php endforeach; ?>
            
            // رسم مخطط التلاميذ
            const studentsCtx = document.getElementById('studentsChart').getContext('2d');
            new Chart(studentsCtx, {
                type: 'bar',
                data: {
                    labels: ['ابتدائي', 'إعدادي', 'ثانوي'],
                    datasets: [{
                        label: 'عدد التلاميذ',
                        data: [
                            studentsData.primary,
                            studentsData.middle,
                            studentsData.secondary
                        ],
                        backgroundColor: [
                            '#00ffe7',
                            '#2ed573',
                            '#ffa500'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
            
            // بيانات الأساتذة حسب التخصص (مثال)
            const teachersData = {
                math: 15,
                physics: 10,
                chemistry: 8,
                biology: 7,
                arabic: 12,
                french: 9,
                english: 6
            };
            
            // رسم مخطط الأساتذة
            const teachersCtx = document.getElementById('teachersChart').getContext('2d');
            new Chart(teachersCtx, {
                type: 'horizontalBar',
                data: {
                    labels: ['الرياضيات', 'الفيزياء', 'الكيمياء', 'الأحياء', 'العربية', 'الفرنسية', 'الإنجليزية'],
                    datasets: [{
                        label: 'عدد الأساتذة',
                        data: [
                            teachersData.math,
                            teachersData.physics,
                            teachersData.chemistry,
                            teachersData.biology,
                            teachersData.arabic,
                            teachersData.french,
                            teachersData.english
                        ],
                        backgroundColor: [
                            '#00ffe7',
                            '#2ed573',
                            '#ffa500',
                            '#ff4757',
                            '#a55eea',
                            '#45aaf2',
                            '#26de81'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>