<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require_once '/xampp/htdocs/projet/config/admin_config.php';

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "moroccolearning";
$port = 3307;

$conn = new mysqli($servername, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $passwordInput = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: login-error.php?error=البريد الإلكتروني غير صالح");
        exit;
    }

    if ($email === "admin@taalim.com") {
        handleAdminLogin($email, $passwordInput);
    }

    $stmt = $conn->prepare("SELECT id, fullname, password FROM users WHERE email = ?");
    if (!$stmt) {
        die("خطأ في إعداد الاستعلام: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($passwordInput, $user['password'])) {
            $userId = $user['id'];
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $user['fullname'];

            try {
                $dsn = "mysql:host=$servername;port=$port;dbname=$dbname;charset=utf8";
                $db = new PDO($dsn, $username, $password);
                $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            } catch (PDOException $e) {
                die("خطأ في قاعدة البيانات: " . htmlspecialchars($e->getMessage()));
            }

            $stmt2 = $conn->prepare("SELECT is_enabled FROM user_2fa WHERE user_id = ? LIMIT 1");
            $stmt2->bind_param("i", $userId);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            $row2fa = $result2->fetch_assoc();

            if ($row2fa && $row2fa['is_enabled'] == 1) {
                $_SESSION['require_2fa'] = true;
                header("Location: verify_2fa.php");
                exit;
            } else {
                $_SESSION['logged_in'] = true;
                header("Location: login_success.php");
                exit;
            }
        } else {
            header("Location: login-error.php?error=كلمة المرور غير صحيحة");
            exit;
        }
    } else {
        header("Location: login-error.php?error=البريد الإلكتروني غير مسجل");
        exit;
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <title>التسجيل الدخول</title>
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
  <link rel="icon" type="image/png" href="ChatGPT Image 4 juin 2025, 14_53_49.png">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap');

    * {
      box-sizing: border-box;
      font-family: 'Cairo', sans-serif;
    }

    body, html {
      margin: 0; padding: 0; height: 100%;
      background-color: #001f2e;
      overflow-x: hidden;
      direction: rtl;
      color: #e0f7fa;
    }

    #particles-js {
      position: fixed;
      top: 0; left: 0;
      width: 100%;
      height: 100%;
      z-index: 0;
      background: linear-gradient(135deg, #001f2e 0%, #004d66 100%);
    }

    .container {
      position: relative;
      z-index: 5;
      max-width: 1200px;
      margin: 0 auto;
      padding: 50px 20px;
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      align-items: center;
      min-height: 100vh;
      gap: 40px;
    }

    .hero-text {
      flex: 1 1 450px;
      max-width: 600px;
      padding: 20px;
      text-align: right;
    }

    .hero-text h1 {
      font-size: 48px;
      font-weight: 700;
      margin-bottom: 20px;
      color: #00ffe7;
      letter-spacing: 2px;
    }

    .hero-text p {
      font-size: 20px;
      line-height: 1.6;
      margin-bottom: 30px;
      color: #b2ebf2;
    }

    .hero-text button {
      background-color: #00ffe7;
      color: #001f2e;
      border: none;
      font-weight: 700;
      font-size: 20px;
      padding: 16px 35px;
      border-radius: 8px;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }
    .hero-text button:hover {
      background-color: #00cbbf;
    }

    .login-box, .register-box {
      background: rgba(0, 0, 0, 0.55);
      border-radius: 15px;
      box-shadow: 0 0 20px #00ffe7;
      max-width: 380px;
      width: 100%;
      padding: 35px 30px;
      text-align: center;
      color: #00ffe7;
      transition: box-shadow 0.3s ease, opacity 0.5s ease;
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      position: relative;
    }
    .login-box:hover, .register-box:hover {
      box-shadow: 0 0 40px #00fff0;
    }
    .login-box h2, .register-box h2 {
      margin-bottom: 30px;
      font-weight: 700;
      font-size: 28px;
      letter-spacing: 1px;
    }

    input {
      width: 100%;
      padding: 14px 12px;
      margin: 12px 0;
      border-radius: 8px;
      border: none;
      background-color: #082c3d;
      color: #e0f7fa;
      font-size: 16px;
      outline: none;
      transition: background-color 0.3s, border-color 0.3s;
      direction: ltr;
      text-align: left;
    }
    input:focus {
      background-color: #004d66;
      box-shadow: 0 0 8px #00ffe7;
      border: 2px solid #00ffe7;
      direction: ltr;
      text-align: left;
    }
    input[dir="rtl"] {
      direction: rtl;
      text-align: right;
    }

    button {
      width: 100%;
      background-color: #00ffe7;
      color: #001f2e;
      border: none;
      font-weight: bold;
      font-size: 18px;
      padding: 14px;
      margin-top: 25px;
      border-radius: 8px;
      cursor: pointer;
      transition: background-color 0.3s ease, transform 0.2s ease;
    }
    button:hover:not(:disabled) {
      background-color: #00cbbf;
      transform: scale(1.03);
    }
    button:disabled {
      background-color: #004d66;
      cursor: not-allowed;
      transform: none;
    }

    .create-account {
      margin-top: 20px;
      font-size: 16px;
      color: #b2ebf2;
      text-align: center;
    }
    .create-account a {
      color: #00ffe7;
      font-weight: 700;
      text-decoration: none;
      cursor: pointer;
      transition: text-decoration 0.3s;
    }
    .create-account a:hover {
      text-decoration: underline;
    }

    .register-box {
      display: none;
      flex-direction: column;
      text-align: right;
    }

    .form-step {
      display: none;
      flex-direction: column;
      opacity: 0;
      transition: opacity 0.5s ease;
    }
    .form-step.active {
      display: flex;
      opacity: 1;
    }

    .step-buttons {
      display: flex;
      justify-content: space-between;
      margin-top: 20px;
      gap: 10px;
    }
    .step-buttons button {
      width: 48%;
      font-size: 16px;
    }
    .step-buttons button#prevBtn.hidden {
      display: none;
    }

    .error-message {
      color: #ff6b6b;
      font-weight: 700;
      min-height: 22px;
      margin-top: 4px;
      text-align: right;
      font-size: 14px;
    }

    .password-wrapper {
      position: relative;
      width: 100%;
      margin: 12px 0;
    }
    .password-wrapper input {
      padding-right: 45px;
      direction: ltr;
      text-align: left;
    }
    .password-toggle {
      position: absolute;
      top: 50%;
      right: 12px;
      transform: translateY(-50%);
      cursor: pointer;
      color: #00ffe7;
      font-size: 18px;
      user-select: none;
      transition: color 0.3s;
    }
    .password-toggle:hover {
      color: #00cbbf;
    }

    @media (max-width: 900px) {
      .container {
        flex-direction: column;
        padding: 30px 15px;
        justify-content: center;
      }
      .hero-text {
        max-width: 100%;
        text-align: center;
        margin-bottom: 40px;
      }
      .hero-text h1 {
        font-size: 36px;
      }
      .hero-text p {
        font-size: 18px;
      }
      .login-box, .register-box {
        max-width: 100%;
        width: 100%;
        padding: 25px 20px;
      }
    }
    .register-box h2 {
      text-align: center;
      width: 100%;
      margin-bottom: 20px;
    }
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
  select {
  width: 100%;
  padding: 14px 12px;
  margin: 12px 0;
  border-radius: 8px;
  border: none;
  background-color: #082c3d;
  color: #e0f7fa;
  font-size: 16px;
  outline: none;
  transition: background-color 0.3s, border-color 0.3s;
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
  direction: rtl;
  text-align: right;
  background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 4 5'%3E%3Cpath fill='%23e0f7fa' d='M2 0L0 2h4z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: left 12px center;
  background-size: 12px;
}

select:focus {
  background-color: #004d66;
  box-shadow: 0 0 8px #00ffe7;
  border: 2px solid #00ffe7;
}

.form-message {
  margin: 15px 0;
  padding: 12px 20px;
  border-radius: 8px;
  font-weight: bold;
  font-size: 16px;
  text-align: center;
  display: none;
}

.form-message.success {
  background-color: #d4edda;
  color: #155724;
  border: 2px solid #c3e6cb;
}

.form-message.error {
  background-color: #f8d7da;
  color: #721c24;
  border: 2px solid #f5c6cb;
}

  </style>
</head>
<body>

  <div id="particles-js"></div>

  <main class="container" role="main">
    <section class="hero-text" aria-label="مقدمة عن ISTALERNING">
      <h1>اكتشف عالم التعلم الذكي مع MOROCCO LEARNING</h1>
      <p>منصة MOROCCO LEARNING تقدم لك أفضل الدورات التعليمية والتدريبات العملية لتطوير مهاراتك والوصول لأهدافك بثقة واحترافية.</p>
      <a href="Home.html">
        <button id="btnLogin" aria-haspopup="true" aria-controls="signupSidebar" aria-expanded="false" style="width: 60%;">العودة إلى الصفحة الرئيسية</button>
      </a>
    </section>

    <section class="login-box" role="form" aria-label="نموذج تسجيل الدخول">
      <h2>تسجيل الدخول</h2>
      <form method="POST" action="login.php" id="loginForm" novalidate>
          <input type="email" name="email" placeholder="البريد الإلكتروني" required aria-required="true" aria-describedby="loginEmailError" />
          <div id="loginEmailError" class="error-message" aria-live="polite"></div>
          <div class="password-wrapper">
            <input type="password" name="password" placeholder="كلمة المرور" required aria-required="true" aria-describedby="loginPasswordError" />
            <span class="password-toggle" role="button" aria-label="إظهار أو إخفاء كلمة المرور" tabindex="0">&#128065;</span>
          </div>
          <div id="loginPasswordError" class="error-message" aria-live="polite"></div>
          <button type="submit" aria-label="تسجيل الدخول">تسجيل الدخول</button>
      </form>
      <p class="create-account">
        لا تملك حسابًا؟ <a href="#" id="showRegister" aria-controls="registerBox" aria-expanded="false">إنشاء حساب جديد</a>
      </p>
    </section>

    <section class="register-box" id="registerBox" role="form" aria-label="نموذج التسجيل">
    <div id="formMessage" class="form-message" aria-live="polite"></div>

      <h2>إنشاء حساب جديد</h2>
      <form id="registerForm" novalidate>
        <!-- الخطوة 1 -->
        <div class="form-step active" id="step1" aria-live="polite">
          <input type="text" id="fullname" name="fullname" placeholder="الاسم الكامل" required aria-required="true" aria-describedby="fullnameError" />
          <div id="fullnameError" class="error-message"></div>

          <input type="email" id="regEmail" name="email" placeholder="البريد الإلكتروني" required aria-required="true" aria-describedby="regEmailError" />
          <div id="regEmailError" class="error-message"></div>

          <input type="tel" id="phone" name="phone" placeholder="رقم الهاتف" pattern="^\+?\d{9,15}$" aria-describedby="phoneError" />
          <div id="phoneError" class="error-message"></div>
        </div>

        <!-- الخطوة 2 -->
        <div class="form-step" id="step2" aria-live="polite" aria-hidden="true">
          <div class="password-wrapper">
            <input type="password" id="regPassword" name="password" placeholder="كلمة المرور" required aria-required="true" aria-describedby="passwordError" />
            <span class="password-toggle" role="button" aria-label="إظهار أو إخفاء كلمة المرور" tabindex="0">&#128065;</span>
          </div>
          <div id="passwordError" class="error-message"></div>

          <div class="password-wrapper">
            <input type="password" id="confirmPassword" name="confirmPassword" placeholder="تأكيد كلمة المرور" required aria-required="true" aria-describedby="confirmPasswordError" />
            <span class="password-toggle" role="button" aria-label="إظهار أو إخفاء كلمة المرور" tabindex="0">&#128065;</span>
          </div>
          <div id="confirmPasswordError" class="error-message"></div>
        </div>

        <!-- الخطوة 3 -->
        <div class="form-step" id="step3" aria-live="polite" aria-hidden="true">
          <input type="text" id="cne" name="cne" pattern="[A-Z]{1,2}[0-9]{5,6}" placeholder="رقم البطاقة الوطنية" required />
          <div id="cneError" class="error-message"></div>
          
          <input type="text" id="massar" name="massar" pattern="[A-Z][0-9]{9}" placeholder="رقم مسار" required />
          <div id="massarError" class="error-message"></div>
          
          <select id="gender" name="gender" required>
            <option value="" disabled selected>الجنس</option>
            <option value="ذكر">ذكر</option>
            <option value="أنثى">أنثى</option>
          </select>
          <div id="genderError" class="error-message"></div>

          <input type="date" id="birthdate" name="birthdate" placeholder="تاريخ الميلاد (YYYY-MM-DD)" required />
          <div id="birthdateError" class="error-message"></div>

          <select id="educationLevel" name="educationLevel" required>
            <option value="" disabled selected>المستوى الدراسي</option>
            <option value="أولى إعدادي">أولى إعدادي</option>
            <option value="ثانية إعدادي">ثانية إعدادي</option>
            <option value="ثالثة إعدادي">ثالثة إعدادي</option>
            <option value="أولى باكالوريا">أولى باكالوريا</option>
            <option value="ثانية باكالوريا">ثانية باكالوريا</option>
          </select>
          <div id="educationLevelError" class="error-message"></div>

          <select id="specialty" name="specialty" required>
            <option value="" disabled selected>اختر تخصصك</option>
            <optgroup label="السلك الإعدادي">
              <option value="السنة الأولى إعدادي">السنة الأولى إعدادي</option>
              <option value="السنة الثانية إعدادي">السنة الثانية إعدادي</option>
              <option value="السنة الثالثة إعدادي">السنة الثالثة إعدادي</option>
            </optgroup>
            <optgroup label="الثانوي التأهيلي">
              <option value="علوم رياضية أ">علوم رياضية أ</option>
              <option value="علوم رياضية ب">علوم رياضية ب</option>
              <option value="علوم فيزيائية">علوم فيزيائية</option>
              <option value="علوم الحياة والأرض">علوم الحياة والأرض</option>
              <option value="علوم زراعية">علوم زراعية</option>
              <option value="العلوم والتكنولوجيات الكهربائية">العلوم والتكنولوجيات الكهربائية</option>
              <option value="العلوم والتكنولوجيات الميكانيكية">العلوم والتكنولوجيات الميكانيكية</option>
              <option value="العلوم الاقتصادية">العلوم الاقتصادية</option>
              <option value="التدبير المحاسباتي">التدبير المحاسباتي</option>
              <option value="الآداب">الآداب</option>
              <option value="الآداب العصرية">الآداب العصرية</option>
              <option value="العلوم الإنسانية">العلوم الإنسانية</option>
              <option value="العلوم الشرعية">العلوم الشرعية</option>
              <option value="اللغة العربية">اللغة العربية</option>
            </optgroup>
            <option value="غير ذلك">غير ذلك</option>
          </select>
          <div id="specialtyError" class="error-message"></div>

          <input type="text" id="institution" name="institution" placeholder="اسم المؤسسة التعليمية" required />
          <div id="institutionError" class="error-message"></div>

          <input type="text" id="address" name="address" placeholder="العنوان الكامل" required />
          <div id="addressError" class="error-message"></div>
        </div>

        <!-- أزرار التنقل بين الخطوات -->
        <div class="step-buttons">
          <button type="button" id="prevBtn" class="hidden" aria-label="الخطوة السابقة">السابق</button>
          <button type="button" id="nextBtn" aria-label="الخطوة التالية">التالي</button>
        </div>

        <button type="submit" id="registerSubmit" style="display:none;" aria-label="إنهاء التسجيل">إنهاء التسجيل</button>
      </form>

      <p class="create-account">
        لديك حساب؟ <a href="#" id="showLogin" aria-controls="loginBox" aria-expanded="false">تسجيل الدخول</a>
      </p>
    </section>

  </main>

<script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
<script>
  particlesJS('particles-js', {
    particles: {
      number: { value: 200, density: { enable: true, value_area: 1500 } },
      color: { value: '#00ffe7' },
      shape: { type: 'circle' },
      opacity: { value: 0.5, anim: { enable: true, speed: 1, opacity_min: 0.1, sync: false } },
      size: { value: 3, random: true },
      line_linked: { enable: true, distance: 150, color: '#00ffe7', opacity: 0.3, width: 1 },
      move: { enable: true, speed: 2, direction: 'none', random: false, straight: false, out_mode: 'bounce', bounce: true }
    },
    interactivity: {
      detect_on: 'canvas',
      events: {
        onhover: { enable: true, mode: 'grab' },
        onclick: { enable: true, mode: 'push' },
        resize: true
      },
      modes: {
        grab: { distance: 140, line_linked: { opacity: 0.5 } },
        push: { particles_nb: 4 }
      }
    },
    retina_detect: true
  });

  const loginBox = document.querySelector('.login-box');
  const registerBox = document.querySelector('.register-box');
  const showRegisterLink = document.getElementById('showRegister');
  const showLoginLink = document.getElementById('showLogin');

  showRegisterLink.addEventListener('click', e => {
    e.preventDefault();
    loginBox.style.display = 'none';
    registerBox.style.display = 'flex';
    showRegisterLink.setAttribute('aria-expanded', 'true');
    showLoginLink.setAttribute('aria-expanded', 'false');
    resetRegisterForm();
  });

  showLoginLink.addEventListener('click', e => {
    e.preventDefault();
    loginBox.style.display = 'block';
    registerBox.style.display = 'none';
    showRegisterLink.setAttribute('aria-expanded', 'false');
    showLoginLink.setAttribute('aria-expanded', 'true');
    resetRegisterForm();
  });

  const registerForm = document.getElementById('registerForm');
  const steps = Array.from(registerForm.querySelectorAll('.form-step'));
  const prevBtn = document.getElementById('prevBtn');
  const nextBtn = document.getElementById('nextBtn');
  const registerSubmit = document.getElementById('registerSubmit');
  let currentStep = 0;

  function showStep(n) {
    steps.forEach((step, i) => {
      step.classList.toggle('active', i === n);
      step.setAttribute('aria-hidden', i !== n);
    });
    prevBtn.classList.toggle('hidden', n === 0);
    if (n === steps.length - 1) {
      nextBtn.style.display = 'none';
      registerSubmit.style.display = 'block';
    } else {
      nextBtn.style.display = 'inline-block';
      registerSubmit.style.display = 'none';
    }
  }
  showStep(currentStep);

  function validateStep(n) {
    let valid = true;
    const errorMessages = steps[n].querySelectorAll('.error-message');
    errorMessages.forEach(msg => msg.textContent = '');

    if (n === 0) {
      const fullname = document.getElementById('fullname');
      const email = document.getElementById('regEmail');
      const phone = document.getElementById('phone');

      if (!fullname.value.trim()) {
        showError('fullnameError', 'يرجى إدخال الاسم الكامل');
        valid = false;
      }
      if (!email.value.trim()) {
        showError('regEmailError', 'يرجى إدخال البريد الإلكتروني');
        valid = false;
      } else if (!validateEmail(email.value.trim())) {
        showError('regEmailError', 'البريد الإلكتروني غير صالح');
        valid = false;
      }
      if (!phone.value.trim()) {
        showError('phoneError', 'يرجى إدخال رقم الهاتف');
        valid = false;
      } else {
        const phoneRegex = /^\+?\d{9,15}$/;
        if (!phoneRegex.test(phone.value.trim())) {
          showError('phoneError', 'رقم الهاتف غير صالح');
          valid = false;
        }
      }
    }

    if (n === 1) {
      const password = document.getElementById('regPassword');
      const confirmPassword = document.getElementById('confirmPassword');

      if (!password.value) {
        showError('passwordError', 'يرجى إدخال كلمة المرور');
        valid = false;
      } else if (!validatePasswordStrength(password.value)) {
        showError('passwordError', 'يجب أن تحتوي كلمة المرور على 8 أحرف على الأقل، وحرف كبير، ورقم');
        valid = false;
      }

      if (!confirmPassword.value) {
        showError('confirmPasswordError', 'يرجى تأكيد كلمة المرور');
        valid = false;
      } else if (password.value !== confirmPassword.value) {
        showError('confirmPasswordError', 'كلمات المرور غير متطابقة');
        valid = false;
      }
    }

    if (n === 2) {
      const requiredFields = [
        { id: 'cne', message: 'يرجى إدخال رقم البطاقة الوطنية' },
        { id: 'massar', message: 'يرجى إدخال رقم مسار' },
        { id: 'gender', message: 'يرجى اختيار الجنس' },
        { id: 'birthdate', message: 'يرجى إدخال تاريخ الميلاد' },
        { id: 'educationLevel', message: 'يرجى اختيار المستوى الدراسي' },
        { id: 'specialty', message: 'يرجى اختيار التخصص الأكاديمي' },
        { id: 'institution', message: 'يرجى إدخال اسم المؤسسة' },
        { id: 'address', message: 'يرجى إدخال العنوان الكامل' },
      ];

      requiredFields.forEach(field => {
        const el = document.getElementById(field.id);
        if (!el.value.trim()) {
          showError(field.id + 'Error', field.message);
          valid = false;
        }
      });
    }

    return valid;
  }

  function showError(id, message) {
    const el = document.getElementById(id);
    if (el) el.textContent = message;
  }

  function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
  }

  function validatePasswordStrength(pw) {
    const re = /^(?=.*[A-Z])(?=.*\d).{8,}$/;
    return re.test(pw);
  }

  nextBtn.addEventListener('click', () => {
    if (validateStep(currentStep)) {
      currentStep++;
      showStep(currentStep);
    }
  });

  prevBtn.addEventListener('click', () => {
    currentStep--;
    showStep(currentStep);
  });

  registerForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!validateStep(currentStep)) return;

    const formData = {
        fullname: document.getElementById('fullname').value.trim(),
        email: document.getElementById('regEmail').value.trim(),
        phone: document.getElementById('phone').value.trim(),
        password: document.getElementById('regPassword').value,
        confirmPassword: document.getElementById('confirmPassword').value,
        cne: document.getElementById('cne').value.trim(),
        massar: document.getElementById('massar').value.trim(),
        gender: document.getElementById('gender').value,
        birthdate: document.getElementById('birthdate').value.trim(),
        educationLevel: document.getElementById('educationLevel').value,
        specialty: document.getElementById('specialty').value,
        institution: document.getElementById('institution').value.trim(),
        address: document.getElementById('address').value.trim(),
    };

    try {
        const response = await fetch('Register.php', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(formData),
        });

        // التحقق من حالة الاستجابة
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const text = await response.text();
        let data;
        
        try {
            data = JSON.parse(text);
        } catch (parseError) {
            console.error('Failed to parse JSON:', text);
            throw new Error('Invalid JSON response from server');
        }

        if (data.success) {
            showFormMessage('success', data.message);
            setTimeout(() => {
                window.location.href = "confirm.php?email=" + encodeURIComponent(formData.email);
            }, 1500);
        } else {
            showFormMessage('error', 'خطأ: ' + data.message);
        }
    } catch (error) {
        console.error('Registration error:', error);
        showFormMessage('error', 'حدث خطأ في الاتصال بالخادم، المرجو المحاولة لاحقًا');
    }
  });

  document.querySelectorAll('.password-toggle').forEach(toggle => {
    toggle.addEventListener('click', () => {
      const input = toggle.previousElementSibling;
      if (input.type === 'password') {
        input.type = 'text';
        toggle.innerHTML = '👀';
      } else {
        input.type = 'password';
        toggle.innerHTML = '🔒';
      }
    });
    toggle.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        toggle.click();
      }
    });
  });

  function resetRegisterForm() {
    registerForm.reset();
    currentStep = 0;
    showStep(currentStep);
    registerForm.querySelectorAll('.error-message').forEach(em => em.textContent = '');
  }

  const loginForm = document.getElementById('loginForm');
  loginForm.addEventListener('submit', e => {
    let valid = true;
    loginForm.querySelectorAll('input').forEach(input => {
      const errorDiv = document.getElementById(input.name + 'Error') || input.nextElementSibling;
      if (input.value.trim() === '') {
        errorDiv.textContent = 'هذا الحقل مطلوب';
        valid = false;
      } else {
        errorDiv.textContent = '';
      }
    });
    if (!valid) e.preventDefault();
  });
  
  function showFormMessage(type, message) {
    const formMessage = document.getElementById('formMessage');
    formMessage.textContent = message;
    formMessage.className = `form-message ${type}`;
    formMessage.style.display = 'block';
  }
</script>

</body>
</html>