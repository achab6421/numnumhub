<?php
// 餐廳列表頁面
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/functions.php';

// 確保用戶已登入
if (!isLoggedIn()) {
    redirect('login');
}

// 獲取當前用戶的餐廳列表
$restaurants = getRestaurants($_SESSION['user_id']);

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

<?php if (!empty($restaurants)): ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="thead-dark">
                <tr>
                    <th>餐廳名稱</th>
                    <th>地址</th>
                    <th>電話</th>
                    <th>店家連結</th>
                    <th>備註</th>
                    <th>建立時間</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($restaurants as $restaurant): ?>
                <tr>
                    <td><?php echo htmlspecialchars($restaurant['name']); ?></td>
                    <td><?php echo htmlspecialchars($restaurant['address'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($restaurant['phone'] ?? ''); ?></td>
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
                        <div class="btn-group btn-group-sm">
                            <a href="<?php echo url('restaurant', ['id' => $restaurant['id']]); ?>" class="btn btn-info" title="查看">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="<?php echo url('edit-restaurant', ['id' => $restaurant['id']]); ?>" class="btn btn-primary" title="編輯">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="<?php echo url('delete-restaurant', ['id' => $restaurant['id']]); ?>" class="btn btn-danger delete-restaurant" title="刪除" 
                               onclick="return confirm('確定要刪除 <?php echo htmlspecialchars($restaurant['name']); ?> 餐廳嗎？');">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> 您尚未新增任何餐廳，點擊上方「新增餐廳」按鈕開始建立。
    </div>
<?php endif; ?>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
