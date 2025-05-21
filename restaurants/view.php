<?php
// 餐廳詳情頁面
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../tags/functions.php';

// 確保用戶已登入
if (!isLoggedIn()) {
    redirect('login');
}

// 獲取餐廳ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 檢查是否有ID參數
if ($id <= 0) {
    setFlashMessage('無效的餐廳ID', 'danger');
    redirect('restaurants');
}

// 檢查餐廳是否存在
$restaurant = getRestaurant($id);
if (!$restaurant) {
    setFlashMessage('找不到指定的餐廳', 'danger');
    redirect('restaurants');
}

// 檢查用戶是否有權限查看此餐廳 - 放寬權限，允許查看所有餐廳
// 若需限制只能查看自己的餐廳，則取消下方註解
/*
if (!canManageRestaurant($id, $_SESSION['user_id'])) {
    setFlashMessage('您沒有權限查看此餐廳', 'danger');
    redirect('restaurants');
}
*/

// 檢查是否有使用此餐廳的活動
$eventCount = 0;
// TODO: 編寫獲取相關活動的函數
// $eventCount = getRestaurantEventCount($id);

// 獲取餐廳標籤
$restaurantTags = getRestaurantTags($id);

// 獲取餐廳菜單圖片
$menu_images = getRestaurantImages($id);

// 設置頁面標題
$pageTitle = htmlspecialchars($restaurant['name']);
include_once __DIR__ . '/../includes/header.php';
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url('restaurants'); ?>">餐廳管理</a></li>
        <li class="breadcrumb-item active" aria-current="page">餐廳詳情</li>
    </ol>
</nav>

<div class="card shadow mb-4">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h4 class="m-0"><?php echo htmlspecialchars($restaurant['name']); ?></h4>
        <div>
            <?php if (canManageRestaurant($id, $_SESSION['user_id'])): ?>
                <a href="<?php echo url('edit-restaurant', ['id' => $id]); ?>" class="btn btn-light btn-sm">
                    <i class="fas fa-edit"></i> 編輯
                </a>
                <button type="button" class="btn btn-danger btn-sm delete-restaurant-btn" 
                        data-id="<?php echo $id; ?>" 
                        data-name="<?php echo htmlspecialchars($restaurant['name']); ?>">
                    <i class="fas fa-trash"></i> 刪除
                </button>
            <?php endif; ?>
            <a href="<?php echo url('create-event', ['restaurant_id' => $id]); ?>" class="btn btn-success btn-sm">
                <i class="fas fa-plus"></i> 建立活動
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h5>基本資訊</h5>
                <table class="table table-bordered">
                    <tr>
                        <th style="width: 30%">餐廳名稱</th>
                        <td><?php echo htmlspecialchars($restaurant['name']); ?></td>
                    </tr>
                    <tr>
                        <th>地址</th>
                        <td>
                            <?php if (!empty($restaurant['address'])): ?>
                                <?php echo htmlspecialchars($restaurant['address']); ?>
                                <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($restaurant['address']); ?>" target="_blank" class="btn btn-sm btn-outline-secondary ml-2">
                                    <i class="fas fa-map-marker-alt"></i> 地圖
                                </a>
                            <?php else: ?>
                                <span class="text-muted">未提供</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>電話</th>
                        <td>
                            <?php if (!empty($restaurant['phone'])): ?>
                                <?php echo htmlspecialchars($restaurant['phone']); ?>
                                <a href="tel:<?php echo htmlspecialchars($restaurant['phone']); ?>" class="btn btn-sm btn-outline-secondary ml-2">
                                    <i class="fas fa-phone"></i> 撥打
                                </a>
                            <?php else: ?>
                                <span class="text-muted">未提供</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>店家連結</th>
                        <td>
                            <?php if (!empty($restaurant['link'])): ?>
                                <a href="<?php echo htmlspecialchars($restaurant['link']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-external-link-alt"></i> <?php echo htmlspecialchars($restaurant['link']); ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">未提供</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>標籤</th>
                        <td>
                            <?php if (!empty($restaurantTags)): ?>
                                <?php foreach($restaurantTags as $tag): ?>
                                <span class="badge badge-info mr-1 p-2">
                                    <?php echo htmlspecialchars($tag['name']); ?>
                                </span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="text-muted">未設定標籤</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>建立時間</th>
                        <td><?php echo date('Y-m-d H:i', strtotime($restaurant['created_at'])); ?></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h5>備註</h5>
                <div class="card bg-light">
                    <div class="card-body">
                        <?php if (!empty($restaurant['note'])): ?>
                            <pre class="mb-0" style="white-space: pre-wrap;"><?php echo htmlspecialchars($restaurant['note']); ?></pre>
                        <?php else: ?>
                            <p class="text-muted mb-0">沒有備註資訊</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <h5 class="mt-4">相關活動</h5>
                <?php if ($eventCount > 0): ?>
                    <p>此餐廳已被用於 <?php echo $eventCount; ?> 個活動中</p>
                    <a href="<?php echo url('events', ['restaurant_id' => $id]); ?>" class="btn btn-info btn-sm">
                        <i class="fas fa-list"></i> 查看相關活動
                    </a>
                <?php else: ?>
                    <p class="text-muted">尚未有使用此餐廳的活動</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- 菜單圖片區塊 -->
<?php 
$menu_images = getRestaurantImages($id);
if (!empty($menu_images)): 
?>
<div class="card mb-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="fas fa-images"></i> 菜單圖片</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <?php foreach ($menu_images as $image): ?>
            <div class="col-md-4 col-sm-6 mb-3">
                <div class="card h-100">
                    <div class="image-preview-container" 
                         data-img-src="/<?php echo htmlspecialchars($image['image_path']); ?>"
                         data-img-title="菜單圖片">
                        <img src="/<?php echo htmlspecialchars($image['image_path']); ?>" 
                             class="card-img-top" alt="菜單圖片" 
                             style="height: 200px; object-fit: cover;">
                        <div class="image-overlay">
                            <i class="fas fa-search-plus"></i>
                        </div>
                    </div>
                    <?php if (canManageRestaurant($id, $_SESSION['user_id'])): ?>
                    <div class="card-footer p-2 text-center">
                        <small class="text-muted">上傳於 <?php echo date('Y-m-d', strtotime($image['created_at'])); ?></small>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- 圖片預覽模態框 -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-labelledby="imagePreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imagePreviewModalTitle">菜單圖片</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <img id="previewImage" src="" class="img-fluid" alt="菜單圖片預覽" style="max-width: 100%; max-height: 80vh;">
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- 上傳圖片模態窗口 -->
<?php if ($restaurant['created_by'] == $_SESSION['user_id']): ?>
<div class="modal fade" id="uploadImagesModal" tabindex="-1" role="dialog" aria-labelledby="uploadImagesModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadImagesModalLabel">上傳菜單圖片</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="<?php echo url('upload-restaurant-images'); ?>" method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="restaurant_id" value="<?php echo $restaurant_id; ?>">
                    
                    <div class="form-group">
                        <label for="menu_images_upload">選擇菜單圖片 (可多選)</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="menu_images_upload" name="menu_images[]" accept="image/*" multiple required>
                            <label class="custom-file-label" for="menu_images_upload">選擇圖片檔案...</label>
                        </div>
                        <small class="form-text text-muted">
                            您可以選擇多個圖片檔案一次上傳。支援的格式: JPG, JPEG, PNG, GIF
                        </small>
                    </div>
                    
                    <div id="image_preview_modal" class="d-flex flex-wrap mt-3">
                        <!-- 預覽圖片將顯示在這裡 -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">上傳圖片</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 模態框中的文件上傳預覽
    document.getElementById('menu_images_upload')?.addEventListener('change', function(e) {
        const preview = document.getElementById('image_preview_modal');
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
                    div.style.width = '100px';
                    div.style.height = '100px';
                    
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'img-thumbnail';
                    img.style.width = '100%';
                    img.style.height = '100%';
                    img.style.objectFit = 'cover';
                    
                    div.appendChild(img);
                    preview.appendChild(div);
                }
                reader.readAsDataURL(file);
            });
            
            // 更新 custom file label 顯示選擇的檔案數量
            const label = this.nextElementSibling;
            label.textContent = this.files.length > 1 ? 
                `已選擇 ${this.files.length} 張圖片` : 
                this.files[0].name;
        }
    });
    
    // 初始化 lightbox
    lightbox.option({
        'resizeDuration': 200,
        'wrapAround': true,
        'albumLabel': "圖片 %1 / %2"
    });
    
    // 圖片預覽功能
    document.querySelectorAll('.image-preview-container').forEach(function(container) {
        container.addEventListener('click', function() {
            const imgSrc = this.getAttribute('data-img-src');
            const imgTitle = this.getAttribute('data-img-title') || '菜單圖片';
            
            document.getElementById('imagePreviewModalTitle').textContent = imgTitle;
            document.getElementById('previewImage').src = imgSrc;
            
            $('#imagePreviewModal').modal('show');
        });
    });
});
</script>

<style>
.image-preview-container {
    position: relative;
    cursor: pointer;
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

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
