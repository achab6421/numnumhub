<?php
// 為餐廳表添加經緯度欄位
require_once 'includes/init.php';

// 確保用戶已登入且是管理員
if (!isLoggedIn()) {
    setFlashMessage('請先登入系統', 'danger');
    redirect('login');
}

// 檢查資料表結構
$checkLat = "SHOW COLUMNS FROM restaurants LIKE 'lat'";
$checkLng = "SHOW COLUMNS FROM restaurants LIKE 'lng'";
$resultLat = $conn->query($checkLat);
$resultLng = $conn->query($checkLng);

// 添加需要的欄位
$updatesNeeded = [];
$updatesSuccess = [];
$updatesFailed = [];

// 如果 lat 欄位不存在，添加它
if ($resultLat->num_rows === 0) {
    $updatesNeeded[] = "lat (緯度)";
    $alterLat = "ALTER TABLE restaurants ADD COLUMN lat DOUBLE DEFAULT NULL AFTER note";
    
    if ($conn->query($alterLat) === TRUE) {
        $updatesSuccess[] = "餐廳資料表已添加 lat 欄位";
    } else {
        $updatesFailed[] = "無法添加 lat 欄位：" . $conn->error;
    }
}

// 如果 lng 欄位不存在，添加它
if ($resultLng->num_rows === 0) {
    $updatesNeeded[] = "lng (經度)";
    $alterLng = "ALTER TABLE restaurants ADD COLUMN lng DOUBLE DEFAULT NULL AFTER lat";
    
    if ($conn->query($alterLng) === TRUE) {
        $updatesSuccess[] = "餐廳資料表已添加 lng 欄位";
    } else {
        $updatesFailed[] = "無法添加 lng 欄位：" . $conn->error;
    }
}

// 設置頁面標題
$pageTitle = "更新餐廳資料表";
include_once 'includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="m-0">餐廳資料表更新</h4>
                </div>
                <div class="card-body">
                    <?php if (empty($updatesNeeded)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> 餐廳資料表結構已是最新，不需要更新。
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 餐廳資料表需要添加以下欄位：<?php echo implode(', ', $updatesNeeded); ?>
                        </div>
                        
                        <?php if (!empty($updatesSuccess)): ?>
                            <div class="alert alert-success">
                                <h5>成功更新：</h5>
                                <ul>
                                    <?php foreach ($updatesSuccess as $success): ?>
                                        <li><?php echo $success; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($updatesFailed)): ?>
                            <div class="alert alert-danger">
                                <h5>更新失敗：</h5>
                                <ul>
                                    <?php foreach ($updatesFailed as $failed): ?>
                                        <li><?php echo $failed; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <div class="mt-4">
                        <a href="<?php echo url('restaurants'); ?>" class="btn btn-primary">返回餐廳管理</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
