<?php
// AJAX 端點：獲取餐廳詳情
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../tags/functions.php';

// 確保用戶已登入
if (!isLoggedIn()) {
    echo '<div class="alert alert-danger">請先登入</div>';
    exit;
}

// 獲取餐廳ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 檢查是否有ID參數
if ($id <= 0) {
    echo '<div class="alert alert-danger">無效的餐廳ID</div>';
    exit;
}

// 檢查餐廳是否存在
$restaurant = getRestaurant($id);
if (!$restaurant) {
    echo '<div class="alert alert-danger">找不到指定的餐廳</div>';
    exit;
}

// 獲取餐廳標籤
$restaurantTags = getRestaurantTags($id);

// 檢查是否有使用此餐廳的活動
$eventCount = 0;
// TODO: 編寫獲取相關活動的函數
// $eventCount = getRestaurantEventCount($id);
?>

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
