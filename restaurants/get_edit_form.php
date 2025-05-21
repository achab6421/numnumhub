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

// 獲取餐廳的菜單圖片
$menu_images = getRestaurantImages($id);

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
    
    <!-- 新增圖片上傳區域 -->
    <div class="form-group">
        <label for="menu_images">餐廳菜單圖片</label>
        <div class="custom-file">
            <input type="file" class="custom-file-input" id="menu_images" name="menu_images[]" accept="image/*" multiple>
            <label class="custom-file-label" for="menu_images">選擇圖片檔案...</label>
        </div>
        <small class="form-text text-muted">
            您可以選擇多個圖片檔案一次上傳。支援的格式: JPG, JPEG, PNG, GIF
        </small>
    </div>
    
    <!-- 圖片預覽區域 -->
    <div id="image_preview" class="d-flex flex-wrap mt-2 mb-3">
        <!-- 預覽圖片將顯示在這裡 -->
    </div>
    
    <!-- 顯示現有的餐廳菜單圖片 -->
    <?php if (!empty($menu_images)): ?>
    <div class="form-group">
        <label>現有菜單圖片</label>
        <div class="row" id="existing_images">
            <?php foreach ($menu_images as $image): ?>
                <div class="col-md-4 col-sm-6 mb-3" data-image-id="<?php echo $image['id']; ?>">
                    <div class="card h-100">
                        <!-- 修改圖片點擊方式，使其能夠在modal內正常工作 -->
                        <div class="image-preview-container">
                            <img src="/<?php echo htmlspecialchars($image['image_path']); ?>" 
                                 class="card-img-top preview-image" alt="菜單圖片" 
                                 data-img-src="/<?php echo htmlspecialchars($image['image_path']); ?>"
                                 data-img-title="<?php echo htmlspecialchars($image['description'] ?? '菜單圖片'); ?>"
                                 style="height: 150px; object-fit: cover;">
                            <div class="image-overlay">
                                <i class="fas fa-search-plus"></i>
                            </div>
                        </div>
                        <div class="card-body p-2 text-center">
                            <small class="text-muted d-block mb-2">上傳於: <?php echo date('Y-m-d H:i', strtotime($image['created_at'])); ?></small>
                            <button type="button" class="btn btn-sm btn-danger delete-image" data-image-id="<?php echo $image['id']; ?>">
                                <i class="fas fa-trash"></i> 刪除
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> 此餐廳尚未上傳任何菜單圖片
    </div>
    <?php endif; ?>
</form>

<!-- 修改預覽模態框 -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1" role="dialog" aria-labelledby="imagePreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imagePreviewModalTitle">圖片預覽</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <img id="previewImage" src="" alt="圖片預覽" style="max-width: 100%; max-height: 70vh;">
            </div>
        </div>
    </div>
</div>

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
.tag-row-hidden {
    display: none;
}

/* 圖片預覽相關樣式 */
.image-preview-container {
    position: relative;
    cursor: pointer;
    overflow: hidden;
}

.image-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    opacity: 0;
    transition: opacity 0.3s;
}

.image-overlay i {
    color: white;
    font-size: 2rem;
}

.image-preview-container:hover .image-overlay {
    opacity: 1;
}
</style>

<script>
// 圖片預覽函數
function previewImage(src, title) {
    console.log('Preview Image:', src, title); // 調試日誌
    const previewModal = document.getElementById('imagePreviewModal');
    const previewImage = document.getElementById('previewImage');
    const previewTitle = document.getElementById('imagePreviewModalTitle');
    
    // 設置模態框內容
    previewImage.src = src;
    previewTitle.textContent = title || '圖片預覽';
    
    // 使用 jQuery 顯示模態框
    $('#imagePreviewModal').modal('show');
}

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
    
    // 標籤展開/收起功能
    const showMoreBtn = document.getElementById('showMoreTags');
    const showLessBtn = document.getElementById('showLessTags');
    const hiddenRows = document.querySelectorAll('.tag-row-hidden');
    
    if (showMoreBtn) {
        showMoreBtn.addEventListener('click', function() {
            hiddenRows.forEach(row => row.style.display = 'flex');
            showMoreBtn.classList.add('d-none');
            showLessBtn.classList.remove('d-none');
        });
    }
    
    if (showLessBtn) {
        showLessBtn.addEventListener('click', function() {
            hiddenRows.forEach(row => row.style.display = 'none');
            showLessBtn.classList.add('d-none');
            showMoreBtn.classList.remove('d-none');
        });
    }
    
    // 文件上傳預覽
    document.getElementById('menu_images').addEventListener('change', function(e) {
        const preview = document.getElementById('image_preview');
        preview.innerHTML = '';
        
        if (this.files) {
            const maxFiles = 10; // 最大文件數量
            const maxSize = 5 * 1024 * 1024; // 5MB，最大檔案大小
            
            if (this.files.length > maxFiles) {
                alert(`最多只能上傳 ${maxFiles} 張圖片`);
                this.value = '';
                return;
            }
            
            Array.from(this.files).forEach(file => {
                if (file.size > maxSize) {
                    alert(`檔案 ${file.name} 超過 5MB 大小限制`);
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'mr-2 mb-2 position-relative';
                    div.style.width = '120px';
                    div.style.height = '120px';
                    
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'img-thumbnail';
                    img.style.width = '100%';
                    img.style.height = '100%';
                    img.style.objectFit = 'cover';
                    img.style.cursor = 'pointer';
                    
                    // 添加圖片點擊事件
                    img.onclick = function() {
                        previewImage(this.src, file.name);
                    };
                    
                    div.appendChild(img);
                    preview.appendChild(div);
                }
                reader.readAsDataURL(file);
            });
            
            // 更新 custom file label
            const label = this.nextElementSibling;
            label.textContent = this.files.length > 1 ? 
                `已選擇 ${this.files.length} 張圖片` : 
                this.files[0].name;
        }
    });
    
    // 為所有圖片預覽容器綁定點擊事件（直接使用事件委託方式）
    document.addEventListener('click', function(event) {
        const container = event.target.closest('.image-preview-container');
        if (container) {
            const src = container.getAttribute('data-img-src');
            const title = container.getAttribute('data-img-title');
            if (src) {
                previewImage(src, title);
                event.preventDefault();
            }
        }
    });
    
    // 確保模態框正確初始化
    try {
        if(typeof $.fn.modal === 'function') {
            $('#imagePreviewModal').modal({
                show: false
            });
        } else {
            console.error('Bootstrap modal plugin not available');
        }
    } catch (e) {
        console.error('Error initializing modal:', e);
    }
    
    // 刪除圖片功能
    document.querySelectorAll('.delete-image').forEach(button => {
        button.addEventListener('click', function() {
            if(confirm('確定要刪除此圖片嗎？')) {
                const imageId = this.getAttribute('data-image-id');
                const restaurantId = <?php echo $id; ?>;
                
                // 發送 AJAX 請求刪除圖片
                fetch('/index.php?route=api-delete-restaurant-image', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `image_id=${imageId}&restaurant_id=${restaurantId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // 從 DOM 中移除圖片
                        document.querySelector(`#existing_images div[data-image-id="${imageId}"]`).remove();
                        
                        // 如果沒有圖片了，顯示提示
                        if (document.querySelectorAll('#existing_images div[data-image-id]').length === 0) {
                            document.getElementById('existing_images').innerHTML = `
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i> 此餐廳已無任何菜單圖片
                                    </div>
                                </div>
                            `;
                        }
                        
                        // 顯示成功訊息
                        const alertDiv = document.createElement('div');
                        alertDiv.className = 'alert alert-success alert-dismissible fade show mt-2';
                        alertDiv.innerHTML = `
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            圖片已成功刪除
                        `;
                        document.getElementById('existing_images').parentNode.insertBefore(alertDiv, document.getElementById('existing_images'));
                        
                        // 自動關閉提示
                        setTimeout(() => {
                            alertDiv.classList.remove('show');
                            setTimeout(() => alertDiv.remove(), 300);
                        }, 3000);
                    } else {
                        alert('刪除圖片失敗：' + (data.message || '未知錯誤'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('刪除圖片時發生錯誤');
                });
            }
        });
    });
});
</script>

<!-- 確保在bootstrap模態窗口內部正確運作的臨時解決方案 -->
<script>
// 使用jQuery處理模態窗口內的預覽功能
$(document).ready(function() {
    // 確保Bootstrap模態框已經初始化
    if ($('#imagePreviewModal').length) {
        $('#imagePreviewModal').modal({show: false});
    }
    
    // 直接綁定事件到圖片，不管它是在哪裡
    $(document).on('click', '.preview-image', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const src = $(this).data('img-src');
        const title = $(this).data('img-title') || '圖片預覽';
        
        $('#imagePreviewModalTitle').text(title);
        $('#previewImage').attr('src', src);
        $('#imagePreviewModal').modal('show');
    });
});
</script>

<!-- 確保 jQuery 和 Bootstrap JS 被載入 -->
<script>
// 檢查是否已載入jQuery和Bootstrap
document.addEventListener('DOMContentLoaded', function() {
    if (typeof jQuery === 'undefined') {
        console.error('jQuery is not loaded!');
    } else {
        console.log('jQuery is loaded, version: ' + jQuery.fn.jquery);
    }
    
    if (typeof jQuery.fn.modal === 'undefined') {
        console.error('Bootstrap modal is not loaded!');
    } else {
        console.log('Bootstrap modal is loaded');
    }
});
</script>
