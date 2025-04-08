<?php
require_once 'includes/init.php';

// 檢查資料表結構
$checkQuery = "SHOW COLUMNS FROM restaurants LIKE 'created_by'";
$result = $conn->query($checkQuery);

if ($result->num_rows === 0) {
    // 如果 created_by 欄位不存在，新增它
    $alterQuery = "ALTER TABLE restaurants ADD COLUMN created_by INT NOT NULL AFTER created_at, ADD FOREIGN KEY (created_by) REFERENCES users(id)";
    
    if ($conn->query($alterQuery) === TRUE) {
        echo "餐廳資料表結構已更新：新增 created_by 欄位<br>";
    } else {
        echo "錯誤：無法更新資料表結構 - " . $conn->error . "<br>";
    }
} else {
    echo "餐廳資料表已有 created_by 欄位<br>";
}

// 檢查現有餐廳是否缺少創建者
$checkExistingQuery = "SELECT COUNT(*) as count FROM restaurants WHERE created_by = 0 OR created_by IS NULL";
$result = $conn->query($checkExistingQuery);
$row = $result->fetch_assoc();

if ($row['count'] > 0) {
    // 如果有缺少創建者的餐廳，將其設為系統管理員(ID=1)或第一個用戶
    $getUserQuery = "SELECT id FROM users ORDER BY id LIMIT 1";
    $userResult = $conn->query($getUserQuery);
    $user = $userResult->fetch_assoc();
    $adminId = $user['id'] ?? 1;
    
    $updateQuery = "UPDATE restaurants SET created_by = $adminId WHERE created_by = 0 OR created_by IS NULL";
    
    if ($conn->query($updateQuery) === TRUE) {
        echo "已將 {$row['count']} 個無創建者記錄的餐廳分配給用戶 ID: $adminId<br>";
    } else {
        echo "錯誤：無法更新無創建者記錄的餐廳 - " . $conn->error . "<br>";
    }
}

echo "<a href='" . url('restaurants') . "' class='btn btn-primary'>返回餐廳管理</a>";
?>
