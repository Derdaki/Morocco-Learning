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

// جلب بيانات المستخدم
$user_id = $_SESSION['user_id'];
$user_stmt = $conn->prepare("SELECT fullname, email, phone, address, cne, massar, gender, birthdate, education_level, specialty, institution, graduation_year FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();

if ($user_result->num_rows !== 1) {
    echo "المستخدم غير موجود.";
    exit();
}

$user = $user_result->fetch_assoc();

// جلب الدورات من قاعدة البيانات
$courses_query = "SELECT * FROM courses ORDER BY created_at DESC";
$courses_result = $conn->query($courses_query);

$courses = [];
$categories = [];
$instructors = [];

if ($courses_result->num_rows > 0) {
    while($row = $courses_result->fetch_assoc()) {
        $courses[] = $row;
        
        // تجميع التصنيفات الفريدة
        if (!in_array($row['category'], $categories)) {
            $categories[] = $row['category'];
        }
        
        // تجميع المدربين الفريدين
        if (!in_array($row['instructor'], $instructors)) {
            $instructors[] = $row['instructor'];
        }
    }
}

// محاولة جلب التقييمات (مع التعامل مع الخطأ إذا كان الجدول غير موجود)
$ratings = [];
try {
    $ratings_query = "SELECT course_id, AVG(rating) as avg_rating, COUNT(*) as rating_count FROM course_ratings GROUP BY course_id";
    $ratings_result = $conn->query($ratings_query);
    while ($row = $ratings_result->fetch_assoc()) {
        $ratings[$row['course_id']] = $row;
    }
} catch (Exception $e) {
    // تجاهل الخطأ إذا كان الجدول غير موجود
}

// محاولة جلد الدورات الموصى بها (مع التعامل مع الخطأ إذا كان الجدول غير موجود)
$recommended_courses = [];
if (!empty($user['specialty'])) {
    try {
        $recommended_query = "SELECT * FROM courses WHERE category = ? LIMIT 3";
        $recommended_stmt = $conn->prepare($recommended_query);
        $recommended_stmt->bind_param("s", $user['specialty']);
        $recommended_stmt->execute();
        $recommended_result = $recommended_stmt->get_result();
        
        while($row = $recommended_result->fetch_assoc()) {
            $recommended_courses[] = $row;
        }
    } catch (Exception $e) {
        // تجاهل الخطأ إذا كانت هناك مشكلة
    }
}

$user_stmt->close();
if (isset($recommended_stmt)) $recommended_stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
  <meta charset="utf-8"/>
  <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
  <meta name="description" content="منصة Morocco Learning - اكتشف أفضل الدورات التعليمية في مختلف المجالات">
  <meta name="keywords" content="دورات تعليمية, تعلم عن بعد, تعلم البرمجة, الذكاء الاصطناعي, تطوير الذات">
  <title>MOROCCO LEARNING - الدورات التعليمية</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&display=swap" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link rel="icon" type="image/png" href="assets/images/logo.png">
  
  <!-- Open Graph / Social Media Meta Tags -->
  <meta property="og:title" content="MOROCCO LEARNING - الدورات التعليمية">
  <meta property="og:description" content="منصة Morocco Learning - اكتشف أفضل الدورات التعليمية في مختلف المجالات">
  <meta property="og:image" content="https://www.moroccolearning.com/assets/images/social-share.jpg">
  <meta property="og:url" content="https://www.moroccolearning.com/learning.php">
  <meta property="og:type" content="website">
  
  <!-- Twitter Meta Tags -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="MOROCCO LEARNING - الدورات التعليمية">
  <meta name="twitter:description" content="منصة Morocco Learning - اكتشف أفضل الدورات التعليمية في مختلف المجالات">
  <meta name="twitter:image" content="https://www.moroccolearning.com/assets/images/social-share.jpg">
  
  <style>
    :root {
      --primary-color: #00ffe7;
      --primary-dark: #00cbbf;
      --secondary-color: #001f2e;
      --secondary-light: #003e52;
      --text-primary: #e0f7fa;
      --text-secondary: #b2ebf2;
      --text-muted: #9ee7ea;
      --bg-dark: #001017;
      --bg-light: #082c3d;
      --success-color: #2ecc71;
      --warning-color: #f39c12;
      --danger-color: #e74c3c;
      --info-color: #3498db;
      --border-radius: 12px;
      --box-shadow: 0 10px 30px rgba(0, 255, 231, 0.1);
      --transition: all 0.3s ease;
    }
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Cairo', sans-serif;
    }
    
    body, html {
      width: 100%;
      min-height: 100vh;
      background-color: var(--secondary-color);
      color: var(--text-primary);
      overflow-x: hidden;
      scroll-behavior: smooth;
      line-height: 1.6;
    }
    
    /* تأثير الجسيمات الخلفية */
    #particles-js {
      position: fixed;
      width: 100%;
      height: 100%;
      top: 0;
      left: 0;
      z-index: -1;
      background: linear-gradient(135deg, var(--secondary-color), var(--secondary-light));
    }
    
    /* التحميل الأولي */
    .preloader {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: var(--secondary-color);
      display: flex;
      justify-content: center;
      align-items: center;
      flex-direction: column;
      z-index: 9999;
      transition: opacity 0.5s ease;
    }
    
    .preloader.fade-out {
      opacity: 0;
    }
    
    .preloader-logo {
      width: 120px;
      height: 120px;
      margin-bottom: 20px;
      animation: pulse 2s infinite;
    }
    
    .preloader-spinner {
      width: 50px;
      height: 50px;
      border: 5px solid rgba(0, 255, 231, 0.2);
      border-radius: 50%;
      border-top-color: var(--primary-color);
      animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
      to { transform: rotate(360deg); }
    }
    
    @keyframes pulse {
      0% { transform: scale(0.95); opacity: 0.8; }
      50% { transform: scale(1.05); opacity: 1; }
      100% { transform: scale(0.95); opacity: 0.8; }
    }
    
    /* الهيدر */
    header {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 1000;
      background-color: rgba(0, 16, 23, 0.95);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border-bottom: 1px solid rgba(0, 255, 231, 0.1);
      padding: 15px 5%;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      transition: var(--transition);
    }
    
    header.scrolled {
      padding: 10px 5%;
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
    }
    
    .logo-container {
      display: flex;
      align-items: center;
      gap: 15px;
    }
    
    .logo {
      font-size: 1.8rem;
      font-weight: 900;
      color: var(--primary-color);
      text-transform: uppercase;
      user-select: none;
      cursor: pointer;
      text-shadow: 0 0 10px rgba(0, 255, 231, 0.5);
      transition: var(--transition);
    }
    
    .logo:hover {
      text-shadow: 0 0 15px rgba(0, 255, 231, 0.8);
    }
    
    .logo-img {
      width: 40px;
      height: 40px;
    }
    
    .subtitle {
      color: var(--text-muted);
      font-size: 0.9rem;
      font-weight: 300;
      opacity: 0;
      animation: fadeIn 1.5s forwards 0.5s;
    }
    
    nav {
      display: flex;
      align-items: center;
      gap: 20px;
    }
    
    .nav-links {
      display: flex;
      list-style: none;
      gap: 25px;
    }
    
    .nav-links a {
      color: var(--text-secondary);
      text-decoration: none;
      font-weight: 600;
      font-size: 1rem;
      position: relative;
      padding: 5px 0;
      transition: var(--transition);
    }
    
    .nav-links a::after {
      content: '';
      position: absolute;
      bottom: 0;
      right: 0;
      width: 0;
      height: 2px;
      background-color: var(--primary-color);
      transition: var(--transition);
    }
    
    .nav-links a:hover {
      color: var(--primary-color);
    }
    
    .nav-links a:hover::after {
      width: 100%;
      left: 0;
    }
    
    .nav-links a.active {
      color: var(--primary-color);
    }
    
    .nav-links a.active::after {
      width: 100%;
      left: 0;
    }
    
    .mobile-menu-btn {
      display: none;
      background: none;
      border: none;
      color: var(--text-primary);
      font-size: 1.5rem;
      cursor: pointer;
    }
    
    /* قائمة الحساب */
    .account-dropdown {
      position: relative;
    }
    
    .account-btn {
      background-color: var(--primary-color);
      color: var(--secondary-color);
      border: none;
      padding: 10px 25px;
      border-radius: var(--border-radius);
      font-weight: 700;
      cursor: pointer;
      transition: var(--transition);
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .account-btn:hover {
      background-color: var(--primary-dark);
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(0, 255, 231, 0.3);
    }
    
    .account-btn i {
      transition: transform 0.3s;
    }
    
    .dropdown-content {
      position: absolute;
      top: 120%;
      right: 0;
      min-width: 220px;
      background-color: var(--bg-light);
      border-radius: var(--border-radius);
      box-shadow: var(--box-shadow);
      z-index: 1100;
      opacity: 0;
      visibility: hidden;
      transform: translateY(-10px);
      transition: var(--transition);
      border: 1px solid rgba(0, 255, 231, 0.1);
    }
    
    .dropdown-content.show {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
    }
    
    .dropdown-content a {
      color: var(--text-primary);
      padding: 12px 20px;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 10px;
      transition: var(--transition);
    }
    
    .dropdown-content a:hover {
      background-color: rgba(0, 255, 231, 0.1);
      padding-right: 25px;
    }
    
    .dropdown-content a i {
      width: 20px;
      text-align: center;
    }
    
    /* المحتوى الرئيسي */
    main.container {
      max-width: 1400px;
      margin: 100px auto 50px;
      padding: 0 20px;
      position: relative;
      z-index: 10;
    }
    
    /* الشريط الجانبي */
    aside.sidebar {
      position: sticky;
      top: 120px;
      background-color: rgba(0, 0, 0, 0.7);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border-radius: var(--border-radius);
      padding: 25px;
      box-shadow: var(--box-shadow);
      border: 1px solid rgba(0, 255, 231, 0.1);
      height: fit-content;
      margin-bottom: 30px;
    }
    
    .sidebar-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }
    
    .sidebar h3 {
      color: var(--primary-color);
      font-size: 1.3rem;
      margin-bottom: 20px;
      position: relative;
      padding-bottom: 10px;
    }
    
    .sidebar h3::after {
      content: '';
      position: absolute;
      bottom: 0;
      right: 0;
      width: 50px;
      height: 2px;
      background: linear-gradient(90deg, var(--primary-color), transparent);
    }
    
    .filter-section {
      margin-bottom: 25px;
    }
    
    .filter-section h4 {
      color: var(--text-secondary);
      font-size: 1rem;
      margin-bottom: 15px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .filter-section h4 i {
      color: var(--primary-color);
    }
    
    .search-box {
      position: relative;
      margin-bottom: 20px;
    }
    
    .search-box input {
      width: 100%;
      padding: 12px 15px 12px 45px;
      border-radius: var(--border-radius);
      border: 1px solid rgba(0, 255, 231, 0.2);
      background-color: rgba(8, 44, 61, 0.5);
      color: var(--text-primary);
      font-size: 0.95rem;
      transition: var(--transition);
    }
    
    .search-box input:focus {
      outline: none;
      border-color: var(--primary-color);
      box-shadow: 0 0 0 2px rgba(0, 255, 231, 0.2);
    }
    
    .search-box i {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--text-muted);
    }
    
    .filter-options {
      list-style: none;
      max-height: 200px;
      overflow-y: auto;
      padding-right: 5px;
    }
    
    .filter-options::-webkit-scrollbar {
      width: 5px;
    }
    
    .filter-options::-webkit-scrollbar-thumb {
      background-color: var(--primary-color);
      border-radius: 5px;
    }
    
    .filter-options li {
      margin-bottom: 10px;
      padding: 8px 12px;
      border-radius: 6px;
      cursor: pointer;
      transition: var(--transition);
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .filter-options li:hover, .filter-options li.active {
      background-color: rgba(0, 255, 231, 0.1);
      color: var(--primary-color);
    }
    
    .filter-options li i {
      font-size: 0.8rem;
    }
    
    .filter-options li .badge {
      margin-right: auto;
      background-color: rgba(0, 255, 231, 0.2);
      color: var(--primary-color);
      padding: 2px 8px;
      border-radius: 10px;
      font-size: 0.7rem;
      font-weight: 600;
    }
    
    .price-range {
      width: 100%;
      margin-bottom: 15px;
    }
    
    .price-range-values {
      display: flex;
      justify-content: space-between;
      color: var(--text-muted);
      font-size: 0.9rem;
    }
    
    .reset-filters {
      width: 100%;
      background-color: transparent;
      border: 1px solid var(--primary-color);
      color: var(--primary-color);
      padding: 10px;
      border-radius: var(--border-radius);
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition);
      margin-top: 10px;
    }
    
    .reset-filters:hover {
      background-color: rgba(0, 255, 231, 0.1);
    }
    
    /* محتوى الدورات */
    .content {
      padding: 0 20px;
    }
    
    .content-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
      flex-wrap: wrap;
      gap: 20px;
    }
    
    .content-header h1 {
      color: var(--primary-color);
      font-size: 2rem;
      position: relative;
      padding-bottom: 10px;
    }
    
    .content-header h1::after {
      content: '';
      position: absolute;
      bottom: 0;
      right: 0;
      width: 80px;
      height: 3px;
      background: linear-gradient(90deg, var(--primary-color), transparent);
    }
    
    .sort-options {
      display: flex;
      align-items: center;
      gap: 15px;
    }
    
    .sort-options select {
      padding: 10px 15px;
      border-radius: var(--border-radius);
      border: 1px solid rgba(0, 255, 231, 0.2);
      background-color: rgba(8, 44, 61, 0.5);
      color: var(--text-primary);
      cursor: pointer;
      transition: var(--transition);
    }
    
    .sort-options select:focus {
      outline: none;
      border-color: var(--primary-color);
    }
    
    .view-toggle {
      display: flex;
      background-color: rgba(8, 44, 61, 0.5);
      border-radius: var(--border-radius);
      overflow: hidden;
    }
    
    .view-toggle button {
      background: none;
      border: none;
      color: var(--text-muted);
      padding: 10px 15px;
      cursor: pointer;
      transition: var(--transition);
    }
    
    .view-toggle button.active {
      background-color: rgba(0, 255, 231, 0.2);
      color: var(--primary-color);
    }
    
    /* بطاقات الدورات */
    .course-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 25px;
      margin-bottom: 50px;
    }
    
    .course-card {
      background-color: rgba(0, 0, 0, 0.6);
      border-radius: var(--border-radius);
      overflow: hidden;
      box-shadow: var(--box-shadow);
      border: 1px solid rgba(0, 255, 231, 0.1);
      transition: var(--transition);
      position: relative;
    }
    
    .course-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 15px 30px rgba(0, 255, 231, 0.2);
      border-color: rgba(0, 255, 231, 0.3);
    }
    
    .course-card .course-badge {
      position: absolute;
      top: 15px;
      left: 15px;
      background-color: var(--primary-color);
      color: var(--secondary-color);
      padding: 5px 10px;
      border-radius: 4px;
      font-size: 0.8rem;
      font-weight: 700;
      z-index: 2;
    }
    
    .course-card .favorite-btn {
      position: absolute;
      top: 15px;
      right: 15px;
      background-color: rgba(0, 0, 0, 0.7);
      border: none;
      width: 36px;
      height: 36px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--text-primary);
      cursor: pointer;
      z-index: 2;
      transition: var(--transition);
    }
    
    .course-card .favorite-btn:hover {
      background-color: rgba(0, 255, 231, 0.2);
      color: var(--primary-color);
    }
    
    .course-card .favorite-btn.active {
      color: var(--danger-color);
    }
    
    .course-card .course-image-container {
      position: relative;
      width: 100%;
      height: 180px;
      overflow: hidden;
    }
    
    .course-card .course-image {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.5s ease;
    }
    
    .course-card:hover .course-image {
      transform: scale(1.05);
    }
    
    .course-card .course-details {
      padding: 20px;
    }
    
    .course-card .course-title {
      color: var(--text-primary);
      font-size: 1.2rem;
      margin-bottom: 10px;
      transition: var(--transition);
    }
    
    .course-card:hover .course-title {
      color: var(--primary-color);
    }
    
    .course-card .course-description {
      color: var(--text-muted);
      font-size: 0.9rem;
      margin-bottom: 15px;
      display: -webkit-box;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    
    .course-card .course-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      margin-bottom: 15px;
    }
    
    .course-card .course-meta-item {
      display: flex;
      align-items: center;
      gap: 5px;
      font-size: 0.85rem;
      color: var(--text-muted);
    }
    
    .course-card .course-meta-item i {
      color: var(--primary-color);
      font-size: 0.9rem;
    }
    
    .course-card .course-instructor {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 15px;
    }
    
    .course-card .instructor-avatar {
      width: 30px;
      height: 30px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid var(--primary-color);
    }
    
    .course-card .instructor-name {
      font-size: 0.9rem;
      color: var(--text-muted);
    }
    
    .course-card .progress-container {
      margin-bottom: 15px;
    }
    
    .course-card .progress-text {
      display: flex;
      justify-content: space-between;
      font-size: 0.8rem;
      color: var(--text-muted);
      margin-bottom: 5px;
    }
    
    .course-card .progress-bar {
      height: 6px;
      background-color: rgba(0, 255, 231, 0.1);
      border-radius: 3px;
      overflow: hidden;
    }
    
    .course-card .progress-fill {
      height: 100%;
      background-color: var(--primary-color);
      border-radius: 3px;
      transition: width 0.5s ease;
    }
    
    .course-card .course-footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 15px;
    }
    
    .course-card .course-price {
      font-weight: 700;
      color: var(--primary-color);
    }
    
    .course-card .course-price.free {
      color: var(--success-color);
    }
    
    .course-card .course-price.discount {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .course-card .course-price.discount .original-price {
      text-decoration: line-through;
      color: var(--text-muted);
      font-size: 0.9rem;
      font-weight: 400;
    }
    
    .course-card .course-action {
      background-color: var(--primary-color);
      color: var(--secondary-color);
      border: none;
      padding: 8px 20px;
      border-radius: var(--border-radius);
      font-weight: 700;
      cursor: pointer;
      transition: var(--transition);
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .course-card .course-action:hover {
      background-color: var(--primary-dark);
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(0, 255, 231, 0.3);
    }
    
    .course-card .course-action.completed {
      background-color: var(--success-color);
    }
    
    .course-card .course-action.completed:hover {
      background-color: #27ae60;
    }
    
    .course-card .course-action i {
      transition: transform 0.3s;
    }
    
    .course-card .course-action:hover i {
      transform: translateX(5px);
    }
    
    .course-card .rating {
      display: flex;
      align-items: center;
      gap: 5px;
      margin-bottom: 10px;
    }
    
    .course-card .rating-stars {
      color: var(--warning-color);
      font-size: 0.9rem;
    }
    
    .course-card .rating-count {
      font-size: 0.8rem;
      color: var(--text-muted);
    }
    
    /* عرض القائمة */
    .course-list {
      display: flex;
      flex-direction: column;
      gap: 20px;
      margin-bottom: 50px;
    }
    
    .course-list-item {
      display: flex;
      background-color: rgba(0, 0, 0, 0.6);
      border-radius: var(--border-radius);
      overflow: hidden;
      box-shadow: var(--box-shadow);
      border: 1px solid rgba(0, 255, 231, 0.1);
      transition: var(--transition);
    }
    
    .course-list-item:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(0, 255, 231, 0.2);
    }
    
    .course-list-item .course-image-container {
      width: 250px;
      height: auto;
      flex-shrink: 0;
    }
    
    .course-list-item .course-details {
      flex: 1;
      padding: 20px;
      display: flex;
      flex-direction: column;
    }
    
    .course-list-item .course-header {
      display: flex;
      justify-content: space-between;
      margin-bottom: 10px;
    }
    
    .course-list-item .course-meta {
      display: flex;
      gap: 20px;
      margin-bottom: 15px;
    }
    
    .course-list-item .course-description {
      margin-bottom: 15px;
      flex: 1;
    }
    
    .course-list-item .course-footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    /* قسم الدورات الموصى بها */
    .recommended-courses {
      margin-bottom: 50px;
    }
    
    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
    }
    
    .section-header h2 {
      color: var(--primary-color);
      font-size: 1.5rem;
      position: relative;
      padding-bottom: 10px;
    }
    
    .section-header h2::after {
      content: '';
      position: absolute;
      bottom: 0;
      right: 0;
      width: 60px;
      height: 2px;
      background: linear-gradient(90deg, var(--primary-color), transparent);
    }
    
    .section-header .view-all {
      color: var(--text-muted);
      text-decoration: none;
      font-size: 0.9rem;
      transition: var(--transition);
      display: flex;
      align-items: center;
      gap: 5px;
    }
    
    .section-header .view-all:hover {
      color: var(--primary-color);
    }
    
    /* التذييل */
    footer {
      background-color: rgba(0, 16, 23, 0.9);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      padding: 50px 5% 20px;
      margin-top: 50px;
      border-top: 1px solid rgba(0, 255, 231, 0.1);
    }
    
    .footer-content {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 30px;
      margin-bottom: 30px;
    }
    
    .footer-column h3 {
      color: var(--primary-color);
      font-size: 1.2rem;
      margin-bottom: 20px;
      position: relative;
      padding-bottom: 10px;
    }
    
    .footer-column h3::after {
      content: '';
      position: absolute;
      bottom: 0;
      right: 0;
      width: 40px;
      height: 2px;
      background: linear-gradient(90deg, var(--primary-color), transparent);
    }
    
    .footer-links {
      list-style: none;
    }
    
    .footer-links li {
      margin-bottom: 12px;
    }
    
    .footer-links a {
      color: var(--text-muted);
      text-decoration: none;
      transition: var(--transition);
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .footer-links a:hover {
      color: var(--primary-color);
      padding-right: 5px;
    }
    
    .footer-links a i {
      font-size: 0.8rem;
    }
    
    .social-links {
      display: flex;
      gap: 15px;
      margin-top: 20px;
    }
    
    .social-links a {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background-color: rgba(0, 255, 231, 0.1);
      color: var(--text-primary);
      transition: var(--transition);
    }
    
    .social-links a:hover {
      background-color: var(--primary-color);
      color: var(--secondary-color);
      transform: translateY(-3px);
    }
    
    .footer-bottom {
      text-align: center;
      padding-top: 20px;
      border-top: 1px solid rgba(0, 255, 231, 0.1);
      color: var(--text-muted);
      font-size: 0.9rem;
    }
    
    /* تأثيرات الحركة */
    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    
    @keyframes slideUp {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .animate {
      animation: slideUp 0.6s ease forwards;
    }
    
    /* شريط التمرير المخصص */
    ::-webkit-scrollbar {
      width: 10px;
      height: 10px;
    }
    
    ::-webkit-scrollbar-track {
      background: var(--bg-dark);
    }
    
    ::-webkit-scrollbar-thumb {
      background: var(--primary-color);
      border-radius: 5px;
    }
    
    ::-webkit-scrollbar-thumb:hover {
      background: var(--primary-dark);
    }
    
    /* رسالة فارغة */
    .empty-state {
      grid-column: 1 / -1;
      text-align: center;
      padding: 50px;
      background-color: rgba(0, 0, 0, 0.3);
      border-radius: var(--border-radius);
      border: 1px dashed rgba(0, 255, 231, 0.3);
    }
    
    .empty-state i {
      font-size: 3rem;
      color: var(--primary-color);
      margin-bottom: 20px;
      opacity: 0.7;
    }
    
    .empty-state h3 {
      color: var(--text-primary);
      margin-bottom: 10px;
    }
    
    .empty-state p {
      color: var(--text-muted);
      max-width: 500px;
      margin: 0 auto 20px;
    }
    
    .empty-state .btn {
      background-color: var(--primary-color);
      color: var(--secondary-color);
      padding: 10px 25px;
      border-radius: var(--border-radius);
      text-decoration: none;
      font-weight: 600;
      display: inline-block;
      transition: var(--transition);
    }
    
    .empty-state .btn:hover {
      background-color: var(--primary-dark);
      transform: translateY(-2px);
    }
    
    /* رسائل التنبيه */
    .alert {
      padding: 15px;
      border-radius: var(--border-radius);
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 15px;
    }
    
    .alert i {
      font-size: 1.5rem;
    }
    
    .alert-success {
      background-color: rgba(46, 204, 113, 0.1);
      border: 1px solid rgba(46, 204, 113, 0.2);
      color: #2ecc71;
    }
    
    .alert-info {
      background-color: rgba(52, 152, 219, 0.1);
      border: 1px solid rgba(52, 152, 219, 0.2);
      color: #3498db;
    }
    
    .alert-warning {
      background-color: rgba(243, 156, 18, 0.1);
      border: 1px solid rgba(243, 156, 18, 0.2);
      color: #f39c12;
    }
    
    .alert-danger {
      background-color: rgba(231, 76, 60, 0.1);
      border: 1px solid rgba(231, 76, 60, 0.2);
      color: #e74c3c;
    }
    
    /* التكيف مع الشاشات الصغيرة */
    @media (max-width: 1200px) {
      .course-grid {
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      }
    }
    
    @media (max-width: 992px) {
      header {
        padding: 15px 3%;
      }
      
      .nav-links {
        gap: 15px;
      }
      
      .course-list-item {
        flex-direction: column;
      }
      
      .course-list-item .course-image-container {
        width: 100%;
        height: 200px;
      }
    }
    
    @media (max-width: 768px) {
      .mobile-menu-btn {
        display: block;
      }
      
      .nav-links {
        position: fixed;
        top: 80px;
        right: -100%;
        width: 80%;
        max-width: 300px;
        height: calc(100vh - 80px);
        background-color: var(--bg-dark);
        flex-direction: column;
        align-items: flex-start;
        padding: 30px;
        transition: var(--transition);
        z-index: 1000;
        border-left: 1px solid rgba(0, 255, 231, 0.1);
      }
      
      .nav-links.active {
        right: 0;
      }
      
      .account-dropdown {
        margin-right: auto;
        margin-left: 20px;
      }
      
      .content-header {
        flex-direction: column;
        align-items: flex-start;
      }
      
      .sort-options {
        width: 100%;
        justify-content: space-between;
      }
    }
    
    @media (max-width: 576px) {
      .logo {
        font-size: 1.5rem;
      }
      
      .course-grid {
        grid-template-columns: 1fr;
      }
      
      .footer-content {
        grid-template-columns: 1fr;
      }
      
      .filter-section {
        margin-bottom: 20px;
      }
    }
  </style>
</head>
<body>
  <!-- التحميل الأولي -->
  <div class="preloader">
    <img src="assets/images/logo.png" alt="Logo" class="preloader-logo">
    <div class="preloader-spinner"></div>
  </div>

  <!-- الهيدر -->
  <header id="main-header">
    <div class="logo-container">
      <img src="assets/images/logo.png" alt="Logo" class="logo-img">
      <div>
        <div class="logo">MOROCCO learning</div>
        <p class="subtitle slide-up">Moroccan National Online Learning Platform</p>
      </div>
    </div>
    
    <button class="mobile-menu-btn" id="mobile-menu-btn">
      <i class="fas fa-bars"></i>
    </button>
    
    <nav>
      <ul class="nav-links" id="nav-links">
        <li><a href="learning.php" class="active"><i class="fas fa-home"></i> الرئيسية</a></li>
        <li><a href="Articles.php"><i class="fas fa-book-open"></i> مكتبة المقالات</a></li>
        <li><a href="faq.php"><i class="fas fa-question-circle"></i> الأسئلة الشائعة</a></li>
        <li><a href="ContactHome.php"><i class="fas fa-envelope"></i> تواصل معنا</a></li>
        <li><a href="Game.php"><i class="fas fa-gamepad"></i> بوابة التعليم</a></li>
      </ul>
      
      <div class="account-dropdown">
        <button class="account-btn" id="account-btn">
          <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($user['fullname']); ?>
        </button>
        <div class="dropdown-content" id="dropdown-content">
          <a href="profil.php"><i class="fas fa-user"></i> ملفي الشخصي</a>
          <a href="Certificate.php"><i class="fas fa-certificate"></i> شهاداتي</a>
          <a href="settings.php"><i class="fas fa-cog"></i> الإعدادات</a>
          <a href="loginout.php"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
        </div>
      </div>
    </nav>
  </header>

  <!-- المحتوى الرئيسي -->
  <main class="container">
    <div class="alert alert-info">
      <i class="fas fa-info-circle"></i>
      <div>
        <strong>مرحباً <?php echo htmlspecialchars($user['fullname']); ?>!</strong> لديك <a href="Certificate.php" style="color: inherit; text-decoration: underline;">3 دورات</a> قيد التقدم و <a href="Certificate.php" style="color: inherit; text-decoration: underline;">شهادتان</a> مكتملة.
      </div>
    </div>
    
    <div class="row">
      <!-- الشريط الجانبي -->
      <aside class="sidebar">
        <div class="sidebar-header">
          <h3>تصفية الدورات</h3>
          <button class="reset-filters" id="reset-filters">
            <i class="fas fa-redo"></i> إعادة تعيين
          </button>
        </div>
        
        <div class="filter-section">
          <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="search-input" placeholder="ابحث عن دورة...">
          </div>
        </div>
        
        <div class="filter-section">
          <h4><i class="fas fa-tags"></i> التصنيفات</h4>
          <ul class="filter-options" id="category-filter">
            <li class="active" data-category="all">الكل</li>
            <?php foreach ($categories as $category): ?>
              <li data-category="<?php echo htmlspecialchars($category); ?>">
                <?php echo htmlspecialchars($category); ?>
                <span class="badge"><?php echo count(array_filter($courses, function($c) use ($category) { return $c['category'] === $category; })); ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
        
        <div class="filter-section">
          <h4><i class="fas fa-chalkboard-teacher"></i> المدربون</h4>
          <ul class="filter-options" id="instructor-filter">
            <li class="active" data-instructor="all">الكل</li>
            <?php foreach ($instructors as $instructor): ?>
              <li data-instructor="<?php echo htmlspecialchars($instructor); ?>">
                <?php echo htmlspecialchars($instructor); ?>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
        
        <div class="filter-section">
          <h4><i class="fas fa-filter"></i> عوامل أخرى</h4>
          <ul class="filter-options">
            <li data-level="all">جميع المستويات</li>
            <li data-level="beginner">مبتدئ</li>
            <li data-level="intermediate">متوسط</li>
            <li data-level="advanced">متقدم</li>
          </ul>
        </div>
        
        <div class="filter-section">
          <h4><i class="fas fa-certificate"></i> الشهادات</h4>
          <ul class="filter-options">
            <li data-certificate="all">الكل</li>
            <li data-certificate="yes">بشهادة</li>
            <li data-certificate="no">بدون شهادة</li>
          </ul>
        </div>
      </aside>
      
      <!-- محتوى الدورات -->
      <div class="content">
        <div class="content-header">
          <h1>استعرض الدورات المتاحة</h1>
          <div class="sort-options">
            <select id="sort-by">
              <option value="newest">الأحدث أولاً</option>
              <option value="oldest">الأقدم أولاً</option>
              <option value="popular">الأكثر شعبية</option>
              <option value="rating">الأعلى تقييماً</option>
            </select>
            
            <div class="view-toggle">
              <button class="view-btn active" data-view="grid"><i class="fas fa-th"></i></button>
              <button class="view-btn" data-view="list"><i class="fas fa-list"></i></button>
            </div>
          </div>
        </div>
        
        <!-- عرض الشبكة -->
        <div class="course-view" id="grid-view">
          <div class="course-grid">
            <?php if (count($courses) > 0): ?>
              <?php foreach ($courses as $course): ?>
                <div class="course-card animate" data-category="<?php echo htmlspecialchars($course['category']); ?>" 
                     data-instructor="<?php echo htmlspecialchars($course['instructor']); ?>" 
                     data-level="<?php echo htmlspecialchars($course['level']); ?>"
                     data-certificate="<?php echo $course['has_certificate'] ? 'yes' : 'no'; ?>"
                     data-title="<?php echo htmlspecialchars($course['title']); ?>">
                  
                  <?php if ($course['is_completed']): ?>
                    <div class="course-badge">مكتمل</div>
                  <?php elseif ($course['user_progress'] > 0): ?>
                    <div class="course-badge">قيد التقدم</div>
                  <?php endif; ?>
                  
                  <button class="favorite-btn <?php echo $course['is_favorite'] ? 'active' : ''; ?>" data-course-id="<?php echo $course['id']; ?>">
                    <i class="fas fa-heart"></i>
                  </button>
                  
                  <div class="course-image-container">
                    <img src="<?php echo htmlspecialchars($course['image_url']); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>" class="course-image">
                  </div>
                  
                  <div class="course-details">
                    <h3 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h3>
                    <p class="course-description"><?php echo htmlspecialchars($course['description']); ?></p>
                    
                    <div class="course-meta">
                      <div class="course-meta-item">
                        <i class="fas fa-clock"></i> <?php echo htmlspecialchars($course['duration']); ?>
                      </div>
                      <div class="course-meta-item">
                        <i class="fas fa-signal"></i> <?php echo htmlspecialchars($course['level']); ?>
                      </div>
                      <div class="course-meta-item">
                        <i class="fas fa-book"></i> <?php echo htmlspecialchars($course['lessons']); ?> دروس
                      </div>
                    </div>
                    
                    <div class="course-instructor">
                      <img src="assets/images/instructors/<?php echo strtolower(str_replace(' ', '-', $course['instructor'])); ?>.jpg" alt="<?php echo htmlspecialchars($course['instructor']); ?>" class="instructor-avatar">
                      <span class="instructor-name"><?php echo htmlspecialchars($course['instructor']); ?></span>
                    </div>
                    
                    <?php if (isset($ratings[$course['id']])): ?>
                      <div class="rating">
                        <div class="rating-stars">
                          <?php 
                            $fullStars = floor($ratings[$course['id']]['avg_rating']);
                            $halfStar = $ratings[$course['id']]['avg_rating'] - $fullStars >= 0.5;
                            $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
                            
                            for ($i = 0; $i < $fullStars; $i++) {
                              echo '<i class="fas fa-star"></i>';
                            }
                            
                            if ($halfStar) {
                              echo '<i class="fas fa-star-half-alt"></i>';
                            }
                            
                            for ($i = 0; $i < $emptyStars; $i++) {
                              echo '<i class="far fa-star"></i>';
                            }
                          ?>
                        </div>
                        <span class="rating-count">(<?php echo $ratings[$course['id']]['rating_count']; ?>)</span>
                      </div>
                    <?php endif; ?>
                    
                    <?php if ($course['user_progress'] > 0): ?>
                      <div class="progress-container">
                        <div class="progress-text">
                          <span>التقدم: <?php echo $course['user_progress']; ?>%</span>
                          <span><?php echo floor($course['lessons'] * $course['user_progress'] / 100); ?> / <?php echo $course['lessons']; ?> دروس</span>
                        </div>
                        <div class="progress-bar">
                          <div class="progress-fill" style="width: <?php echo $course['user_progress']; ?>%"></div>
                        </div>
                      </div>
                    <?php endif; ?>
                    
                    <div class="course-footer">
                      <div class="course-price <?php echo $course['price'] == 0 ? 'free' : ''; ?>">
                        <?php if ($course['price'] == 0): ?>
                          مجاني
                        <?php else: ?>
                          <?php if ($course['discount_price'] < $course['price']): ?>
                            <div class="discount">
                              <span class="current-price"><?php echo $course['discount_price']; ?> MAD</span>
                              <span class="original-price"><?php echo $course['price']; ?> MAD</span>
                            </div>
                          <?php else: ?>
                            <?php echo $course['price']; ?> MAD
                          <?php endif; ?>
                        <?php endif; ?>
                      </div>
                      
                      <a href="course_details.php?id=<?php echo $course['id']; ?>" class="course-action <?php echo $course['is_completed'] ? 'completed' : ''; ?>">
                        <?php if ($course['is_completed']): ?>
                          <i class="fas fa-check-circle"></i> مكتمل
                        <?php elseif ($course['user_progress'] > 0): ?>
                          <i class="fas fa-play-circle"></i> متابعة
                        <?php else: ?>
                          <i class="fas fa-play-circle"></i> ابدأ الآن
                        <?php endif; ?>
                      </a>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="empty-state">
                <i class="fas fa-book-open"></i>
                <h3>لا توجد دورات متاحة حالياً</h3>
                <p>لم يتم العثور على أي دورات تطابق معايير البحث الخاصة بك. يرجى محاولة تغيير عوامل التصفية.</p>
                <button class="btn" id="reset-filters-btn"><i class="fas fa-redo"></i> إعادة تعيين الفلاتر</button>
              </div>
            <?php endif; ?>
          </div>
        </div>
        
        <!-- عرض القائمة (مخفي افتراضياً) -->
        <div class="course-view" id="list-view" style="display: none;">
          <div class="course-list">
            <?php if (count($courses) > 0): ?>
              <?php foreach ($courses as $course): ?>
                <div class="course-list-item animate" data-category="<?php echo htmlspecialchars($course['category']); ?>" 
                     data-instructor="<?php echo htmlspecialchars($course['instructor']); ?>" 
                     data-level="<?php echo htmlspecialchars($course['level']); ?>"
                     data-certificate="<?php echo $course['has_certificate'] ? 'yes' : 'no'; ?>"
                     data-title="<?php echo htmlspecialchars($course['title']); ?>">
                  
                  <div class="course-image-container">
                    <img src="<?php echo htmlspecialchars($course['image_url']); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>" class="course-image">
                  </div>
                  
                  <div class="course-details">
                    <div class="course-header">
                      <h3 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h3>
                      <button class="favorite-btn <?php echo $course['is_favorite'] ? 'active' : ''; ?>" data-course-id="<?php echo $course['id']; ?>">
                        <i class="fas fa-heart"></i>
                      </button>
                    </div>
                    
                    <div class="course-meta">
                      <div class="course-meta-item">
                        <i class="fas fa-clock"></i> <?php echo htmlspecialchars($course['duration']); ?>
                      </div>
                      <div class="course-meta-item">
                        <i class="fas fa-signal"></i> <?php echo htmlspecialchars($course['level']); ?>
                      </div>
                      <div class="course-meta-item">
                        <i class="fas fa-book"></i> <?php echo htmlspecialchars($course['lessons']); ?> دروس
                      </div>
                    </div>
                    
                    <p class="course-description"><?php echo htmlspecialchars($course['description']); ?></p>
                    
                    <div class="course-footer">
                      <div class="course-instructor">
                        <img src="assets/images/instructors/<?php echo strtolower(str_replace(' ', '-', $course['instructor'])); ?>.jpg" alt="<?php echo htmlspecialchars($course['instructor']); ?>" class="instructor-avatar">
                        <span class="instructor-name"><?php echo htmlspecialchars($course['instructor']); ?></span>
                      </div>
                      
                      <a href="course_details.php?id=<?php echo $course['id']; ?>" class="course-action <?php echo $course['is_completed'] ? 'completed' : ''; ?>">
                        <?php if ($course['is_completed']): ?>
                          <i class="fas fa-check-circle"></i> مكتمل
                        <?php elseif ($course['user_progress'] > 0): ?>
                          <i class="fas fa-play-circle"></i> متابعة
                        <?php else: ?>
                          <i class="fas fa-play-circle"></i> ابدأ الآن
                        <?php endif; ?>
                      </a>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="empty-state">
                <i class="fas fa-book-open"></i>
                <h3>لا توجد دورات متاحة حالياً</h3>
                <p>لم يتم العثور على أي دورات تطابق معايير البحث الخاصة بك. يرجى محاولة تغيير عوامل التصفية.</p>
                <button class="btn" id="reset-filters-btn"><i class="fas fa-redo"></i> إعادة تعيين الفلاتر</button>
              </div>
            <?php endif; ?>
          </div>
        </div>
        
        <!-- الدورات الموصى بها -->
        <?php if (!empty($recommended_courses)): ?>
          <div class="recommended-courses">
            <div class="section-header">
              <h2>دورات مقترحة لك</h2>
              <a href="#" class="view-all">عرض الكل <i class="fas fa-arrow-left"></i></a>
            </div>
            
            <div class="course-grid">
              <?php foreach ($recommended_courses as $course): ?>
                <div class="course-card animate" data-category="<?php echo htmlspecialchars($course['category']); ?>">
                  <div class="course-image-container">
                    <img src="<?php echo htmlspecialchars($course['image_url']); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>" class="course-image">
                  </div>
                  
                  <div class="course-details">
                    <h3 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h3>
                    <p class="course-description"><?php echo htmlspecialchars($course['description']); ?></p>
                    
                    <div class="course-meta">
                      <div class="course-meta-item">
                        <i class="fas fa-clock"></i> <?php echo htmlspecialchars($course['duration']); ?>
                      </div>
                      <div class="course-meta-item">
                        <i class="fas fa-signal"></i> <?php echo htmlspecialchars($course['level']); ?>
                      </div>
                    </div>
                    
                    <div class="course-footer">
                      <div class="course-price <?php echo $course['price'] == 0 ? 'free' : ''; ?>">
                        <?php echo $course['price'] == 0 ? 'مجاني' : $course['price'] . ' MAD'; ?>
                      </div>
                      
                      <a href="course_details.php?id=<?php echo $course['id']; ?>" class="course-action">
                        <i class="fas fa-play-circle"></i> ابدأ الآن
                      </a>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>

  <!-- التذييل -->
  <footer>
    <div class="footer-content">
      <div class="footer-column">
        <h3>عن المنصة</h3>
        <ul class="footer-links">
          <li><a href="#"><i class="fas fa-angle-left"></i> عن Morocco Learning</a></li>
          <li><a href="#"><i class="fas fa-angle-left"></i> رسالتنا ورؤيتنا</a></li>
          <li><a href="#"><i class="fas fa-angle-left"></i> فريق العمل</a></li>
          <li><a href="#"><i class="fas fa-angle-left"></i> الشراكات</a></li>
        </ul>
      </div>
      
      <div class="footer-column">
        <h3>الدورات</h3>
        <ul class="footer-links">
          <li><a href="#"><i class="fas fa-angle-left"></i> جميع الدورات</a></li>
          <li><a href="#"><i class="fas fa-angle-left"></i> الدورات الجديدة</a></li>
          <li><a href="#"><i class="fas fa-angle-left"></i> الدورات الأكثر شعبية</a></li>
          <li><a href="#"><i class="fas fa-angle-left"></i> الدورات المجانية</a></li>
        </ul>
      </div>
      
      <div class="footer-column">
        <h3>الدعم</h3>
        <ul class="footer-links">
          <li><a href="#"><i class="fas fa-angle-left"></i> مركز المساعدة</a></li>
          <li><a href="#"><i class="fas fa-angle-left"></i> الأسئلة الشائعة</a></li>
          <li><a href="#"><i class="fas fa-angle-left"></i> تواصل معنا</a></li>
          <li><a href="#"><i class="fas fa-angle-left"></i> الإبلاغ عن مشكلة</a></li>
        </ul>
      </div>
      
      <div class="footer-column">
        <h3>تواصل معنا</h3>
        <ul class="footer-links">
          <li><a href="#"><i class="fas fa-angle-left"></i> البريد الإلكتروني</a></li>
          <li><a href="#"><i class="fas fa-angle-left"></i> الهاتف</a></li>
          <li><a href="#"><i class="fas fa-angle-left"></i> العنوان</a></li>
        </ul>
        
        <div class="social-links">
          <a href="#"><i class="fab fa-facebook-f"></i></a>
          <a href="#"><i class="fab fa-twitter"></i></a>
          <a href="#"><i class="fab fa-instagram"></i></a>
          <a href="#"><i class="fab fa-linkedin-in"></i></a>
          <a href="#"><i class="fab fa-youtube"></i></a>
        </div>
      </div>
    </div>
    
    <div class="footer-bottom">
      <p>&copy; 2025 MOROCCO LEARNING - جميع الحقوق محفوظة</p>
    </div>
  </footer>

  <!-- الجسيمات الخلفية -->
  <div id="particles-js"></div>

  <!-- مكتبات JavaScript -->
  <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  
  <script>
    // تهيئة الجسيمات الخلفية
    particlesJS("particles-js", {
      particles: {
        number: { value: 80, density: { enable: true, value_area: 800 } },
        color: { value: "#00ffe7" },
        shape: { type: "circle" },
        opacity: { value: 0.5, random: false },
        size: { value: 3, random: true },
        line_linked: {
          enable: true,
          distance: 150,
          color: "#00ffe7",
          opacity: 0.4,
          width: 1
        },
        move: {
          enable: true,
          speed: 2,
          direction: "none",
          random: true,
          straight: false,
          out_mode: "bounce",
          bounce: true
        }
      },
      interactivity: {
        detect_on: "canvas",
        events: {
          onhover: { enable: true, mode: "grab" },
          onclick: { enable: true, mode: "push" },
          resize: true
        },
        modes: {
          grab: { distance: 140, line_linked: { opacity: 0.8 } },
          push: { particles_nb: 4 }
        }
      },
      retina_detect: true
    });

    // إخفاء التحميل الأولي عند تحميل الصفحة
    window.addEventListener('load', function() {
      setTimeout(function() {
        document.querySelector('.preloader').classList.add('fade-out');
        setTimeout(function() {
          document.querySelector('.preloader').style.display = 'none';
        }, 500);
      }, 1000);
    });

    // تأثير التمرير للهيدر
    window.addEventListener('scroll', function() {
      const header = document.getElementById('main-header');
      if (window.scrollY > 50) {
        header.classList.add('scrolled');
      } else {
        header.classList.remove('scrolled');
      }
    });

    // القائمة المتنقلة
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const navLinks = document.getElementById('nav-links');
    
    mobileMenuBtn.addEventListener('click', function() {
      navLinks.classList.toggle('active');
      mobileMenuBtn.innerHTML = navLinks.classList.contains('active') ? 
        '<i class="fas fa-times"></i>' : '<i class="fas fa-bars"></i>';
    });

    // القائمة المنسدلة للحساب
    const accountBtn = document.getElementById('account-btn');
    const dropdownContent = document.getElementById('dropdown-content');
    
    accountBtn.addEventListener('click', function() {
      dropdownContent.classList.toggle('show');
    });
    
    // إغلاق القائمة المنسدلة عند النقر خارجها
    window.addEventListener('click', function(e) {
      if (!e.target.matches('#account-btn') && !e.target.closest('#account-btn')) {
        if (dropdownContent.classList.contains('show')) {
          dropdownContent.classList.remove('show');
        }
      }
    });

    // تبديل عرض الدورات بين الشبكة والقائمة
    const viewBtns = document.querySelectorAll('.view-btn');
    const gridView = document.getElementById('grid-view');
    const listView = document.getElementById('list-view');
    
    viewBtns.forEach(btn => {
      btn.addEventListener('click', function() {
        viewBtns.forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        if (this.dataset.view === 'grid') {
          gridView.style.display = 'block';
          listView.style.display = 'none';
        } else {
          gridView.style.display = 'none';
          listView.style.display = 'block';
        }
      });
    });

    // تصفية الدورات
    const searchInput = document.getElementById('search-input');
    const categoryFilter = document.getElementById('category-filter');
    const instructorFilter = document.getElementById('instructor-filter');
    const resetFiltersBtn = document.getElementById('reset-filters');
    const courseCards = document.querySelectorAll('.course-card, .course-list-item');
    
    function filterCourses() {
      const searchTerm = searchInput.value.toLowerCase();
      const selectedCategory = categoryFilter.querySelector('.active').dataset.category;
      const selectedInstructor = instructorFilter.querySelector('.active').dataset.instructor;
      
      courseCards.forEach(card => {
        const title = card.dataset.title.toLowerCase();
        const category = card.dataset.category;
        const instructor = card.dataset.instructor;
        
        const matchesSearch = title.includes(searchTerm);
        const matchesCategory = selectedCategory === 'all' || category === selectedCategory;
        const matchesInstructor = selectedInstructor === 'all' || instructor === selectedInstructor;
        
        if (matchesSearch && matchesCategory && matchesInstructor) {
          card.style.display = 'block';
        } else {
          card.style.display = 'none';
        }
      });
      
      // إظهار رسالة فارغة إذا لم توجد نتائج
      const visibleCourses = document.querySelectorAll('.course-card[style="display: block"], .course-list-item[style="display: block"]');
      const emptyState = document.querySelector('.empty-state');
      
      if (visibleCourses.length === 0) {
        if (!emptyState) {
          const emptyStateHTML = `
            <div class="empty-state">
              <i class="fas fa-book-open"></i>
              <h3>لا توجد دورات متاحة حالياً</h3>
              <p>لم يتم العثور على أي دورات تطابق معايير البحث الخاصة بك. يرجى محاولة تغيير عوامل التصفية.</p>
              <button class="btn" id="reset-filters-btn"><i class="fas fa-redo"></i> إعادة تعيين الفلاتر</button>
            </div>
          `;
          
          if (gridView.style.display !== 'none') {
            gridView.querySelector('.course-grid').innerHTML = emptyStateHTML;
          } else {
            listView.querySelector('.course-list').innerHTML = emptyStateHTML;
          }
          
          document.getElementById('reset-filters-btn').addEventListener('click', resetFilters);
        }
      } else if (emptyState) {
        emptyState.remove();
      }
    }
    
    function resetFilters() {
      searchInput.value = '';
      categoryFilter.querySelectorAll('li').forEach(li => li.classList.remove('active'));
      categoryFilter.querySelector('li[data-category="all"]').classList.add('active');
      instructorFilter.querySelectorAll('li').forEach(li => li.classList.remove('active'));
      instructorFilter.querySelector('li[data-instructor="all"]').classList.add('active');
      filterCourses();
    }
    
    searchInput.addEventListener('input', filterCourses);
    
    categoryFilter.querySelectorAll('li').forEach(li => {
      li.addEventListener('click', function() {
        categoryFilter.querySelector('.active').classList.remove('active');
        this.classList.add('active');
        filterCourses();
      });
    });
    
    instructorFilter.querySelectorAll('li').forEach(li => {
      li.addEventListener('click', function() {
        instructorFilter.querySelector('.active').classList.remove('active');
        this.classList.add('active');
        filterCourses();
      });
    });
    
    resetFiltersBtn.addEventListener('click', resetFilters);
    
    // إضافة/إزالة من المفضلة
    document.querySelectorAll('.favorite-btn').forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        const courseId = this.dataset.courseId;
        const isFavorite = this.classList.contains('active');
        
        // هنا يمكنك إضافة كود AJAX لحفظ التفضيل في قاعدة البيانات
        fetch('update_favorite.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            course_id: courseId,
            is_favorite: !isFavorite
          }),
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            this.classList.toggle('active');
          }
        })
        .catch(error => {
          console.error('Error:', error);
        });
      });
    });
    
    // تأثيرات الظهور عند التمرير
    const animateElements = document.querySelectorAll('.animate');
    
    function checkScroll() {
      animateElements.forEach(element => {
        const elementTop = element.getBoundingClientRect().top;
        const windowHeight = window.innerHeight;
        
        if (elementTop < windowHeight - 100) {
          element.style.opacity = '1';
          element.style.transform = 'translateY(0)';
        }
      });
    }
    
    window.addEventListener('load', checkScroll);
    window.addEventListener('scroll', checkScroll);
    
    // تهيئة العناصر المتحركة
    animateElements.forEach((element, index) => {
      element.style.opacity = '0';
      element.style.transform = 'translateY(20px)';
      element.style.transition = `all 0.6s ease ${index * 0.1}s`;
    });
    
    // تحميل المزيد من الدورات عند التمرير للأسفل
    let isLoading = false;
    
    window.addEventListener('scroll', function() {
      if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 500 && !isLoading) {
        isLoading = true;
        
        // عرض مؤشر التحميل
        const loader = document.createElement('div');
        loader.className = 'loader';
        loader.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري تحميل المزيد من الدورات...';
        document.querySelector('.course-grid').appendChild(loader);
        
        // محاكاة جلب المزيد من البيانات
        setTimeout(function() {
          // هنا يمكنك إضافة كود AJAX لتحميل المزيد من الدورات
          fetch('load_more_courses.php')
            .then(response => response.text())
            .then(data => {
              loader.remove();
              document.querySelector('.course-grid').insertAdjacentHTML('beforeend', data);
              isLoading = false;
              
              // إعادة تهيئة العناصر المتحركة الجديدة
              const newAnimateElements = document.querySelectorAll('.animate:not([initialized])');
              newAnimateElements.forEach((element, index) => {
                element.setAttribute('initialized', 'true');
                element.style.opacity = '0';
                element.style.transform = 'translateY(20px)';
                element.style.transition = `all 0.6s ease ${index * 0.1}s`;
              });
              
              checkScroll();
            });
        }, 1500);
      }
    });
  </script>
</body>
</html>