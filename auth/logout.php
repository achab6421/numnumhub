<?php
require_once __DIR__ . '/functions.php';

// 清除所有會話變數
$_SESSION = array();

// 如果要刪除儲存在用戶電腦中的 cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 結束會話
session_destroy();

// 重定向到登入頁面
header("Location: /auth/login.php");
exit;
?>
