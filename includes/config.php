<?php
// 資料庫連接設定
if (!defined('DB_SERVER')) define('DB_SERVER', 'localhost');
if (!defined('DB_USERNAME')) define('DB_USERNAME', 'root');
if (!defined('DB_PASSWORD')) define('DB_PASSWORD', '');
if (!defined('DB_NAME')) define('DB_NAME', 'numnumhub');
// 正式環境
// if (!defined('DB_SERVER')) define('DB_SERVER', 'sql101.infinityfree.com');
// if (!defined('DB_USERNAME'))  define('DB_USERNAME', 'if0_37303437');
// if (!defined('DB_PASSWORD'))  define('DB_PASSWORD', 'GGEJlUj4V0D');
// if (!defined('DB_NAME')) define('DB_NAME', 'if0_37303437_numnumhub');
// 網站基本設定
$url_name = "numnumhub";
// 修正：確保BASE_URL包含前後斜線，這對於乾淨URL至關重要
define('BASE_URL', '/' . $url_name . '/');
define('SITE_NAME', 'NumNumHub');
define('BASE_PATH', dirname(__DIR__));

// 路由定義
$routes = [
    // 公開頁面
    'index' => [
        'file' => 'index.php',
        'auth' => false,
        'title' => '首頁'
    ],
    'login' => [
        'file' => 'login.php',
        'auth' => false,
        'title' => '會員登入'
    ],
    'register' => [
        'file' => 'auth/register.php', 
        'auth' => false,
        'title' => '會員註冊'
    ],
    'logout' => [
        'file' => 'logout.php',
        'auth' => false,
        'title' => '登出'
    ],
    
    // 需認證的頁面
    'dashboard' => [
        'file' => 'dashboard.php',
        'auth' => true,
        'title' => '會員主頁'
    ],
    // 餐廳管理 - 更新路徑指向新資料夾
    'restaurants' => [
        'file' => 'restaurants/index.php',
        'auth' => true,
        'title' => '餐廳管理'
    ],
    'restaurant' => [
        'file' => 'restaurants/view.php',
        'auth' => true,
        'title' => '餐廳詳情'
    ],
    'create-restaurant' => [
        'file' => 'restaurants/create.php',
        'auth' => true,
        'title' => '新增餐廳'
    ],
    'edit-restaurant' => [
        'file' => 'restaurants/edit.php',
        'auth' => true,
        'title' => '編輯餐廳'
    ],
    'delete-restaurant' => [
        'file' => 'restaurants/delete.php',
        'auth' => true,
        'title' => '刪除餐廳'
    ],
    'events' => [
        'file' => 'events.php',
        'auth' => true,
        'title' => '活動管理'
    ],
    'event-info' => [
        'file' => 'event_detail.php',
        'auth' => true,
        'title' => '活動詳情'
    ],
    'create-event' => [
        'file' => 'create_event.php',
        'auth' => true,
        'title' => '建立活動'
    ],
    'edit-event' => [
        'file' => 'edit_event.php',
        'auth' => true,
        'title' => '編輯活動'
    ],
    'join' => [
        'file' => 'join_event.php',
        'auth' => false,
        'title' => '加入活動'
    ],
    'join-by-code' => [
        'file' => 'join_by_code.php',
        'auth' => true,
        'title' => '透過分享碼加入活動'
    ],
    
    // 資料庫更新路由
    'update_restaurant_table' => [
        'file' => 'update_restaurant_table.php',
        'auth' => true,
        'title' => '更新餐廳資料表'
    ],
    
    // 餐廳更新坐標
    'update_restaurant_coordinates' => [
        'file' => 'update_restaurant_coordinates.php',
        'auth' => true,
        'title' => '更新餐廳座標'
    ],
    
    // 使用者相關頁面
    'user_profile' => [
        'file' => 'user_profile.php',
        'auth' => true,
        'title' => '個人資料設定'
    ],
    // 點餐系統相關路由
    'order-system' => [
        'file' => 'order_system.php',
        'auth' => true,
        'title' => '活動點餐系統'
    ],
    'add-order' => [
        'file' => 'orders/add_order.php',
        'auth' => true,
        'title' => '新增餐點'
    ],
    'delete-order' => [
        'file' => 'orders/delete_order.php',
        'auth' => true,
        'title' => '刪除餐點'
    ],
    'copy-order' => [
        'file' => 'orders/copy_order.php',
        'auth' => true,
        'title' => '複製餐點'
    ],
    // 確保有加入關閉活動的路由
    'close-event' => [
        'file' => 'close_event.php',
        'auth' => true,
        'title' => '關閉活動'
    ]
];

// 嘗試連接到資料庫
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// 檢查連接
if ($conn->connect_error) {
    die("連接失敗: " . $conn->connect_error);
}

// 設定字符集
$conn->set_charset("utf8mb4");
?>
