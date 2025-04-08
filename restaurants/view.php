<?php
// 餐廳詳情頁面
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/functions.php';

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

// 檢查是否有使用此餐廳的活動
$eventCount = 0;
// TODO: 編寫獲取相關活動的函數
// $eventCount = getRestaurantEventCount($id);

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
                <a href="<?php echo url('delete-restaurant', ['id' => $id]); ?>" class="btn btn-danger btn-sm" 
                   onclick="return confirm('確定要刪除此餐廳嗎？');">
                    <i class="fas fa-trash"></i> 刪除
                </a>
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

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
