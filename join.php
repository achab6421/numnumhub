<?php
// 加入活動處理
require_once 'includes/init.php';

// 確保用戶已登入
if (!isLoggedIn()) {
    redirect('login');
}

// 只處理 POST 請求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('events');
}

// 獲取活動ID
$event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
$user_id = $_SESSION['user_id'];

if ($event_id <= 0) {
    setFlashMessage('error', '無效的活動ID');
    redirect('events');
}

global $conn;

// 檢查此活動是否存在且未關閉
$check_sql = "SELECT id, creator_id FROM events WHERE id = ? AND (is_closed = 0 OR is_closed IS NULL)";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $event_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    $check_stmt->close();
    $conn->close();
    setFlashMessage('error', '此活動不存在或已關閉');
    redirect('events');
}

$event = $check_result->fetch_assoc();
$check_stmt->close();

// 檢查用戶是否已經參加此活動
$exists_sql = "SELECT id FROM event_participants WHERE event_id = ? AND user_id = ?";
$exists_stmt = $conn->prepare($exists_sql);
$exists_stmt->bind_param("ii", $event_id, $user_id);
$exists_stmt->execute();
$exists_result = $exists_stmt->get_result();

if ($exists_result->num_rows > 0) {
    $exists_stmt->close();
    $conn->close();
    setFlashMessage('error', '您已經參加了此活動');
    redirect('event', ['id' => $event_id]);
}

$exists_stmt->close();

// 檢查是否為創建者（創建者已自動添加）
if ($event['creator_id'] == $user_id) {
    $conn->close();
    setFlashMessage('error', '您是此活動的創建者，無需加入');
    redirect('event', ['id' => $event_id]);
}

// 將用戶添加到參與者列表
$join_sql = "INSERT INTO event_participants (event_id, user_id, joined_at) VALUES (?, ?, NOW())";
$join_stmt = $conn->prepare($join_sql);
$join_stmt->bind_param("ii", $event_id, $user_id);

if ($join_stmt->execute()) {
    setFlashMessage('success', '您已成功加入此活動！');
} else {
    setFlashMessage('error', '加入活動時發生錯誤');
}

$join_stmt->close();
$conn->close();

redirect('event', ['id' => $event_id]);
?>
