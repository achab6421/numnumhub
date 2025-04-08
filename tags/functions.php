<?php
/**
 * 標籤相關功能函數
 */

/**
 * 獲取所有標籤
 * @return array 標籤數據數組
 */
function getAllTags() {
    global $conn;
    
    $sql = "SELECT * FROM tags ORDER BY name";
    $result = $conn->query($sql);
    
    $tags = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $tags[] = $row;
        }
    }
    
    return $tags;
}

/**
 * 獲取指定ID的標籤
 * @param int $id 標籤ID
 * @return array|null 標籤數據或null
 */
function getTag($id) {
    global $conn;
    
    $sql = "SELECT * FROM tags WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return null;
    }
    
    return $result->fetch_assoc();
}

/**
 * 根據名稱獲取標籤，如果不存在則創建
 * @param string $name 標籤名稱
 * @return array 標籤數據
 */
function getOrCreateTag($name) {
    $tag = getTagByName($name);
    
    if (!$tag) {
        $result = createTag($name);
        if ($result['success']) {
            $tag = getTag($result['id']);
        }
    }
    
    return $tag;
}

/**
 * 根據名稱獲取標籤
 * @param string $name 標籤名稱
 * @return array|null 標籤數據或null
 */
function getTagByName($name) {
    global $conn;
    
    $sql = "SELECT * FROM tags WHERE name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return null;
    }
    
    return $result->fetch_assoc();
}

/**
 * 創建新標籤
 * @param string $name 標籤名稱
 * @return array 操作結果
 */
function createTag($name) {
    global $conn;
    
    // 檢查標籤名稱是否為空
    if (empty($name)) {
        return [
            'success' => false, 
            'message' => '標籤名稱不能為空'
        ];
    }
    
    // 檢查標籤是否已存在
    $existingTag = getTagByName($name);
    if ($existingTag) {
        return [
            'success' => true,
            'message' => '標籤已存在',
            'id' => $existingTag['id']
        ];
    }
    
    // 創建新標籤
    $sql = "INSERT INTO tags (name) VALUES (?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $name);
    
    if ($stmt->execute()) {
        return [
            'success' => true,
            'message' => '標籤建立成功',
            'id' => $conn->insert_id
        ];
    } else {
        return [
            'success' => false,
            'message' => '標籤建立失敗: ' . $stmt->error
        ];
    }
}

/**
 * 獲取餐廳的所有標籤
 * @param int $restaurantId 餐廳ID
 * @return array 標籤數據數組
 */
function getRestaurantTags($restaurantId) {
    global $conn;
    
    $sql = "SELECT t.* FROM tags t
            JOIN restaurant_tags rt ON t.id = rt.tag_id
            WHERE rt.restaurant_id = ?
            ORDER BY t.name";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $restaurantId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $tags = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $tags[] = $row;
        }
    }
    
    return $tags;
}

/**
 * 為餐廳添加標籤
 * @param int $restaurantId 餐廳ID
 * @param int $tagId 標籤ID
 * @return bool 操作是否成功
 */
function addTagToRestaurant($restaurantId, $tagId) {
    global $conn;
    
    // 檢查關聯是否已存在
    $checkSql = "SELECT id FROM restaurant_tags WHERE restaurant_id = ? AND tag_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("ii", $restaurantId, $tagId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        return true; // 關聯已存在，視為成功
    }
    
    // 添加新的關聯
    $sql = "INSERT INTO restaurant_tags (restaurant_id, tag_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $restaurantId, $tagId);
    
    return $stmt->execute();
}

/**
 * 從餐廳移除標籤
 * @param int $restaurantId 餐廳ID
 * @param int $tagId 標籤ID
 * @return bool 操作是否成功
 */
function removeTagFromRestaurant($restaurantId, $tagId) {
    global $conn;
    
    $sql = "DELETE FROM restaurant_tags WHERE restaurant_id = ? AND tag_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $restaurantId, $tagId);
    
    return $stmt->execute();
}

/**
 * 更新餐廳的標籤
 * @param int $restaurantId 餐廳ID
 * @param array $tagIds 標籤ID數組
 * @return bool 操作是否成功
 */
function updateRestaurantTags($restaurantId, $tagIds) {
    global $conn;
    
    // 開始事務
    $conn->begin_transaction();
    
    try {
        // 先刪除所有現有關聯
        $deleteSql = "DELETE FROM restaurant_tags WHERE restaurant_id = ?";
        $deleteStmt = $conn->prepare($deleteSql);
        $deleteStmt->bind_param("i", $restaurantId);
        $deleteStmt->execute();
        
        // 添加新的關聯
        if (!empty($tagIds)) {
            foreach ($tagIds as $tagId) {
                $insertSql = "INSERT INTO restaurant_tags (restaurant_id, tag_id) VALUES (?, ?)";
                $insertStmt = $conn->prepare($insertSql);
                $insertStmt->bind_param("ii", $restaurantId, $tagId);
                $insertStmt->execute();
            }
        }
        
        // 提交事務
        $conn->commit();
        return true;
    } catch (Exception $e) {
        // 回滾事務
        $conn->rollback();
        return false;
    }
}

/**
 * 獲取使用者的所有標籤
 * @param int $userId 使用者ID
 * @return array 標籤數據數組
 */
function getUserTags($userId) {
    global $conn;
    
    $sql = "SELECT t.* FROM tags t
            JOIN user_tags ut ON t.id = ut.tag_id
            WHERE ut.user_id = ?
            ORDER BY t.name";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $tags = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $tags[] = $row;
        }
    }
    
    return $tags;
}

/**
 * 為使用者添加標籤
 * @param int $userId 使用者ID
 * @param int $tagId 標籤ID
 * @return bool 操作是否成功
 */
function addTagToUser($userId, $tagId) {
    global $conn;
    
    // 檢查關聯是否已存在
    $checkSql = "SELECT id FROM user_tags WHERE user_id = ? AND tag_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("ii", $userId, $tagId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        return true; // 關聯已存在，視為成功
    }
    
    // 添加新的關聯
    $sql = "INSERT INTO user_tags (user_id, tag_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $userId, $tagId);
    
    return $stmt->execute();
}

/**
 * 從使用者移除標籤
 * @param int $userId 使用者ID
 * @param int $tagId 標籤ID
 * @return bool 操作是否成功
 */
function removeTagFromUser($userId, $tagId) {
    global $conn;
    
    $sql = "DELETE FROM user_tags WHERE user_id = ? AND tag_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $userId, $tagId);
    
    return $stmt->execute();
}

/**
 * 更新使用者的標籤
 * @param int $userId 使用者ID
 * @param array $tagIds 標籤ID數組
 * @return bool 操作是否成功
 */
function updateUserTags($userId, $tagIds) {
    global $conn;
    
    // 開始事務
    $conn->begin_transaction();
    
    try {
        // 先刪除所有現有關聯
        $deleteSql = "DELETE FROM user_tags WHERE user_id = ?";
        $deleteStmt = $conn->prepare($deleteSql);
        $deleteStmt->bind_param("i", $userId);
        $deleteStmt->execute();
        
        // 添加新的關聯
        if (!empty($tagIds)) {
            foreach ($tagIds as $tagId) {
                $insertSql = "INSERT INTO user_tags (user_id, tag_id) VALUES (?, ?)";
                $insertStmt = $conn->prepare($insertSql);
                $insertStmt->bind_param("ii", $userId, $tagId);
                $insertStmt->execute();
            }
        }
        
        // 提交事務
        $conn->commit();
        return true;
    } catch (Exception $e) {
        // 回滾事務
        $conn->rollback();
        return false;
    }
}
?>
