<?php
/**
 * 餐廳相關功能函數
 */

/**
 * 獲取所有餐廳
 * @param int $user_id 用戶ID，如果指定則只返回該用戶創建的餐廳
 * @return array 餐廳數據數組
 */
function getRestaurants($user_id = null) {
    global $conn;
    
    $sql = "SELECT * FROM restaurants";
    if ($user_id) {
        $sql .= " WHERE created_by = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
    } else {
        $stmt = $conn->prepare($sql);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $restaurants = [];
    while ($row = $result->fetch_assoc()) {
        $restaurants[] = $row;
    }
    
    return $restaurants;
}

/**
 * 獲取單個餐廳詳情
 * @param int $id 餐廳ID
 * @return array|null 餐廳數據或null
 */
function getRestaurant($id) {
    global $conn;
    
    $sql = "SELECT * FROM restaurants WHERE id = ?";
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
 * 創建新餐廳
 * @param array $data 餐廳數據
 * @return array 操作結果
 */
function createRestaurant($data) {
    global $conn;
    
    // 檢查餐廳名稱是否為空
    if (empty($data['name'])) {
        return [
            'success' => false, 
            'message' => '餐廳名稱不能為空'
        ];
    }
    
    // 確保 created_by 有值
    if (!isset($data['created_by']) || empty($data['created_by'])) {
        // 如果沒有提供創建者ID，使用當前登入用戶
        if (isset($_SESSION['user_id'])) {
            $data['created_by'] = $_SESSION['user_id'];
        } else {
            return [
                'success' => false,
                'message' => '缺少創建者ID'
            ];
        }
    }
    
    // 檢查資料表是否有 latitude 和 longitude 欄位
    $checkLatField = $conn->query("SHOW COLUMNS FROM restaurants LIKE 'latitude'");
    $checkLngField = $conn->query("SHOW COLUMNS FROM restaurants LIKE 'longitude'");
    
    if ($checkLatField->num_rows > 0 && $checkLngField->num_rows > 0) {
        // 表中有經緯度欄位，使用更新後的SQL語句
        $sql = "INSERT INTO restaurants (name, address, phone, link, note, latitude, longitude, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssddi", 
            $data['name'], 
            $data['address'], 
            $data['phone'], 
            $data['link'],
            $data['note'],
            $data['latitude'],
            $data['longitude'],
            $data['created_by']
        );
    } else {
        // 表中沒有經緯度欄位，使用原始SQL語句
        $sql = "INSERT INTO restaurants (name, address, phone, link, note, created_by) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssi", 
            $data['name'], 
            $data['address'], 
            $data['phone'], 
            $data['link'],
            $data['note'], 
            $data['created_by']
        );
    }
    
    if ($stmt->execute()) {
        return [
            'success' => true,
            'message' => '餐廳新增成功',
            'id' => $conn->insert_id
        ];
    } else {
        return [
            'success' => false,
            'message' => '餐廳新增失敗: ' . $stmt->error
        ];
    }
}

/**
 * 更新餐廳資料
 * @param int $id 餐廳ID
 * @param array $data 餐廳數據
 * @return array 操作結果
 */
function updateRestaurant($id, $data) {
    global $conn;
    
    // 檢查餐廳名稱是否為空
    if (empty($data['name'])) {
        return [
            'success' => false, 
            'message' => '餐廳名稱不能為空'
        ];
    }
    
    // 檢查資料表是否有 latitude 和 longitude 欄位
    $checkLatField = $conn->query("SHOW COLUMNS FROM restaurants LIKE 'latitude'");
    $checkLngField = $conn->query("SHOW COLUMNS FROM restaurants LIKE 'longitude'");
    
    if ($checkLatField->num_rows > 0 && $checkLngField->num_rows > 0 && 
        isset($data['latitude']) && isset($data['longitude'])) {
        // 表中有經緯度欄位，使用更新後的SQL語句
        $sql = "UPDATE restaurants SET name = ?, address = ?, phone = ?, link = ?, note = ?, 
                latitude = ?, longitude = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssdi", 
            $data['name'], 
            $data['address'], 
            $data['phone'], 
            $data['link'],
            $data['note'],
            $data['latitude'],
            $data['longitude'],
            $id
        );
    } else {
        // 表中沒有經緯度欄位或沒有提供經緯度，使用原始SQL語句
        $sql = "UPDATE restaurants SET name = ?, address = ?, phone = ?, link = ?, note = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssi", 
            $data['name'], 
            $data['address'], 
            $data['phone'], 
            $data['link'],
            $data['note'], 
            $id
        );
    }
    
    if ($stmt->execute()) {
        return [
            'success' => true,
            'message' => '餐廳資料更新成功'
        ];
    } else {
        return [
            'success' => false,
            'message' => '餐廳資料更新失敗: ' . $stmt->error
        ];
    }
}

/**
 * 更新餐廳的經緯度
 * @param int $id 餐廳ID
 * @param float $lat 緯度
 * @param float $lng 經度
 * @return array 操作結果
 */
function updateRestaurantCoordinates($id, $lat, $lng) {
    global $conn;
    
    $sql = "UPDATE restaurants SET lat = ?, lng = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ddi", $lat, $lng, $id);
    
    if ($stmt->execute()) {
        return [
            'success' => true,
            'message' => '餐廳座標更新成功'
        ];
    } else {
        return [
            'success' => false,
            'message' => '餐廳座標更新失敗: ' . $stmt->error
        ];
    }
}

/**
 * 刪除餐廳
 * @param int $id 餐廳ID
 * @return array 操作結果
 */
function deleteRestaurant($id) {
    global $conn;
    
    // 檢查是否存在使用此餐廳的活動
    $check_sql = "SELECT COUNT(*) as count FROM events WHERE restaurant_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $result = $check_stmt->get_result()->fetch_assoc();
    
    if ($result['count'] > 0) {
        return [
            'success' => false,
            'message' => "無法刪除：有 {$result['count']} 個活動使用此餐廳"
        ];
    }
    
    // 開始交易
    $conn->begin_transaction();
    
    try {
        // 步驟 1: 先刪除與此餐廳相關的標籤關聯
        $delete_tags_sql = "DELETE FROM restaurant_tags WHERE restaurant_id = ?";
        $delete_tags_stmt = $conn->prepare($delete_tags_sql);
        $delete_tags_stmt->bind_param("i", $id);
        $delete_tags_stmt->execute();
        
        // 步驟 2: 再刪除餐廳本身
        $delete_restaurant_sql = "DELETE FROM restaurants WHERE id = ?";
        $delete_restaurant_stmt = $conn->prepare($delete_restaurant_sql);
        $delete_restaurant_stmt->bind_param("i", $id);
        $delete_restaurant_stmt->execute();
        
        // 提交交易
        $conn->commit();
        
        return [
            'success' => true,
            'message' => '餐廳已成功刪除'
        ];
    } catch (Exception $e) {
        // 發生錯誤，回滾交易
        $conn->rollback();
        
        return [
            'success' => false,
            'message' => '刪除餐廳失敗: ' . $e->getMessage()
        ];
    }
}

/**
 * 檢查用戶是否有權限操作指定餐廳
 * @param int $restaurant_id 餐廳ID
 * @param int $user_id 用戶ID
 * @return bool 是否有權限
 */
function canManageRestaurant($restaurant_id, $user_id) {
    global $conn;
    
    $sql = "SELECT created_by FROM restaurants WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $restaurant_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    $restaurant = $result->fetch_assoc();
    
    // 如果created_by為null或未設定，允許任何登入用戶管理
    if (!isset($restaurant['created_by']) || $restaurant['created_by'] === null || $restaurant['created_by'] === 0) {
        return true;
    }
    
    return $restaurant['created_by'] == $user_id;
}

/**
 * 設置提示訊息（通常用於重導後顯示）
 * @param string $message 訊息內容
 * @param string $type 訊息類型 (success, danger, warning, info)
 */
function setFlashMessage($message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

/**
 * 獲取所有餐廳的標籤關係
 * @param array $restaurant_ids 餐廳ID陣列，如果為空則獲取所有關聯
 * @return array 餐廳標籤關聯資料，以餐廳ID為鍵
 */
function getAllRestaurantTags($restaurant_ids = []) {
    global $conn;
    
    // 準備SQL查詢
    $sql = "SELECT rt.restaurant_id, t.id as tag_id, t.name as tag_name 
            FROM restaurant_tags rt 
            JOIN tags t ON rt.tag_id = t.id";
    
    // 如果指定了餐廳ID，則只獲取這些餐廳的標籤
    if (!empty($restaurant_ids)) {
        $ids = implode(',', array_map('intval', $restaurant_ids));
        $sql .= " WHERE rt.restaurant_id IN ($ids)";
    }
    
    $result = $conn->query($sql);
    
    // 將結果整理為以餐廳ID為鍵的陣列
    $restaurantTags = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $restaurant_id = $row['restaurant_id'];
            
            // 初始化該餐廳的標籤陣列（如果還不存在）
            if (!isset($restaurantTags[$restaurant_id])) {
                $restaurantTags[$restaurant_id] = [];
            }
            
            // 添加標籤信息到對應餐廳的陣列中
            $restaurantTags[$restaurant_id][] = [
                'id' => $row['tag_id'],
                'name' => $row['tag_name']
            ];
        }
    }
    
    return $restaurantTags;
}
?>
