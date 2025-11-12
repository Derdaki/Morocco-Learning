<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$host = 'localhost';
$port = 3307;
$dbname = 'moroccolearning';
$username = 'root';
$password = '';
$error = '';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name'] ?? '');
        $institution_name = trim($_POST['institution_name'] ?? '');
        $region = trim($_POST['region'] ?? '');
        $province = trim($_POST['province'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $students = (int) ($_POST['students'] ?? 0);
        $type = trim($_POST['type'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $affiliation = trim($_POST['affiliation'] ?? '');
        $password_raw = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if ($name && $institution_name && $region && $province && $city && $type && $email && $phone && $password_raw && $confirm_password) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = "البريد الإلكتروني غير صالح.";
            } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
                $error = "رقم الهاتف يجب أن يحتوي على 10 أرقام.";
            } elseif ($password_raw !== $confirm_password) {
                $error = "كلمتا المرور غير متطابقتين.";
            } else {
                // تحقق من البريد والهاتف فقط إذا لم تكن المؤسسة مرفوضة
                $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM institutions WHERE (email = :email OR phone = :phone) AND status != 'rejected'");
                $stmtCheck->execute([':email' => $email, ':phone' => $phone]);

                if ($stmtCheck->fetchColumn() > 0) {
                $error = "البريد الإلكتروني أو رقم الهاتف مسجل بالفعل في مؤسسة نشطة.";
                } else {
                    $password_hashed = password_hash($password_raw, PASSWORD_DEFAULT);
                    $access_code = substr(md5(uniqid(mt_rand(), true)), 0, 8); // توليد كود وصول عشوائي
                    $created_at = date('Y-m-d H:i:s');

                    $stmt = $pdo->prepare("
                        INSERT INTO institutions 
                            (name, institution_name, region, province, city, students, type, email, phone, password, status, access_code, created_at, affiliation)
                        VALUES 
                            (:name, :institution_name, :region, :province, :city, :students, :type, :email, :phone, :password, 'pending', :access_code, :created_at, :affiliation)
                    ");

                    $stmt->execute([
                        ':name' => $name,
                        ':institution_name' => $institution_name,
                        ':region' => $region,
                        ':province' => $province,
                        ':city' => $city,
                        ':students' => $students,
                        ':type' => $type,
                        ':email' => $email,
                        ':phone' => $phone,
                        ':password' => $password_hashed,
                        ':access_code' => $access_code,
                        ':created_at' => $created_at,
                        ':affiliation' => $affiliation
                    ]);

                    header("Location: institutions_list.php");
                    exit();
                }
            }
        } else {
            $error = "يرجى ملء جميع الحقول المطلوبة.";
        }
    }
} catch (PDOException $e) {
    $error = "فشل الاتصال بقاعدة البيانات: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إضافة مؤسسة</title>
      <style>
    @import url('https://fonts.googleapis.com/css2?family=Cairo&display=swap');
    * {
      margin: 0; padding: 0; box-sizing: border-box; font-family: 'Cairo', sans-serif;
    }
    body, html {
      min-height: 100vh;
      background-color: #001f2e;
      color: #e0f7fa;
    }
    .container {
      max-width: 700px;
      margin: 80px auto;
      background: rgba(0,31,46,0.95);
      padding: 30px 40px;
      border-radius: 15px;
      box-shadow: 0 0 40px #00ffe7;
      position: relative;
      z-index: 1;
    }
    #particles-js {
      position: fixed;
      width: 100%;
      height: 100%;
      top: 0; left: 0;
      z-index: 0;
      pointer-events: none;
    }
    h2 {
      text-align: center;
      margin-bottom: 30px;
      color: #00ffe7;
    }
    label {
      margin-top: 15px;
      display: block;
    }
    input, select {
      width: 100%;
      padding: 10px;
      margin-top: 5px;
      background-color: #002f44;
      border: 1px solid #00ffe7;
      color: #e0f7fa;
      border-radius: 10px;
    }
    button {
      margin-top: 25px;
      width: 100%;
      padding: 12px;
      background-color: #00ffe7;
      color: #001f2e;
      font-weight: bold;
      border: none;
      border-radius: 12px;
      font-size: 1.2rem;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }
    button:hover {
      background-color: #00cfc2;
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
    }
    ::-webkit-scrollbar-thumb:hover {
      background: #00f2ffcc;
    }
  </style>
<style>
#adminNoticeModal {
  position: fixed;
  z-index: 9999;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: "Arial", sans-serif;
}

.modal-content-long {
  background-color: rgba(0, 31, 46, 0.95);
  padding: 30px;
  border-radius: 15px;
  width: 95%;
  max-width: 800px;
  max-height: 85vh;
  overflow-y: auto;
  box-shadow: 0 0 40px #00ffe7;
  position: relative;
  direction: rtl;
  text-align: right;
  color: #e0f7fa;
}

.modal-content-long h2 {
  color: #00ffe7;
  margin-top: 0;
  font-size: 26px;
}

.modal-content-long p {
  font-size: 17px;
  line-height: 2;
  color: #e0f7fa;
}

.close-btn {
  position: absolute;
  top: 12px;
  left: 20px;
  font-size: 26px;
  font-weight: bold;
  color: #e0f7fa;
  cursor: pointer;
}

.retour {
      margin-top: 25px;
      width: 100%;
      padding: 12px;
      background-color: #00ffe7;
      color: #001f2e;
      font-weight: bold;
      border: none;
      border-radius: 12px;
      font-size: 1.2rem;
      cursor: pointer;
      transition: background-color 0.3s ease;
      text-decoration: none;
}
.retour:hover {
     background-color: #00cfc2;

}
</style>


</head>
<body>
<!-- نافذة رسالة المديرين -->
<div id="adminNoticeModal">
  <div class="modal-content-long">
    <span class="close-btn" onclick="document.getElementById('adminNoticeModal').style.display='none'">&times;</span>
    <h2 style="color: red;">تنويه هام لمديري المؤسسات التعليمية</h2>
   <strong> <p>
      هذه الصفحة مخصصة حصريًا لمديري ومديرات المؤسسات التعليمية، حيث يمكنكم من خلال هذه الاستمارة تسجيل مؤسستكم ضمن قائمة المؤسسات التعليمية المعتمدة على المنصة.
      <br><br>
      تهدف هذه الخطوة إلى ضمان استفادة التلميذات والتلاميذ التابعين للمؤسسة من خدمات المنصة في بيئة آمنة، تحترم المعايير الاجتماعية وتحافظ على الصورة التربوية الجيدة للمؤسسة.
      <br><br>
      عند تسجيل حساب جديد من طرف التلميذ أو التلميذة، وبعد تأكيد بريده الإلكتروني، تُرسل بياناته تلقائيًا إلى المؤسسة التي حددها في هذه الاستمارة. يتم بعد ذلك التحقق من صحة هذه البيانات، كرقم المسار أو رقم البطاقة الوطنية، من قبل إدارة المؤسسة.
      <br><br>
      وتعتمد هذه العملية أيضًا على بيانات تلاميذ سابقين ينتمون إلى نفس المؤسسة، مما يعزز من مصداقية التسجيل ويُسهّل التحقق.
      <br><br>
      تهدف هذه الآلية إلى خلق منظومة تربوية رقمية متكاملة تحافظ على الخصوصية وتحمي التلميذ من أي انتهاك أو مضايقة محتملة، كما تسهم في تقديم خدمات تعليمية أكثر تخصيصًا وجودة.
      <br><br>
      إن مشاركتكم كمديري مؤسسات في إدراج مؤسستكم بدقة يساهم في تحسين أداء المنصة، ويُعزز الثقة في البيئة التعليمية الرقمية.
      <br><br>
      نشكركم على تعاونكم، ونُقدر دائمًا جهودكم في تطوير التعليم.
    </p>
</strong>
<p style="margin-top: 30px; border-top: 1px solid #00ffe7; padding-top: 20px; color: #e0f7fa;">
  <strong style="color: #ffeb3b;">تنويه :</strong> بعد ملء الاستمارة، سيتم إرسال بيانات مؤسستكم إلى الأكاديمية الجهوية للتربية والتكوين التابعة لجهتكم للتحقق من صحة المعلومات. <br>
  عند القبول، سيتم إشعاركم عبر البريد الإلكتروني خلال مدة لا تتجاوز 24 ساعة.
</p>
  </div>
</div>


      <div id="particles-js"></div>
<div class="container">
  <h2>إضافة مؤسسة تعليمية</h2>
      <?php if ($error): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
      <form method="POST" action="">
      <label for="name">الإسم الكامل للمدير :</label>
      <input type="text" name="name" id="name" required />

      <label for="institution_name">اسم المؤسسة :</label>
      <input type="text" name="institution_name" id="institution_name" required />

      <label for="students">عدد التلاميذ :</label>
      <input type="number" name="students" id="students" min="0" required />

      <label for="type">نوع المؤسسة :</label>
      <select name="type" id="type" required>
        <option value="" disabled selected>اختر نوع المؤسسة</option>
        <option value="إعدادي">إعدادي</option>
        <option value="ثانوي تأهيلي">ثانوي تأهيلي</option>
        <option value="مهني / تقني">مهني / تقني</option>
        <option value="جامعي / عالي">جامعي / عالي</option>
      </select>

      <label for="affiliation">الانتماء :</label>
      <select name="affiliation" id="affiliation" required>
        <option value="عمومية">عمومية</option>
        <option value="خصوصية">خصوصية</option>
        <option value="شراكة">شراكة</option>
      </select>
  
        <label for="regionSelect">الجهة :</label>
        <select name="region" id="regionSelect" required>
        <option value="">اختر الجهة</option>
        <option value="طنجة تطوان الحسيمة">طنجة تطوان الحسيمة</option>
        <option value="الجهة الشرقية">الجهة الشرقية</option>
        <option value="فاس مكناس">فاس مكناس</option>
        <option value="الرباط سلا القنيطرة">الرباط سلا القنيطرة</option>
        <option value="بني ملال خنيفرة">بني ملال خنيفرة</option>
        <option value="الدار البيضاء سطات">الدار البيضاء سطات</option>
        <option value="مراكش آسفي">مراكش آسفي</option>
        <option value="درعة تافيلالت">درعة تافيلالت</option>
        <option value="سوس ماسة">سوس ماسة</option>
        <option value="كلميم واد نون">كلميم واد نون</option>
        <option value="العيون الساقية الحمراء">العيون الساقية الحمراء</option>
        <option value="الداخلة وادي الذهب">الداخلة وادي الذهب</option>
        </select>



        <label for="provinceSelect">الإقليم / العمالة :</label>
        <select name="province" id="provinceSelect" required>
        <option value="">اختر الإقليم</option>

        <!-- عمالات -->
        <option value="الدار البيضاء">الدار البيضاء</option>
        <option value="الرباط">الرباط</option>
        <option value="سلا">سلا</option>
        <option value="فاس">فاس</option>
        <option value="المحمدية">المحمدية</option>
        <option value="مراكش">مراكش</option>
        <option value="أكادير إدا وتنان">أكادير إدا وتنان</option>
        <option value="القنيطرة">القنيطرة</option>
        <option value="طنجة أصيلة">طنجة أصيلة</option>
        <option value="وجدة أنكاد">وجدة أنكاد</option>
        <option value="مكناس">مكناس</option>
        <option value="تطوان">تطوان</option>

        <!-- أقاليم -->
        <option value="بركان">بركان</option>
        <option value="الناظور">الناظور</option>
        <option value="تاوريرت">تاوريرت</option>
        <option value="جرادة">جرادة</option>
        <option value="الدريوش">الدريوش</option>
        <option value="جرسيف">جرسيف</option>
        <option value="تازة">تازة</option>
        <option value="تاونات">تاونات</option>
        <option value="إفران">إفران</option>
        <option value="الحاجب">الحاجب</option>
        <option value="سيدي قاسم">سيدي قاسم</option>
        <option value="سيدي سليمان">سيدي سليمان</option>
        <option value="الخميسات">الخميسات</option>
        <option value="خريبكة">خريبكة</option>
        <option value="بني ملال">بني ملال</option>
        <option value="أزيلال">أزيلال</option>
        <option value="خنيفرة">خنيفرة</option>
        <option value="سطات">سطات</option>
        <option value="الجديدة">الجديدة</option>
        <option value="برشيد">برشيد</option>
        <option value="مديونة">مديونة</option>
        <option value="النواصر">النواصر</option>
        <option value="بن سليمان">بن سليمان</option>
        <option value="شيشاوة">شيشاوة</option>
        <option value="الحوز">الحوز</option>
        <option value="قلعة السراغنة">قلعة السراغنة</option>
        <option value="الرحامنة">الرحامنة</option>
        <option value="آسفي">آسفي</option>
        <option value="اليوسفية">اليوسفية</option>
        <option value="تنغير">تنغير</option>
        <option value="ميدلت">ميدلت</option>
        <option value="زاكورة">زاكورة</option>
        <option value="ورزازات">ورزازات</option>
        <option value="الرشيدية">الرشيدية</option>
        <option value="تارودانت">تارودانت</option>
        <option value="تيزنيت">تيزنيت</option>
        <option value="شتوكة آيت باها">شتوكة آيت باها</option>
        <option value="إنزكان آيت ملول">إنزكان آيت ملول</option>
        <option value="سيدي إفني">سيدي إفني</option>
        <option value="كلميم">كلميم</option>
        <option value="آسا الزاك">آسا الزاك</option>
        <option value="طانطان">طانطان</option>
        <option value="السمارة">السمارة</option>
        <option value="العيون">العيون</option>
        <option value="بوجدور">بوجدور</option>
        <option value="طرفاية">طرفاية</option>
        <option value="أوسرد">أوسرد</option>
        <option value="الداخلة">الداخلة</option>
        </select>

            <label for="citySelect">المدينة :</label>
        <select name="city" id="citySelect" required>
        <option value="">اختر المدينة</option>
        <option value="أكادير">أكادير</option>
        <option value="أحفير">أحفير</option>
        <option value="آيت ملول">آيت ملول</option>
        <option value="الحسيمة">الحسيمة</option>
        <option value="آسا">آسا</option>
        <option value="أزمور">أزمور</option>
        <option value="أزيلال">أزيلال</option>
        <option value="بن جرير">بن جرير</option>
        <option value="بنڭرير">بنڭرير</option>
        <option value="بني ملال">بني ملال</option>
        <option value="بركان">بركان</option>
        <option value="برشيد">برشيد</option>
        <option value="بوعرفة">بوعرفة</option>
        <option value="بوجدور">بوجدور</option>
        <option value="الدار البيضاء">الدار البيضاء</option>
        <option value="شفشاون">شفشاون</option>
        <option value="شيشاوة">شيشاوة</option>
        <option value="الداخلة">الداخلة</option>
        <option value="الدريوش">الدريوش</option>
        <option value="الجديدة">الجديدة</option>
        <option value="قلعة السراغنة">قلعة السراغنة</option>
        <option value="الرشيدية">الرشيدية</option>
        <option value="الصويرة">الصويرة</option>
        <option value="فاس">فاس</option>
        <option value="الفنيدق">الفنيدق</option>
        <option value="كلميم">كلميم</option>
        <option value="جرسيف">جرسيف</option>
        <option value="إفران">إفران</option>
        <option value="إنزكان">إنزكان</option>
        <option value="جرادة">جرادة</option>
        <option value="قلعة مڭونة">قلعة مڭونة</option>
        <option value="القنيطرة">القنيطرة</option>
        <option value="الخميسات">الخميسات</option>
        <option value="خنيفرة">خنيفرة</option>
        <option value="خريبكة">خريبكة</option>
        <option value="العرائش">العرائش</option>
        <option value="العيون">العيون</option>
        <option value="مراكش">مراكش</option>
        <option value="مرتيل">مرتيل</option>
        <option value="المضيق">المضيق</option>
        <option value="مكناس">مكناس</option>
        <option value="ميدلت">ميدلت</option>
        <option value="المحمدية">المحمدية</option>
        <option value="الناظور">الناظور</option>
        <option value="ورزازات">ورزازات</option>
        <option value="وزان">وزان</option>
        <option value="وادي زم">وادي زم</option>
        <option value="وجدة">وجدة</option>
        <option value="الرباط">الرباط</option>
        <option value="آسفي">آسفي</option>
        <option value="سلا">سلا</option>
        <option value="صفرو">صفرو</option>
        <option value="سطات">سطات</option>
        <option value="سبو">سبو</option>
        <option value="سيدي بنور">سيدي بنور</option>
        <option value="سيدي قاسم">سيدي قاسم</option>
        <option value="سيدي سليمان">سيدي سليمان</option>
        <option value="الصخيرات">الصخيرات</option>
        <option value="السمارة">السمارة</option>
        <option value="سوق أربعاء الغرب">سوق أربعاء الغرب</option>
        <option value="تافيلالت">تافيلالت</option>
        <option value="تحناوت">تحناوت</option>
        <option value="طنجة">طنجة</option>
        <option value="طانطان">طانطان</option>
        <option value="تاوريرت">تاوريرت</option>
        <option value="تاونات">تاونات</option>
        <option value="تارودانت">تارودانت</option>
        <option value="تازة">تازة</option>
        <option value="تمارة">تمارة</option>
        <option value="تطوان">تطوان</option>
        <option value="تنغير">تنغير</option>
        <option value="تيزنيت">تيزنيت</option>
        <option value="اليوسفية">اليوسفية</option>
        <option value="زاكورة">زاكورة</option>
        </select>



      <label for="email">البريد الإلكتروني للمدير :</label>
      <input type="email" name="email" id="email" required />

      <label for="phone">رقم الهاتف :</label>
      <input type="tel" name="phone" id="phone" required pattern="0[5-7][0-9]{8}" placeholder="مثال: 0612345678" />
      <label for="password">كلمة المرور :</label>
      <input type="password" name="password" id="password" required />

      <label for="confirm_password">تأكيد كلمة المرور :</label>
      <input type="password" name="confirm_password" id="confirm_password" required />


<button type="submit"> إضافة المؤسسة </button>
<button><a href="institutions_list.php" class="retour">العودة إلى قائمة المؤسسات</a></button>
</form>
</div>
</body>
</html>


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
