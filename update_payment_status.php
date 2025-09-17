<?php
// 更新付款狀態API
require_once 'includes/init.php';

// 確保用戶已登入
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => '請先登入']);
    exit;
}

// 檢查是否為POST請求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '無效的請求方式']);
    exit;
}

// 獲取參數
$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
$is_paid = isset($_POST['is_paid']) ? (int)$_POST['is_paid'] : 0;

// 驗證參數
if ($user_id <= 0 || $event_id <= 0) {
    echo json_encode(['success' => false, 'message' => '參數無效']);
    exit;
}

// 檢查當前用戶是否為活動建立者
$check_creator_sql = "SELECT creator_id FROM events WHERE id = ?";
$check_creator_stmt = $conn->prepare($check_creator_sql);
$check_creator_stmt->bind_param("i", $event_id);
$check_creator_stmt->execute();
$creator_result = $check_creator_stmt->get_result();

if ($creator_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => '找不到指定活動']);
    exit;
}

$event = $creator_result->fetch_assoc();
$check_creator_stmt->close();

if ($event['creator_id'] !== $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => '只有活動建立者可以更新付款狀態']);
    exit;
}

// 檢查用戶是否為參與者，並更新付款狀態
$update_sql = "UPDATE event_participants SET is_paid = ?, updated_at = NOW() WHERE event_id = ? AND user_id = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("iii", $is_paid, $event_id, $user_id);
$update_stmt->execute();

// 檢查是否成功更新
if ($update_stmt->affected_rows === 0) {
    echo json_encode(['success' => false, 'message' => '此用戶不是活動參與者或狀態未變更']);
    $update_stmt->close();
    exit;
}

$update_stmt->close();

// 獲取參與者名稱
$get_name_sql = "SELECT name FROM users WHERE id = ?";
$get_name_stmt = $conn->prepare($get_name_sql);
$get_name_stmt->bind_param("i", $user_id);
$get_name_stmt->execute();
$name_result = $get_name_stmt->get_result();

if ($name_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => '找不到該用戶']);
    $get_name_stmt->close();
    exit;
}

$user_name = $name_result->fetch_assoc()['name'];
$get_name_stmt->close();

$message = $is_paid ? "已標記 {$user_name} 為已付款" : "已標記 {$user_name} 為未付款";
echo json_encode(['success' => true, 'message' => $message]);
