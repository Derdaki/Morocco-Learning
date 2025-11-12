<?php
session_start();
date_default_timezone_set('Africa/Casablanca');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "moroccolearning";
$port = 3307;

$conn = new mysqli($servername, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

$token = $_GET['token'] ?? '';
$validToken = false;

if (!empty($token)) {
    $stmt = $conn->prepare("SELECT created_at FROM password_resets WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $reset = $result->fetch_assoc();

    if ($reset) {
        $createdAt = strtotime($reset['created_at']);
        if (time() - $createdAt <= 1800) { // 30 دقيقة
            $validToken = true;
        }
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="icon" type="image/png" href="ChatGPT Image 4 juin 2025, 14_53_49.png" />
  <title>إعادة تعيين كلمة المرور</title>
  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: url('https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=1470&q=80') no-repeat center center fixed;
      background-size: cover;
      position: relative;
      height: 100vh;
      color: #00ffe7;
      display: flex;
      justify-content: center;
      align-items: center;
      direction: rtl;
      overflow: hidden;
    }
    body::before {
      content: "";
      position: absolute;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0, 0, 0, 0.6);
      backdrop-filter: blur(6px);
      z-index: 0;
    }
    .container {
      position: relative;
      background: rgba(1, 33, 42, 0.85);
      padding: 40px 35px;
      border-radius: 12px;
      box-shadow: 0 0 30px #00ffe7;
      width: 380px;
      text-align: center;
      z-index: 1;
      animation: fadeIn 1s ease forwards;
    }
    h1 {
      margin-bottom: 22px;
      font-size: 28px;
      font-weight: 700;
      text-shadow: 0 0 10px #00ffe7;
    }
    p {
      font-size: 16px;
      margin-bottom: 28px;
      color: #a0e8e1;
      text-shadow: 0 0 7px #009999;
    }
    input[type="password"] {
      width: 100%;
      padding: 15px 20px;
      border-radius: 9px;
      border: none;
      font-size: 17px;
      outline: none;
      box-sizing: border-box;
      transition: box-shadow 0.35s ease;
      color: #01212a;
      font-weight: 600;
    }
    input[type="password"]::placeholder {
      color: #444;
    }
    input[type="password"]:focus {
      box-shadow: 0 0 15px #00ffe7;
      background: #defafa;
    }
    button {
      margin-top: 28px;
      width: 100%;
      padding: 15px 0;
      border-radius: 9px;
      border: none;
      background: #00ffe7;
      font-size: 18px;
      font-weight: 700;
      color: #01212a;
      cursor: pointer;
      transition: background-color 0.3s ease, box-shadow 0.3s ease;
      box-shadow: 0 0 20px #00ffe7;
    }
    button:hover {
      background-color: #00d4b3;
      box-shadow: 0 0 25px #00d4b3;
    }
    .message {
      margin-top: 15px;
      font-weight: bold;
    }
    .error {
      color: #ff4d4d;
      text-shadow: 0 0 5px #ff0000;
    }
    .success {
      color: #00ffae;
      text-shadow: 0 0 5px #00d488;
    }
    @keyframes fadeIn {
      from {opacity: 0; transform: translateY(-20px);}
      to {opacity: 1; transform: translateY(0);}
    }
  </style>
</head>
<body>

  <div class="container" role="form" aria-label="نموذج إعادة تعيين كلمة المرور">

    <?php
    if (isset($_SESSION['error'])) {
        echo '<div class="message error">' . htmlspecialchars($_SESSION['error']) . '</div>';
        unset($_SESSION['error']);
    }
    if (isset($_SESSION['success'])) {
        echo '<div class="message success">' . htmlspecialchars($_SESSION['success']) . '</div>';
        unset($_SESSION['success']);
    }
    ?>

    <?php if ($validToken): ?>
      <h1>إعادة تعيين كلمة المرور</h1>
      <p>أدخل كلمة المرور الجديدة أدناه.</p>
      <form method="POST" action="update_password.php">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>" />
        <input type="password" name="new_password" placeholder="كلمة المرور الجديدة" required />
        <br><br>
        <input type="password" name="confirm_password" placeholder="تأكيد كلمة المرور" required />
        <button type="submit">تحديث كلمة المرور</button>
      </form>
    <?php else: ?>
      <h1>رابط غير صالح</h1>
      <p>انتهت صلاحية الرابط أو أنه غير صحيح.</p>
    <?php endif; ?>

  </div>

</body>
</html>
