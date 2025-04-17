<?php
require_once '../includes/init.php';

try {
    // 添加 show_in_nav 欄位到 user_tags 表
    $db->exec("
        ALTER TABLE user_tags 
        ADD COLUMN show_in_nav TINYINT(1) NOT NULL DEFAULT 0
        COMMENT '是否在導航欄顯示' 
        AFTER tag_id
    ");
    
    echo "成功添加 show_in_nav 欄位到 user_tags 表。<br>";
    
} catch (PDOException $e) {
    echo "遷移失敗: " . $e->getMessage();
}
?>
