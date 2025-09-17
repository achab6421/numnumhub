<?php
// 退出活動處理程序
require_once 'includes/init.php';

// 新增記錄，幫助調試
error_log('Leave event script was called');

// 確保用戶已登入
if (!isLoggedIn()) {
    error_log('User not logged in');
    redirect('login');
}

// 檢查是否為POST請求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log('Not a POST request');
    setFlashMessage('無效的請求方式', 'danger');
    redirect('events');
    exit;
}

// 獲取活動ID
$event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
error_log('Event ID: ' . $event_id);

// 以下確保資料庫連線正常
if (!isset($conn) || $conn->connect_error) {
    error_log('Database connection issue');
    setFlashMessage('資料庫連接錯誤', 'danger');
    redirect('events');
    exit;
}

// 驗證活動ID
if ($event_id <= 0) {
    error_log('Invalid event ID');
    setFlashMessage('無效的活動ID', 'danger');
    redirect('events');
    exit;
}

$user_id = $_SESSION['user_id'];
error_log('User ID: ' . $user_id);

// 檢查是否是活動創建者
$check_creator_sql = "SELECT creator_id, title, is_closed FROM events WHERE id = ?";
$check_creator_stmt = $conn->prepare($check_creator_sql);
$check_creator_stmt->bind_param("i", $event_id);
$check_creator_stmt->execute();
$result = $check_creator_stmt->get_result();

if ($result->num_rows === 0) {
    setFlashMessage('找不到指定的活動', 'danger');
    redirect('events');
    exit;
}

$event = $result->fetch_assoc();
$check_creator_stmt->close();

// 檢查活動是否已關閉
if ($event['is_closed']) {
    setFlashMessage('此活動已關閉，無法執行退出操作', 'warning');
    redirect('events');
    exit;
}

// 創建者不能退出自己的活動
if ($event['creator_id'] == $user_id) {
    setFlashMessage('您是活動創建者，無法退出活動。如需結束活動，請關閉活動', 'warning');
    redirect('events');
    exit;
}

// 檢查是否已參加該活動
$check_sql = "SELECT id FROM event_participants WHERE event_id = ? AND user_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ii", $event_id, $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    setFlashMessage('您尚未加入此活動', 'warning');
    redirect('events');
    exit;
}
$check_stmt->close();

// 檢查使用者是否已在活動中點餐
$check_order_sql = "SELECT COUNT(*) AS order_count FROM event_orders WHERE event_id = ? AND user_id = ?";
$check_order_stmt = $conn->prepare($check_order_sql);
$check_order_stmt->bind_param("ii", $event_id, $user_id);
$check_order_stmt->execute();
$order_result = $check_order_stmt->get_result()->fetch_assoc();
$check_order_stmt->close();

if ($order_result['order_count'] > 0) {
    setFlashMessage('您已在此活動中點餐，無法退出活動', 'warning');
    redirect('events');
    exit;
}

try {
    // 開始事務
    error_log('Starting transaction');
    $conn->begin_transaction();
    
    // 執行退出操作
    $leave_sql = "DELETE FROM event_participants WHERE event_id = ? AND user_id = ?";
    $leave_stmt = $conn->prepare($leave_sql);
    if (!$leave_stmt) {
        error_log('Prepare statement failed: ' . $conn->error);
        throw new Exception("準備刪除查詢失敗: " . $conn->error);
    }
    
    $leave_stmt->bind_param("ii", $event_id, $user_id);
    
    if (!$leave_stmt->execute()) {
        error_log('Execute failed: ' . $leave_stmt->error);
        throw new Exception("刪除參與記錄失敗: " . $leave_stmt->error);
    }
    
    // 檢查是否成功刪除記錄
    $affected_rows = $leave_stmt->affected_rows;
    error_log('Affected rows: ' . $affected_rows);
    
    if ($affected_rows === 0) {
        error_log('No records deleted');
        throw new Exception("未成功移除參與記錄，可能記錄已不存在");
    }
    
    $leave_stmt->close();
    
    // 提交事務
    error_log('Committing transaction');
    $conn->commit();
    
    error_log('Successfully left event');
    setFlashMessage("您已成功退出「{$event['title']}」活動", 'success');
    
} catch (Exception $e) {
    // 發生錯誤時回滾事務
    error_log('Error: ' . $e->getMessage());
    $conn->rollback();
    setFlashMessage('退出活動時發生錯誤: ' . $e->getMessage(), 'danger');
}

// 返回活動列表頁面
error_log('Redirecting to events page');
redirect('events');
