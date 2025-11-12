  <?php
session_start();

// منع الكاش
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

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

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT fullname, email, phone, address, cne, massar, gender, birthdate, education_level, specialty, institution, graduation_year FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    echo "المستخدم غير موجود.";
    exit();
}

$user = $result->fetch_assoc();

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
  <html dir="rtl" lang="ar">
  <head>
  <meta charset="utf-8"/>
  <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
  <title>MOROCCO LEARNING - الدورات</title>
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
        main.container {
        top: 100px; /* لإعطاء مساحة تحت الهيدر */
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
        max-width: 1300px;
        margin: 0 auto;
        padding: 30px;
        gap: 30px;
        flex-wrap: wrap;
      }

      aside.sidebar {
        flex: 0 0 280px;
        background: rgba(0,0,0,0.75);
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 0 15px #00ffe7;
      }

      .sidebar h3 {
        font-size: 18px;
        border-bottom: 1px solid #00ffe7;
        margin-bottom: 15px;
        padding-bottom: 5px;
      }

      .sidebar input[type="text"] {
        width: 100%;
        padding: 10px;
        margin-bottom: 15px;
        border-radius: 6px;
        border: none;
        background-color: #082c3d;
        color: #fff;
      }

      .sidebar ul {
        list-style: none;
        padding: 0;
      }

      .sidebar ul li {
        margin-bottom: 10px;
        cursor: pointer;
        transition: color 0.3s;
      }

      .sidebar ul li:hover {
        color: #00cbbf;
      }

      .content {
        flex: 1;
        min-width: 300px;
      }

      .content-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
      }

      .content-header h1 {
        font-size: 28px;
        color: #00ffe7;
        margin-bottom: 10px;
      }

      .course-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 20px;
      }

      .course-card {
        background-color: rgba(0,0,0,0.6);
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 0 10px #00ffe7;
        transition: 0.3s;
      }

      .course-card:hover {
        box-shadow: 0 0 20px #00fff0;
        transform: translateY(-5px);
      }

      .course-card img {
        width: 100%;
        border-radius: 10px;
        margin-bottom: 10px;
      }

      .course-card h4 {
        color: #b2ebf2;
        margin: 10px 0 5px;
      }

      .course-card p {
        font-size: 14px;
        color: #e0f7fa;
        margin: 0 0 10px;
      }

      .course-info {
        font-size: 13px;
        color: #9ee7ea;
      }

      .certificate {
        margin-top: 8px;
        font-size: 14px;
        color: #00ffe7;
      }

      .certificate i {
        margin-left: 6px;
        color: gold;
      }

      .course-card button {
        margin-top: 10px;
        width: 100%;
        background-color: #00ffe7;
        color: #001f2e;
        border: none;
        padding: 10px;
        border-radius: 6px;
        font-weight: bold;
        cursor: pointer;
        transition: background 0.3s;
      }

      .course-card button:hover {
        background-color: #00cbbf;
      }

      @media(max-width: 900px) {
        main.container {
          flex-direction: column;
          padding: 20px;
        }

        aside.sidebar {
          width: 100%;
        }
      }
      .course-card img {
    width: 100%;
    height: 180px; /* أو أي ارتفاع ثابت يناسبك */
    object-fit: cover;
    border-radius: 10px;
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
  </link></head>
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
  <aside class="sidebar">
  <h3>بحث وتصنيفات</h3>
  <input id="searchInput" placeholder="ابحث عن دورة..." type="text"/>
  <ul>
  <li data-category="الكل">الكل</li>
  <li data-category="برمجة">برمجة</li>
  <li data-category="تصميم">تصميم</li>
  <li data-category="أمن معلومات">أمن معلومات</li>
  <li data-category="ذكاء اصطناعي">ذكاء اصطناعي</li>
  <li data-category="تطوير الذات">تطوير الذات</li>
  <li data-category="التسويق الرقمي">التسويق الرقمي</li>
  <li data-category="اللغة الإنجليزية">اللغة الإنجليزية</li>
  <li data-category="إدارة الأعمال">إدارة الأعمال</li>
  <li data-category="المالية">المالية</li>
  <li data-category="الصحة واللياقة">الصحة واللياقة</li>
  </ul>
  </aside>
  <section class="content">
  <div class="content-header">
  <h1>استعرض الدورات المتاحة</h1>
  </div>
  <div class="course-grid">
  <div class="course-card" data-category="برمجة" data-title="مقدمة في بايثون">
  <img alt="Python Course" src="https://ibsacademy.org/U/c/pathon.jpg"/>
  <h4>مقدمة في بايثون</h4>
  <p>تعلم أساسيات البرمجة باستخدام Python بطريقة ممتعة وعملية.</p>
  <div class="course-info">
  <span><i class="fas fa-clock"></i> 8 ساعات</span> - 
          <span><i class="fas fa-signal"></i> مبتدئ</span> - 
          <span><i class="fas fa-book"></i> 20 درس</span>
  </div>
  <div class="instructor">الأستاذ: أحمد محمد</div>
  <div class="certificate"><i class="fas fa-certificate"></i> شهادة متاحة</div>
  <a href="pagepython.php"><button>عرض التفاصيل</button></a>
  </div>
  <div class="course-card" data-category="تصميم" data-title="تصميم واجهات UI/UX">
  <img alt="Course Image" src="https://mo3alemalhaseb.com/wp-content/uploads/2024/12/%D8%A3%D9%85%D9%A1%D9%A3.png"/>
  <h4>تصميم واجهات UI/UX</h4>
  <p>اكتشف مبادئ التصميم الحديث واصنع واجهات تذهل المستخدم.</p>
  <div class="course-info">
  <span><i class="fas fa-clock"></i> 10 ساعات</span> - 
          <span><i class="fas fa-signal"></i> متوسط</span> - 
          <span><i class="fas fa-book"></i> 25 درس</span>
  </div>
  <div class="instructor">الأستاذة: ليلى حسن</div>
  <div class="certificate"><i class="fas fa-certificate"></i> شهادة متاحة</div>
  <a href="pageuixi.php"><button>عرض التفاصيل</button></a>
  </div>
  <div class="course-card" data-category="برمجة" data-title="جافا سكريبت للمبتدئين">
  <img alt="دورة جافا سكريبت" src="https://files.ably.io/ghost/prod/2023/12/choosing-the-best-javascript-frameworks-for-your-next-project.png"/>
  <h4>جافا سكريبت للمبتدئين</h4>
  <p>تعلم لغة البرمجة الأكثر استخدامًا لتطوير الويب.</p>
  <div class="course-info">
  <span><i class="fas fa-clock"></i> 12 ساعة</span> - 
          <span><i class="fas fa-signal"></i> مبتدئ</span> - 
          <span><i class="fas fa-book"></i> 30 درس</span>
  </div>
  <div class="instructor">الأستاذ: سامي العلي</div>
  <div class="certificate"><i class="fas fa-certificate"></i> شهادة متاحة</div>
  <a href="pagejavascript.php"><button>عرض التفاصيل</button></a>
  </div>
  <div class="course-card" data-category="التسويق الرقمي" data-title="أساسيات التسويق الرقمي">
  <img alt="دورة تسويق رقمي" src="https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&amp;fit=crop&amp;w=300&amp;q=80"/>
  <h4>أساسيات التسويق الرقمي</h4>
  <p>تعلم استراتيجيات التسويق عبر الإنترنت للوصول لجمهورك.</p>
  <div class="course-info">
  <span><i class="fas fa-clock"></i> 15 ساعة</span> - 
          <span><i class="fas fa-signal"></i> متوسط</span> - 
          <span><i class="fas fa-book"></i> 40 درس</span>
  </div>
  <div class="instructor">الأستاذة: نجلاء فارس</div>
  <div class="certificate"><i class="fas fa-certificate"></i> شهادة متاحة</div>
  <a href="pagee-commerce.php"><button>عرض التفاصيل</button></a>
  </div>
  <div class="course-card" data-category="اللغة الإنجليزية" data-title="تعلم اللغة الإنجليزية">
  <img alt="دورة لغة إنجليزية" src="https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&amp;fit=crop&amp;w=300&amp;q=80"/>
  <h4>تعلم اللغة الإنجليزية</h4>
  <p>ابدأ رحلتك في تعلم اللغة الإنجليزية بأسلوب سهل وشيق.</p>
  <div class="course-info">
  <span><i class="fas fa-clock"></i> 20 ساعة</span> - 
          <span><i class="fas fa-signal"></i> مبتدئ</span> - 
          <span><i class="fas fa-book"></i> 50 درس</span>
  </div>
  <div class="instructor">الأستاذ: كريم صالح</div>
  <div class="certificate"><i class="fas fa-certificate"></i> شهادة متاحة</div>
  <a href="pageenglish.php"><button>عرض التفاصيل</button></a>
  </div>
  <div class="course-card" data-category="أمن معلومات" data-title="مقدمة في الأمن السيبراني">
  <img alt="دورة الأمن السيبراني" src="https://madar21.com/wp-content/uploads/2024/05/66584ac1f3bda.jpg.webp"/>
  <h4>مقدمة في الأمن السيبراني</h4>
  <p>تعرف على أساسيات حماية البيانات والشبكات من الهجمات.</p>
  <div class="course-info">
  <span><i class="fas fa-clock"></i> 10 ساعات</span> - 
          <span><i class="fas fa-signal"></i> مبتدئ</span> - 
          <span><i class="fas fa-book"></i> 22 درس</span>
  </div>
  <div class="instructor">الأستاذ: وليد عبد الله</div>
  <div class="certificate"><i class="fas fa-certificate"></i> شهادة متاحة</div>
  <a href="pagesécurité.php"><button>عرض التفاصيل</button></a>
  </div>
  <div class="course-card" data-category="تطوير الذات" data-title="تطوير الذات والمهارات الشخصية">
  <img alt="دورة تطوير الذات" src="https://images.unsplash.com/photo-1525182008055-f88b95ff7980?auto=format&amp;fit=crop&amp;w=300&amp;q=80"/>
  <h4>تطوير الذات والمهارات الشخصية</h4>
  <p>اكتسب مهارات لتحسين حياتك الشخصية والمهنية.</p>
  <div class="course-info">
  <span><i class="fas fa-clock"></i> 8 ساعات</span> - 
          <span><i class="fas fa-signal"></i> جميع المستويات</span> - 
          <span><i class="fas fa-book"></i> 18 درس</span>
  </div>
  <div class="instructor">الأستاذة: مريم الزهراء</div>
  <div class="certificate"><i class="fas fa-certificate"></i> شهادة متاحة</div>
  <a href="pageskills.php"><button>عرض التفاصيل</button></a>
  </div>
  <div class="course-card" data-category="إدارة الأعمال" data-title="إدارة المشاريع الاحترافية">
  <img alt="دورة إدارة المشاريع" src="https://blog.mostaql.com/wp-content/uploads/2016/04/-%D9%88%D8%B9%D9%85%D9%84%D9%8A%D8%A7%D8%AA-%D8%A5%D8%AF%D8%A7%D8%B1%D8%AA%D9%87-%D8%A7%D9%84%D8%A3%D8%B3%D8%A7%D8%B3%D9%8A%D8%A9-%D8%A7%D9%84%D8%AE%D9%85%D8%B3-e1642951169524.jpg"/>
  <h4>إدارة المشاريع الاحترافية</h4>
  <p>تعلم كيفية إدارة المشاريع وتنظيم الفرق بكفاءة.</p>
  <div class="course-info">
  <span><i class="fas fa-clock"></i> 14 ساعة</span> - 
          <span><i class="fas fa-signal"></i> متوسط</span> - 
          <span><i class="fas fa-book"></i> 35 درس</span>
  </div>
  <div class="instructor">الأستاذ: فهد الخالد</div>
  <div class="certificate"><i class="fas fa-certificate"></i> شهادة متاحة</div>
  <a href="pagegestion.php"><button>عرض التفاصيل</button></a>
  </div>
  <div class="course-card" data-category="ذكاء اصطناعي" data-title="الذكاء الاصطناعي للمبتدئين">
  <img alt="دورة الذكاء الاصطناعي" src="https://ar.forex.com/wp-content/uploads/2023/08/Digital.ai-unveils-the-latest-version-of-AI-enhanced-DevOps-product-.jpg"/>
  <h4>الذكاء الاصطناعي للمبتدئين</h4>
  <p>مقدمة إلى عالم الذكاء الاصطناعي وتطبيقاته العملية.</p>
  <div class="course-info">
  <span><i class="fas fa-clock"></i> 16 ساعة</span> - 
          <span><i class="fas fa-signal"></i> مبتدئ</span> - 
          <span><i class="fas fa-book"></i> 40 درس</span>
  </div>
  <div class="instructor">الأستاذة: سلمى خالد</div>
  <div class="certificate"><i class="fas fa-certificate"></i> شهادة متاحة</div>
  <a href="pageIA.php"><button>عرض التفاصيل</button></a>
  </div>
  </div>
  </section>
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

  <script>
    const searchInput = document.getElementById("searchInput");
    const categoryItems = document.querySelectorAll(".sidebar ul li");
    const courseCards = document.querySelectorAll(".course-card");

    // فلترة حسب البحث
    searchInput.addEventListener("input", () => {
      const query = searchInput.value.toLowerCase();
      courseCards.forEach(card => {
        const title = card.getAttribute("data-title").toLowerCase();
        card.style.display = title.includes(query) ? "block" : "none";
      });
    });

    // فلترة حسب التصنيفات
    categoryItems.forEach(item => {
      item.addEventListener("click", () => {
        const selectedCategory = item.getAttribute("data-category");

        courseCards.forEach(card => {
          const cardCategory = card.getAttribute("data-category");
          if (selectedCategory === "الكل" || selectedCategory === cardCategory) {
            card.style.display = "block";
          } else {
            card.style.display = "none";
          }
        });
      });
    });
    
  </script>
  </body>
  </html>
