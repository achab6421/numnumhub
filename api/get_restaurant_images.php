<?php
require_once dirname(__DIR__) . '/includes/init.php';

// 確保用戶已登入
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '未登入']);
    exit;
}

header('Content-Type: application/json');

// 獲取餐廳 ID
$restaurant_id = isset($_GET['restaurant_id']) ? (int)$_GET['restaurant_id'] : 0;

if ($restaurant_id <= 0) {
    echo json_encode(['success' => false, 'message' => '無效的餐廳ID']);
    exit;
}

// 獲取餐廳菜單圖片
global $conn;

$sql = "SELECT ri.*, r.name AS restaurant_name 
        FROM restaurant_images ri
        JOIN restaurants r ON ri.restaurant_id = r.id 
        WHERE ri.restaurant_id = ? AND ri.is_menu = 1
        ORDER BY ri.created_at DESC";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $restaurant_id);
$stmt->execute();
$result = $stmt->get_result();

$images = [];
while ($row = $result->fetch_assoc()) {
    $images[] = $row;
}

echo json_encode([
    'success' => true,
    'restaurant_id' => $restaurant_id,
    'restaurant_name' => $images[0]['restaurant_name'] ?? '',
    'images' => $images,
    'count' => count($images)
]);
?>
