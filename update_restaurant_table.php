<?php
require_once 'includes/init.php';

// 檢查資料表結構
$checkQuery = "SHOW COLUMNS FROM restaurants LIKE 'created_by'";
$result = $conn->query($checkQuery);

if ($result->num_rows === 0) {
    // 如果 created_by 欄位不存在，新增它
    $alterQuery = "ALTER TABLE restaurants ADD COLUMN created_by INT NOT NULL DEFAULT 0 AFTER note";
    
    if ($conn->query($alterQuery) === TRUE) {
        echo "<div class='alert alert-success'>餐廳資料表結構已更新：新增 created_by 欄位</div>";
    } else {
        echo "<div class='alert alert-danger'>錯誤：無法更新資料表結構 - " . $conn->error . "</div>";
    }
    
    // 將所有現有餐廳指派給當前用戶
    if (isLoggedIn()) {
        $updateQuery = "UPDATE restaurants SET created_by = ? WHERE created_by = 0";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("i", $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            echo "<div class='alert alert-success'>已將所有無主餐廳指派給您的帳號</div>";
        } else {
            echo "<div class='alert alert-danger'>錯誤：無法更新餐廳擁有者 - " . $stmt->error . "</div>";
        }
    }
} else {
    echo "<div class='alert alert-info'>餐廳資料表已有 created_by 欄位，無需修改</div>";
}

// 檢查tag表格
$checkTagsTable = "SHOW TABLES LIKE 'tags'";
$tagsResult = $conn->query($checkTagsTable);

if ($tagsResult->num_rows === 0) {
    // 建立tags表格
    $createTagsTable = "
    CREATE TABLE tags (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE
    )";
    
    if ($conn->query($createTagsTable) === TRUE) {
        echo "<div class='alert alert-success'>已建立標籤(tags)資料表</div>";
    } else {
        echo "<div class='alert alert-danger'>錯誤：無法建立標籤資料表 - " . $conn->error . "</div>";
    }
}

// 檢查restaurant_tags表格
$checkRestaurantTagsTable = "SHOW TABLES LIKE 'restaurant_tags'";
$restaurantTagsResult = $conn->query($checkRestaurantTagsTable);

if ($restaurantTagsResult->num_rows === 0) {
    // 建立restaurant_tags表格
    $createRestaurantTagsTable = "
    CREATE TABLE restaurant_tags (
        id INT AUTO_INCREMENT PRIMARY KEY,
        restaurant_id INT NOT NULL,
        tag_id INT NOT NULL,
        FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
        FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($createRestaurantTagsTable) === TRUE) {
        echo "<div class='alert alert-success'>已建立餐廳標籤關聯(restaurant_tags)資料表</div>";
    } else {
        echo "<div class='alert alert-danger'>錯誤：無法建立餐廳標籤關聯資料表 - " . $conn->error . "</div>";
    }
}

// 檢查user_tags表格
$checkUserTagsTable = "SHOW TABLES LIKE 'user_tags'";
$userTagsResult = $conn->query($checkUserTagsTable);

if ($userTagsResult->num_rows === 0) {
    // 建立user_tags表格
    $createUserTagsTable = "
    CREATE TABLE user_tags (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        tag_id INT NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($createUserTagsTable) === TRUE) {
        echo "<div class='alert alert-success'>已建立用戶標籤關聯(user_tags)資料表</div>";
    } else {
        echo "<div class='alert alert-danger'>錯誤：無法建立用戶標籤關聯資料表 - " . $conn->error . "</div>";
    }
}

echo "<div class='mt-4'><a href='" . url('restaurants') . "' class='btn btn-primary'>返回餐廳管理</a></div>";
?>
