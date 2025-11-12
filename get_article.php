<?php
$host = 'localhost';
$port = 3307;
$dbname = 'moroccolearning';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);
} catch (PDOException $e) {
    die("فشل الاتصال بقاعدة البيانات");
}

$id = intval($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT title, content, author, created_at FROM articles WHERE id = :id");
$stmt->execute([':id' => $id]);
$article = $stmt->fetch(PDO::FETCH_ASSOC);

if ($article) {
    echo "<h2 style='margin-bottom:15px;'>" . htmlspecialchars($article['title']) . "</h2>";
    echo "<p><strong>الكاتب:</strong> " . htmlspecialchars($article['author']) . "</p>";
    echo "<p><strong>تاريخ النشر:</strong> " . htmlspecialchars($article['created_at']) . "</p>";
    echo "<hr style='margin:15px 0; border-color:#00ffe7;'>";
    echo "<div style='line-height:1.8;'>" . nl2br(htmlspecialchars($article['content'])) . "</div>";
} else {
    echo "<p style='color:red;'>المقال غير موجود.</p>";
}
?>
