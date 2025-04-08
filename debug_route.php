<?php
// 路由調試工具
require_once 'includes/init.php';

// 獲取當前請求的完整URL
$currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

// 獲取當前路由
$currentRoute = getCurrentRoute();

// 設置調試模式，顯示詳細信息
$debug = true;

// 顯示調試信息
echo '<h1>路由調試工具</h1>';
echo '<hr>';

echo '<div style="margin: 20px; padding: 15px; background: #f5f5f5; border: 1px solid #ddd; border-radius: 5px;">';
echo '<h2>基本信息</h2>';
echo '<p><strong>當前URL:</strong> ' . htmlspecialchars($currentUrl) . '</p>';
echo '<p><strong>當前路由:</strong> ' . htmlspecialchars($currentRoute) . '</p>';
echo '<p><strong>路由有效性:</strong> ' . (isValidRoute($currentRoute) ? '<span style="color:green;">有效</span>' : '<span style="color:red;">無效</span>') . '</p>';

if (isValidRoute($currentRoute)) {
    global $routes;
    echo '<p><strong>對應檔案:</strong> ' . htmlspecialchars($routes[$currentRoute]['file']) . '</p>';
    echo '<p><strong>需要認證:</strong> ' . ($routes[$currentRoute]['auth'] ? '是' : '否') . '</p>';
    echo '<p><strong>頁面標題:</strong> ' . htmlspecialchars($routes[$currentRoute]['title']) . '</p>';
}

echo '<p><strong>用戶狀態:</strong> ' . (isLoggedIn() ? '已登入' : '未登入') . '</p>';
echo '</div>';

// 測試几個關鍵路由的URL
echo '<div style="margin: 20px; padding: 15px; background: #f5f5f5; border: 1px solid #ddd; border-radius: 5px;">';
echo '<h2>常用路由URL測試</h2>';
echo '<p><strong>首頁:</strong> ' . url('index') . '</p>';
echo '<p><strong>餐廳列表:</strong> ' . url('restaurants') . '</p>';
echo '<p><strong>餐廳詳情(ID=1):</strong> ' . url('restaurant', ['id' => 1]) . '</p>';
echo '<p><strong>編輯餐廳(ID=1):</strong> ' . url('edit-restaurant', ['id' => 1]) . '</p>';
echo '</div>';

// $_SERVER變數
echo '<div style="margin: 20px; padding: 15px; background: #f5f5f5; border: 1px solid #ddd; border-radius: 5px;">';
echo '<h2>SERVER變數</h2>';
echo '<pre>';
foreach ($_SERVER as $key => $value) {
    echo htmlspecialchars("$key: $value") . "\n";
}
echo '</pre>';
echo '</div>';

// 定義的路由
echo '<div style="margin: 20px; padding: 15px; background: #f5f5f5; border: 1px solid #ddd; border-radius: 5px;">';
echo '<h2>已定義的路由</h2>';
echo '<table border="1" style="width: 100%; border-collapse: collapse;">';
echo '<tr><th>路由名稱</th><th>檔案</th><th>需認證</th><th>標題</th><th>測試連結</th></tr>';

global $routes;
foreach ($routes as $route => $config) {
    echo '<tr>';
    echo '<td>' . htmlspecialchars($route) . '</td>';
    echo '<td>' . htmlspecialchars($config['file']) . '</td>';
    echo '<td>' . ($config['auth'] ? '是' : '否') . '</td>';
    echo '<td>' . htmlspecialchars($config['title']) . '</td>';
    echo '<td><a href="' . url($route) . '" target="_blank">測試</a></td>';
    echo '</tr>';
}

echo '</table>';
echo '</div>';
