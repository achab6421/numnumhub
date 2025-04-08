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
    
    $sql = "DELETE FROM restaurants WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        return [
            'success' => true,
            'message' => '餐廳已成功刪除'
        ];
    } else {
        return [
            'success' => false,
            'message' => '刪除餐廳失敗: ' . $stmt->error
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
?>
