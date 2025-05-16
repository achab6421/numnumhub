<?php
// 活動管理頁面
require_once 'includes/init.php';

// 確保用戶已登入
if (!isLoggedIn()) {
    redirect('login');
}

$user_id = $_SESSION['user_id'];

// 獲取用戶的活動
global $conn;

// 獲取我創建的活動
$created_sql = "SELECT e.*, r.name AS restaurant_name, 
                COUNT(ep.id) AS participant_count 
                FROM events e 
                LEFT JOIN restaurants r ON e.restaurant_id = r.id 
                LEFT JOIN event_participants ep ON e.id = ep.event_id 
                WHERE e.creator_id = ? 
                GROUP BY e.id 
                ORDER BY e.created_at DESC";
                
$created_stmt = $conn->prepare($created_sql);
$created_stmt->bind_param("i", $user_id);
$created_stmt->execute();
$created_result = $created_stmt->get_result();

// 獲取我參加的活動
$joined_sql = "SELECT e.*, r.name AS restaurant_name, 
              COUNT(ep.id) AS participant_count,
              (e.creator_id = ?) AS is_creator 
              FROM events e 
              LEFT JOIN restaurants r ON e.restaurant_id = r.id 
              JOIN event_participants ep ON e.id = ep.event_id 
              WHERE ep.user_id = ? AND e.creator_id != ? 
              GROUP BY e.id 
              ORDER BY e.created_at DESC";
              
$joined_stmt = $conn->prepare($joined_sql);
$joined_stmt->bind_param("iii", $user_id, $user_id, $user_id);
$joined_stmt->execute();
$joined_result = $joined_stmt->get_result();

// 頁面標題
$pageTitle = "活動管理";
include_once 'includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">活動管理</h1>
        <a href="<?php echo url('create-event'); ?>" class="btn btn-primary">
            <i class="fas fa-plus"></i> 建立新活動
        </a>
    </div>
    
    <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="created-tab" data-toggle="tab" href="#created" role="tab">
                我建立的活動 <span class="badge badge-primary"><?php echo $created_result->num_rows; ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="joined-tab" data-toggle="tab" href="#joined" role="tab">
                我參加的活動 <span class="badge badge-success"><?php echo $joined_result->num_rows; ?></span>
            </a>
        </li>
    </ul>
    
    <div class="tab-content" id="myTabContent">
        <!-- 我建立的活動 -->
        <div class="tab-pane fade show active" id="created" role="tabpanel">
            <?php if ($created_result->num_rows > 0): ?>
                <div class="list-group">
                    <?php while ($event = $created_result->fetch_assoc()): ?>
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1"><?php echo htmlspecialchars($event['title']); ?></h5>
                                <small>
                                    <?php echo date('Y-m-d H:i', strtotime($event['created_at'])); ?>
                                </small>
                            </div>
                            <p class="mb-1">
                                地點：<?php echo htmlspecialchars($event['restaurant_name']); ?> | 
                                參加人數：<?php echo (int)$event['participant_count']; ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <small>
                                    截止時間：<?php echo $event['deadline'] ? date('Y-m-d H:i', strtotime($event['deadline'])) : '無限期'; ?>
                                    <?php if ($event['is_closed']): ?>
                                        <span class="badge badge-danger">已關閉</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">進行中</span>
                                    <?php endif; ?>
                                </small>
                                <div class="btn-group">
                                    <a href="<?php echo url('order-system', ['id' => $event['id']]); ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-utensils"></i> 點餐系統
                                    </a>
                                    <?php if (!$event['is_closed'] && $event['creator_id'] == $user_id): ?>
                                        <a href="<?php echo url('edit-event', ['id' => $event['id']]); ?>" class="btn btn-outline-secondary btn-sm">
                                            <i class="fas fa-edit"></i> 編輯
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    您尚未建立任何活動。<a href="<?php echo url('create-event'); ?>">立即建立</a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- 我參加的活動 -->
        <div class="tab-pane fade" id="joined" role="tabpanel">
            <?php if ($joined_result->num_rows > 0): ?>
                <div class="list-group">
                    <?php while ($event = $joined_result->fetch_assoc()): ?>
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1"><?php echo htmlspecialchars($event['title']); ?></h5>
                                <small>
                                    <?php echo date('Y-m-d H:i', strtotime($event['created_at'])); ?>
                                </small>
                            </div>
                            <p class="mb-1">
                                地點：<?php echo htmlspecialchars($event['restaurant_name']); ?> | 
                                參加人數：<?php echo (int)$event['participant_count']; ?>
                            </p>
                            <small>
                                截止時間：<?php echo date('Y-m-d H:i', strtotime($event['deadline'])); ?>
                            </small>
                            <div>
                                <a href="<?php echo url('order-system', ['id' => $event['id']]); ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-utensils"></i> 進入點餐
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    您尚未參加任何其他人建立的活動。
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php 
$created_stmt->close();
$joined_stmt->close();
$conn->close();
include_once 'includes/footer.php'; 
?>
