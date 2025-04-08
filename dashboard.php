<?php
require_once __DIR__ . '/auth/functions.php';
requireLogin(); // 確保用戶已登入

// 獲取使用者創建的餐廳數量
$restaurantQuery = "SELECT COUNT(*) AS restaurant_count FROM restaurants WHERE created_by = ?";
$restaurantStmt = $conn->prepare($restaurantQuery);
$restaurantStmt->bind_param("i", $_SESSION['user_id']);
$restaurantStmt->execute();
$restaurantResult = $restaurantStmt->get_result();
$restaurantCount = $restaurantResult->fetch_assoc()['restaurant_count'];

// 獲取使用者創建的活動數量
$eventQuery = "SELECT COUNT(*) AS event_count FROM events WHERE created_by = ?";
$eventStmt = $conn->prepare($eventQuery);
$eventStmt->bind_param("i", $_SESSION['user_id']);
$eventStmt->execute();
$eventResult = $eventStmt->get_result();
$eventCount = $eventResult->fetch_assoc()['event_count'];

// 獲取最近的活動
$recentEventsQuery = "SELECT e.id, e.title, r.name AS restaurant_name, e.created_at, e.is_closed 
                    FROM events e 
                    JOIN restaurants r ON e.restaurant_id = r.id 
                    WHERE e.created_by = ? 
                    ORDER BY e.created_at DESC 
                    LIMIT 5";
$recentEventsStmt = $conn->prepare($recentEventsQuery);
$recentEventsStmt->bind_param("i", $_SESSION['user_id']);
$recentEventsStmt->execute();
$recentEvents = $recentEventsStmt->get_result();

include 'header.php';
?>

<div class="jumbotron">
    <h1 class="display-4">歡迎回來，<?php echo htmlspecialchars($_SESSION['user_name']); ?>！</h1>
    <p class="lead">使用 NumNumHub 管理您的團體點餐活動。</p>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card border-primary h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="m-0">統計資訊</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6 text-center">
                        <h2><?php echo $restaurantCount; ?></h2>
                        <p>餐廳</p>
                    </div>
                    <div class="col-6 text-center">
                        <h2><?php echo $eventCount; ?></h2>
                        <p>活動</p>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="restaurants.php" class="btn btn-primary btn-sm">管理餐廳</a>
                <a href="events.php" class="btn btn-success btn-sm">管理活動</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-8 mb-4">
        <div class="card border-success h-100">
            <div class="card-header bg-success text-white">
                <h5 class="m-0">最近活動</h5>
            </div>
            <div class="card-body">
                <?php if ($recentEvents->num_rows > 0): ?>
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>活動名稱</th>
                                <th>餐廳</th>
                                <th>建立時間</th>
                                <th>狀態</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($event = $recentEvents->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($event['title']); ?></td>
                                    <td><?php echo htmlspecialchars($event['restaurant_name']); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($event['created_at'])); ?></td>
                                    <td>
                                        <?php if ($event['is_closed']): ?>
                                            <span class="badge badge-secondary">已關閉</span>
                                        <?php else: ?>
                                            <span class="badge badge-success">進行中</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="event_detail.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-info">查看</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-center py-3">您尚未創建任何活動。</p>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <a href="create_event.php" class="btn btn-success">建立新活動</a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card border-info">
            <div class="card-header bg-info text-white">
                <h5 class="m-0">快速操作</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <a href="create_restaurant.php" class="btn btn-outline-primary btn-lg btn-block">
                            <i class="fa fa-plus-circle"></i> 新增餐廳
                        </a>
                    </div>
                    <div class="col-md-4 mb-3">
                        <a href="create_event.php" class="btn btn-outline-success btn-lg btn-block">
                            <i class="fa fa-calendar-plus"></i> 建立活動
                        </a>
                    </div>
                    <div class="col-md-4 mb-3">
                        <a href="join_event.php" class="btn btn-outline-info btn-lg btn-block">
                            <i class="fa fa-sign-in-alt"></i> 加入活動
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
