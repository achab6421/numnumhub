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

// 直接構建ID數組，不使用array_column
$restaurantTagIds = [];
foreach ($restaurantTags as $tag) {
    if (isset($tag['id'])) {
        $restaurantTagIds[] = $tag['id'];
    } else if (isset($tag['tag_id'])) {
        // 如果返回的是tag_id字段而不是id字段
        $restaurantTagIds[] = $tag['tag_id'];
    }
}

// 添加調試信息 (開發時使用，正式環境請移除)
// echo '<pre>餐廳標籤：'; print_r($restaurantTags); echo '</pre>';
// echo '<pre>標籤ID：'; print_r($restaurantTagIds); echo '</pre>';
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
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">餐廳標籤</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($allTags)): ?>
                    <div class="mb-3 tag-cloud" id="tagContainer">
                        <?php 
                        // 計算標籤總數
                        $totalTags = count($allTags);
                        // 對標籤進行分組（每行4個）
                        $tagGroups = array_chunk($allTags, 4);
                        // 是否需要展開/收起功能（超過2組，即8個標籤）
                        $needsExpanding = $totalTags > 8;
                        ?>
                        
                        <?php foreach ($tagGroups as $index => $tagGroup): 
                            // 只有前2組顯示，其他隱藏
                            $rowClass = $index >= 2 ? 'tag-row-hidden' : '';
                        ?>
                        <div class="row mb-2 tag-row <?php echo $rowClass; ?>">
                            <?php foreach ($tagGroup as $tag): 
                                $isChecked = in_array($tag['id'], $restaurantTagIds);
                            ?>
                                <div class="col-md-3 col-sm-6 mb-2">
                                    <div class="form-check">
                                        <input type="checkbox" 
                                            class="form-check-input" 
                                            id="tag_<?php echo $tag['id']; ?>" 
                                            name="tags[]" 
                                            value="<?php echo $tag['id']; ?>"
                                            <?php echo $isChecked ? 'checked="checked"' : ''; ?>>
                                        <label class="form-check-label tag-label <?php echo $isChecked ? 'tag-selected' : ''; ?>" 
                                            for="tag_<?php echo $tag['id']; ?>">
                                            <?php echo htmlspecialchars($tag['name']); ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php if ($needsExpanding): ?>
                        <div class="text-center mt-2">
                            <button type="button" class="btn btn-sm btn-link toggle-tags" id="showMoreTags">
                                <i class="fas fa-ellipsis-h"></i> 顯示更多標籤 (還有 <?php echo $totalTags - 8; ?> 個)
                            </button>
                            <button type="button" class="btn btn-sm btn-link toggle-tags d-none" id="showLessTags">
                                <i class="fas fa-chevron-up"></i> 收起標籤
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted">目前沒有可用的標籤</p>
                <?php endif; ?>
                
                <div class="input-group mt-3">
                    <input type="text" class="form-control" name="new_tag" id="new_tag" placeholder="輸入新標籤名稱">
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary" type="button" id="addNewTagBtn">新增標籤</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="form-group">
        <label for="note">備註</label>
        <textarea class="form-control" id="note" name="note" rows="3"><?php echo htmlspecialchars($restaurant['note'] ?? ''); ?></textarea>
    </div>
</form>

<style>
.tag-label {
    transition: all 0.2s;
    border-radius: 3px;
    padding: 2px 5px;
}
.tag-selected {
    background-color: #e9f5ff;
    font-weight: 500;
}
.form-check:hover .tag-label {
    background-color: #f0f8ff;
}
.tag-cloud {
    max-height: 200px;
    overflow-y: auto;
    padding-right: 5px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 為新增標籤按鈕添加點擊事件
    document.getElementById('addNewTagBtn').addEventListener('click', function() {
        const newTagInput = document.getElementById('new_tag');
        if (newTagInput.value.trim() !== '') {
            // 這裡可以添加視覺反饋，表明標籤已添加
            newTagInput.classList.add('is-valid');
            setTimeout(function() {
                newTagInput.classList.remove('is-valid');
            }, 2000);
        }
    });
});
</script>
