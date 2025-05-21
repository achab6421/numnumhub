<?php
// 刪除餐廳圖片處理程序
require_once dirname(__DIR__) . '/includes/init.php';
require_once __DIR__ . '/functions.php';

// 確保用戶已登入
if (!isLoggedIn()) {
    setFlashMessage('請先登入', 'warning');
    redirect('login');
}

// 檢查提交方式
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlashMessage('無效的請求方式', 'danger');
    redirect('restaurants');
    exit;
}

// 獲取參數
$image_id = isset($_POST['image_id']) ? (int)$_POST['image_id'] : 0;
$restaurant_id = isset($_POST['restaurant_id']) ? (int)$_POST['restaurant_id'] : 0;

// 確認參數有效性
if ($image_id <= 0 || $restaurant_id <= 0) {
    setFlashMessage('無效的請求參數', 'danger');
    redirect('restaurants');
    exit;
}

// 檢查用戶是否有權限管理此餐廳
if (!canManageRestaurant($restaurant_id, $_SESSION['user_id'])) {
    setFlashMessage('您沒有權限刪除此圖片', 'danger');
    redirect('restaurants');
    exit;
}

// 執行刪除操作
$result = deleteRestaurantImage($image_id, $_SESSION['user_id']);

if ($result['success']) {
    setFlashMessage($result['message'], 'success');
} else {
    setFlashMessage($result['message'], 'danger');
}

// 返回餐廳編輯頁面
redirect('edit-restaurant', ['id' => $restaurant_id]);
