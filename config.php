<?php
// 資料庫連接設定
if (!defined('DB_SERVER')) define('DB_SERVER', 'localhost');
if (!defined('DB_USERNAME')) define('DB_USERNAME', 'root');
if (!defined('DB_PASSWORD')) define('DB_PASSWORD', '');
if (!defined('DB_NAME')) define('DB_NAME', 'numnumhub');
// 正式環境
// if (!defined('DB_SERVER')) define('DB_SERVER', 'sql101.infinityfree.com');
// if (!defined('DB_USERNAME')) define('DB_USERNAME', 'if0_37303437');
// if (!defined('DB_PASSWORD')) define('DB_PASSWORD', 'GGEJlUj4V0D');
// if (!defined('DB_NAME')) define('DB_NAME', 'if0_37303437_numnumhub');
// 嘗試連接到資料庫
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// 檢查連接
if ($conn->connect_error) {
    die("連接失敗: " . $conn->connect_error);
}

// 設定字符集
$conn->set_charset("utf8mb4");
?>
