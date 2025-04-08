<?php
// 主應用入口點
require_once 'includes/init.php';

// 偵錯模式 (解決問題後移除)
$debug = false;

// 獲取請求的路由
$route = getCurrentRoute();

if ($debug) {
    echo "偵測到的路由: $route<br>";
    echo "有效路由檢查: " . (isValidRoute($route) ? '有效' : '無效') . "<br>";
    echo "當前用戶: " . (isLoggedIn() ? '已登入' : '未登入') . "<br>";
}

// 檢查路由是否有效
if (!isValidRoute($route)) {
    if ($debug) {
        echo "路由無效，將顯示404頁面<br>";
    } else {
        show404();
    }
}

// 檢查是否需要認證
if (routeRequiresAuth($route) && !isLoggedIn()) {
    // 保存原始請求頁面，以便登入後重導向
    $_SESSION['redirect_after_login'] = $route;
    
    if ($debug) {
        echo "需要認證，將跳轉到登入頁面<br>";
    } else {
        redirect('login');
    }
}

// 載入對應的檔案
$filePath = getRouteFilePath($route);

if ($debug) {
    echo "將載入文件: $filePath<br>";
} else {
    // 頁面標題設定
    global $routes;
    $pageTitle = $routes[$route]['title'] ?? SITE_NAME;
    
    // 載入對應的檔案
    if (file_exists($filePath)) {
        include $filePath;
    } else {
        echo "錯誤：無法找到文件 $filePath";
        show404();
    }
}
?>
