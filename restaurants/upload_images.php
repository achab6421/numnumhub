<?php
require_once dirname(__DIR__) . '/includes/init.php';
require_once __DIR__ . '/functions.php';

// 確保用戶已登入
if (!isLoggedIn()) {
    $_SESSION['flash_message'] = '請先登入';
    $_SESSION['flash_type'] = 'error';
    redirect('login');
}

// 只處理 POST 請求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('restaurants');
}

$restaurant_id = isset($_POST['restaurant_id']) ? (int)$_POST['restaurant_id'] : 0;

if ($restaurant_id <= 0) {
    $_SESSION['flash_message'] = '無效的餐廳ID';
    $_SESSION['flash_type'] = 'error';
    redirect('restaurants');
}

// 檢查權限
$restaurant = getRestaurantById($restaurant_id);
if (!$restaurant || $restaurant['created_by'] != $_SESSION['user_id']) {
    $_SESSION['flash_message'] = '您無權為此餐廳上傳圖片';
    $_SESSION['flash_type'] = 'error';
    redirect('restaurants');
}

// 處理圖片上傳
$result = handleMultipleImageUpload($restaurant_id);

if (!empty($result['errors'])) {
    $_SESSION['flash_message'] = '上傳過程中發生錯誤:<br>' . implode('<br>', $result['errors']);
    $_SESSION['flash_type'] = 'error';
} else if ($result['count'] > 0) {
    $_SESSION['flash_message'] = "成功上傳了 {$result['count']} 張菜單圖片！";
    $_SESSION['flash_type'] = 'success';
} else {
    $_SESSION['flash_message'] = '未選擇任何文件上傳';
    $_SESSION['flash_type'] = 'warning';
}

// 重定向回餐廳詳情頁
redirect('restaurant', ['id' => $restaurant_id]);
?>
