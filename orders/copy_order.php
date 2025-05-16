<?php
// 複製餐點處理
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
$check_sql = "SELECT id, is_closed FROM events WHERE id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $event_id);
$check_stmt->execute();
$event = $check_stmt->get_result()->fetch_assoc();
$check_stmt->close();

if (!$event) {
    redirect('events', ['error' => '找不到該活動']);
}

if ($event['is_closed']) {
    redirect('order-system', ['id' => $event_id, 'error' => '此活動已關閉，無法複製餐點']);
}

// 檢查用戶是否為參與者
$participant_sql = "SELECT id FROM event_participants WHERE event_id = ? AND user_id = ?";
$participant_stmt = $conn->prepare($participant_sql);
$participant_stmt->bind_param("ii", $event_id, $user_id);
$participant_stmt->execute();
$is_participant = $participant_stmt->get_result()->num_rows > 0;
$participant_stmt->close();

if (!$is_participant) {
    redirect('events', ['error' => '您不是此活動的參與者']);
}

// 獲取原始訂單信息
$original_sql = "SELECT * FROM event_orders WHERE id = ? AND event_id = ?";
$original_stmt = $conn->prepare($original_sql);
$original_stmt->bind_param("ii", $order_id, $event_id);
$original_stmt->execute();
$original_order = $original_stmt->get_result()->fetch_assoc();
$original_stmt->close();

if (!$original_order) {
    redirect('order-system', ['id' => $event_id, 'error' => '找不到要複製的餐點']);
}

// 複製訂單
$copy_sql = "INSERT INTO event_orders (event_id, user_id, menu_item, price, quantity, note, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())";
$copy_stmt = $conn->prepare($copy_sql);
$copy_stmt->bind_param("iisdis", $event_id, $user_id, $original_order['menu_item'], 
                        $original_order['price'], $original_order['quantity'], $original_order['note']);

if ($copy_stmt->execute()) {
    redirect('order-system', ['id' => $event_id, 'success' => '已成功複製餐點']);
} else {
    redirect('order-system', ['id' => $event_id, 'error' => '複製餐點失敗: ' . $conn->error]);
}

$copy_stmt->close();
