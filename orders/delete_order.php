<?php
// 刪除餐點處理
require_once __DIR__ . '/../includes/init.php';

// 確保用戶已登入
if (!isLoggedIn()) {
    redirect('login');
}

// 只處理 POST 請求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard');
}

$event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$user_id = $_SESSION['user_id'];

if ($event_id <= 0 || $order_id <= 0) {
    redirect('events', ['error' => '無效的參數']);
}

global $conn;

// 檢查活動是否存在且未關閉
$check_sql = "SELECT e.id, e.is_closed, e.creator_id 
              FROM events e 
              WHERE e.id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $event_id);
$check_stmt->execute();
$event = $check_stmt->get_result()->fetch_assoc();
$check_stmt->close();

if (!$event) {
    redirect('events', ['error' => '找不到該活動']);
}

if ($event['is_closed']) {
    redirect('order-system', ['id' => $event_id, 'error' => '此活動已關閉，無法刪除餐點']);
}

// 檢查訂單所屬
$order_sql = "SELECT user_id FROM event_orders WHERE id = ? AND event_id = ?";
$order_stmt = $conn->prepare($order_sql);
$order_stmt->bind_param("ii", $order_id, $event_id);
$order_stmt->execute();
$order = $order_stmt->get_result()->fetch_assoc();
$order_stmt->close();

if (!$order) {
    redirect('order-system', ['id' => $event_id, 'error' => '找不到要刪除的餐點']);
}

// 檢查是否為訂單擁有者或活動創建者
$can_delete = ($order['user_id'] == $user_id || $event['creator_id'] == $user_id);

if (!$can_delete) {
    redirect('order-system', ['id' => $event_id, 'error' => '您沒有權限刪除此餐點']);
}

// 刪除訂單
$delete_sql = "DELETE FROM event_orders WHERE id = ? AND event_id = ?";
$delete_stmt = $conn->prepare($delete_sql);
$delete_stmt->bind_param("ii", $order_id, $event_id);

if ($delete_stmt->execute()) {
    redirect('order-system', ['id' => $event_id, 'success' => '已成功刪除餐點']);
} else {
    redirect('order-system', ['id' => $event_id, 'error' => '刪除餐點失敗: ' . $conn->error]);
}

$delete_stmt->close();
