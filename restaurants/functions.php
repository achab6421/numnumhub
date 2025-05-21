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
    
    // 檢查資料表結構
    $tableColumns = [];
    $columnsResult = $conn->query("SHOW COLUMNS FROM restaurants");
    while($column = $columnsResult->fetch_assoc()) {
        $tableColumns[] = $column['Field'];
    }
    
    $fieldsToUpdate = [];
    $typesString = '';
    $bindParams = [];
    
    // 基本欄位
    if (in_array('name', $tableColumns)) {
        $fieldsToUpdate[] = 'name = ?';
        $typesString .= 's';
        $bindParams[] = $data['name'];
    }
    
    if (in_array('address', $tableColumns)) {
        $fieldsToUpdate[] = 'address = ?';
        $typesString .= 's';
        $bindParams[] = $data['address'] ?? '';
    }
    
    if (in_array('phone', $tableColumns)) {
        $fieldsToUpdate[] = 'phone = ?';
        $typesString .= 's';
        $bindParams[] = $data['phone'] ?? '';
    }
    
    if (in_array('link', $tableColumns)) {
        $fieldsToUpdate[] = 'link = ?';
        $typesString .= 's';
        $bindParams[] = $data['link'] ?? '';
    }
    
    if (in_array('note', $tableColumns)) {
        $fieldsToUpdate[] = 'note = ?';
        $typesString .= 's';
        $bindParams[] = $data['note'] ?? '';
    }
    
    // 經緯度欄位
    if (in_array('latitude', $tableColumns) && isset($data['latitude'])) {
        $fieldsToUpdate[] = 'latitude = ?';
        $typesString .= 'd';
        $bindParams[] = $data['latitude'];
    }
    
    if (in_array('longitude', $tableColumns) && isset($data['longitude'])) {
        $fieldsToUpdate[] = 'longitude = ?';
        $typesString .= 'd';
        $bindParams[] = $data['longitude'];
    }
    
    // 城市和區域欄位
    if (in_array('city', $tableColumns)) {
        $fieldsToUpdate[] = 'city = ?';
        $typesString .= 's';
        $bindParams[] = $data['city'] ?? '';
    }
    
    if (in_array('area', $tableColumns)) {
        $fieldsToUpdate[] = 'area = ?';
        $typesString .= 's';
        $bindParams[] = $data['area'] ?? '';
    }
    
    // 描述欄位
    if (in_array('description', $tableColumns)) {
        $fieldsToUpdate[] = 'description = ?';
        $typesString .= 's';
        $bindParams[] = $data['description'] ?? '';
    }
    
    // 如果資料表有 updated_at 欄位，自動更新它
    if (in_array('updated_at', $tableColumns)) {
        $fieldsToUpdate[] = 'updated_at = NOW()';
    }
    
    // 添加ID參數
    $typesString .= 'i';
    $bindParams[] = $id;
    
    // 建立SQL語句
    $sql = "UPDATE restaurants SET " . implode(', ', $fieldsToUpdate) . " WHERE id = ?";
    
    // 準備並執行語句
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [
            'success' => false,
            'message' => '準備SQL語句失敗: ' . $conn->error . ' [' . $sql . ']'
        ];
    }
    
    // 只有在有參數的情況下才執行bind_param
    if (count($bindParams) > 0) {
        $bindParamsRef = [];
        foreach ($bindParams as $key => $value) {
            $bindParamsRef[$key] = &$bindParams[$key];
        }
        array_unshift($bindParamsRef, $typesString);
        call_user_func_array([$stmt, 'bind_param'], $bindParamsRef);
    }
    
    if ($stmt->execute()) {
        // 更新成功
        return [
            'success' => true,
            'message' => '餐廳資料更新成功',
            'id' => $id,
            'affected_rows' => $stmt->affected_rows
        ];
    } else {
        // 更新失敗
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
if (!function_exists('setFlashMessage')) {
    function setFlashMessage($message, $type = 'success') {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
}

/**
 * 獲取提示訊息
 * @return array|null 包含訊息內容和類型的陣列，或null
 */
if (!function_exists('getFlashMessage')) {
    function getFlashMessage() {
        if (isset($_SESSION['flash_message']) && isset($_SESSION['flash_type'])) {
            $message = $_SESSION['flash_message'];
            $type = $_SESSION['flash_type'];
            
            // 清除訊息，避免重複顯示
            unset($_SESSION['flash_message']);
            unset($_SESSION['flash_type']);
            
            return [
                'message' => $message,
                'type' => $type
            ];
        }
        
        return null;
    }
}

/**
 * 為餐廳獲取標籤選項，現在只返回當前使用者的標籤
 * @param int $userId 使用者ID
 * @return array 標籤列表
 */
function getTagOptions($userId) {
    // 直接使用 getTags 函數獲取使用者的標籤
    return getTags($userId);
}

/**
 * 更新餐廳的標籤關聯，只允許使用當前使用者的標籤
 * @param int $restaurantId 餐廳ID
 * @param array $tagIds 標籤ID數組
 * @param int $userId 當前使用者ID，用於驗證標籤所有權
 * @return array 操作結果
 */
function updateRestaurantTags($restaurantId, $tagIds = [], $userId = null) {
    global $conn;
    
    // 如果提供了使用者ID，則驗證標籤所有權
    if ($userId) {
        // 獲取使用者的標籤
        $userTags = getTags($userId);
        $userTagIds = array_column($userTags, 'id');
        
        // 過濾標籤ID，只保留屬於該使用者的標籤
        $tagIds = array_intersect($tagIds, $userTagIds);
    }
    
    // 啟用交易處理
    $conn->begin_transaction();
    
    try {
        // 刪除該餐廳與該使用者標籤的所有現有關聯
        // 如果提供了使用者ID，可以加入條件只刪除屬於該使用者的標籤關聯
        if ($userId) {
            // 首先獲取要刪除的標籤關聯ID
            $deleteSql = "DELETE rt FROM restaurant_tags rt 
                         INNER JOIN user_tags ut ON rt.tag_id = ut.tag_id
                         WHERE rt.restaurant_id = ? AND ut.user_id = ?";
            $deleteStmt = $conn->prepare($deleteSql);
            $deleteStmt->bind_param("ii", $restaurantId, $userId);
        } else {
            // 刪除所有標籤關聯
            $deleteSql = "DELETE FROM restaurant_tags WHERE restaurant_id = ?";
            $deleteStmt = $conn->prepare($deleteSql);
            $deleteStmt->bind_param("i", $restaurantId);
        }
        
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
            return ["success" => true, "message" => "餐廳標籤已更新"];
        }
        
        // 準備批次插入的語句
        $insertSql = "INSERT INTO restaurant_tags (restaurant_id, tag_id) VALUES (?, ?)";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param("ii", $restaurantId, $tagId);
        
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

/**
 * 獲取指定餐廳的所有標籤
 * @param int $restaurantId 餐廳ID
 * @return array 餐廳標籤數組
 */
function getRestaurantTags($restaurantId) {
    global $conn;
    
    $sql = "SELECT t.* 
            FROM tags t 
            INNER JOIN restaurant_tags rt ON t.id = rt.tag_id 
            WHERE rt.restaurant_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $restaurantId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $tags = [];
    while ($row = $result->fetch_assoc()) {
        $tags[] = $row;
    }
    
    return $tags;
}

/**
 * 處理多張餐廳菜單圖片上傳
 *
 * @param int $restaurant_id 餐廳ID
 * @return array 處理結果
 */
function handleMultipleImageUpload($restaurant_id) {
    $result = [
        'success' => true,
        'errors' => [],
        'count' => 0
    ];
    
    // 檢查餐廳ID是否有效
    if (empty($restaurant_id) || !is_numeric($restaurant_id) || $restaurant_id <= 0) {
        $result['errors'][] = "無效的餐廳ID: " . var_export($restaurant_id, true);
        $result['success'] = false;
        return $result;
    }
    
    // 檢查是否有文件上傳
    if (!isset($_FILES['menu_images']) || empty($_FILES['menu_images']['name'][0]) || $_FILES['menu_images']['error'][0] === UPLOAD_ERR_NO_FILE) {
        return $result;
    }
    
    // 創建上傳目錄
    $upload_dir = dirname(__DIR__) . '/uploads/restaurants/' . $restaurant_id;
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            $result['errors'][] = "無法創建上傳目錄: {$upload_dir}";
            $result['success'] = false;
            return $result;
        }
    }
    
    // 確認目錄是否可寫
    if (!is_writable($upload_dir)) {
        $result['errors'][] = "上傳目錄不可寫: {$upload_dir}";
        $result['success'] = false;
        return $result;
    }
    
    // 處理每一個上傳的文件
    $file_count = count($_FILES['menu_images']['name']);
    for ($i = 0; $i < $file_count; $i++) {
        // 跳過沒有內容的文件
        if ($_FILES['menu_images']['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        
        $file_name = $_FILES['menu_images']['name'][$i];
        $file_tmp = $_FILES['menu_images']['tmp_name'][$i];
        $file_size = $_FILES['menu_images']['size'][$i];
        $file_error = $_FILES['menu_images']['error'][$i];
        
        // 檢查上傳錯誤
        if ($file_error !== UPLOAD_ERR_OK) {
            $error_message = getFileUploadErrorMessage($file_error);
            $result['errors'][] = "上傳檔案 {$file_name} 失敗: {$error_message}";
            continue;
        }
        
        // 獲取文件擴展名
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // 檢查擴展名
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($file_ext, $allowed)) {
            $result['errors'][] = "檔案 {$file_name} 格式不支援";
            continue;
        }
        
        // 檢查文件大小，限制為5MB
        if ($file_size > 5242880) {
            $result['errors'][] = "檔案 {$file_name} 超過5MB大小限制";
            continue;
        }
        
        // 生成唯一文件名
        $new_file_name = uniqid('menu_', true) . '.' . $file_ext;
        $upload_path = $upload_dir . '/' . $new_file_name;
        $db_path = 'uploads/restaurants/' . $restaurant_id . '/' . $new_file_name;
        
        // 移動上傳的文件到目標位置
        if (move_uploaded_file($file_tmp, $upload_path)) {
            // 保存到資料庫
            if (saveRestaurantImage($restaurant_id, $db_path)) {
                $result['count']++;
            } else {
                $result['errors'][] = "無法將圖片 {$file_name} 儲存到資料庫";
                if (file_exists($upload_path)) {
                    unlink($upload_path); // 如果數據庫保存失敗，刪除上傳的文件
                }
            }
        } else {
            $result['errors'][] = "移動檔案 {$file_name} 失敗";
        }
    }
    
    return $result;
}

/**
 * 獲取文件上傳錯誤的詳細描述
 *
 * @param int $error_code PHP文件上傳錯誤代碼
 * @return string 錯誤描述
 */
function getFileUploadErrorMessage($error_code) {
    switch ($error_code) {
        case UPLOAD_ERR_INI_SIZE:
            return '上傳的檔案超過了 php.ini 中 upload_max_filesize 的限制';
        case UPLOAD_ERR_FORM_SIZE:
            return '上傳的檔案超過了表單中 MAX_FILE_SIZE 的限制';
        case UPLOAD_ERR_PARTIAL:
            return '檔案僅部分上傳';
        case UPLOAD_ERR_NO_FILE:
            return '沒有檔案被上傳';
        case UPLOAD_ERR_NO_TMP_DIR:
            return '找不到臨時資料夾';
        case UPLOAD_ERR_CANT_WRITE:
            return '檔案寫入失敗';
        case UPLOAD_ERR_EXTENSION:
            return '某個 PHP 擴展阻止了檔案上傳';
        default:
            return '未知錯誤';
    }
}

/**
 * 保存餐廳圖片到資料庫
 *
 * @param int $restaurant_id 餐廳ID
 * @param string $image_path 圖片路徑
 * @return bool 是否成功
 */
function saveRestaurantImage($restaurant_id, $image_path, $description = null) {
    global $conn;
    
    $sql = "INSERT INTO restaurant_images (restaurant_id, image_path, description, is_menu, created_at) 
            VALUES (?, ?, ?, 1, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $restaurant_id, $image_path, $description);
    
    return $stmt->execute();
}

/**
 * 獲取餐廳的菜單圖片
 *
 * @param int $restaurant_id 餐廳ID
 * @return array 圖片資料陣列
 */
function getRestaurantImages($restaurant_id) {
    global $conn;
    
    $sql = "SELECT * FROM restaurant_images WHERE restaurant_id = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $restaurant_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $images = [];
    while ($row = $result->fetch_assoc()) {
        $images[] = $row;
    }
    
    return $images;
}

/**
 * 刪除餐廳圖片
 *
 * @param int $image_id 圖片ID
 * @param int $user_id 當前用戶ID (用於權限檢查)
 * @return array 處理結果
 */
function deleteRestaurantImage($image_id, $user_id) {
    global $conn;
    
    // 檢查圖片是否存在並獲取路徑
    $sql = "SELECT ri.*, r.created_by 
            FROM restaurant_images ri 
            JOIN restaurants r ON ri.restaurant_id = r.id 
            WHERE ri.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $image_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => '找不到該圖片'];
    }
    
    $image = $result->fetch_assoc();
    
    // 檢查權限
    if ($image['created_by'] != $user_id) {
        return ['success' => false, 'message' => '您無權刪除此圖片'];
    }
    
    // 刪除檔案
    $file_path = dirname(__DIR__) . '/' . $image['image_path'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    
    // 從資料庫移除記錄
    $sql = "DELETE FROM restaurant_images WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $image_id);
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => '圖片已成功刪除'];
    } else {
        return ['success' => false, 'message' => '刪除圖片失敗: ' . $conn->error];
    }
}
?>
