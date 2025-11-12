<?php
session_start();
$message = '';
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $message = "تم تحديث صلاحية المستخدم بنجاح.";
} elseif (isset($_GET['error'])) {
    $message = "حدث خطأ أثناء تحديث الصلاحية.";
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8" />
    <title>صلاحيات المستخدمين</title>
    <style>
        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            color: #fff;
        }
        .success {
            background-color: #4caf50;
        }
        .error {
            background-color: #f44336;
        }
    </style>
</head>
<body>

<?php if ($message): ?>
    <div class="message <?php echo isset($_GET['success']) ? 'success' : 'error'; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<!-- هنا جدول الصلاحيات وأكواد الصفحة الأخرى -->

</body>
</html>
