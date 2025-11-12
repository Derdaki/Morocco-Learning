    <?php
    header('Content-Type: application/json');
    session_start();

    // تحقق من تسجيل الدخول (مثلاً)
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'غير مسموح']);
        exit;
    }

    // استلم البيانات
    $name = $_POST['fullName'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';

    // تحقق من صحة البيانات هنا، ثم حدث قاعدة البيانات حسب user_id من الجلسة

    // مثال: تحقق من وجود ملف صورة مرفوع
    if (isset($_FILES['profilePhoto']) && $_FILES['profilePhoto']['error'] === 0) {
        // احفظ الصورة في مجلد معين مع اسم فريد، ثم حدّث مسار الصورة في قاعدة البيانات
    }

    // في حال نجاح التحديث:
    echo json_encode(['status' => 'success', 'message' => 'تم تحديث بيانات الحساب بنجاح.']);
    exit;
    ?>
