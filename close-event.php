<?php
// 引入必要的檔案
require_once 'includes/init.php';

// 確保用戶已登入
if (!isLoggedIn()) {
    redirect('login');
}

// 檢查是否為POST請求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlashMessage('無效的請求方式', 'danger');
    redirect('events');
    exit;
}

// 獲取活動ID
$event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;

// 驗證活動ID
if ($event_id <= 0) {
    setFlashMessage('無效的活動ID', 'danger');
    redirect('events');
    exit;
}

// 檢查是否為活動創建者
$checkSql = "SELECT creator_id, title FROM events WHERE id = ?";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param("i", $event_id);
$checkStmt->execute();
$result = $checkStmt->get_result();

if ($result->num_rows === 0) {
    setFlashMessage('找不到指定的活動', 'danger');
    redirect('events');
    exit;
}

$event = $result->fetch_assoc();

// 確認用戶是活動創建者
if ($event['creator_id'] != $_SESSION['user_id']) {
    setFlashMessage('您沒有權限關閉此活動', 'danger');
    redirect('events');
    exit;
}

// 關閉活動
$updateSql = "UPDATE events SET is_closed = 1, closed_at = NOW() WHERE id = ?";
$updateStmt = $conn->prepare($updateSql);
$updateStmt->bind_param("i", $event_id);

if ($updateStmt->execute()) {
    setFlashMessage("活動「{$event['title']}」已成功關閉", 'success');
} else {
    setFlashMessage('關閉活動時發生錯誤', 'danger');
}

redirect('events');
