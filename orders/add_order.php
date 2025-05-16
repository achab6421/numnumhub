<?php
// 新增餐點處理
require_once __DIR__ . '/../includes/init.php';

// 確保用戶已登入
if (!isLoggedIn()) {
    $_SESSION['flash_message'] = '請先登入';
    $_SESSION['flash_type'] = 'error';
    redirect('login');
}

// 只處理 POST 請求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard');
}

$event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
$user_id = $_SESSION['user_id'];

if ($event_id <= 0) {
    $_SESSION['flash_message'] = '無效的活動ID';
    $_SESSION['flash_type'] = 'error';
    redirect('events');
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
    $_SESSION['flash_message'] = '找不到該活動';
    $_SESSION['flash_type'] = 'error';
    redirect('events');
}

if ($event['is_closed']) {
    $_SESSION['flash_message'] = '此活動已關閉，無法新增餐點';
    $_SESSION['flash_type'] = 'error';
    redirect('order-system', ['id' => $event_id]);
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

// 獲取表單數據
$menu_item = trim($_POST['menu_item'] ?? '');
$price = floatval($_POST['price'] ?? 0);
$quantity = intval($_POST['quantity'] ?? 1);
$note = trim($_POST['note'] ?? '');

// 驗證數據
if (empty($menu_item)) {
    redirect('order-system', ['id' => $event_id, 'error' => '餐點名稱不能為空']);
}

if ($quantity < 1) {
    $quantity = 1;
}

// 新增訂單
$sql = "INSERT INTO event_orders (event_id, user_id, menu_item, price, quantity, note, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iisdis", $event_id, $user_id, $menu_item, $price, $quantity, $note);

if ($stmt->execute()) {
    redirect('order-system', ['id' => $event_id, 'success' => '已成功新增餐點']);
} else {
    redirect('order-system', ['id' => $event_id, 'error' => '新增餐點失敗: ' . $conn->error]);
}

$stmt->close();
