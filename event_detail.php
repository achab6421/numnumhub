<?php
// 活動詳情頁面
require_once 'includes/init.php';
require_once 'restaurants/functions.php';

// 確保用戶已登入
if (!isLoggedIn()) {
    redirect('login');
}

// 獲取活動ID
$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($event_id <= 0) {
    setFlashMessage('error', '無效的活動ID');
    redirect('events');
}

$user_id = $_SESSION['user_id'];
global $conn;

// 獲取活動資料
$sql = "SELECT e.*, r.name AS restaurant_name, r.address, r.latitude, r.longitude, 
        u.username AS creator_name, 
        (e.creator_id = ?) AS is_creator,
        (SELECT COUNT(*) FROM event_participants WHERE event_id = e.id) AS participant_count,
        (SELECT COUNT(*) FROM event_participants WHERE event_id = e.id AND user_id = ?) AS is_participant
        FROM events e 
        JOIN restaurants r ON e.restaurant_id = r.id 
        JOIN users u ON e.creator_id = u.id 
        WHERE e.id = ?";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $user_id, $user_id, $event_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    setFlashMessage('error', '找不到該活動');
    redirect('events');
}

$event = $result->fetch_assoc();
$stmt->close();

// 獲取參與者列表
$participants_sql = "SELECT u.id, u.username, ep.joined_at 
                    FROM event_participants ep 
                    JOIN users u ON ep.user_id = u.id 
                    WHERE ep.event_id = ? 
                    ORDER BY ep.joined_at ASC";
                    
$participants_stmt = $conn->prepare($participants_sql);
$participants_stmt->bind_param("i", $event_id);
$participants_stmt->execute();
$participants_result = $participants_stmt->get_result();
$participants_stmt->close();

// 頁面標題
$pageTitle = $event['title'] . " - 活動詳情";
include_once 'includes/header.php';
?>

<!-- 引入 Leaflet CSS 和 JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>

<style>
    #map {
        height: 300px;
        border-radius: 5px;
    }
</style>

<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url('dashboard'); ?>">首頁</a></li>
            <li class="breadcrumb-item"><a href="<?php echo url('events'); ?>">活動管理</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($event['title']); ?></li>
        </ol>
    </nav>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="m-0">活動詳情</h5>
                    
                    <?php if ($event['is_creator']): ?>
                        <div>
                            <a href="<?php echo url('edit-event', ['id' => $event_id]); ?>" class="btn btn-sm btn-light">
                                <i class="fas fa-edit"></i> 編輯
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <h3 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                    
                    <div class="mb-4">
                        <span class="badge badge-primary">建立者: <?php echo htmlspecialchars($event['creator_name']); ?></span>
                        <span class="badge badge-info">參與人數: <?php echo (int)$event['participant_count']; ?></span>
                        <span class="badge badge-warning">截止時間: <?php echo date('Y-m-d H:i', strtotime($event['deadline'])); ?></span>
                    </div>
                    
                    <?php if (!empty($event['description'])): ?>
                        <div class="card mb-4">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">活動描述</h6>
                                <p class="card-text"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="card mb-4">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">餐廳資訊</h6>
                            <h5><?php echo htmlspecialchars($event['restaurant_name']); ?></h5>
                            <p class="mb-0"><?php echo htmlspecialchars($event['address']); ?></p>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <div id="map"></div>
                    </div>
                    
                    <?php if (!$event['is_participant'] && !$event['is_creator']): ?>
                        <div class="text-center">
                            <form action="<?php echo url('join'); ?>" method="post">
                                <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-plus-circle"></i> 加入活動
                                </button>
                            </form>
                        </div>
                    <?php elseif (!$event['is_creator']): ?>
                        <div class="text-center">
                            <form action="<?php echo url('leave'); ?>" method="post" onsubmit="return confirm('確定要退出此活動嗎？');">
                                <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                                <button type="submit" class="btn btn-outline-danger">
                                    <i class="fas fa-sign-out-alt"></i> 退出活動
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-header bg-info text-white">
                    <h5 class="m-0">參與者名單</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php if ($participants_result->num_rows > 0): ?>
                            <?php while ($participant = $participants_result->fetch_assoc()): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <?php echo htmlspecialchars($participant['username']); ?>
                                        <?php if ($participant['id'] == $event['creator_id']): ?>
                                            <span class="badge badge-primary">建立者</span>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo date('m/d H:i', strtotime($participant['joined_at'])); ?>
                                    </small>
                                </li>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <li class="list-group-item text-center text-muted">暫無參與者</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 初始化地圖
    const map = L.map('map');
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
    
    // 設置餐廳位置
    const lat = <?php echo $event['latitude'] ?: 25.033; ?>;
    const lng = <?php echo $event['longitude'] ?: 121.565; ?>;
    
    if (lat && lng) {
        map.setView([lat, lng], 16);
        
        // 添加標記
        const marker = L.marker([lat, lng]).addTo(map);
        marker.bindPopup("<strong><?php echo htmlspecialchars($event['restaurant_name']); ?></strong><br><?php echo htmlspecialchars($event['address']); ?>").openPopup();
    } else {
        map.setView([25.033, 121.565], 13); // 預設顯示台北市
    }
});
</script>

<?php 
$conn->close();
include_once 'includes/footer.php'; 
?>
