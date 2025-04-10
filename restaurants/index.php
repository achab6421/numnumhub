<?php
// 餐廳列表頁面
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../tags/functions.php';

// 確保用戶已登入
if (!isLoggedIn()) {
    redirect('login');
}

// 檢查餐廳資料表結構
$checkQuery = "SHOW COLUMNS FROM restaurants LIKE 'created_by'";
$result = $conn->query($checkQuery);

// 如果餐廳資料表尚未更新，顯示提示
if ($result->num_rows === 0) {
    setFlashMessage('餐廳資料表需要更新，請點擊<a href="' . url('update_restaurant_table') . '">這裡</a>進行更新', 'warning');
}

// 獲取所有標籤以用於篩選
$allTags = getAllTags();
$filterTag = isset($_GET['tag']) ? (int)$_GET['tag'] : 0;

// 獲取當前用戶的所有餐廳
try {
    // 根據是否有created_by欄位決定如何獲取餐廳
    if ($result->num_rows > 0) {
        $restaurants = getRestaurants($_SESSION['user_id']);
    } else {
        $restaurants = getRestaurants();
    }
    
    // 獲取所有餐廳IDs
    $restaurantIds = array_column($restaurants, 'id');
    
    // 一次性獲取所有餐廳的標籤關係
    $allRestaurantTags = getAllRestaurantTags($restaurantIds);
    
    // 將標籤關聯到對應的餐廳
    $restaurantsWithTags = [];
    foreach ($restaurants as $restaurant) {
        // 設置餐廳的標籤，如果沒有標籤則為空數組
        $restaurant['tags'] = isset($allRestaurantTags[$restaurant['id']]) 
            ? $allRestaurantTags[$restaurant['id']] 
            : [];
        
        // 如果有標籤篩選，檢查餐廳是否有指定標籤
        if ($filterTag > 0) {
            $hasTag = false;
            foreach ($restaurant['tags'] as $tag) {
                if ($tag['id'] == $filterTag) {
                    $hasTag = true;
                    break;
                }
            }
            // 只添加包含指定標籤的餐廳
            if ($hasTag) {
                $restaurantsWithTags[] = $restaurant;
            }
        } else {
            // 無篩選時添加所有餐廳
            $restaurantsWithTags[] = $restaurant;
        }
    }
    
    // 更新餐廳列表為處理後的版本
    $restaurants = $restaurantsWithTags;
    
    // 只在頁面底部顯示調試信息，避免干擾表格顯示
    // debug($restaurants);
} catch (Exception $e) {
    setFlashMessage('獲取餐廳資料時發生錯誤: ' . $e->getMessage(), 'danger');
    $restaurants = [];
}

// 設置頁面標題
$pageTitle = "餐廳管理";
include_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">餐廳管理</h1>
    <a href="<?php echo url('create-restaurant'); ?>" class="btn btn-primary">
        <i class="fas fa-plus"></i> 新增餐廳
    </a>
</div>

<!-- 標籤篩選 -->
<?php if (!empty($allTags)): ?>
<div class="card mb-4">
    <div class="card-body">
        <h5 class="mb-3">依標籤篩選</h5>
        <div class="d-flex flex-wrap">
            <a href="<?php echo url('restaurants'); ?>" class="btn <?php echo $filterTag === 0 ? 'btn-primary' : 'btn-outline-primary'; ?> mr-2 mb-2">
                全部
            </a>
            <?php foreach($allTags as $tag): ?>
            <a href="<?php echo url('restaurants', ['tag' => $tag['id']]); ?>" class="btn <?php echo $filterTag === $tag['id'] ? 'btn-primary' : 'btn-outline-primary'; ?> mr-2 mb-2">
                <?php echo htmlspecialchars($tag['name']); ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($restaurants)): ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="thead-dark">
                <tr>
                    <th>餐廳名稱</th>
                    <th>地址</th>
                    <th>電話</th>
                    <th>標籤</th>
                    <th>店家連結</th>
                    <th>備註</th>
                    <th>建立時間</th>
                    <th style="min-width: 120px;">操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($restaurants as $restaurant): ?>
                <tr>
                    <td><?php echo htmlspecialchars($restaurant['name']); ?></td>
                    <td><?php echo htmlspecialchars($restaurant['address'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($restaurant['phone'] ?? ''); ?></td>
                    <td>
                        <?php if (!empty($restaurant['tags'])): ?>
                            <?php foreach($restaurant['tags'] as $tag): ?>
                            <span class="badge badge-info mr-1">
                                <?php echo htmlspecialchars($tag['name']); ?>
                            </span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($restaurant['link'])): ?>
                            <a href="<?php echo htmlspecialchars($restaurant['link']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($restaurant['note'])): ?>
                        <span class="text-truncate d-inline-block" style="max-width: 150px;" title="<?php echo htmlspecialchars($restaurant['note']); ?>">
                            <?php echo htmlspecialchars($restaurant['note']); ?>
                        </span>
                        <?php else: ?>
                        <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo date('Y-m-d', strtotime($restaurant['created_at'])); ?></td>
                    <td>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-info mr-1 view-restaurant-btn" 
                                    data-id="<?php echo $restaurant['id']; ?>" 
                                    data-name="<?php echo htmlspecialchars($restaurant['name']); ?>"
                                    title="查看">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-primary mr-1 edit-restaurant-btn" 
                                    data-id="<?php echo $restaurant['id']; ?>" 
                                    data-name="<?php echo htmlspecialchars($restaurant['name']); ?>"
                                    title="編輯">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger delete-restaurant-btn" 
                                    data-id="<?php echo $restaurant['id']; ?>" 
                                    data-name="<?php echo htmlspecialchars($restaurant['name']); ?>"
                                    title="刪除">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="alert alert-info">
        <?php if ($filterTag > 0): ?>
            <i class="fas fa-info-circle"></i> 沒有找到符合此標籤的餐廳。
        <?php else: ?>
            <i class="fas fa-info-circle"></i> 您尚未新增任何餐廳，點擊上方「新增餐廳」按鈕開始建立。
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- 添加自訂 JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 所有編輯按鈕的點擊事件
    const editButtons = document.querySelectorAll('.edit-restaurant-btn');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const restaurantId = this.getAttribute('data-id');
            const restaurantName = this.getAttribute('data-name');
            editRestaurant(restaurantId, restaurantName);
        });
    });
    
    // 所有刪除按鈕的點擊事件
    const deleteButtons = document.querySelectorAll('.delete-restaurant-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const restaurantId = this.getAttribute('data-id');
            const restaurantName = this.getAttribute('data-name');
            deleteRestaurant(restaurantId, restaurantName);
        });
    });
    
    // 所有查看按鈕的點擊事件
    const viewButtons = document.querySelectorAll('.view-restaurant-btn');
    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const restaurantId = this.getAttribute('data-id');
            const restaurantName = this.getAttribute('data-name');
            viewRestaurantSwal(restaurantId, restaurantName);
        });
    });
    
    // 使用 SweetAlert 編輯餐廳
    function editRestaurant(restaurantId, restaurantName) {
        // 首先顯示載入中
        Swal.fire({
            title: '載入中...',
            html: `正在載入 ${restaurantName} 的資料`,
            didOpen: () => {
                Swal.showLoading();
            },
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false
        });
        
        // 通過 AJAX 獲取餐廳資料
        fetch(`<?php echo url('restaurants'); ?>/get_edit_form.php?id=${restaurantId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('無法載入餐廳資料');
                }
                return response.text();
            })
            .then(html => {
                // 顯示編輯表單
                Swal.fire({
                    title: `編輯餐廳 - ${restaurantName}`,
                    html: html,
                    width: '800px',
                    showCancelButton: true,
                    confirmButtonText: '保存',
                    cancelButtonText: '取消',
                    showCloseButton: true,
                    customClass: {
                        container: 'swal-restaurant-container',
                        popup: 'swal-restaurant-popup',
                        content: 'swal-restaurant-content'
                    },
                    preConfirm: () => {
                        // 獲取表單中的數據
                        const form = Swal.getPopup().querySelector('form');
                        const formData = new FormData(form);
                        
                        // 發送表單數據
                        return fetch('<?php echo url('restaurants'); ?>/update_restaurant_ajax.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (!data.success) {
                                throw new Error(data.message || '更新失敗');
                            }
                            return data;
                        })
                        .catch(error => {
                            Swal.showValidationMessage(`請求失敗: ${error.message}`);
                        });
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: '成功!',
                            text: '餐廳資料已更新',
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            // 重新載入頁面顯示更新後的數據
                            location.reload();
                        });
                    }
                });
            })
            .catch(error => {
                Swal.fire({
                    title: '錯誤',
                    text: `載入失敗: ${error.message}`,
                    icon: 'error'
                });
            });
    }
    
    // 使用 SweetAlert 查看餐廳
    function viewRestaurantSwal(restaurantId, restaurantName) {
        // 顯示載入中
        Swal.fire({
            title: '載入中...',
            html: `正在載入 ${restaurantName} 的資料`,
            didOpen: () => {
                Swal.showLoading();
            },
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false
        });
        
        // 通過 AJAX 獲取餐廳資料
        fetch(`<?php echo url('restaurants'); ?>/get_view_data.php?id=${restaurantId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('無法載入餐廳資料');
                }
                return response.text();
            })
            .then(html => {
                // 顯示餐廳詳情
                Swal.fire({
                    title: restaurantName,
                    html: html,
                    width: '800px',
                    showCloseButton: true,
                    showCancelButton: false,
                    confirmButtonText: '關閉',
                    showDenyButton: true,
                    denyButtonText: '編輯',
                    showConfirmButton: true,
                    focusConfirm: false,
                    customClass: {
                        container: 'swal-restaurant-container',
                        popup: 'swal-restaurant-popup',
                        content: 'swal-restaurant-content'
                    }
                }).then((result) => {
                    if (result.isDenied) {
                        // 如果點擊編輯按鈕，打開編輯模態窗
                        editRestaurant(restaurantId, restaurantName);
                    }
                });
            })
            .catch(error => {
                Swal.fire({
                    title: '錯誤',
                    text: `載入失敗: ${error.message}`,
                    icon: 'error'
                });
            });
    }
    
    // 使用 SweetAlert 刪除餐廳
    function deleteRestaurant(restaurantId, restaurantName) {
        Swal.fire({
            title: '確定要刪除此餐廳嗎?',
            text: `您將刪除 "${restaurantName}"，此操作無法復原！`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: '是的，刪除它!',
            cancelButtonText: '取消'
        }).then((result) => {
            if (result.isConfirmed) {
                // 跳轉到刪除處理頁面
                window.location.href = `<?php echo url('delete-restaurant'); ?>?id=${restaurantId}`;
            }
        });
    }
});
</script>

<!-- 專門用於 SweetAlert 的樣式 -->
<style>
.swal-restaurant-popup {
    max-width: 800px;
}
.swal-restaurant-content {
    padding: 0 20px;
    overflow-y: auto;
    max-height: 70vh;
}
.swal-restaurant-container {
    z-index: 1060; /* 確保在其他元素之上 */
}
</style>

<!-- 調試信息區 -->
<?php if (isset($_GET['debug']) && $_GET['debug'] === '1'): ?>
<div class="card mt-4">
    <div class="card-header">調試信息</div>
    <div class="card-body"></div>
        <pre><?php print_r($restaurants); ?></pre>
    </div>
</div>
<?php endif; ?>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
