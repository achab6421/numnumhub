<?php
// 路由測試頁面
require_once 'includes/init.php';

echo "<h1>路由系統測試</h1>";

// 測試 getCurrentRoute
echo "<h2>當前路由</h2>";
echo "當前路由: " . getCurrentRoute();

// 測試 isValidRoute
echo "<h2>有效路由測試</h2>";
$testRoutes = ['index', 'login', 'dashboard', 'invalid_route'];
foreach ($testRoutes as $route) {
    echo "路由 '$route' 是否有效: " . (isValidRoute($route) ? '有效' : '無效') . "<br>";
}

// 測試 url 函數
echo "<h2>URL 生成測試</h2>";
echo "首頁 URL: " . url('index') . "<br>";
echo "登入頁 URL: " . url('login') . "<br>";
echo "餐廳詳情 URL (ID=5): " . url('restaurant', ['id' => 5]) . "<br>";
echo "搜尋 URL: " . url('restaurants', ['keyword' => '義大利麵']) . "<br>";

// 列出所有可用路由
echo "<h2>所有可用路由</h2>";
global $routes;
echo "<table border='1'>";
echo "<tr><th>路由名稱</th><th>檔案</th><th>需要認證</th><th>測試連結</th></tr>";
foreach ($routes as $routeName => $routeInfo) {
    echo "<tr>";
    echo "<td>$routeName</td>";
    echo "<td>{$routeInfo['file']}</td>";
    echo "<td>" . ($routeInfo['auth'] ? '是' : '否') . "</td>";
    echo "<td><a href='" . url($routeName) . "' target='_blank'>測試</a></td>";
    echo "</tr>";
}
echo "</table>";
?>
