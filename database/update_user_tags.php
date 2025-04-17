<?php
require_once '../includes/init.php';

// 檢查是否已經存在 show_in_nav 欄位
$stmt = $db->prepare("SHOW COLUMNS FROM user_tags LIKE 'show_in_nav'");
$stmt->execute();
$exists = $stmt->fetch();

if (!$exists) {
    try {
        // 添加 show_in_nav 欄位
        $db->exec("
            ALTER TABLE user_tags 
            ADD COLUMN show_in_nav TINYINT(1) NOT NULL DEFAULT 0
            COMMENT '是否在導航欄顯示' 
            AFTER tag_id
        ");
        
        echo "成功添加 show_in_nav 欄位到 user_tags 表。<br>";
        echo "<a href='../index.php'>返回首頁</a>";
    } catch (PDOException $e) {
        echo "添加欄位失敗: " . $e->getMessage();
    }
} else {
    echo "show_in_nav 欄位已存在，無需更新。<br>";
    echo "<a href='../index.php'>返回首頁</a>";
}
?>
