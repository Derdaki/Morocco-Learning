<?php
session_start();

// تحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "moroccolearning";
$port = 3307;

// الاتصال بقاعدة البيانات
$conn = new mysqli($servername, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    die("فشل الاتصال: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$message = "";
$password_message = "";

// جلب حالة تفعيل المصادقة الثنائية ومفتاح السر الخاص بها
$is2FAEnabled = false;
$secret2FA = null;
$stmt2fa = $conn->prepare("SELECT is_enabled, secret FROM user_2fa WHERE user_id = ?");
$stmt2fa->bind_param("i", $user_id);
$stmt2fa->execute();
$stmt2fa->bind_result($is_enabled, $secret);
if ($stmt2fa->fetch()) {
    $is2FAEnabled = (bool)$is_enabled;
    $secret2FA = $secret;
}
$stmt2fa->close();

// جلب إعدادات الخصوصية والإشعارات من جدول user_settings
$email_notifications = 1; // القيم الافتراضية
$sms_notifications = 0;
$show_profile = 1;
$activity_log_enabled = 1;
$community_rules_accept = 1;

$stmtSettings = $conn->prepare("SELECT email_notifications, sms_notifications, show_profile, activity_log_enabled, community_rules_accept FROM user_settings WHERE user_id = ?");
$stmtSettings->bind_param("i", $user_id);
$stmtSettings->execute();
$stmtSettings->bind_result($email_notifications_db, $sms_notifications_db, $show_profile_db, $activity_log_enabled_db, $community_rules_accept_db);
if ($stmtSettings->fetch()) {
    $email_notifications = (int)$email_notifications_db;
    $sms_notifications = (int)$sms_notifications_db;
    $show_profile = (int)$show_profile_db;
    $activity_log_enabled = (int)$activity_log_enabled_db;
    $community_rules_accept = (int)$community_rules_accept_db;
}
$stmtSettings->close();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // تفعيل/إلغاء المصادقة الثنائية
    if (isset($_POST['toggle_2fa'])) {
        if ($_POST['toggle_2fa'] === 'enable') {
            // توليد سر جديد أو استخدام الموجود (لأجل المثال هنا ثابت)
            $secret = "مثال_السر_الخاص_بالمستخدم"; 

            // تحقق هل يوجد سجل سابق
            $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM user_2fa WHERE user_id = ?");
            $stmtCheck->bind_param("i", $user_id);
            $stmtCheck->execute();
            $stmtCheck->bind_result($count);
            $stmtCheck->fetch();
            $stmtCheck->close();

            if ($count > 0) {
                $stmt = $conn->prepare("UPDATE user_2fa SET is_enabled = 1, secret = ? WHERE user_id = ?");
                $stmt->bind_param("si", $secret, $user_id);
            } else {
                $stmt = $conn->prepare("INSERT INTO user_2fa (user_id, is_enabled, secret) VALUES (?, 1, ?)");
                $stmt->bind_param("is", $user_id, $secret);
            }

            if ($stmt->execute()) {
                $message = "تم تفعيل المصادقة الثنائية.";
                $is2FAEnabled = true;
                $secret2FA = $secret;
            } else {
                $message = "حدث خطأ أثناء تفعيل المصادقة الثنائية.";
            }
            $stmt->close();

        } elseif ($_POST['toggle_2fa'] === 'disable') {
            $stmt_disable = $conn->prepare("UPDATE user_2fa SET is_enabled = 0 WHERE user_id = ?");
            $stmt_disable->bind_param("i", $user_id);
            if ($stmt_disable->execute()) {
                $message = "تم إلغاء تفعيل المصادقة الثنائية بنجاح.";
                $is2FAEnabled = false;
                $secret2FA = null;
            } else {
                $message = "حدث خطأ أثناء إلغاء تفعيل المصادقة الثنائية.";
            }
            $stmt_disable->close();
        }
    }

    // تحديث بيانات المستخدم (الاسم، البريد، الهاتف)
    if (isset($_POST['update_profile'])) {
        $fullname = trim($_POST['fullname']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "البريد الإلكتروني غير صالح.";
        } else {
            $stmt = $conn->prepare("UPDATE users SET fullname = ?, email = ?, phone = ? WHERE id = ?");
            $stmt->bind_param("sssi", $fullname, $email, $phone, $user_id);
            if ($stmt->execute()) {
                $message = "تم تحديث البيانات بنجاح.";
                $_SESSION['user_name'] = $fullname;
            } else {
                $message = "حدث خطأ أثناء التحديث.";
            }
            $stmt->close();
        }
    }

    // تغيير كلمة المرور
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if ($new_password !== $confirm_password) {
            $password_message = "كلمتا المرور الجديدتان غير متطابقتين.";
        } elseif (strlen($new_password) < 6) {
            $password_message = "كلمة المرور الجديدة يجب أن تكون على الأقل 6 أحرف.";
        } else {
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->bind_result($hashed_password);
            $stmt->fetch();
            $stmt->close();

            if (!password_verify($current_password, $hashed_password)) {
                $password_message = "كلمة المرور الحالية غير صحيحة.";
            } else {
                $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $new_hashed_password, $user_id);
                if ($stmt->execute()) {
                    $password_message = "تم تغيير كلمة المرور بنجاح.";
                } else {
                    $password_message = "حدث خطأ أثناء تحديث كلمة المرور.";
                }
                $stmt->close();
            }
        }
    }

    // تحديث إعدادات الخصوصية والإشعارات
    if (isset($_POST['update_settings'])) {
        $email_notifications_new = isset($_POST['email_notifications']) ? 1 : 0;
        $sms_notifications_new = isset($_POST['sms_notifications']) ? 1 : 0;
        $show_profile_new = isset($_POST['show_profile']) ? 1 : 0;
        $activity_log_enabled_new = isset($_POST['activity_log_enabled']) ? 1 : 0;
        $community_rules_accept_new = isset($_POST['community_rules_accept']) ? 1 : 0;

        // تحقق هل يوجد سجل مسبقاً
        $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM user_settings WHERE user_id = ?");
        $stmtCheck->bind_param("i", $user_id);
        $stmtCheck->execute();
        $stmtCheck->bind_result($count);
        $stmtCheck->fetch();
        $stmtCheck->close();

        if ($count > 0) {
            // تحديث الإعدادات
            $stmtUpdate = $conn->prepare("UPDATE user_settings SET email_notifications=?, sms_notifications=?, show_profile=?, activity_log_enabled=?, community_rules_accept=?, updated_at=NOW() WHERE user_id=?");
            $stmtUpdate->bind_param("iiiiii", $email_notifications_new, $sms_notifications_new, $show_profile_new, $activity_log_enabled_new, $community_rules_accept_new, $user_id);
            $success = $stmtUpdate->execute();
            $stmtUpdate->close();
        } else {
            // إدخال سجل جديد
            $stmtInsert = $conn->prepare("INSERT INTO user_settings (user_id, email_notifications, sms_notifications, show_profile, activity_log_enabled, community_rules_accept) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtInsert->bind_param("iiiiii", $user_id, $email_notifications_new, $sms_notifications_new, $show_profile_new, $activity_log_enabled_new, $community_rules_accept_new);
            $success = $stmtInsert->execute();
            $stmtInsert->close();
        }

        if ($success) {
            $message = "تم تحديث إعدادات الخصوصية والإشعارات بنجاح.";
            // تحديث القيم المعروضة في النموذج بعد الحفظ
            $email_notifications = $email_notifications_new;
            $sms_notifications = $sms_notifications_new;
            $show_profile = $show_profile_new;
            $activity_log_enabled = $activity_log_enabled_new;
            $community_rules_accept = $community_rules_accept_new;
        } else {
            $message = "حدث خطأ أثناء تحديث الإعدادات.";
        }
    }
}

     if (isset($_POST['save_language_timezone'])) {
    $language = $_POST['language'];
    $timezone = $_POST['timezone'];

    if (!in_array($language, ['ar', 'en', 'fr']) || !in_array($timezone, timezone_identifiers_list())) {
        $lang_tz_message = "يرجى اختيار لغة ومنطقة زمنية صحيحتين.";
    } else {
        $stmtCheck = $conn->prepare("SELECT id FROM user_settings WHERE user_id = ?");
        $stmtCheck->bind_param("i", $user_id);
        $stmtCheck->execute();
        $stmtCheck->store_result();

        if ($stmtCheck->num_rows > 0) {
            $stmt = $conn->prepare("UPDATE user_settings SET language = ?, timezone = ?, updated_at = NOW() WHERE user_id = ?");
            $stmt->bind_param("ssi", $language, $timezone, $user_id);
        } else {
            $stmt = $conn->prepare("INSERT INTO user_settings (user_id, language, timezone, updated_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("iss", $user_id, $language, $timezone);
        }

        if ($stmt->execute()) {
            $lang_tz_message = "تم حفظ اللغة والمنطقة الزمنية بنجاح.";
        } else {
            $lang_tz_message = "حدث خطأ أثناء حفظ البيانات: " . $stmt->error;
        }

        $stmtCheck->close();
        $stmt->close();
    }
}

// قائمة اللغات المتاحة
$languages = [
    'ar' => 'العربية',
];

// جميع المناطق الزمنية
$timezone_identifiers = DateTimeZone::listIdentifiers();

// جلب اللغة والمنطقة الزمنية
$lang = "";
$timezone = "";
$stmt = $conn->prepare("SELECT language, timezone FROM user_settings WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($language, $timezone);
$stmt->fetch();
$stmt->close();


// جلب بيانات المستخدم للعرض في النموذج
$stmt = $conn->prepare("SELECT fullname, email, phone FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_privacy_settings'])) {
    $show_profile = isset($_POST['show_profile']) ? 1 : 0;
    $share_data = isset($_POST['share_data_with_partners']) ? 1 : 0;

    // تحقق من وجود سجل للمستخدم
    $stmtCheck = $conn->prepare("SELECT id FROM user_settings WHERE user_id = ?");
    $stmtCheck->bind_param("i", $user_id);
    $stmtCheck->execute();
    $stmtCheck->store_result();

    if ($stmtCheck->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE user_settings SET show_profile = ?, share_data_with_partners = ?, updated_at = NOW() WHERE user_id = ?");
        $stmt->bind_param("iii", $show_profile, $share_data, $user_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO user_settings (user_id, show_profile, share_data_with_partners, updated_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iii", $user_id, $show_profile, $share_data);
    }

    if ($stmt->execute()) {
        $privacy_message = "✅ تم حفظ إعدادات الخصوصية بنجاح.";
    } else {
        $privacy_message = "❌ حدث خطأ أثناء الحفظ: " . $stmt->error;
    }

    $stmt->close();
    $stmtCheck->close();
}

// استرجاع القيم الحالية للعرض في النموذج
$show_profile = 1;
$share_data = 0;
$stmt = $conn->prepare("SELECT show_profile, share_data_with_partners FROM user_settings WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($show_profile, $share_data);
$stmt->fetch();
$stmt->close();

$notification_message = "";

if (isset($_POST['save_notification_settings'])) {
    // قيم الإشعارات من الفورم (تكون موجودة لو محددة)
    $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
    $sms_notifications = isset($_POST['sms_notifications']) ? 1 : 0;

    // افترض عندك $user_id معرف مسبقاً وجاهز

    // تحقق إذا كان هناك سجل سابق للمستخدم
    $stmtCheck = $conn->prepare("SELECT id FROM user_settings WHERE user_id = ?");
    $stmtCheck->bind_param("i", $user_id);
    $stmtCheck->execute();
    $stmtCheck->store_result();

    if ($stmtCheck->num_rows > 0) {
        // تحديث
        $stmt = $conn->prepare("UPDATE user_settings SET email_notifications = ?, sms_notifications = ?, updated_at = NOW() WHERE user_id = ?");
        $stmt->bind_param("iii", $email_notifications, $sms_notifications, $user_id);
    } else {
        // إدراج جديد
        $stmt = $conn->prepare("INSERT INTO user_settings (user_id, email_notifications, sms_notifications, updated_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iii", $user_id, $email_notifications, $sms_notifications);
    }

    if ($stmt->execute()) {
        $notification_message = "✅ تم حفظ إعدادات الاشعارات بنجاح.";
    } else {
        $notification_message = "حدث خطأ أثناء حفظ البيانات: " . $stmt->error;
    }

    $stmtCheck->close();
    $stmt->close();
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// إعدادات الاتصال بقاعدة البيانات
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "moroccolearning";
$port = 3307;

try {
    $pdo = new PDO("mysql:host=$servername;port=$port;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("فشل الاتصال بقاعدة البيانات: " . $e->getMessage());
}

// التحقق من طلب حذف الحساب عبر GET (يمكنك تغييره لـ POST حسب التصميم)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['deleteAccount'])) {

    if (!isset($_SESSION['user_id'])) {
        // إذا لم يكن المستخدم مسجل دخول
        header("Location: login.php");
        exit;
    }

    $userId = $_SESSION['user_id'];

    try {
        // يجب حذف البيانات المرتبطة أولاً التي تعتمد على user_id بسبب قيد المفتاح الأجنبي
        // مثال: حذف نقاط السلوك المرتبطة بالمستخدم
        $pdo->prepare("DELETE FROM user_behavior_points WHERE user_id = ?")->execute([$userId]);

        // هنا يمكنك حذف جداول أخرى مرتبطة بالمستخدم حسب قاعدة بياناتك
        // مثال:
        // $pdo->prepare("DELETE FROM complaints WHERE user_id = ?")->execute([$userId]);
        // $pdo->prepare("DELETE FROM user_settings WHERE user_id = ?")->execute([$userId]);
        // وهكذا...

        // بعد حذف المرتبطات، حذف المستخدم نفسه
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);

        // تدمير الجلسة بعد حذف الحساب
        session_destroy();

        // إعادة التوجيه لصفحة تسجيل الدخول مع رسالة نجاح (يمكنك تعديل الرابط)
        header("Location: login.php?deleted=1");
        exit;

    } catch (PDOException $e) {
        // إذا حصل خطأ (مثل وجود قيود مفتاح أجنبي غير محذوفة)
        $errorMsg = "حدث خطأ أثناء حذف الحساب: " . $e->getMessage();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>MOROCCO LEARNING - إعدادات الحساب</title>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet"/>
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
  width: 100%;
  background-color: #001f2e;
  color: #e0f7fa;
  overflow-x: hidden; /* منع تمرير أفقي غير مرغوب */
  position: relative;
}

/* تعديل رئيسي: التأكد أن الجسم يغطي كامل العرض بدون هوامش */
#particles-js {
  position: fixed;
  width: 100vw;  /* استخدمت vw بدل % لضمان التغطية */
  height: 100vh;
  top: 0; left: 0;
  z-index: 0;
  background: linear-gradient(135deg, #001f2e, #003e52);
}

header {
  z-index: 1000;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 48px;
  background-color: rgba(0,0,0,0.6);
  border-bottom: 1px solid #00ffe7;
  width: 100vw; /* تأكدنا من العرض الكامل */
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
  z-index: 200;
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

/* التعديل الرئيسي على main ليغطي العرض كامل بدون هوامش جانبية */
main {
  max-width: 100vw; /* عرض كامل الشاشة */
  width: 100vw;     /* تأكدنا من العرض */
  padding: 20px 20px 50px; 
  display: flex;
  gap: 20px;
  flex-wrap: nowrap;
  position: relative;
  z-index: 10;
  overflow-x: auto; /* السماح بالتمرير أفقيًا إذا محتوى كبير */
  margin: 0 auto; /* ضبط التمركز */
  box-sizing: border-box;
  margin-top: 100px;
}

/* تعديل بسيط: لا نحدد min-width ضيق حتى لا يتسبب بقصر في بعض الأجهزة */
aside.sidebar {
  flex: 0 0 280px;
  background: rgba(0,0,0,0.75);
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 0 15px #00ffe7;
  height: fit-content;
  min-width: 280px;
  box-sizing: border-box;
}

.sidebar h3 {
  font-size: 18px;
  border-bottom: 1px solid #00ffe7;
  margin-bottom: 15px;
  padding-bottom: 5px;
  color: #00ffe7;
}

.sidebar ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.sidebar ul li {
  margin-bottom: 12px;
  padding: 8px 12px;
  border-radius: 8px;
  cursor: pointer;
  transition: background-color 0.3s, color 0.3s;
  color: #b2ebf2;
  user-select: none;
}

.sidebar ul li:hover,
.sidebar ul li.active {
  background-color: #00ffe7;
  color: #001f2e;
  font-weight: bold;
}

section.content {
  flex: 1 1 auto;
  background: rgba(0,0,0,0.6);
  border-radius: 12px;
  padding: 30px;
  box-shadow: 0 0 15px #00ffe7;
  min-width: 0;
  box-sizing: border-box;
}

section.content h1 {
  margin-top: 0;
  font-size: 28px;
  color: #00ffe7;
  margin-bottom: 20px;
}

section.content h3 {
  color: #00ffe7;
  margin-top: 30px;
}

form {
  margin-top: 10px;
}

form label {
  display: block;
  margin-top: 15px;
  color: #00ffe7;
  font-weight: 600;
}

form input[type="text"],
form input[type="email"],
form input[type="tel"],
form input[type="password"],
form select,
form input[type="file"] {
  width: 100%;
  padding: 10px;
  margin-top: 6px;
  border-radius: 8px;
  border: none;
  background-color: #082c3d;
  color: #e0f7fa;
  font-size: 16px;
  box-shadow: inset 0 0 5px #00575e;
}

form input[type="checkbox"] {
  margin-right: 8px;
  transform: scale(1.2);
  vertical-align: middle;
  cursor: pointer;
}

form button {
  margin-top: 25px;
  background-color: #00ffe7;
  color: #001f2e;
  border: none;
  padding: 12px 25px;
  border-radius: 8px;
  font-weight: bold;
  font-size: 16px;
  cursor: pointer;
  transition: background-color 0.3s;
}

form button:hover:enabled {
  background-color: #00cbbf;
}

form button:disabled {
  background-color: #004d66;
  cursor: not-allowed;
}

form .danger-button {
  background-color: #ff4c4c;
  color: white;
}

form .danger-button:hover:enabled {
  background-color: #cc3a3a;
}

.message {
  margin-top: 12px;
  font-weight: 600;
  color: #00ff99;
}

.message.error {
  color: #ff4c4c;
}

@media (max-width: 900px) {
  main {
    flex-direction: column;
    padding: 20px;
    width: 100vw; /* عرض كامل */
  }
  aside.sidebar {
    width: 100%;
    margin-bottom: 25px;
  }
  section.content {
    width: 100%;
    min-width: unset;
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
.red {
  color: red;
}
.green {
  color: green;
}
.content-item {
  display: none;
}

.modal {
    position: fixed;
    top: 0; left: 0; right:0; bottom:0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
  }
  .modal-content {
    background: #222;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    color: #eee;
    max-width: 300px;
    width: 90%;
  }
  .modal-content button {
    margin: 10px 5px 0 5px;
    padding: 8px 16px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
  }
  .modal-content button.danger-button {
    background-color: #dc3545;
    color: white;
  }
  #confirmNo {
    background-color: #6c757d;
    color: white;
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
  
<main>
  <aside class="sidebar" role="navigation" aria-label="القائمة الجانبية للإعدادات">
    <h3>الإعدادات</h3>
    <ul id="settings-menu">
      <li class="active" data-section="profile">معلومات الحساب</li>
      <li data-section="password">تغيير كلمة المرور</li>
      <li data-section="notifications">الإشعارات</li>
      <li data-section="privacy">الخصوصية</li>
      <li data-section="display">اللغة والمنطقة الزمنية</li>
      <li data-section="security">الأمان</li>
      <li data-section="communityGuidelines">معايير المجتمع</li>
      <li data-section="support">الدعم الفني</li>
      <li data-section="delete">حذف الحساب</li>
    </ul>
  </aside>

  <section class="content" id="content-section">
    <div id="profile" class="content-item" style="display:block;">
      <h1>معلومات الحساب الشخصية</h1>
<form id="form-profile" method="post" novalidate>
  <label for="fullname">الاسم الكامل</label>
  <input id="fullname" name="fullname" type="text" 
          value="<?= htmlspecialchars($user['fullname'] ?? '') ?>"
         placeholder="أدخل الإسم الكامل " required readonly />

  <label for="email">البريد الإلكتروني</label>
  <input id="email" name="email" type="email" 
         value="<?php echo htmlspecialchars($user['email']); ?>" 
         placeholder="أدخل البريد الإلكتروني" required readonly />

  <label for="phone">رقم الهاتف</label>
  <input id="phone" name="phone" type="tel" 
         value="<?php echo htmlspecialchars($user['phone']); ?>" 
         placeholder="أدخل رقم الهاتف"  required readonly pattern="^\+?\d{7,15}$" dir="rtl" />

  <button type="submit">حفظ التغييرات</button>
  <div class="message" aria-live="polite"></div>
</form>

    </div>

<div id="password" class="content-item" style="display:none;">
  <h1>تغيير كلمة المرور</h1>
  <form id="form-password" method="post" novalidate>

    <label for="currentPassword">كلمة المرور الحالية</label>
    <input id="currentPassword" name="currentPassword" type="password"
           value="<?= htmlspecialchars($_POST['currentPassword'] ?? '') ?>"
           placeholder="أدخل كلمة المرور الحالية" required minlength="6" />

    <label for="newPassword">كلمة المرور الجديدة</label>
    <input id="newPassword" name="newPassword" type="password"
           value="<?= htmlspecialchars($_POST['newPassword'] ?? '') ?>"
           placeholder="أدخل كلمة المرور الجديدة" required minlength="6" />

    <label for="confirmPassword">تأكيد كلمة المرور الجديدة</label>
    <input id="confirmPassword" name="confirmPassword" type="password"
           value="<?= htmlspecialchars($_POST['confirmPassword'] ?? '') ?>"
           placeholder="أكد كلمة المرور الجديدة" required minlength="6" />

    <button type="submit" name="change_password">تغيير كلمة المرور</button>

    <!-- رسالة الخطأ/النجاح - موجودة دائماً لتحديثها بجافاسكريبت -->
    <div class="message" aria-live="polite">
      <?php if (!empty($passwordMessage)): ?>
        <?= htmlspecialchars($passwordMessage) ?>
      <?php endif; ?>
    </div>

  </form>
</div>

<div id="notifications" class="content-item" style="display:none; direction: rtl; text-align: right;">
    <form method="POST" action="">
        <h1 style="text-align: right;">إعدادات الإشعارات</h1>

        <div style="margin-bottom: 15px; display: flex; direction: ltr; gap: 10px; justify-content: flex-end;">
            <label for="email_notifications" style="margin: 0;">تلقي إشعارات البريد الإلكتروني</label>
            <input type="checkbox" id="email_notifications" name="email_notifications" <?= isset($email_notifications) && $email_notifications ? 'checked' : '' ?>>
        </div>

        <div style="margin-bottom: 15px; display: flex; direction: ltr; gap: 10px; justify-content: flex-end;">
            <label for="sms_notifications" style="margin: 0;">تلقي إشعارات الرسائل النصية</label>
            <input type="checkbox" id="sms_notifications" name="sms_notifications" <?= isset($sms_notifications) && $sms_notifications ? 'checked' : '' ?>>
        </div>

        <button type="submit" name="save_notification_settings">حفظ التغييرات</button>

        <?php if (!empty($notification_message)): ?>
            <p style="color: green; margin-top: 15px; text-align: right;"><?= $notification_message ?></p>
        <?php endif; ?>
    </form>
</div>


<div id="privacy" class="content-item" style="display:none; direction: rtl; text-align: right;">
    <form method="POST" action="">
        <h1 style="text-align: right;">إعدادات الخصوصية</h1>

        <div style="margin-bottom: 15px; display: flex;  direction: ltr ; gap: 10px; justify-content: flex-end;">
        <label for="show_profile" style="margin: 0;">إظهار ملفي الشخصي للآخرين</label>
        <input type="checkbox" id="show_profile" name="show_profile" <?= isset($show_profile) && $show_profile ? 'checked' : '' ?>>
        </div>

        <div style="margin-bottom: 15px; display: flex; direction: ltr ; gap: 10px; justify-content: flex-end;">
        <label for="share_data_with_partners" style="margin: 0;">مشاركة بياناتي مع شركاء</label> 
        <input type="checkbox" id="share_data_with_partners" name="share_data_with_partners" <?= isset($share_data) && $share_data ? 'checked' : '' ?>>
        </div>

        <button type="submit" name="save_privacy_settings" >حفظ التغييرات</button>

        <?php if (!empty($privacy_message)): ?>
            <p style="color: green; margin-top: 15px; text-align:right;"><?= $privacy_message ?></p>
        <?php endif; ?>
    </form>
</div>

<div id="display" class="content-item" style="display:none;">
  <h1>اللغة والمنطقة الزمنية</h1>
<form method="post" action="">
  <select name="language">
    <option value="ar" <?= ($language == 'ar') ? 'selected' : '' ?>>العربية</option>
  </select>

  <select name="timezone">
    <?php foreach ($timezone_identifiers as $tz): ?>
      <option value="<?= $tz ?>" <?= ($timezone == $tz) ? 'selected' : '' ?>><?= $tz ?></option>
    <?php endforeach; ?>
  </select>

  <button type="submit" name="save_language_timezone">حفظ التغييرات</button>

      <!-- عرض الرسالة -->
    <?php if (!empty($lang_tz_message)): ?>
        <p style="color: green;"><?= $lang_tz_message ?></p>
    <?php endif; ?>
</form>
</div>

<div id="security" class="content-item" style="display:none;">
  <h1>الأمان</h1>
  
  <p>
    <strong>ما هي المصادقة الثنائية؟</strong><br />
    المصادقة الثنائية (2FA) هي طبقة أمان إضافية تحمي حسابك بخلاف كلمة المرور فقط.  
    باستخدام تطبيق مثل <em>Google Authenticator</em>، يمكنك توليد رمز مؤقت يتغير كل 30 ثانية للدخول الآمن.
  </p>
  
  <p>
    <strong>لماذا تفعّل المصادقة الثنائية؟</strong><br />
    • حماية حسابك من الاختراق حتى لو تم سرقة كلمة المرور.<br />
    • لا تعتمد على الرسائل النصية التي قد تكون معرضة للتجسس.<br />
    • مجانية وسهلة الاستخدام عبر تطبيقات الهاتف.
  </p>

  <p>
    <strong>كيف تعمل؟</strong><br />
    عند تفعيل المصادقة الثنائية، ستحصل على مفتاح سري لمسحه باستخدام تطبيق Google Authenticator أو أي تطبيق مشابه.  
    بعدها ستدخل رمز مؤقت يُولَّد تلقائياً كل 30 ثانية لتأكيد هويتك عند تسجيل الدخول،
    عند تفعيل المصادقة الثنائية بنجاح، ستظهر لكم عبارة "تفعيل المصادقة الثنائية" باللون الأخضر.
  </p>

<!-- ✅ HTML وPHP -->
<label for="twoFactor" id="labelTwoFactor" style="color: <?= $is2FAEnabled ? 'green' : 'red' ?>">
 <strong>تفعيل المصادقة الثنائية</strong>
</label>

<form method="POST" id="disable2FAForm" style="margin-top: 10px;">
<?php if ($is2FAEnabled): ?>
    <!-- زر لا يرسل الفورم مباشرة -->
    <button type="button" id="btnDisable2FA" style="background-color: red; color: white; border: none; padding: 8px 12px; cursor: pointer;">
      إلغاء المصادقة الثنائية
    </button>
    <!-- حقل مخفي يتم إرساله عند التأكيد -->
    <input type="hidden" name="toggle_2fa" value="disable">
<?php else: ?>
    <a href="setup_2fa.php" 
       style="display: inline-block; background-color: green; color: white; border: none; padding: 8px 12px; cursor: pointer; text-align: center; text-decoration: none; font-size: 16px; border-radius: 3px;">
      تفعيل المصادقة الثنائية
    </a>
<?php endif; ?>
</form>

<?php if (!empty($message)): ?>
  <p style="color: <?= strpos($message, 'نجاح') !== false ? 'green' : 'red' ?>; margin-top: 10px;">
    <?= $message ?>
  </p>
<?php endif; ?>

<!-- مودال تأكيد إلغاء التفعيل بتصميم صفحة 2FA -->
<div id="confirmDisable2FAModal" style="display:none; padding-top: 50px; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.65); z-index: 9999; direction: rtl; backdrop-filter: blur(5px);">
  <div style="background: rgba(0, 0, 0, 0.85); max-width: 420px; margin: 10% auto; padding: 30px 25px; border-radius: 16px; box-shadow: 0 0 20px #00ffe7; text-align: center; color: #e0f7fa; font-family: 'Cairo', sans-serif;">
    <h2 style="color: #00ffe7; font-size: 22px; margin-bottom: 15px;">هل أنت متأكد؟</h2>
    <p style="font-size: 15px; margin-bottom: 20px; color: #d0f0f0;">
      هل أنت متأكد من أنك تريد <strong style="color:#ff4d4d;">إلغاء تفعيل</strong> المصادقة الثنائية؟
    </p>
    <br>
    <div style="display: flex; justify-content: center; gap: 15px;margin-bottom: 15px;">
      <button id="confirmDisable2FA" style="background-color: #ff4d4d; color: #e0f7fa; border: none; padding: 10px 25px; font-size: 16px; border-radius: 8px; cursor: pointer; transition: 0.3s ease;">
        نعم، إلغاء التفعيل
      </button>
      <button id="cancelDisable2FA" style="background-color: #6c757d; color: #e0f7fa; border: none; padding: 10px 25px; font-size: 16px; border-radius: 8px; cursor: pointer; transition: 0.3s ease;">
        إلغاء
      </button>
    </div>
  </div>
</div>
</div>

<div id="communityGuidelines" class="content-item" style="display:none; direction: rtl; font-family: 'Cairo', sans-serif; color: #e0f7fa; padding: 20px;">
  <h1 style="color: #00ffe7; margin-bottom: 15px;">معايير المجتمع</h1>
  <p style="font-size: 16px; line-height: 1.6; margin-bottom: 15px;">
    مرحباً بك في مجتمعنا التعليمي. لضمان بيئة آمنة ومحترمة للجميع، نرجو منك الالتزام بالمعايير التالية:
  </p>
  <ul style="list-style-type: disc; margin-right: 20px; margin-bottom: 20px; font-size: 15px; line-height: 1.5; color: #b0e0e6;">
    <li>احترام جميع الأعضاء وعدم استخدام لغة مسيئة أو تحقيرية.</li>
    <li>عدم نشر محتوى مخالف للقوانين أو حقوق الملكية الفكرية.</li>
    <li>الامتناع عن السلوكيات التي تزعج أو تضر بالمجتمع مثل التنمر أو التهديد.</li>
    <li>الحفاظ على سرية المعلومات الشخصية وعدم مشاركتها بدون إذن.</li>
    <li>المساهمة بشكل إيجابي وبناء في المناقشات والأنشطة التعليمية.</li>
  </ul>
  <p style="font-size: 15px; margin-bottom: 15px;">
    بالإضافة إلى ذلك، يتم تقييم سلوك المستخدمين عبر نظام نقاط السلوك التالي:
  </p>
  <ul style="list-style-type: disc; margin-right: 20px; margin-bottom: 20px; font-size: 15px; line-height: 1.5; color: #b0e0e6;">
    <li>110 نقطة: سلوك جيد جداً، ويستفيد المستخدم من مزايا كثيرة في المنصة.</li>
    <li>100 نقطة: نقطة البداية عند إنشاء الصفحة.</li>
    <li>أقل من 95 نقطة: يمنع المستخدم من الاستفادة من المزايا الجديدة.</li>
    <li>أقل من 90 نقطة: يتم إغلاق الحساب تماماً، ويتم إصدار مذكرة رسمية باسمه، ورقم بطاقته الوطنية، وبريده الإلكتروني، ورقم مساره. ولا يسمح له بالتسجيل مجدداً.</li>
  </ul>
  <p style="font-size: 15px; margin-bottom: 15px;">
    بمشاركتك في هذا الموقع، فإنك توافق على الالتزام بسياسة المجتمع والقوانين ذات الصلة.
  </p>
    <div style="margin-top: 25px; direction: rtl;">
    <button onclick="window.open('behavior_points.php', '_blank')" 
      style="background-color: #00bfa5; color: white; border: none; padding: 10px 20px; font-size: 16px; border-radius: 8px; cursor: pointer; transition: background-color 0.3s;">
      مشاهدة تفاصيل نقاط السلوك
    </button>
  </div>
</div>


<div id="support" class="content-item" style="display:none; padding: 20px; background-color: #013243; color: #e0f7fa; border-radius: 10px; font-family: Arial, sans-serif;">

  <h1 style="margin-bottom: 15px;">الدعم الفني</h1>

  <p style="line-height: 1.6; font-size: 16px;">
    الدعم الفني هو فريق متخصص جاهز لمساعدتك في حال واجهت أي مشكلة تقنية أو استفسار بخصوص استخدام الموقع أو حسابك.  
    نحن هنا لنضمن لك تجربة استخدام سلسة ومريحة، سواء كنت تواجه صعوبات في الإعدادات، تواجه خطأ في النظام،  
    أو لديك أي طلبات أخرى تتعلق بتحسين الخدمات المقدمة.  
    لا تتردد في التواصل معنا، فنحن نقدر ملاحظاتك ونسعى لتقديم الدعم بأسرع وقت ممكن.
  </p>

  <div style="margin-top: 20px; padding: 15px; background-color: #fff3cd; color: #856404; border-radius: 8px; font-weight: bold;">
    ⚠️ انتبه: إذا اخترت التواصل معنا بشأن مشكلة في الإعدادات، يرجى كتابة <span style="text-decoration: underline;">الدعم الفني</span> في حقل الموضوع.<br>
    وإذا كانت لديك اقتراحات أو تحسينات، يرجى كتابة <span style="text-decoration: underline;">تحسينات</span> في حقل الموضوع.
  </div>

  <button id="contact-us" style="
    margin-top: 25px; 
    background-color: #0288d1; 
    color: white; 
    border: none; 
    padding: 12px 25px; 
    font-size: 16px;
    border-radius: 8px; 
    cursor: pointer;
    transition: background-color 0.3s ease;
  " 
  onmouseover="this.style.backgroundColor='#0277bd';" 
  onmouseout="this.style.backgroundColor='#0288d1';"
  onclick="window.location.href='ContactHome.php';">
    تواصل معنا
  </button>

</div>




<div id="delete" class="content-item" style="display:block;">
  <h1>حذف الحساب</h1>
  <p>للتأكيد، اكتب كلمة "حذف" في الصندوق أدناه لتفعيل زر الحذف.</p>
  <?php if (!empty($deleteError)): ?>
    <p style="color:red;"><?= htmlspecialchars($deleteError) ?></p>
  <?php endif; ?>
  <?php if (isset($_GET['deleted']) && $_GET['deleted'] == 1): ?>
    <p id="delete-success" style="color:green;">تم حذف الحساب نهائيًا. سيتم إعادة التوجيه خلال <span id="countdown">3</span> ثواني...</p>
  <?php else: ?>
    <form id="form-delete" method="POST" novalidate>
      <input id="confirmDelete" name="confirmDelete" type="text" placeholder="اكتب 'حذف' هنا" autocomplete="off" />
      <button type="submit" class="danger-button" disabled>حذف الحساب نهائيًا</button>
      <div class="message" aria-live="polite"></div>
    </form>
  <?php endif; ?>
</div>




</section>
</main>
<script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
<script>
  /* تهيئة particles.js */
  particlesJS.load('particles-js', null, function() {
    console.log('particles.js loaded');
  });
  /* إعدادات particles.js */
  particlesJS('particles-js',
    {
      "particles": {
        "number": {
          "value": 60,
          "density": {
            "enable": true,
            "value_area": 800
          }
        },
        "color": {
          "value": "#00ffe7"
        },
        "shape": {
          "type": "circle",
          "stroke": {
            "width": 0,
            "color": "#000000"
          }
        },
        "opacity": {
          "value": 0.4,
          "random": false,
          "anim": {
            "enable": false
          }
        },
        "size": {
          "value": 3,
          "random": true,
          "anim": {
            "enable": false
          }
        },
        "line_linked": {
          "enable": true,
          "distance": 150,
          "color": "#00ffe7",
          "opacity": 0.3,
          "width": 1
        },
        "move": {
          "enable": true,
          "speed": 2,
          "direction": "none",
          "random": false,
          "straight": false,
          "out_mode": "bounce",
          "bounce": true,
          "attract": {
            "enable": false
          }
        }
      },
      "interactivity": {
        "detect_on": "canvas",
        "events": {
          "onhover": {
            "enable": true,
            "mode": "grab"
          },
          "onclick": {
            "enable": true,
            "mode": "push"
          },
          "resize": true
        },
        "modes": {
          "grab": {
            "distance": 140,
            "line_linked": {
              "opacity": 0.6
            }
          },
          "push": {
            "particles_nb": 4
          }
        }
      },
      "retina_detect": true
    }
  );

document.addEventListener('DOMContentLoaded', function () {
  const menuItems = document.querySelectorAll('#settings-menu li');
  const contentItems = document.querySelectorAll('.content-item');

  function showSection(id) {
    contentItems.forEach(section => {
      section.style.display = section.id === id ? 'block' : 'none';
    });
  }

  // عند تحميل الصفحة، أظهر القسم الأول
  if (menuItems.length > 0) {
    const firstSection = menuItems[0].dataset.section;
    menuItems[0].classList.add('active');
    showSection(firstSection);
  }

  // عند النقر على عنصر في القائمة
  menuItems.forEach(item => {
    item.addEventListener('click', function () {
      // إزالة تفعيل الكل
      menuItems.forEach(i => i.classList.remove('active'));
      item.classList.add('active');

      const sectionId = item.dataset.section;
      showSection(sectionId);
    });
  });
});


  /* التحقق وإدارة النماذج */
  function showMessage(form, msg, isError = false) {
    const messageDiv = form.querySelector('.message');
    messageDiv.textContent = msg;
    messageDiv.classList.toggle('error', isError);
  }

  /* التحقق من البريد الإلكتروني */
  function validateEmail(email) {
    // صيغة بسيطة للتحقق من الايميل
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
  }

  /* التحقق من رقم الهاتف */
  function validatePhone(phone) {
    if (phone.trim() === '') return true; // رقم الهاتف اختياري
    const re = /^\+?\d{7,15}$/;
    return re.test(phone);
  }

  /* التحقق من كلمة المرور */
  function validatePasswords(current, newPass, confirm) {
    if (newPass.length < 6) return { valid: false, message: 'كلمة المرور الجديدة يجب أن تكون 6 أحرف على الأقل.' };
    if (newPass !== confirm) return { valid: false, message: 'تأكيد كلمة المرور لا يتطابق مع الجديدة.' };
    if (current.length < 6) return { valid: false, message: 'كلمة المرور الحالية غير صحيحة.' };
    return { valid: true };
  }

  /* معالجات لكل نموذج */
  document.getElementById('form-profile').addEventListener('submit', function(e){
    e.preventDefault();
    const form = e.target;
    const fullName = form.fullName.value.trim();
    const email = form.email.value.trim();
    const phone = form.phone.value.trim();

    if(fullName.length < 3){
      showMessage(form, 'الاسم الكامل يجب أن يكون 3 أحرف على الأقل.', true);
      return;
    }
    if(!validateEmail(email)){
      showMessage(form, 'الرجاء إدخال بريد إلكتروني صحيح.', true);
      return;
    }
    if(!validatePhone(phone)){
      showMessage(form, 'رقم الهاتف غير صالح.', true);
      return;
    }
    // محاكاة نجاح الإرسال
    showMessage(form, 'تم تحديث معلومات الحساب بنجاح.');
    form.reset();
  });

document.getElementById('form-password').addEventListener('submit', function(e) {
  e.preventDefault();
  const form = e.target;
  const current = form.currentPassword.value.trim();
  const newPass = form.newPassword.value.trim();
  const confirm = form.confirmPassword.value.trim();

  if (newPass.length < 6) {
    showMessage(form, 'كلمة المرور الجديدة يجب أن تكون 6 أحرف على الأقل.', true);
    return;
  }
  if (newPass !== confirm) {
    showMessage(form, 'تأكيد كلمة المرور لا يتطابق مع الجديدة.', true);
    return;
  }
  if (current.length < 6) {
    showMessage(form, 'الرجاء إدخال كلمة المرور الحالية.', true);
    return;
  }

  // إرسال الطلب إلى الخادم
  fetch('change_password_settings.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `currentPassword=${encodeURIComponent(current)}&newPassword=${encodeURIComponent(newPass)}`
  })
  .then(response => response.json())
  .then(data => {
    showMessage(form, data.message, !data.success);
    if (data.success) form.reset();
  })
  .catch(() => {
    showMessage(form, 'حدث خطأ في الاتصال بالخادم.', true);
  });
});


  document.getElementById('form-notifications').addEventListener('submit', function(e){
    e.preventDefault();
    const form = e.target;
    showMessage(form, 'تم تحديث إعدادات الإشعارات.');
  });

  document.getElementById('form-privacy').addEventListener('submit', function(e){
    e.preventDefault();
    const form = e.target;
    showMessage(form, 'تم تحديث إعدادات الخصوصية.');
  });


  document.getElementById('form-security').addEventListener('submit', function(e){
    e.preventDefault();
    const form = e.target;
    showMessage(form, 'تم تحديث إعدادات الأمان.');
  });

  const confirmDeleteInput = document.getElementById('confirmDelete');
  const deleteBtn = document.querySelector('#form-delete button');

  confirmDeleteInput.addEventListener('input', function(){
    if(this.value.trim() === 'حذف'){
      deleteBtn.disabled = false;
    } else {
      deleteBtn.disabled = true;
    }
  });
  

document.getElementById('goTo2FASetup').addEventListener('click', () => {
  window.location.href = 'setup_2fa.php'; // غيّر هذا إلى اسم صفحة الإعداد التي ستصممها
});

document.addEventListener("DOMContentLoaded", function () {
  document.getElementById("language").value = "<?php echo $lang; ?>";
  document.getElementById("timezone").value = "<?php echo $tz; ?>";
  });

</script>

<script>
document.addEventListener("DOMContentLoaded", function () {
  const btnDisable2FA = document.getElementById('btnDisable2FA');
  const modal = document.getElementById('confirmDisable2FAModal');
  const confirmBtn = document.getElementById('confirmDisable2FA');
  const cancelBtn = document.getElementById('cancelDisable2FA');
  const form = document.getElementById('disable2FAForm');

  if (btnDisable2FA && modal && confirmBtn && cancelBtn && form) {
    btnDisable2FA.addEventListener('click', function () {
      modal.style.display = 'block';
    });

    confirmBtn.addEventListener('click', function () {
      modal.style.display = 'none';
      form.submit(); // يتم الإرسال هنا بعد التأكيد
    });

    cancelBtn.addEventListener('click', function () {
      modal.style.display = 'none';
    });
  }
});
</script>
<script>

document.addEventListener('DOMContentLoaded', () => {
  const confirmDeleteInput = document.getElementById('confirmDelete');
  const deleteBtn = document.querySelector('#form-delete button');
  const formDelete = document.getElementById('form-delete');
  const messageDiv = formDelete.querySelector('.message');

  const modalHTML = `
    <div id="confirmModal" style="
      display:none;
      position: fixed;
      top:0; left:0; right:0; bottom:0;
      background: rgba(0,0,0,0.5);
      align-items: center;
      justify-content: center;
      z-index: 9999;
    ">
      <div style="
        background: #222;
        padding: 20px;
        border-radius: 8px;
        text-align: center;
        color: #eee;
        max-width: 320px;
        margin: auto;
      ">
        <p>هل أنت متأكد من حذف الحساب نهائيًا؟ هذا الإجراء لا يمكن التراجع عنه.</p>
        <button id="confirmYes" style="
          background-color: #dc3545;
          color: white;
          border: none;
          padding: 10px 20px;
          border-radius: 5px;
          cursor: pointer;
          margin-right: 10px;
        ">نعم، احذف الحساب</button>
        <button id="confirmNo" style="
          background-color: #6c757d;
          color: white;
          border: none;
          padding: 10px 20px;
          border-radius: 5px;
          cursor: pointer;
        ">إلغاء</button>
      </div>
    </div>
  `;

  document.body.insertAdjacentHTML('beforeend', modalHTML);

  const modal = document.getElementById('confirmModal');
  const confirmYesBtn = document.getElementById('confirmYes');
  const confirmNoBtn = document.getElementById('confirmNo');

  deleteBtn.disabled = true;

  confirmDeleteInput.addEventListener('input', () => {
    if (confirmDeleteInput.value.trim() === 'حذف') {
      deleteBtn.disabled = false;
      messageDiv.textContent = '';
    } else {
      deleteBtn.disabled = true;
      messageDiv.textContent = '';
    }
  });

  formDelete.addEventListener('submit', (e) => {
    e.preventDefault();

    if (confirmDeleteInput.value.trim() !== 'حذف') {
      messageDiv.textContent = 'يجب كتابة كلمة "حذف" لتأكيد العملية.';
      messageDiv.style.color = 'red';
      return;
    }

    modal.style.display = 'flex';
  });

  confirmYesBtn.addEventListener('click', () => {
    modal.style.display = 'none';

    // هنا يتم إرسال الطلب للخادم لحذف الحساب
    // ممكن تستخدم AJAX أو فورم مخفي هنا

    let countdown = 3;
    messageDiv.style.color = 'green';

    const updateMessage = () => {
      messageDiv.textContent = `تم حذف الحساب نهائيًا. سيتم إعادة التوجيه خلال ${countdown} ثانية${countdown > 1 ? '' : ''}...`;
      countdown--;
      if (countdown < 0) {
        clearInterval(intervalId);
        // إعادة التوجيه لحذف الحساب فعلياً
        window.location.href = 'settings.php?deleteAccount=1';
      }
    };

    updateMessage(); // عرض أول رسالة فورًا
    const intervalId = setInterval(updateMessage, 1000);

    confirmDeleteInput.value = '';
    deleteBtn.disabled = true;
  });

  confirmNoBtn.addEventListener('click', () => {
    modal.style.display = 'none';
    messageDiv.textContent = 'تم إلغاء حذف الحساب.';
    messageDiv.style.color = 'orange';
  });

  // إذا رابط الصفحة يحتوي ?deleted=1 نعرض رسالة فقط مع عداد العد التنازلي للانتقال لصفحة login.php
  if (window.location.search.includes('deleted=1')) {
    let countdown = 3;
    messageDiv.style.color = 'green';

    const updateMessage = () => {
      messageDiv.textContent = `تم حذف الحساب نهائيًا. سيتم إعادة التوجيه خلال ${countdown} ثانية${countdown > 1 ? '' : ''}...`;
      countdown--;
      if (countdown < 0) {
        clearInterval(intervalId);
        window.location.href = 'login.php';
      }
    };

    updateMessage();
    const intervalId = setInterval(updateMessage, 1000);
  }
});


</script>
</body>
</html>
