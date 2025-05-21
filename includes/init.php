<?php
// 初始化應用
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

// 啟動會話（如果尚未啟動）
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 添加自動重導向功能，確保未登入用戶訪問需要登入的頁面時會被重導向
$currentRoute = getCurrentRoute();

// 確保 CURRENT_ROUTE 是正確設置的
if (!defined('CURRENT_ROUTE')) {
    // 獲取當前 URI
    $requestUri = $_SERVER['REQUEST_URI'];
    
    // 移除 BASE_URL 前綴以獲取真正的路由路徑
    $basePath = parse_url(BASE_URL, PHP_URL_PATH);
    $currentPath = str_replace($basePath, '', $requestUri);
    
    // 移除查詢字串
    $currentRoute = strtok($currentPath, '?');
    
    // 移除開頭和結尾的斜線
    $currentRoute = trim($currentRoute, '/');
    
    // 如果路由為空，使用默認路由
    if (empty($currentRoute)) {
        $currentRoute = 'index';
    }
    
    define('CURRENT_ROUTE', $currentRoute);
}

// 如果當前路由需要認證，但用戶未登入
if (isset($routes[$currentRoute]) && $routes[$currentRoute]['auth'] && !isLoggedIn()) {
    // 保存原始請求的URL，以便登入後重導向回來
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    
    // 重導向到登入頁面
    if ($currentRoute !== 'login') {
        header('Location: ' . url('login'));
        exit;
    }
}
?>
