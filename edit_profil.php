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

$error = '';
$success = '';

// جلب بيانات المستخدم للعرض في النموذج
$stmt = $conn->prepare("SELECT fullname, email, phone, address, cne, massar, gender, birthdate, education_level, specialty, institution, graduation_year FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    echo "المستخدم غير موجود.";
    exit();
}

$user = $result->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // تنظيف المدخلات
    $fullname = trim($_POST['fullname']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $cne = trim($_POST['cne']);
    $massar = trim($_POST['massar']);
    $gender = $_POST['gender'];
    $birthdate = $_POST['birthdate'];
    $education_level = $_POST['education_level'];
    $specialty = $_POST['specialty'];
    $institution = trim($_POST['institution']);
    $graduation_year = trim($_POST['graduation_year']);

    if (empty($fullname)) {
        $error = "الاسم الكامل مطلوب.";
    } else {
        // تحديث البيانات في قاعدة البيانات
        $update_stmt = $conn->prepare("UPDATE users SET fullname=?, phone=?, address=?, cne=?, massar=?, gender=?, birthdate=?, education_level=?, specialty=?, institution=?, graduation_year=? WHERE id=?");
        $update_stmt->bind_param("sssssssssssi", $fullname, $phone, $address, $cne, $massar, $gender, $birthdate, $education_level, $specialty, $institution, $graduation_year, $user_id);
        if ($update_stmt->execute()) {
            $success = "تم تحديث الملف الشخصي بنجاح.";
            // تحديث بيانات المستخدم للعرض بعد التحديث
            $user = [
                'fullname' => $fullname,
                'phone' => $phone,
                'address' => $address,
                'cne' => $cne,
                'massar' => $massar,
                'gender' => $gender,
                'birthdate' => $birthdate,
                'education_level' => $education_level,
                'specialty' => $specialty,
                'institution' => $institution,
                'graduation_year' => $graduation_year
            ];
        } else {
            $error = "حدث خطأ أثناء التحديث. حاول مرة أخرى.";
        }
        $update_stmt->close();
    }
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" type="image/png" href="ChatGPT Image 4 juin 2025, 14_53_49.png" />
    <title>تعديل الملف الشخصي</title>
    <style>
        body, html {
            margin: 0; padding: 0; height: 100%;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: #222;
            color: #fff;
            position: relative;
            overflow-x: hidden;
        }
        #particles-js {
            position: fixed; width: 100%; height: 100%; top: 0; left: 0; z-index: 0;
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
        form {
            display: flex;
            flex-direction: column;
        }
        label {
            font-weight: 700;
            color: #f0a500;
            margin: 10px 0 6px;
            user-select: none;
        }
        input[type="text"], input[type="email"], input[type="date"], select {
            padding: 10px;
            border-radius: 6px;
            border: none;
            font-size: 1rem;
        }
        .btn-group {
            margin-top: 25px;
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        .btn {
            padding: 12px 30px;
            background-color: #f0a500;
            color: #222;
            font-weight: 700;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            box-shadow: 0 4px 10px rgba(240, 165, 0, 0.7);
            user-select: none;
            min-width: 140px;
            text-align: center;
            text-decoration: none;
        }
        .btn:hover {
            background-color: #cf8500;
            box-shadow: 0 6px 15px rgba(207, 133, 0, 0.9);
        }
        .message {
            margin: 20px 0;
            text-align: center;
            font-weight: 700;
            padding: 12px;
            border-radius: 8px;
        }
        .error {
            background-color: #ff4c4c;
            color: #fff;
        }
        .success {
            background-color: #4caf50;
            color: #fff;
        }
        @media (max-width: 500px) {
            .container {
                margin: 30px 15px 50px;
                padding: 25px 20px;
            }
            .btn-group {
                flex-direction: column;
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
    <div id="particles-js"></div>

    <main class="container" role="main" aria-labelledby="edit-profile-title">
        <h1 id="edit-profile-title">تعديل الملف الشخصي</h1>

        <?php if ($error): ?>
            <div class="message error" role="alert"><?= htmlspecialchars($error) ?></div>
        <?php elseif ($success): ?>
            <div class="message success" role="alert"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="post" action="edit_profil.php" novalidate>
            <label for="fullname">الاسم الكامل *</label>
            <input type="text" id="fullname" name="fullname" required value="<?= htmlspecialchars($user['fullname']) ?>" />

            <label for="phone">رقم الهاتف</label>
            <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" />

            <label for="address">العنوان</label>
            <input type="text" id="address" name="address" value="<?= htmlspecialchars($user['address']) ?>" />

            <label for="cne">CNE</label>
            <input type="text" id="cne" name="cne" value="<?= htmlspecialchars($user['cne']) ?>" />

            <label for="massar">Massar</label>
            <input type="text" id="massar" name="massar" value="<?= htmlspecialchars($user['massar']) ?>" />

            <label for="gender">الجنس</label>
            <select id="gender" name="gender">
                <option value="" <?= ($user['gender'] === '') ? 'selected' : '' ?>>اختر</option>
                <option value="ذكر" <?= ($user['gender'] === 'ذكر') ? 'selected' : '' ?>>ذكر</option>
                <option value="أنثى" <?= ($user['gender'] === 'أنثى') ? 'selected' : '' ?>>أنثى</option>
            </select>

            <label for="birthdate">تاريخ الميلاد</label>
            <input type="date" id="birthdate" name="birthdate" value="<?= htmlspecialchars($user['birthdate']) ?>" />

            <label for="education_level">المستوى الدراسي</label>
            <select id="education_level" name="education_level">
                <option value="" <?= ($user['education_level'] === '') ? 'selected' : '' ?>>اختر</option>
                <option value="الإعدادي" <?= ($user['education_level'] === 'الإعدادي') ? 'selected' : '' ?>>الإعدادي</option>
                <option value="الثانوي" <?= ($user['education_level'] === 'الثانوي') ? 'selected' : '' ?>>الثانوي</option>
                <option value="البكالوريا" <?= ($user['education_level'] === 'البكالوريا') ? 'selected' : '' ?>>البكالوريا</option>
                <option value="الجامعة" <?= ($user['education_level'] === 'الجامعة') ? 'selected' : '' ?>>الجامعة</option>
                <option value="دبلوم تقني" <?= ($user['education_level'] === 'دبلوم تقني') ? 'selected' : '' ?>>دبلوم تقني</option>
                <option value="ماجستير" <?= ($user['education_level'] === 'ماجستير') ? 'selected' : '' ?>>ماجستير</option>
            </select>

            <label for="specialty">التخصص</label>
            <input type="text" id="specialty" name="specialty" value="<?= htmlspecialchars($user['specialty']) ?>" />

            <label for="institution">المؤسسة</label>
            <input type="text" id="institution" name="institution" value="<?= htmlspecialchars($user['institution']) ?>" />

            <label for="graduation_year">سنة التخرج</label>
            <input type="text" id="graduation_year" name="graduation_year" value="<?= htmlspecialchars($user['graduation_year']) ?>" />

            <div class="btn-group">
                <button type="submit" class="btn">حفظ التعديلات</button>
                <a href="profil.php" class="btn" role="button" aria-label="العودة إلى الملف الشخصي">عودة</a>
            </div>
        </form>
    </main>

    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <script>
        particlesJS.load('particles-js', 'particles.json', function() {
            console.log('particles.js تم التحميل');
        });
        particlesJS('particles-js',
{
  "particles": {
    "number": { "value": 80, "density": { "enable": true, "value_area": 800 }},
    "color": { "value": "#f0a500" },
    "shape": { "type": "circle" },
    "opacity": { "value": 0.5 },
    "size": { "value": 3, "random": true },
    "move": { "enable": true, "speed": 2, "out_mode": "out" },
    "line_linked": { "enable": true, "distance": 150, "color": "#f0a500", "opacity": 0.4, "width": 1 }
  },
  "interactivity": {
    "detect_on": "canvas",
    "events": { "onhover": { "enable": true, "mode": "grab" }, "onclick": { "enable": true, "mode": "push" }},
    "modes": { "grab": { "distance": 140, "line_linked": { "opacity": 1 } }, "push": { "particles_nb": 4 }}
  },
  "retina_detect": true
});

    </script>
</body>
</html>
