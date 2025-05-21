```php
<?php
// 編輯餐廳頁面
require_once dirname(__DIR__) . '/includes/init.php';
require_once __DIR__ . '/functions.php';
require_once dirname(__DIR__) . '/tags/functions.php';

// 確保用戶已登入
if (!isLoggedIn()) {
    redirect('login');
}

// 獲取餐廳ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 檢查餐廳是否存在
$restaurant = getRestaurant($id);
if (!$restaurant) {
    setFlashMessage('找不到指定的餐廳', 'danger');
    redirect('restaurants');
}

// 檢查用戶是否有權限編輯此餐廳
if (!canManageRestaurant($id, $_SESSION['user_id'])) {
    setFlashMessage('您沒有權限編輯此餐廳', 'danger');
    redirect('restaurants');
}

// 獲取餐廳已有的標籤
$restaurantTags = getRestaurantTags($id);
$restaurantTagIds = array_column($restaurantTags, 'id');

// 獲取所有標籤選項
$tagOptions = getTagOptions($_SESSION['user_id']);

// 獲取餐廳已有的圖片
$menu_images = getRestaurantImages($id);

$error = '';
$success = '';

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $link = trim($_POST['link'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $area = trim($_POST['area'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $note = trim($_POST['note'] ?? '');
    
    // 獲取標籤
    $selectedTagIds = isset($_POST['tags']) ? $_POST['tags'] : [];
    
    // 驗證必填欄位
    if (empty($name)) {
        $error = '請輸入餐廳名稱';
    } else {
        // 更新餐廳資訊
        $result = updateRestaurant($id, [
            'name' => $name,
            'phone' => $phone,
            'address' => $address,
            'link' => $link,
            'city' => $city,
            'area' => $area,
            'description' => $description,
            'note' => $note
        ]);
        
        if ($result['success']) {
            // 更新餐廳標籤
            $tagResult = updateRestaurantTags($id, $selectedTagIds, $_SESSION['user_id']);
            
            // 處理圖片上傳 - 檢查是否有新圖片上傳
            $hasNewFiles = isset($_FILES['menu_images']) && 
                        !empty($_FILES['menu_images']['name'][0]) && 
                        $_FILES['menu_images']['error'][0] !== UPLOAD_ERR_NO_FILE;
            
            if ($hasNewFiles) {
                // 嘗試上傳新圖片
                $uploadResults = handleMultipleImageUpload($id);
                
                if (!empty($uploadResults['errors'])) {
                    // 有上傳錯誤，但不阻止更新
                    $error = "餐廳資料已更新，但部分圖片上傳失敗：<br>" . implode('<br>', $uploadResults['errors']);
                    $success = "餐廳基本資料已成功更新";
                } else if ($uploadResults['count'] > 0) {
                    $success = "餐廳資料已更新，並成功上傳了 {$uploadResults['count']} 張菜單圖片";
                    
                    // 成功上傳圖片，設置閃存消息並重定向
                    $_SESSION['flash_message'] = $success;
                    $_SESSION['flash_type'] = 'success';
                    redirect('restaurant', ['id' => $id]);
                } else {
                    // 沒有成功上傳任何圖片
                    $success = '餐廳資料已成功更新，但沒有圖片上傳成功';
                }
            } else {
                // 沒有選擇新圖片上傳，直接設置成功消息
                $success = '餐廳資料已成功更新';
                
                // 設置閃存消息並重定向
                $_SESSION['flash_message'] = $success;
                $_SESSION['flash_type'] = 'success';
                redirect('restaurant', ['id' => $id]);
            }
            
            // 如果有錯誤訊息，會保留在頁面上，不進行重定向
            if (empty($error)) {
                // 如果沒有錯誤但也沒有重定向，這裡再確保重定向
                $_SESSION['flash_message'] = $success;
                $_SESSION['flash_type'] = 'success';
                redirect('restaurant', ['id' => $id]);
            }
        } else {
            $error = $result['message'];
        }
    }
}

// 頁面標題和頁頭
$pageTitle = '編輯餐廳';
include_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">編輯餐廳</h1>
        <a href="<?php echo url('restaurant', ['id' => $id]); ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> 返回詳情
        </a>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="m-0">編輯餐廳</h4>
                </div>
                <div class="card-body">
                    <form id="restaurantForm" action="<?php echo url('edit-restaurant', ['id' => $id]); ?>" method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="name">餐廳名稱 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($restaurant['name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="address">地址</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($restaurant['address'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="city">城市</label>
                                <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($restaurant['city'] ?? ''); ?>">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="area">區域</label>
                                <input type="text" class="form-control" id="area" name="area" value="<?php echo htmlspecialchars($restaurant['area'] ?? ''); ?>">
                            </div>
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
                            <label for="description">餐廳描述</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($restaurant['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="note">備註</label>
                            <textarea class="form-control" id="note" name="note" rows="3"><?php echo htmlspecialchars($restaurant['note'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>標籤</label>
                            <div class="card">
                                <div class="card-body">
                                    <?php if (!empty($tagOptions)): ?>
                                        <div class="mb-3">
                                            <?php foreach($tagOptions as $tag): ?>
                                            <div class="custom-control custom-checkbox custom-control-inline">
                                                <input type="checkbox" class="custom-control-input" id="tag_<?php echo $tag['id']; ?>" name="tags[]" value="<?php echo $tag['id']; ?>" <?php echo in_array($tag['id'], $restaurantTagIds) ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="tag_<?php echo $tag['id']; ?>"><?php echo htmlspecialchars($tag['name']); ?></label>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted">尚無可用標籤。您可以在<a href="<?php echo url('user_profile'); ?>#preferences">個人偏好設定</a>中創建標籤。</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 圖片上傳功能 -->
                        <div class="form-group">
                            <label for="menu_images">上傳菜單圖片 (可多選)</label>
                            <div class="input-group">
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="menu_images" name="menu_images[]" accept="image/jpeg,image/png,image/gif" multiple>
                                    <label class="custom-file-label" for="menu_images">選擇圖片檔案...</label>
                                </div>
                                <div class="input-group-append">
                                    <span class="input-group-text" id="fileCounter">0 檔案</span>
                                </div>
                            </div>
                            <small class="form-text text-muted">
                                您可以選擇多個圖片檔案一次上傳。支援的格式: JPG, JPEG, PNG, GIF，每個檔案大小不超過5MB
                            </small>
                        </div>
                        
                        <!-- 圖片預覽區域 -->
                        <div id="image_preview" class="d-flex flex-wrap mt-2 mb-4">
                            <!-- 預覽圖片將顯示在這裡 -->
                        </div>
                        
                        <!-- 顯示現有的菜單圖片 -->
                        <?php if (!empty($menu_images)): ?>
                        <div class="form-group">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">現有菜單圖片 (<?php echo count($menu_images); ?> 張)</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row" id="existing_images">
                                        <?php foreach ($menu_images as $image): ?>
                                            <div class="col-md-4 col-sm-6 mb-3" data-image-id="<?php echo $image['id']; ?>">
                                                <div class="card h-100">
                                                    <div class="image-preview-container" 
                                                         data-img-src="/<?php echo htmlspecialchars($image['image_path']); ?>" 
                                                         data-img-title="<?php echo htmlspecialchars($image['description'] ?? '菜單圖片'); ?>">
                                                        <img src="/<?php echo htmlspecialchars($image['image_path']); ?>" 
                                                             class="card-img-top preview-image" alt="菜單圖片" 
                                                             style="height: 150px; object-fit: cover;" 
                                                             loading="lazy">
                                                        <div class="image-overlay">
                                                            <i class="fas fa-search-plus"></i>
                                                        </div>
                                                    </div>
                                                    <div class="card-body p-2 text-center">
                                                        <small class="text-muted d-block mb-2">上傳於: <?php echo date('Y-m-d H:i', strtotime($image['created_at'])); ?></small>
                                                        <form method="post" action="<?php echo url('delete-restaurant-image'); ?>" 
                                                              onsubmit="return confirm('確定要刪除此圖片嗎？此操作不可恢復');">
                                                            <input type="hidden" name="image_id" value="<?php echo $image['id']; ?>">
                                                            <input type="hidden" name="restaurant_id" value="<?php echo $id; ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger delete-image">
                                                                <i class="fas fa-trash"></i> 刪除
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> 儲存更新
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 添加 Lightbox CSS 和 JS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>

<!-- 文件上傳預覽 JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 處理文件上傳預覽
    const menuImagesInput = document.getElementById('menu_images');
    const fileCounter = document.getElementById('fileCounter');
    const preview = document.getElementById('image_preview');
    const fileLabel = document.querySelector('.custom-file-label');
    
    menuImagesInput.addEventListener('change', function(e) {
        preview.innerHTML = '';
        
        if (this.files) {
            const maxFiles = 10; // 最大文件數量
            const maxSize = 5 * 1024 * 1024; // 5MB，最大檔案大小
            let validFiles = 0;
            let invalidFiles = 0;
            
            if (this.files.length > maxFiles) {
                alert(`最多只能上傳 ${maxFiles} 張圖片`);
                this.value = '';
                fileCounter.textContent = '0 檔案';
                fileLabel.textContent = '選擇圖片檔案...';
                return;
            }
            
            Array.from(this.files).forEach(file => {
                if (file.size > maxSize) {
                    alert(`檔案 ${file.name} 超過5MB大小限制`);
                    invalidFiles++;
                    return;
                }
                
                // 檢查檔案類型
                const fileExt = file.name.split('.').pop().toLowerCase();
                if (!['jpg', 'jpeg', 'png', 'gif'].includes(fileExt)) {
                    alert(`檔案 ${file.name} 格式不支援`);
                    invalidFiles++;
                    return;
                }
                
                validFiles++;
                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'mr-2 mb-2 position-relative';
                    div.style.width = '150px';
                    div.style.height = '150px';
                    
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'img-thumbnail';
                    img.style.width = '100%';
                    img.style.height = '100%';
                    img.style.objectFit = 'cover';
                    img.style.cursor = 'pointer';
                    
                    // 添加圖片點擊事件
                    img.onclick = function() {
                        document.getElementById('previewImage').src = e.target.result;
                        document.getElementById('imagePreviewModalTitle').textContent = file.name;
                        $('#imagePreviewModal').modal('show');
                    };
                    
                    div.appendChild(img);
                    
                    // 添加文件名顯示
                    const nameDiv = document.createElement('div');
                    nameDiv.className = 'small text-center mt-1 text-truncate';
                    nameDiv.title = file.name;
                    nameDiv.textContent = file.name;
                    div.appendChild(nameDiv);
                    
                    preview.appendChild(div);
                }
                reader.readAsDataURL(file);
            });
            
            // 更新計數器和標籤
            fileCounter.textContent = validFiles > 0 ? 
                `${validFiles} 個有效檔案` + (invalidFiles > 0 ? ` (${invalidFiles} 個無效)` : '') : 
                '0 檔案';
            
            fileLabel.textContent = validFiles > 1 ? 
                `已選擇 ${validFiles} 張圖片` : 
                (validFiles === 1 ? this.files[0].name : '選擇圖片檔案...');
        }
    });
    
    // 圖片預覽功能
    $(document).on('click', '.image-preview-container', function() {
        const imgSrc = $(this).data('img-src');
        const imgTitle = $(this).data('img-title');
        
        $('#imagePreviewModalTitle').text(imgTitle || '菜單圖片');
        $('#previewImage').attr('src', imgSrc);
        $('#imagePreviewModal').modal('show');
    });
    
    // 初始化 lightbox
    if (typeof lightbox !== 'undefined') {
        lightbox.option({
            'resizeDuration': 200,
            'wrapAround': true,
            'albumLabel': "圖片 %1 / %2"
        });
    }
});
</script>

<!-- 添加圖片預覽模態框 -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-labelledby="imagePreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imagePreviewModalTitle">圖片預覽</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <img id="previewImage" src="" class="img-fluid" alt="菜單預覽" style="max-width: 100%; max-height: 70vh;">
            </div>
        </div>
    </div>
</div>

<?php include_once dirname(__DIR__) . '/includes/footer.php'; ?>
```