<?php
// AJAX 端點：處理餐廳更新
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../tags/functions.php';

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

// 獲取餐廳ID
$id = isset($_POST['restaurant_id']) ? (int)$_POST['restaurant_id'] : 0;

// 檢查餐廳是否存在
$restaurant = getRestaurant($id);
if (!$restaurant) {
    echo json_encode(['success' => false, 'message' => '找不到指定的餐廳']);
    exit;
}

// 檢查用戶是否有權限編輯此餐廳
if (!canManageRestaurant($id, $_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => '您沒有權限編輯此餐廳']);
    exit;
}

// 獲取並過濾表單數據
$name = trim($_POST['name'] ?? '');
$address = trim($_POST['address'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$link = trim($_POST['link'] ?? '');
$note = trim($_POST['note'] ?? '');
$tagIds = isset($_POST['tags']) ? $_POST['tags'] : [];

// 處理新增標籤
$newTag = trim($_POST['new_tag'] ?? '');
if (!empty($newTag)) {
    $tagResult = createTag($newTag);
    if ($tagResult['success'] && isset($tagResult['id'])) {
        $tagIds[] = $tagResult['id'];
    }
}

// 基本驗證
if (empty($name)) {
    echo json_encode(['success' => false, 'message' => '餐廳名稱不能為空']);
    exit;
}

// 更新餐廳數據
$restaurantData = [
    'name' => $name,
    'address' => $address,
    'phone' => $phone,
    'link' => $link,
    'note' => $note
];

// 嘗試更新餐廳
$result = updateRestaurant($id, $restaurantData);

if ($result['success']) {
    // 更新餐廳標籤
    updateRestaurantTags($id, $tagIds);
    
    echo json_encode([
        'success' => true,
        'message' => '餐廳資料已成功更新',
        'restaurant' => [
            'id' => $id,
            'name' => $name
        ]
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => $result['message']
    ]);
}
