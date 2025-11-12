<?php
// إعدادات الاتصال بقاعدة البيانات (عدّل حسب بياناتك)
$host = 'localhost';
$port = 3307;
$dbname = 'moroccolearning';
$username = 'root';
$password = '';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // لا تضع echo هنا في الصفحة الحقيقية لتفادي مشاكل العرض
} catch (PDOException $e) {
    die("❌ خطأ في الاتصال بقاعدة البيانات: " . htmlspecialchars($e->getMessage()));
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $author = trim($_POST['author']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);

    // التحقق من الحقول الأساسية
    if (!$author || !$email || !$title || !$content) {
        $message = "يرجى ملء الحقول المطلوبة.";
    } else {
        // معالجة رفع الصورة
        $image_path = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
            $file_name = $_FILES['image']['name'];
            $file_tmp = $_FILES['image']['tmp_name'];
            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            if (in_array($ext, $allowed_ext)) {
                $new_name = uniqid("img_") . "." . $ext;
                $upload_dir = "uploads/";
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $target_file = $upload_dir . $new_name;
                if (move_uploaded_file($file_tmp, $target_file)) {
                    $image_path = $target_file;
                } else {
                    $message = "حدث خطأ أثناء رفع الصورة.";
                }
            } else {
                $message = "امتداد الصورة غير مدعوم. فقط jpg, jpeg, png, gif مسموح.";
            }
        }

        if (!$message) {
            // ملخص بسيط (أول 150 حرف من المحتوى)
            $summary = mb_substr(strip_tags($content), 0, 150) . "...";

            // إعداد بيانات إضافية
            $category = "مقالات المرسلين";
            $tags = "";

            // تحضير استعلام الإدخال
            $sql = "INSERT INTO articles 
                    (title, author, content, email, phone, summary, image, category, tags, status, created_at) 
                    VALUES 
                    (:title, :author, :content, :email, :phone, :summary, :image, :category, :tags, 'pending', NOW())";

            $stmt = $pdo->prepare($sql);

            try {
                $stmt->execute([
                    ':title' => $title,
                    ':author' => $author,
                    ':content' => $content,
                    ':email' => $email,
                    ':phone' => $phone,
                    ':summary' => $summary,
                    ':image' => $image_path,
                    ':category' => $category,
                    ':tags' => $tags,
                ]);
                $message = "تم إرسال المقال بنجاح! شكراً لمساهمتك.";
            } catch (PDOException $e) {
                $message = "❌ خطأ أثناء حفظ المقال: " . htmlspecialchars($e->getMessage());
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>إرسال مقال جديد</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Cairo&display=swap');
  * {
    margin: 0; padding: 0; box-sizing: border-box; font-family: 'Cairo', sans-serif;
  }
  body {
    background: #001f2e;
    color: #00ffe7;
    min-height: 100vh;
    position: relative;
    overflow-x: hidden;
  }
  #particles-js {
    position: fixed;
    width: 100%;
    height: 100%;
    z-index: 0;
    top: 0; left: 0;
  }
  .container {
    max-width: 900px;
    margin: 80px auto 40px;
    background: rgba(0,31,46,0.85);
    padding: 30px 40px;
    border-radius: 15px;
    box-shadow: 0 0 40px #00ffe7;
    position: relative;
    z-index: 1;
  }
  h1 {
    font-size: 2.2rem;
    margin-bottom: 20px;
    text-align: center;
    color: #00ffe7;
  }
  label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
  }
  input[type="text"],
  input[type="email"],
  input[type="tel"],
  textarea,
  input[type="file"] {
    width: 100%;
    background: #001f2e;
    border: 2px solid #00ffe7;
    border-radius: 8px;
    padding: 12px 15px;
    color: #00ffe7;
    margin-bottom: 20px;
    font-size: 1rem;
    resize: vertical;
    transition: border-color 0.3s ease;
  }
  input[type="text"]:focus,
  input[type="email"]:focus,
  input[type="tel"]:focus,
  textarea:focus,
  input[type="file"]:focus {
    border-color: #00cfc2;
    outline: none;
  }
  textarea {
    min-height: 140px;
  }
  button {
    background: #00ffe7;
    border: none;
    color: #001f2e;
    font-weight: bold;
    padding: 14px 25px;
    font-size: 1.2rem;
    border-radius: 12px;
    cursor: pointer;
    box-shadow: 0 0 15px #00ffe7;
    transition: background-color 0.3s ease;
    width: 100%;
  }
  button:hover {
    background: #00cfc2;
  }
  .message {
    padding: 15px 20px;
    margin-bottom: 20px;
    border-radius: 10px;
    font-weight: 700;
    text-align: center;
  }
  .message.success {
    background-color: #0f4;
    color: #004400;
    box-shadow: 0 0 15px #0f4;
  }
  .message.error {
    background-color: #f44;
    color: #440000;
    box-shadow: 0 0 15px #f44;
  }
          /* تخصيص شريط التمرير */
    ::-webkit-scrollbar {
      width: 10px;
    }

    ::-webkit-scrollbar-track {
      background: #0d1117; /* خلفية داكنة */
    }

    ::-webkit-scrollbar-thumb {
      background: #00f2ff; /* لون ساطع */
      border-radius: 10px;
      box-shadow: 0 0 10px #00f2ff; /* تأثير مضيء */
      transition: background 0.3s ease;
    }

    ::-webkit-scrollbar-thumb:hover {
      background: #00f2ffcc; /* عند التمرير يصبح أكثر سطوعًا */
    }
</style>
</head>
<body>

<div id="particles-js"></div>

<div class="container">
  <h1>إرسال مقال جديد</h1>

  <?php if ($message): ?>
    <div class="message <?php echo strpos($message, 'نجاح') !== false ? 'success' : 'error'; ?>">
      <?php echo htmlspecialchars($message); ?>
    </div>
  <?php endif; ?>

  <form action="submit_article.php" method="post" enctype="multipart/form-data" autocomplete="off">
    <label for="author">اسم الكاتب *</label>
    <input type="text" id="author" name="author" required />

    <label for="email">البريد الإلكتروني *</label>
    <input type="email" id="email" name="email" required />

    <label for="phone">رقم الهاتف</label>
    <input type="tel" id="phone" name="phone" />

    <label for="image">صورة الكتاب</label>
    <input type="file" id="image" name="image" accept="image/*" />

    <label for="title">عنوان المقال *</label>
    <input type="text" id="title" name="title" required />

    <label for="content">نص المقال *</label>
    <textarea id="content" name="content" required></textarea>

    <button type="submit">إرسال المقال</button>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
<script>
  particlesJS.load('particles-js', 'https://cdn.jsdelivr.net/gh/VincentGarreau/particles.js/particles.json', function() {
    console.log('particles.js loaded');
  });
</script>
<script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
<script>
  particlesJS("particles-js", {
    "particles": {
      "number": { "value": 50, "density": { "enable": true, "value_area": 700 }},
      "color": { "value": "#00ffe7" },
      "shape": { "type": "circle" },
      "opacity": { "value": 0.5, "random": true },
      "size": { "value": 4, "random": true },
      "line_linked": {
        "enable": true,
        "distance": 140,
        "color": "#00ffe7",
        "opacity": 0.35,
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
