<?php
// 刪除餐廳處理頁面
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/functions.php';

// 確保用戶已登入
if (!isLoggedIn()) {
    redirect('login');
}

// 獲取餐廳ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 檢查餐廳是否存在
$restaurant = getRestaurant($id);
if (!$restaurant) {
    setFlashMessage('找不到指定的餐廳', 'danger');
    redirect('restaurants');
}

// 檢查用戶是否有權限刪除此餐廳
if (!canManageRestaurant($id, $_SESSION['user_id'])) {
    setFlashMessage('您沒有權限刪除此餐廳', 'danger');
    redirect('restaurants');
}

// 嘗試刪除餐廳
$result = deleteRestaurant($id);

// 設置提示訊息
setFlashMessage($result['message'], $result['success'] ? 'success' : 'danger');

// 重定向回餐廳列表
redirect('restaurants');
?>
