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
