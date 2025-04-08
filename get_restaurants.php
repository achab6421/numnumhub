<?php
// API 端點：獲取餐廳數據
require_once 'includes/init.php';
require_once 'restaurants/functions.php';
require_once 'tags/functions.php';

// 設置回應類型為 JSON
header('Content-Type: application/json');

// 確保用戶已登入
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => '未授權的訪問']);
    exit;
}

// 獲取過濾條件
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$tag_id = isset($_GET['tag']) ? (int)$_GET['tag'] : null;
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : null;

try {
    // 獲取餐廳數據
    $restaurants = getRestaurants($user_id);
    
    // 如果沒有餐廳，直接返回空數組
    if (empty($restaurants)) {
        echo json_encode([
            'success' => true,
            'restaurants' => [],
            'tags' => getAllTags()
        ]);
        exit;
    }
    
    // 獲取所有餐廳IDs
    $restaurantIds = array_column($restaurants, 'id');
    
    // 一次性獲取所有餐廳的標籤
    $allRestaurantTags = getAllRestaurantTags($restaurantIds);
    
    // 為每個餐廳添加標籤
    foreach ($restaurants as &$restaurant) {
        $restaurant['tags'] = isset($allRestaurantTags[$restaurant['id']]) 
            ? $allRestaurantTags[$restaurant['id']] 
            : [];
    }
    
    // 如果有標籤過濾條件
    if ($tag_id) {
        $restaurants = array_filter($restaurants, function($restaurant) use ($tag_id) {
            foreach ($restaurant['tags'] as $tag) {
                if ($tag['id'] == $tag_id) {
                    return true;
                }
            }
            return false;
        });
    }
    
    // 如果有關鍵字過濾條件
    if ($keyword) {
        $keyword = strtolower($keyword);
        $restaurants = array_filter($restaurants, function($restaurant) use ($keyword) {
            return (
                stripos(strtolower($restaurant['name']), $keyword) !== false || 
                (isset($restaurant['address']) && stripos(strtolower($restaurant['address']), $keyword) !== false)
            );
        });
    }
    
    // 重新索引數組以獲得正確的 JSON 數組
    $restaurants = array_values($restaurants);
    
    // 返回成功結果
    echo json_encode([
        'success' => true,
        'restaurants' => $restaurants,
        'tags' => getAllTags()
    ]);
} catch (Exception $e) {
    // 返回錯誤訊息
    echo json_encode([
        'success' => false,
        'message' => '獲取餐廳資料時發生錯誤: ' . $e->getMessage()
    ]);
}
