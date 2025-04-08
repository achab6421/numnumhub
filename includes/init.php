<?php
// 初始化應用
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

// 啟動會話（如果尚未啟動）
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
