<?php
// AJAX 端點：更新餐廳座標
require_once 'includes/init.php';
require_once 'restaurants/functions.php';

// 設置回應類型為 JSON
header('Content-Type: application/json');

// 確保用戶已登入
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => '未授權的訪問']);
    exit;
}

// 確保是 POST 請求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '無效的請求方法']);
    exit;
}

// 獲取餐廳ID和經緯度
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$lat = isset($_POST['lat']) ? (float)$_POST['lat'] : null;
$lng = isset($_POST['lng']) ? (float)$_POST['lng'] : null;

// 檢查數據是否有效
if ($id <= 0 || $lat === null || $lng === null) {
    echo json_encode(['success' => false, 'message' => '提供的數據無效']);
    exit;
}

// 檢查餐廳是否存在
$restaurant = getRestaurant($id);
if (!$restaurant) {
    echo json_encode(['success' => false, 'message' => '找不到指定的餐廳']);
    exit;
}

// 檢查用戶是否有權限更新此餐廳
if (!canManageRestaurant($id, $_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => '您沒有權限更新此餐廳']);
    exit;
}

// 更新餐廳座標
$result = updateRestaurantCoordinates($id, $lat, $lng);

// 回傳結果
echo json_encode($result);
?>
