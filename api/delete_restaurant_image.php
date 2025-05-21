<?php
// API: 刪除餐廳圖片
header('Content-Type: application/json');

require_once dirname(__DIR__) . '/includes/init.php';
require_once dirname(__DIR__) . '/restaurants/functions.php';

// 確保用戶已登入
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false, 
        'message' => '請先登入'
    ]);
    exit;
}

// 檢查請求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false, 
        'message' => '無效的請求方法'
    ]);
    exit;
}

// 獲取必要參數
$image_id = isset($_POST['image_id']) ? (int)$_POST['image_id'] : 0;
$restaurant_id = isset($_POST['restaurant_id']) ? (int)$_POST['restaurant_id'] : 0;

// 檢查參數有效性
if ($image_id <= 0 || $restaurant_id <= 0) {
    echo json_encode([
        'success' => false, 
        'message' => '缺少必要參數'
    ]);
    exit;
}

// 檢查用戶是否有權限管理該餐廳
if (!canManageRestaurant($restaurant_id, $_SESSION['user_id'])) {
    echo json_encode([
        'success' => false, 
        'message' => '您沒有權限刪除此圖片'
    ]);
    exit;
}

// 執行刪除操作
$result = deleteRestaurantImage($image_id, $_SESSION['user_id']);

// 回傳結果
echo json_encode($result);
exit;
