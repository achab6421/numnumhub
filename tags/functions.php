<?php
/**
 * 標籤相關功能函數
 */

/**
 * 獲取使用者的標籤
 * @param int $userId 使用者ID
 * @return array 標籤列表
 */
function getTags($userId) {
    global $conn;
    
    // 修正：使用JOIN查詢user_tags表獲取使用者的標籤
    $sql = "SELECT t.* FROM tags t 
            INNER JOIN user_tags ut ON t.id = ut.tag_id 
            WHERE ut.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    
    $stmt->execute();
    $result = $stmt->get_result();
    $tags = [];
    
    while ($row = $result->fetch_assoc()) {
        $tags[] = $row;
    }
    
    return $tags;
}

/**
 * 獲取所有標籤，可用於選擇
 * @return array 所有標籤列表
 */
function getAllTags() {
    global $conn;
    
    $sql = "SELECT * FROM tags";
    $result = $conn->query($sql);
    $tags = [];
    
    while ($row = $result->fetch_assoc()) {
        $tags[] = $row;
    }
    
    return $tags;
}

/**
 * 獲取餐廳的標籤
 * @param array $restaurantIds 餐廳ID數組
 * @return array 餐廳標籤關聯數組
 */
if (!function_exists('getAllRestaurantTags')) {
    function getAllRestaurantTags($restaurantIds) {
        global $conn;
        
        if (empty($restaurantIds)) {
            return [];
        }
        
        // 將ID數組轉換為逗號分隔的字符串
        $idList = implode(',', array_map('intval', $restaurantIds));
        
        $sql = "SELECT rt.restaurant_id, t.* 
                FROM restaurant_tags rt 
                INNER JOIN tags t ON rt.tag_id = t.id 
                WHERE rt.restaurant_id IN ($idList)";
        
        $result = $conn->query($sql);
        $restaurantTags = [];
        
        while ($row = $result->fetch_assoc()) {
            $restaurantId = $row['restaurant_id'];
            unset($row['restaurant_id']);
            
            if (!isset($restaurantTags[$restaurantId])) {
                $restaurantTags[$restaurantId] = [];
            }
            
            $restaurantTags[$restaurantId][] = $row;
        }
        
        return $restaurantTags;
    }
}

/**
 * 創建新標籤
 * 這個函數會先檢查標籤是否存在，如果不存在則創建
 * 然後會在user_tags表中創建關聯
 * 
 * @param string $name 標籤名稱
 * @param int $userId 使用者ID
 * @return array 操作結果
 */
function createTag($name, $userId) {
    global $conn;
    
    // 啟用交易處理
    $conn->begin_transaction();
    
    try {
        // 檢查標籤是否已存在
        $checkSql = "SELECT id FROM tags WHERE name = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("s", $name);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        // 如果標籤已存在，獲取ID
        if ($result->num_rows > 0) {
            $tagId = $result->fetch_assoc()['id'];
        } else {
            // 標籤不存在，創建新標籤
            $insertSql = "INSERT INTO tags (name) VALUES (?)";
            $insertStmt = $conn->prepare($insertSql);
            $insertStmt->bind_param("s", $name);
            
            if (!$insertStmt->execute()) {
                throw new Exception("創建標籤失敗: " . $insertStmt->error);
            }
            
            $tagId = $conn->insert_id;
        }
        
        // 檢查user_tags關聯是否已存在
        $checkRelationSql = "SELECT id FROM user_tags WHERE user_id = ? AND tag_id = ?";
        $checkRelationStmt = $conn->prepare($checkRelationSql);
        $checkRelationStmt->bind_param("ii", $userId, $tagId);
        $checkRelationStmt->execute();
        
        // 如果關聯不存在，創建關聯
        if ($checkRelationStmt->get_result()->num_rows === 0) {
            $relationSql = "INSERT INTO user_tags (user_id, tag_id) VALUES (?, ?)";
            $relationStmt = $conn->prepare($relationSql);
            $relationStmt->bind_param("ii", $userId, $tagId);
            
            if (!$relationStmt->execute()) {
                throw new Exception("建立使用者標籤關聯失敗: " . $relationStmt->error);
            }
        }
        
        $conn->commit();
        return ["success" => true, "message" => "標籤已成功創建", "id" => $tagId];
    } catch (Exception $e) {
        $conn->rollback();
        return ["success" => false, "message" => $e->getMessage()];
    }
}

/**
 * 更新標籤
 * 注意：這只會更新標籤名稱，不修改使用者關聯
 * @param int $tagId 標籤ID
 * @param string $name 新標籤名稱
 * @param int $userId 使用者ID，用於確認權限
 * @return array 操作結果
 */
function updateTag($tagId, $name, $userId) {
    global $conn;
    
    // 檢查使用者是否擁有此標籤
    $checkSql = "SELECT id FROM user_tags WHERE user_id = ? AND tag_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("ii", $userId, $tagId);
    $checkStmt->execute();
    
    if ($checkStmt->get_result()->num_rows === 0) {
        return ["success" => false, "message" => "標籤不存在或您無權修改"];
    }
    
    // 檢查新標籤名稱是否已存在
    $checkNameSql = "SELECT id FROM tags WHERE name = ? AND id != ?";
    $checkNameStmt = $conn->prepare($checkNameSql);
    $checkNameStmt->bind_param("si", $name, $tagId);
    $checkNameStmt->execute();
    
    if ($checkNameStmt->get_result()->num_rows > 0) {
        return ["success" => false, "message" => "標籤名稱已存在"];
    }
    
    // 更新標籤名稱
    $sql = "UPDATE tags SET name = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $name, $tagId);
    
    if ($stmt->execute()) {
        return ["success" => true, "message" => "標籤已成功更新"];
    } else {
        return ["success" => false, "message" => "標籤更新失敗: " . $stmt->error];
    }
}

/**
 * 刪除標籤關聯
 * 僅從user_tags表中刪除關聯，不刪除標籤本身
 * 
 * @param int $tagId 標籤ID
 * @param int $userId 使用者ID
 * @return array 操作結果
 */
function deleteTag($tagId, $userId) {
    global $conn;
    
    // 刪除標籤關聯而非標籤本身
    $sql = "DELETE FROM user_tags WHERE tag_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $tagId, $userId);
    
    if ($stmt->execute()) {
        return ["success" => true, "message" => "標籤已從您的列表中移除"];
    } else {
        return ["success" => false, "message" => "移除標籤失敗: " . $stmt->error];
    }
}

/**
 * 獲取特定標籤詳情
 * @param int $tagId 標籤ID
 * @param int $userId 使用者ID
 * @return array|null 標籤信息
 */
function getTag($tagId, $userId) {
    global $conn;
    
    // 確保標籤存在且屬於該使用者
    $sql = "SELECT t.* FROM tags t 
            INNER JOIN user_tags ut ON t.id = ut.tag_id 
            WHERE t.id = ? AND ut.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $tagId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return null;
    }
    
    return $result->fetch_assoc();
}

/**
 * 更新使用者的標籤關聯
 * @param int $userId 使用者ID
 * @param array $tagIds 標籤ID數組
 * @return array 操作結果
 */
function updateUserTags($userId, $tagIds = []) {
    global $conn;
    
    // 啟用交易處理
    $conn->begin_transaction();
    
    try {
        // 刪除該使用者的所有現有標籤關聯
        $deleteSql = "DELETE FROM user_tags WHERE user_id = ?";
        $deleteStmt = $conn->prepare($deleteSql);
        $deleteStmt->bind_param("i", $userId);
        
        if (!$deleteStmt->execute()) {
            $conn->rollback();
            return [
                "success" => false, 
                "message" => "移除舊標籤關聯失敗: " . $deleteStmt->error
            ];
        }
        
        // 如果沒有新標籤，直接返回成功
        if (empty($tagIds)) {
            $conn->commit();
            return ["success" => true, "message" => "標籤已更新"];
        }
        
        // 準備批次插入的語句
        $insertSql = "INSERT INTO user_tags (user_id, tag_id) VALUES (?, ?)";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param("ii", $userId, $tagId);
        
        // 為每個標籤創建關聯
        $insertCount = 0;
        foreach ($tagIds as $tagId) {
            if (empty($tagId)) continue;
            
            $tagId = (int)$tagId;
            if ($insertStmt->execute()) {
                $insertCount++;
            }
        }
        
        // 提交交易
        $conn->commit();
        return [
            "success" => true, 
            "message" => "已更新 {$insertCount} 個標籤關聯", 
            "count" => $insertCount
        ];
    } catch (Exception $e) {
        $conn->rollback();
        return ["success" => false, "message" => "標籤更新失敗: " . $e->getMessage()];
    }
}
?>
