<?php
// 使用正確的初始化文件
require_once 'includes/init.php';

// 確保用戶已登入
if (!isLoggedIn()) {
    redirect('login');
}

// 獲取使用者創建的餐廳數量
$restaurantQuery = "SELECT COUNT(*) AS restaurant_count FROM restaurants WHERE created_by = ?";
$restaurantStmt = $conn->prepare($restaurantQuery);
$restaurantStmt->bind_param("i", $_SESSION['user_id']);
$restaurantStmt->execute();
$restaurantResult = $restaurantStmt->get_result();
$restaurantCount = $restaurantResult->fetch_assoc()['restaurant_count'];

// 獲取使用者創建的活動數量
$eventQuery = "SELECT COUNT(*) AS event_count FROM events WHERE creator_id = ?";
$eventStmt = $conn->prepare($eventQuery);
$eventStmt->bind_param("i", $_SESSION['user_id']);
$eventStmt->execute();
$eventResult = $eventStmt->get_result();
$eventCount = $eventResult->fetch_assoc()['event_count'];

// 獲取最近的活動
$recentEventsQuery = "SELECT e.id, e.title, r.name AS restaurant_name, e.created_at, e.is_closed, 
                      e.share_code, e.deadline,
                      (SELECT COUNT(*) FROM event_participants WHERE event_id = e.id) AS participant_count
                      FROM events e 
                      JOIN restaurants r ON e.restaurant_id = r.id 
                      WHERE e.creator_id = ? 
                      ORDER BY e.created_at DESC 
                      LIMIT 5";
$recentEventsStmt = $conn->prepare($recentEventsQuery);
$recentEventsStmt->bind_param("i", $_SESSION['user_id']);
$recentEventsStmt->execute();
$recentEvents = $recentEventsStmt->get_result();

// 獲取使用者參與的活動
$joinedEventsQuery = "SELECT e.id, e.title, r.name AS restaurant_name, e.created_at, e.is_closed,
                      u.name AS creator_name, e.share_code, e.deadline,
                      (SELECT COUNT(*) FROM event_participants WHERE event_id = e.id) AS participant_count
                      FROM events e
                      JOIN event_participants ep ON e.id = ep.event_id
                      JOIN restaurants r ON e.restaurant_id = r.id
                      JOIN users u ON e.creator_id = u.id
                      WHERE ep.user_id = ? AND e.creator_id != ?
                      ORDER BY ep.joined_at DESC
                      LIMIT 3";
$joinedEventsStmt = $conn->prepare($joinedEventsQuery);
$joinedEventsStmt->bind_param("ii", $_SESSION['user_id'], $_SESSION['user_id']);
$joinedEventsStmt->execute();
$joinedEvents = $joinedEventsStmt->get_result();

include 'includes/header.php';
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
                <!-- 修正餐廳管理連結 -->
                <a href="<?php echo url('restaurants'); ?>" class="btn btn-primary btn-sm">管理餐廳</a>
                <a href="<?php echo url('events'); ?>" class="btn btn-success btn-sm">管理活動</a>
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
                                <th>參與人數</th>
                                <th>分享碼</th>
                                <th>截止時間</th>
                                <th>狀態</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($event = $recentEvents->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($event['title']); ?></td>
                                    <td><?php echo htmlspecialchars($event['restaurant_name']); ?></td>
                                    <td><span class="badge badge-info"><?php echo (int)$event['participant_count']; ?></span></td>
                                    <td>
                                        <?php if ($event['share_code']): ?>
                                            <span class="badge badge-warning copy-code" 
                                                  style="letter-spacing: 2px; cursor: pointer;" 
                                                  data-toggle="tooltip" 
                                                  title="點擊複製" 
                                                  data-code="<?php echo htmlspecialchars($event['share_code']); ?>">
                                                <?php echo htmlspecialchars($event['share_code']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">無</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $event['deadline'] ? date('m/d H:i', strtotime($event['deadline'])) : '無限期'; ?></td>
                                    <td>
                                        <?php if ($event['is_closed']): ?>
                                            <span class="badge badge-secondary">已關閉</span>
                                        <?php else: ?>
                                            <span class="badge badge-success">進行中</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo url('event', ['id' => $event['id']]); ?>" class="btn btn-sm btn-info">查看</a>
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
                <a href="<?php echo url('create-event'); ?>" class="btn btn-success">建立新活動</a>
                <a href="<?php echo url('events'); ?>" class="btn btn-outline-success">查看所有活動</a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 mb-4">
        <div class="card border-secondary">
            <div class="card-header bg-secondary text-white">
                <h5 class="m-0">我參與的活動</h5>
            </div>
            <div class="card-body">
                <?php if ($joinedEvents->num_rows > 0): ?>
                    <div class="row">
                        <?php while ($joinedEvent = $joinedEvents->fetch_assoc()): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card h-100 <?php echo $joinedEvent['is_closed'] ? 'border-secondary' : 'border-info'; ?>">
                                    <div class="card-header <?php echo $joinedEvent['is_closed'] ? 'bg-secondary' : 'bg-info'; ?> text-white">
                                        <h6 class="m-0 text-truncate" title="<?php echo htmlspecialchars($joinedEvent['title']); ?>">
                                            <?php echo htmlspecialchars($joinedEvent['title']); ?>
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text">
                                            <strong>餐廳:</strong> <?php echo htmlspecialchars($joinedEvent['restaurant_name']); ?><br>
                                            <strong>創建者:</strong> <?php echo htmlspecialchars($joinedEvent['creator_name']); ?><br>
                                            <strong>參與人數:</strong> <?php echo (int)$joinedEvent['participant_count']; ?><br>
                                            <?php if ($joinedEvent['deadline']): ?>
                                                <strong>截止時間:</strong> <?php echo date('m/d H:i', strtotime($joinedEvent['deadline'])); ?>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <div class="card-footer text-center">
                                        <a href="<?php echo url('event', ['id' => $joinedEvent['id']]); ?>" class="btn btn-sm btn-info">進入活動</a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p class="text-center py-3">您尚未參與任何活動。</p>
                <?php endif; ?>
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
                        <!-- 修正新增餐廳連結 -->
                        <a href="<?php echo url('create-restaurant'); ?>" class="btn btn-outline-primary btn-lg btn-block">
                            <i class="fas fa-plus-circle"></i> 新增餐廳
                        </a>
                    </div>
                    <div class="col-md-4 mb-3">
                        <!-- 修正建立活動連結 -->
                        <a href="<?php echo url('create-event'); ?>" class="btn btn-outline-success btn-lg btn-block">
                            <i class="fas fa-calendar-plus"></i> 建立活動
                        </a>
                    </div>
                    <div class="col-md-4 mb-3">
                        <!-- 修正加入活動連結 -->
                        <a href="<?php echo url('join-by-code'); ?>" class="btn btn-outline-info btn-lg btn-block">
                            <i class="fas fa-sign-in-alt"></i> 加入活動
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<!-- 添加用於複製功能的 JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 初始化 Bootstrap 的 tooltip
    $('[data-toggle="tooltip"]').tooltip();
    
    // 為所有帶有 copy-code 類的元素添加點擊事件
    document.querySelectorAll('.copy-code').forEach(function(element) {
        element.addEventListener('click', function() {
            // 獲取要複製的代碼
            var code = this.getAttribute('data-code');
            
            // 創建一個臨時的 textarea 元素
            var textarea = document.createElement('textarea');
            textarea.value = code;
            textarea.setAttribute('readonly', '');
            textarea.style.position = 'absolute';
            textarea.style.left = '-9999px';
            document.body.appendChild(textarea);
            
            // 選中並複製文本
            textarea.select();
            document.execCommand('copy');
            
            // 從 DOM 中移除臨時元素
            document.body.removeChild(textarea);
            
            // 更改工具提示內容，並顯示
            var originalTitle = this.getAttribute('data-original-title');
            $(this).tooltip('hide')
                .attr('data-original-title', '已複製！')
                .tooltip('show');
            
            // 閃爍效果，顯示複製成功
            this.classList.add('bg-success');
            setTimeout(() => {
                this.classList.remove('bg-success');
                $(this).tooltip('hide')
                    .attr('data-original-title', originalTitle);
            }, 1000);
        });
    });
});
</script>
