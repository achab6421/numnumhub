<?php
// 活動管理頁面
require_once 'includes/init.php';

// 確保用戶已登入
if (!isLoggedIn()) {
    redirect('login');
}

$user_id = $_SESSION['user_id'];

// 獲取篩選參數
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$status = isset($_GET['status']) ? $_GET['status'] : 'active'; // 默認只顯示進行中的活動
$role = isset($_GET['role']) ? $_GET['role'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// 構建SQL查詢
$sql_conditions = [];
$params = [];
$param_types = '';

// 基本SQL查詢
$sql = "SELECT e.*, r.name AS restaurant_name, u.name AS creator_name, 
        COUNT(DISTINCT ep2.id) AS participant_count, 
        (e.creator_id = ?) AS is_creator,
        1 AS is_participant  /* 固定為1，因為只顯示參與的活動 */
        FROM events e
        JOIN restaurants r ON e.restaurant_id = r.id
        JOIN users u ON e.creator_id = u.id
        JOIN event_participants ep ON e.id = ep.event_id AND ep.user_id = ?  /* 直接JOIN參與表，只顯示已參與的 */
        LEFT JOIN event_participants ep2 ON e.id = ep2.event_id";

// 添加參數
$params[] = $user_id;
$params[] = $user_id;
$param_types .= 'ii';

// 根據角色篩選
if ($role == 'creator') {
    $sql_conditions[] = "e.creator_id = ?";
    $params[] = $user_id;
    $param_types .= 'i';
} elseif ($role == 'participant') {
    $sql_conditions[] = "e.creator_id != ?"; // 只顯示參與但非創建者的活動
    $params[] = $user_id;
    $param_types .= 'i';
}

// 根據狀態篩選
if ($status == 'active') {
    $sql_conditions[] = "e.is_closed = 0";
} elseif ($status == 'closed') {
    $sql_conditions[] = "e.is_closed = 1";
}

// 搜尋功能
if (!empty($search)) {
    $sql_conditions[] = "(e.title LIKE ? OR r.name LIKE ?)";
    $searchParam = "%{$search}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $param_types .= 'ss';
}

// 添加WHERE條件
if (!empty($sql_conditions)) {
    $sql .= " WHERE " . implode(" AND ", $sql_conditions);
}

// 分組和排序
$sql .= " GROUP BY e.id ORDER BY e.created_at DESC";

// 準備和執行查詢
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}

$stmt->execute();
$events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 頁面標題
$pageTitle = '活動管理';
include 'includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">活動管理</h1>
        <div>
            <a href="<?php echo url('join-by-code'); ?>" class="btn btn-info mr-2">
                <i class="fas fa-sign-in-alt"></i> 加入活動
            </a>
            <a href="<?php echo url('create-event'); ?>" class="btn btn-primary">
                <i class="fas fa-plus"></i> 建立新活動
            </a>
        </div>
    </div>
    
    <!-- 篩選表單 -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">篩選選項</h5>
        </div>
        <div class="card-body">
            <form method="get" action="<?php echo url('events'); ?>" class="form-inline">
                <div class="form-group mr-2 mb-2">
                    <label for="role" class="mr-2">角色：</label>
                    <select name="role" id="role" class="form-control">
                        <option value="all" <?php echo $role == 'all' ? 'selected' : ''; ?>>全部角色</option>
                        <option value="creator" <?php echo $role == 'creator' ? 'selected' : ''; ?>>我建立的</option>
                        <option value="participant" <?php echo $role == 'participant' ? 'selected' : ''; ?>>我參與的</option>
                    </select>
                </div>
                <div class="form-group mr-2 mb-2">
                    <label for="status" class="mr-2">狀態：</label>
                    <select name="status" id="status" class="form-control">
                        <option value="all" <?php echo $status == 'all' ? 'selected' : ''; ?>>全部狀態</option>
                        <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>進行中</option>
                        <option value="closed" <?php echo $status == 'closed' ? 'selected' : ''; ?>>已關閉</option>
                    </select>
                </div>
                <div class="form-group mr-2 mb-2">
                    <label for="search" class="mr-2">搜尋：</label>
                    <input type="text" name="search" id="search" class="form-control" placeholder="活動名稱或餐廳" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <button type="submit" class="btn btn-primary mb-2">
                    <i class="fas fa-search"></i> 搜尋
                </button>
                <a href="<?php echo url('events'); ?>" class="btn btn-secondary mb-2 ml-2">
                    <i class="fas fa-undo"></i> 重置
                </a>
            </form>
        </div>
    </div>

    <?php if (!empty($events)): ?>
        <div class="card shadow">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">活動列表</h5>
                    <span class="badge badge-info">共 <?php echo count($events); ?> 個活動</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>活動名稱</th>
                            <th>餐廳</th>
                            <th>參與人數</th>
                            <th>截止時間</th>
                            <th>狀態</th>
                            <th>你的角色</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $event): ?>
                            <tr>
                                <td class="text-nowrap"><?php echo htmlspecialchars($event['title']); ?></td>
                                <td class="text-nowrap"><?php echo htmlspecialchars($event['restaurant_name']); ?></td>
                                <td><span class="badge badge-info"><?php echo (int)$event['participant_count']; ?></span></td>
                                <td class="text-nowrap"><?php echo $event['deadline'] ? date('m/d H:i', strtotime($event['deadline'])) : '無限期'; ?></td>
                                <td>
                                    <?php if ($event['is_closed']): ?>
                                        <span class="badge badge-secondary">已關閉</span>
                                    <?php else: ?>
                                        <?php if ($event['deadline'] && strtotime($event['deadline']) < time()): ?>
                                            <span class="badge badge-warning">已截止</span>
                                        <?php else: ?>
                                            <span class="badge badge-success">進行中</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($event['is_creator']): ?>
                                        <span class="badge badge-primary">建立者</span>
                                    <?php else: ?>
                                        <span class="badge badge-info">參與者</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?php echo url('order-system', ['id' => $event['id']]); ?>" class="btn btn-primary" title="進入點餐系統">
                                            <i class="fas fa-utensils"></i>
                                        </a>
                                        <?php if ($event['is_creator']): ?>
                                            <?php if (!$event['is_closed']): ?>
                                                <button type="button" class="btn btn-danger close-event-btn" 
                                                        data-id="<?php echo $event['id']; ?>" 
                                                        data-title="<?php echo htmlspecialchars($event['title']); ?>"
                                                        title="關閉活動">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <?php if (!$event['is_closed']): ?>
                                                <button type="button" class="btn btn-secondary leave-event-btn" 
                                                        data-id="<?php echo $event['id']; ?>" 
                                                        data-title="<?php echo htmlspecialchars($event['title']); ?>"
                                                        title="退出活動">
                                                    <i class="fas fa-sign-out-alt"></i>
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> 沒有找到符合條件的活動。
            <?php if (!empty($search) || $status != 'all' || $role != 'all'): ?>
                <a href="<?php echo url('events'); ?>" class="alert-link">清除篩選條件</a>
            <?php else: ?>
                <div class="mt-3">
                    <a href="<?php echo url('create-event'); ?>" class="btn btn-primary">
                        <i class="fas fa-plus"></i> 立即創建活動
                    </a>
                    <a href="<?php echo url('join-by-code'); ?>" class="btn btn-info ml-2">
                        <i class="fas fa-sign-in-alt"></i> 加入活動
                    </a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- 關閉活動確認模態框 -->
<div class="modal fade" id="closeEventModal" tabindex="-1" role="dialog" aria-labelledby="closeEventModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="closeEventModalLabel">確認關閉活動</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                您確定要關閉活動「<span id="eventTitleSpan"></span>」嗎？
                <p class="text-danger mt-2">
                    <i class="fas fa-exclamation-triangle"></i> 警告：關閉後活動將不再接受新的訂單，且無法重新開啟。
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                <form id="closeEventForm" method="post" action="<?php echo url('close-event'); ?>">
                    <input type="hidden" name="event_id" id="eventIdInput">
                    <button type="submit" class="btn btn-danger">確認關閉</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 退出活動確認模態框 -->
<div class="modal fade" id="leaveEventModal" tabindex="-1" role="dialog" aria-labelledby="leaveEventModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title" id="leaveEventModalLabel">確認退出活動</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                您確定要退出活動「<span id="leaveEventTitleSpan"></span>」嗎？
                <p class="text-warning mt-2">
                    <i class="fas fa-exclamation-triangle"></i> 注意：如果您已經在活動中點餐，將無法退出活動。
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                <form id="leaveEventForm" method="post" action="leave_event.php">
                    <input type="hidden" name="event_id" id="leaveEventIdInput">
                    <button type="submit" class="btn btn-danger">確認退出</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 關閉活動按鈕點擊事件
    document.querySelectorAll('.close-event-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            const eventId = this.getAttribute('data-id');
            const eventTitle = this.getAttribute('data-title');
            
            document.getElementById('eventIdInput').value = eventId;
            document.getElementById('eventTitleSpan').textContent = eventTitle;
            
            $('#closeEventModal').modal('show');
        });
    });
    
    // 退出活動按鈕點擊事件
    document.querySelectorAll('.leave-event-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            const eventId = this.getAttribute('data-id');
            const eventTitle = this.getAttribute('data-title');
            
            console.log('Leaving event:', eventId, eventTitle); // 添加日誌
            
            document.getElementById('leaveEventIdInput').value = eventId;
            document.getElementById('leaveEventTitleSpan').textContent = eventTitle;
            
            $('#leaveEventModal').modal('show');
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
