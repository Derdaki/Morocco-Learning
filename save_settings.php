<?php
session_start();
header('Content-Type: application/json; charset=utf-8'); // نوع الرد JSON

require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'غير مسموح بالدخول']);
    exit;
}

$user_id = $_SESSION['user_id'];

$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$show_profile = isset($_POST['show_profile']) ? 1 : 0;
$community_rules_accept = isset($_POST['community_rules_accept']) ? 1 : 0;
$email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
$sms_notifications = isset($_POST['sms_notifications']) ? 1 : 0;
$activity_log_enabled = isset($_POST['activity_log_enabled']) ? 1 : 0;
$support_notes = trim($_POST['support_notes'] ?? '');

$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

$delete_confirm = trim($_POST['delete_confirm'] ?? '');

try {
    $conn->begin_transaction();

    if ($delete_confirm === "حذف") {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM users_settings WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();

        session_destroy();

        echo json_encode(['success' => true, 'message' => 'تم حذف الحساب نهائيًا']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('البريد الإلكتروني غير صالح');
    }

    $stmt = $conn->prepare("UPDATE users SET email = ?, phone = ? WHERE id = ?");
    $stmt->bind_param("ssi", $email, $phone, $user_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE users_settings SET 
        show_profile = ?, 
        community_rules_accept = ?, 
        email_notifications = ?, 
        sms_notifications = ?, 
        activity_log_enabled = ?, 
        support_notes = ? 
        WHERE user_id = ?");
    $stmt->bind_param("iiiiisi",
        $show_profile,
        $community_rules_accept,
        $email_notifications,
        $sms_notifications,
        $activity_log_enabled,
        $support_notes,
        $user_id);
    $stmt->execute();
    $stmt->close();

    if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            throw new Exception('يرجى ملء جميع حقول تغيير كلمة المرور');
        }
        if ($new_password !== $confirm_password) {
            throw new Exception('كلمات المرور الجديدة غير متطابقة');
        }
        if (!preg_match('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/', $new_password)) {
            throw new Exception('كلمة المرور الجديدة يجب أن تحتوي على 8 أحرف على الأقل، حرف كبير، حرف صغير ورقم');
        }

        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if (!$row || !password_verify($current_password, $row['password'])) {
            throw new Exception('كلمة المرور الحالية غير صحيحة');
        }

        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $new_password_hash, $user_id);
        $stmt->execute();
        $stmt->close();
    }

    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'تم تحديث الإعدادات بنجاح']);
    exit;

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}
