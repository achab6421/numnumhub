<?php
// AJAX 端點：獲取餐廳編輯表單
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../tags/functions.php';

// 確保用戶已登入
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => '未授權的訪問']);
    exit;
}

// 獲取餐廳ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 檢查餐廳是否存在
$restaurant = getRestaurant($id);
if (!$restaurant) {
    echo '<div class="alert alert-danger">找不到指定的餐廳</div>';
    exit;
}

// 檢查用戶是否有權限編輯此餐廳
if (!canManageRestaurant($id, $_SESSION['user_id'])) {
    echo '<div class="alert alert-danger">您沒有權限編輯此餐廳</div>';
    exit;
}

// 獲取所有標籤和餐廳已有標籤
$allTags = getAllTags();
$restaurantTags = getRestaurantTags($id);
$restaurantTagIds = array_column($restaurantTags, 'id');
?>

<form id="editRestaurantForm">
    <input type="hidden" name="restaurant_id" value="<?php echo $id; ?>">
    
    <div class="form-group">
        <label for="name">餐廳名稱 <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($restaurant['name']); ?>" required>
    </div>
    
    <div class="form-group">
        <label for="address">地址</label>
        <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($restaurant['address'] ?? ''); ?>">
    </div>
    
    <div class="form-group">
        <label for="phone">電話</label>
        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($restaurant['phone'] ?? ''); ?>">
    </div>
    
    <div class="form-group">
        <label for="link">店家連結</label>
        <input type="url" class="form-control" id="link" name="link" value="<?php echo htmlspecialchars($restaurant['link'] ?? ''); ?>" placeholder="https://">
    </div>
    
    <div class="form-group">
        <label>標籤</label>
        <div class="card">
            <div class="card-body">
                <?php if (!empty($allTags)): ?>
                    <div class="mb-3">
                        <?php foreach($allTags as $tag): ?>
                        <div class="custom-control custom-checkbox custom-control-inline">
                            <input type="checkbox" class="custom-control-input" id="tag_<?php echo $tag['id']; ?>" name="tags[]" value="<?php echo $tag['id']; ?>" <?php echo in_array($tag['id'], $restaurantTagIds) ? 'checked' : ''; ?>>
                            <label class="custom-control-label" for="tag_<?php echo $tag['id']; ?>"><?php echo htmlspecialchars($tag['name']); ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <div class="input-group">
                    <input type="text" class="form-control" name="new_tag" placeholder="新增標籤">
                </div>
            </div>
        </div>
    </div>
    
    <div class="form-group">
        <label for="note">備註</label>
        <textarea class="form-control" id="note" name="note" rows="3"><?php echo htmlspecialchars($restaurant['note'] ?? ''); ?></textarea>
    </div>
</form>
