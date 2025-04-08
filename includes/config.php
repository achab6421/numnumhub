<?php
// 資料庫連接設定
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'numnumhub');
// 正式環境
// define('DB_SERVER', 'sql101.infinityfree.com');
// define('DB_USERNAME', 'if0_37303437');
// define('DB_PASSWORD', 'GGEJlUj4V0D');
// define('DB_NAME', 'if0_37303437_numnumhub');
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
        'file' => 'register.php', 
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
    'event' => [
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
