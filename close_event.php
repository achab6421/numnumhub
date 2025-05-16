<?php
// 關閉活動功能
require_once 'includes/init.php';

// 確保用戶已登入
if (!isLoggedIn()) {
    $_SESSION['flash_message'] = '請先登入';
    $_SESSION['flash_type'] = 'error';
    redirect('login');
}

// 只處理 POST 請求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('events');
}

$event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
$user_id = $_SESSION['user_id'];
$action = isset($_POST['action']) ? $_POST['action'] : 'close'; // 'close' 或 'reopen'

if ($event_id <= 0) {
    $_SESSION['flash_message'] = '無效的活動ID';
    $_SESSION['flash_type'] = 'error';
    redirect('events');
}

global $conn;

// 檢查活動是否存在並且用戶是否為創建者
$check_sql = "SELECT id, is_closed FROM events WHERE id = ? AND creator_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ii", $event_id, $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    $check_stmt->close();
    $_SESSION['flash_message'] = '您沒有權限更改此活動狀態或活動不存在';
    $_SESSION['flash_type'] = 'error';
    redirect('events');
}

$event = $check_result->fetch_assoc();
$check_stmt->close();

// 根據動作更新活動狀態
$new_status = ($action === 'close') ? 1 : 0;
$status_text = ($action === 'close') ? '關閉' : '重新開啟';

// 只有在狀態需要變更時才執行更新
if ($event['is_closed'] != $new_status) {
    $update_sql = "UPDATE events SET is_closed = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ii", $new_status, $event_id);

    if ($update_stmt->execute()) {
        $_SESSION['flash_message'] = "活動已成功{$status_text}";
        $_SESSION['flash_type'] = 'success';
    } else {
        $_SESSION['flash_message'] = "{$status_text}活動時發生錯誤: " . $conn->error;
        $_SESSION['flash_type'] = 'error';
    }

    $update_stmt->close();
} else {
    $_SESSION['flash_message'] = "活動已經是{$status_text}狀態";
    $_SESSION['flash_type'] = 'info';
}

// 重定向回活動點餐系統頁面
redirect('order-system', ['id' => $event_id]);
?>
