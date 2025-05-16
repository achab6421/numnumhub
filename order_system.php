<?php
// 點餐系統主頁面
require_once 'includes/init.php';

// 確保用戶已登入
if (!isLoggedIn()) {
    // 使用 session 直接設置閃存消息
    $_SESSION['flash_message'] = '請先登入';
    $_SESSION['flash_type'] = 'error';
    redirect('login');
}

// 獲取活動ID
$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($event_id <= 0) {
    // 使用 session 直接設置閃存消息
    $_SESSION['flash_message'] = '無效的活動ID';
    $_SESSION['flash_type'] = 'error';
    redirect('events');
}

$user_id = $_SESSION['user_id'];
global $conn;

// 獲取活動資料
$sql = "SELECT e.*, r.name AS restaurant_name, r.address, r.phone, r.link, 
        u.name AS creator_name, 
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
    // 使用 session 直接設置閃存消息
    $_SESSION['flash_message'] = '找不到該活動';
    $_SESSION['flash_type'] = 'error';
    redirect('events');
}

$event = $result->fetch_assoc();
$stmt->close();

// 檢查使用者是否已參與此活動
if (!$event['is_participant'] && !$event['is_creator']) {
    // 使用 session 直接設置閃存消息
    $_SESSION['flash_message'] = '您尚未加入此活動';
    $_SESSION['flash_type'] = 'error';
    redirect('events');
}

// 獲取參與者列表
$participants_sql = "SELECT u.id, u.name, ep.joined_at 
                    FROM event_participants ep 
                    JOIN users u ON ep.user_id = u.id 
                    WHERE ep.event_id = ? 
                    ORDER BY ep.joined_at ASC";
                    
$participants_stmt = $conn->prepare($participants_sql);
$participants_stmt->bind_param("i", $event_id);
$participants_stmt->execute();
$participants_result = $participants_stmt->get_result();
$participants = [];
while ($row = $participants_result->fetch_assoc()) {
    $participants[$row['id']] = $row;
}
$participants_stmt->close();

// 獲取點餐列表
$orders_sql = "SELECT eo.*, u.name AS user_name 
              FROM event_orders eo 
              JOIN users u ON eo.user_id = u.id 
              WHERE eo.event_id = ? 
              ORDER BY eo.created_at DESC";
              
$orders_stmt = $conn->prepare($orders_sql);
$orders_stmt->bind_param("i", $event_id);
$orders_stmt->execute();
$orders_result = $orders_stmt->get_result();
$orders_stmt->close();

// 計算各項統計數據
$total_amount = 0;
$user_totals = [];
$menu_items = [];

if ($orders_result->num_rows > 0) {
    $orders_copy = $orders_result;
    while ($order = $orders_copy->fetch_assoc()) {
        $item_total = $order['price'] * $order['quantity'];
        $total_amount += $item_total;
        
        // 計算每個用戶的消費
        if (!isset($user_totals[$order['user_id']])) {
            $user_totals[$order['user_id']] = [
                'name' => $order['user_name'],
                'total' => 0,
                'items' => []
            ];
        }
        $user_totals[$order['user_id']]['total'] += $item_total;
        $user_totals[$order['user_id']]['items'][] = $order;
        
        // 收集菜單項目
        if (!in_array($order['menu_item'], $menu_items)) {
            $menu_items[] = $order['menu_item'];
        }
    }
    // 重置結果集指針
    $orders_result->data_seek(0);
}

// 獲取來自 URL 的成功/錯誤訊息
$success = isset($_GET['success']) ? $_GET['success'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';

// 頁面標題
$pageTitle = htmlspecialchars($event['title']) . " - 點餐系統";
include_once 'includes/header.php';
?>

<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url('dashboard'); ?>">首頁</a></li>
            <li class="breadcrumb-item"><a href="<?php echo url('events'); ?>">活動管理</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($event['title']); ?></li>
        </ol>
    </nav>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- 左側：活動資訊和新增點餐 -->
        <div class="col-lg-8">
            <!-- 活動資訊卡片 -->
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="m-0">活動資訊</h5>
                    
                    <?php if (!$event['is_closed']): ?>
                        <div>
                            <?php if ($event['is_creator']): ?>
                                <button class="btn btn-sm btn-light" data-toggle="modal" data-target="#shareCodeModal">
                                    <i class="fas fa-share-alt"></i> 分享碼
                                </button>
                                <a href="<?php echo url('edit-event', ['id' => $event_id]); ?>" class="btn btn-sm btn-light">
                                    <i class="fas fa-edit"></i> 編輯
                                </a>
                                <button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#closeEventModal">
                                    <i class="fas fa-times-circle"></i> 關閉活動
                                </button>
                            <?php endif; ?>
                            <a href="<?php echo url('event', ['id' => $event_id]); ?>" class="btn btn-sm btn-light">
                                <i class="fas fa-info-circle"></i> 活動詳情
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-7">
                            <h5><?php echo htmlspecialchars($event['title']); ?></h5>
                            
                            <p>
                                <i class="fas fa-store text-primary"></i> 
                                <strong>餐廳：</strong> <?php echo htmlspecialchars($event['restaurant_name']); ?>
                            </p>
                            
                            <?php if ($event['phone']): ?>
                            <p>
                                <i class="fas fa-phone text-success"></i> 
                                <strong>電話：</strong> <?php echo htmlspecialchars($event['phone']); ?>
                            </p>
                            <?php endif; ?>
                            
                            <?php if ($event['address']): ?>
                            <p>
                                <i class="fas fa-map-marker-alt text-danger"></i> 
                                <strong>地址：</strong> <?php echo htmlspecialchars($event['address']); ?>
                            </p>
                            <?php endif; ?>
                            
                            <?php if ($event['link']): ?>
                            <p>
                                <i class="fas fa-link text-info"></i> 
                                <strong>連結：</strong> 
                                <a href="<?php echo htmlspecialchars($event['link']); ?>" target="_blank">
                                    <?php echo htmlspecialchars($event['link']); ?>
                                </a>
                            </p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-5">
                            <div class="alert <?php echo $event['is_closed'] ? 'alert-secondary' : 'alert-info'; ?>">
                                <p>
                                    <i class="fas fa-calendar-alt"></i> 
                                    <strong>截止時間：</strong> 
                                    <?php echo $event['deadline'] ? date('Y-m-d H:i', strtotime($event['deadline'])) : '無限期'; ?>
                                </p>
                                <p class="mb-0">
                                    <i class="fas fa-users"></i> 
                                    <strong>目前參與人數：</strong> <?php echo (int)$event['participant_count']; ?>
                                </p>
                                <p class="mb-0">
                                    <i class="fas fa-user"></i> 
                                    <strong>建立者：</strong> <?php echo htmlspecialchars($event['creator_name']); ?>
                                </p>
                                <?php if ($event['is_closed']): ?>
                                    <div class="mt-2 text-center">
                                        <span class="badge badge-warning p-2">此活動已關閉，無法再新增點餐</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 新增點餐表單 -->
            <?php if (!$event['is_closed']): ?>
            <div class="card shadow mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="m-0">新增點餐</h5>
                </div>
                <div class="card-body">
                    <form id="orderForm" action="<?php echo url('add-order'); ?>" method="post" class="needs-validation" novalidate>
                        <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                        
                        <div class="form-row">
                            <div class="col-md-5 mb-3">
                                <label for="menu_item">餐點名稱 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="menu_item" name="menu_item" list="menu_suggestions" required>
                                <datalist id="menu_suggestions">
                                    <?php foreach($menu_items as $item): ?>
                                        <option value="<?php echo htmlspecialchars($item); ?>">
                                    <?php endforeach; ?>
                                </datalist>
                                <div class="invalid-feedback">
                                    請輸入餐點名稱
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="price">價格</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">$</span>
                                    </div>
                                    <input type="number" class="form-control" id="price" name="price" min="0" step="1" value="0">
                                </div>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="quantity">數量</label>
                                <input type="number" class="form-control" id="quantity" name="quantity" min="1" value="1">
                            </div>
                            <div class="col-md-2 mb-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-success btn-block">
                                    <i class="fas fa-plus"></i> 新增
                                </button>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="note">備註</label>
                            <textarea class="form-control" id="note" name="note" rows="2" placeholder="例如：不要辣、少糖..."></textarea>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- 點餐列表 -->
            <div class="card shadow">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <h5 class="m-0">點餐列表</h5>
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-light" id="toggleGroupByUser">
                            <i class="fas fa-users"></i> 依用戶分組
                        </button>
                        <button type="button" class="btn btn-sm btn-light dropdown-toggle" data-toggle="dropdown">
                            <i class="fas fa-sort"></i> 排序
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item sort-option" href="#" data-sort="newest">最新到最舊</a>
                            <a class="dropdown-item sort-option" href="#" data-sort="oldest">最舊到最新</a>
                            <a class="dropdown-item sort-option" href="#" data-sort="price-high">價格由高到低</a>
                            <a class="dropdown-item sort-option" href="#" data-sort="price-low">價格由低到高</a>
                            <a class="dropdown-item sort-option" href="#" data-sort="name">餐點名稱</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($orders_result->num_rows > 0): ?>
                        <div class="alert alert-info">
                            <strong>總金額：</strong> $<?php echo number_format($total_amount); ?>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover" id="ordersTable">
                                <thead>
                                    <tr>
                                        <th>點餐者</th>
                                        <th>餐點</th>
                                        <th>數量</th>
                                        <th>價格</th>
                                        <th>備註</th>
                                        <th>時間</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($order = $orders_result->fetch_assoc()): ?>
                                        <tr class="<?php echo $order['user_id'] == $user_id ? 'table-info' : ''; ?>" 
                                            data-user-id="<?php echo $order['user_id']; ?>" 
                                            data-order-price="<?php echo $order['price'] * $order['quantity']; ?>"
                                            data-order-date="<?php echo strtotime($order['created_at']); ?>"
                                            data-item-name="<?php echo htmlspecialchars($order['menu_item']); ?>">
                                            <td><?php echo htmlspecialchars($order['user_name']); ?></td>
                                            <td><?php echo htmlspecialchars($order['menu_item']); ?></td>
                                            <td><?php echo $order['quantity']; ?></td>
                                            <td>
                                                <?php if ($order['price'] > 0): ?>
                                                    $<?php echo number_format($order['price'], 0); ?>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($order['note'])): ?>
                                                    <span data-toggle="tooltip" title="<?php echo htmlspecialchars($order['note']); ?>">
                                                        <?php echo strlen($order['note']) > 20 ? htmlspecialchars(substr($order['note'], 0, 20)) . '...' : htmlspecialchars($order['note']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('m/d H:i', strtotime($order['created_at'])); ?></td>
                                            <td>
                                                <?php if (!$event['is_closed']): ?>
                                                    <form method="post" action="<?php echo url('copy-order'); ?>" class="d-inline">
                                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                        <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-primary" title="複製此餐點">
                                                            <i class="fas fa-copy"></i>
                                                        </button>
                                                    </form>
                                                    
                                                    <?php if ($order['user_id'] == $user_id || $event['is_creator']): ?>
                                                        <form method="post" action="<?php echo url('delete-order'); ?>" class="d-inline" 
                                                              onsubmit="return confirm('確定要刪除此餐點嗎？');">
                                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                            <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="刪除">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-circle"></i> 此活動尚未有任何點餐記錄
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- 右側：參與者列表和統計 -->
        <div class="col-lg-4">
            <!-- 參與者列表 -->
            <div class="card shadow mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="m-0">參與者列表 (<?php echo count($participants); ?>)</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php if (count($participants) > 0): ?>
                            <?php foreach ($participants as $participant): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <?php echo htmlspecialchars($participant['name']); ?>
                                        <?php if ($participant['id'] == $event['creator_id']): ?>
                                            <span class="badge badge-primary">建立者</span>
                                        <?php endif; ?>
                                        <?php if ($participant['id'] == $user_id): ?>
                                            <span class="badge badge-success">你</span>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo date('m/d H:i', strtotime($participant['joined_at'])); ?>
                                    </small>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="list-group-item text-center text-muted">暫無參與者</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            
            <!-- 消費統計 -->
            <?php if (count($user_totals) > 0): ?>
            <div class="card shadow mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="m-0">消費統計</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($user_totals as $id => $user_data): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong>
                                        <?php echo htmlspecialchars($user_data['name']); ?>
                                        <?php if ($id == $user_id): ?>
                                            <span class="badge badge-success">你</span>
                                        <?php endif; ?>
                                    </strong>
                                    <span class="badge badge-warning badge-pill">
                                        $<?php echo number_format($user_data['total']); ?>
                                    </span>
                                </div>
                                <div class="mt-2 small">
                                    <ul class="list-unstyled mb-0">
                                        <?php foreach ($user_data['items'] as $item): ?>
                                            <li>
                                                <?php echo htmlspecialchars($item['menu_item']); ?> 
                                                x <?php echo $item['quantity']; ?> 
                                                <?php if ($item['price'] > 0): ?>
                                                    ($<?php echo number_format($item['price']); ?>)
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div class="list-group-item bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong>總計</strong>
                                <span class="badge badge-primary badge-pill">
                                    $<?php echo number_format($total_amount); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- 活動操作 -->
            <div class="card shadow">
                <div class="card-header bg-dark text-white">
                    <h5 class="m-0">活動操作</h5>
                </div>
                <div class="card-body">
                    <?php if (!$event['is_closed']): ?>
                        <?php if ($event['is_creator']): ?>
                            <button class="btn btn-danger btn-block mb-2" data-toggle="modal" data-target="#closeEventModal">
                                <i class="fas fa-times-circle"></i> 關閉活動
                            </button>
                        <?php endif; ?>
                        
                        <?php if (!$event['is_creator']): ?>
                            <form action="<?php echo url('leave'); ?>" method="post" onsubmit="return confirm('確定要退出此活動嗎？這將刪除您的所有點餐記錄');">
                                <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                                <button type="submit" class="btn btn-outline-danger btn-block mb-2">
                                    <i class="fas fa-sign-out-alt"></i> 退出活動
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-secondary text-center">
                            <i class="fas fa-lock"></i> 此活動已關閉
                        </div>
                    <?php endif; ?>
                    
                    <a href="<?php echo url('events'); ?>" class="btn btn-outline-secondary btn-block">
                        <i class="fas fa-arrow-left"></i> 返回活動列表
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 分享碼模態框 -->
<?php if ($event['share_code']): ?>
<div class="modal fade" id="shareCodeModal" tabindex="-1" role="dialog" aria-labelledby="shareCodeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="shareCodeModalLabel">活動分享碼</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <p>分享此代碼給他人，讓他們可以加入此活動：</p>
                <h2 class="copy-code" style="letter-spacing: 3px; cursor: pointer;" data-toggle="tooltip" title="點擊複製" data-code="<?php echo htmlspecialchars($event['share_code']); ?>">
                    <?php echo htmlspecialchars($event['share_code']); ?>
                </h2>
                <p class="mt-3">或分享以下連結：</p>
                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="shareLink" value="<?php echo htmlspecialchars(getBaseUrl() . url('join-by-code') . '?code=' . urlencode($event['share_code'])); ?>" readonly>
                    <div class="input-group-append">
                        <button class="btn btn-outline-primary" type="button" id="copyLink" title="複製連結">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">關閉</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- 關閉活動模態框 -->
<?php if ($event['is_creator'] && !$event['is_closed']): ?>
<div class="modal fade" id="closeEventModal" tabindex="-1" role="dialog" aria-labelledby="closeEventModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="closeEventModalLabel">關閉活動</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>確定要關閉此活動嗎？關閉後將無法再新增餐點。</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> 此操作無法撤銷！
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                <form action="<?php echo url('close-event'); ?>" method="post">
                    <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                    <button type="submit" class="btn btn-danger">確定關閉</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 初始化工具提示
    $('[data-toggle="tooltip"]').tooltip();
    
    // 表單驗證
    (function() {
        'use strict';
        window.addEventListener('load', function() {
            var forms = document.getElementsByClassName('needs-validation');
            Array.prototype.filter.call(forms, function(form) {
                form.addEventListener('submit', function(event) {
                    if (form.checkValidity() === false) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        }, false);
    })();
    
    // 複製功能
    document.querySelectorAll('.copy-code').forEach(function(element) {
        element.addEventListener('click', function() {
            const code = this.getAttribute('data-code');
            copyToClipboard(code, this);
        });
    });
    
    // 複製連結按鈕
    document.getElementById('copyLink')?.addEventListener('click', function() {
        const link = document.getElementById('shareLink').value;
        copyToClipboard(link, this);
    });
    
    // 按用戶分組切換
    document.getElementById('toggleGroupByUser')?.addEventListener('click', function() {
        const rows = Array.from(document.querySelectorAll('#ordersTable tbody tr'));
        const tbody = document.querySelector('#ordersTable tbody');
        
        if (this.getAttribute('data-grouped') === 'true') {
            // 還原原始順序
            rows.sort((a, b) => {
                return parseInt(a.getAttribute('data-original-index')) - parseInt(b.getAttribute('data-original-index'));
            }).forEach(row => tbody.appendChild(row));
            
            this.setAttribute('data-grouped', 'false');
            this.innerHTML = '<i class="fas fa-users"></i> 依用戶分組';
        } else {
            // 給每行添加原始索引
            rows.forEach((row, index) => {
                if (!row.hasAttribute('data-original-index')) {
                    row.setAttribute('data-original-index', index);
                }
            });
            
            // 按用戶分組
            rows.sort((a, b) => {
                return parseInt(a.getAttribute('data-user-id')) - parseInt(b.getAttribute('data-user-id'));
            }).forEach(row => tbody.appendChild(row));
            
            this.setAttribute('data-grouped', 'true');
            this.innerHTML = '<i class="fas fa-sort"></i> 恢復原始排序';
        }
    });
    
    // 排序功能
    document.querySelectorAll('.sort-option').forEach(function(element) {
        element.addEventListener('click', function(e) {
            e.preventDefault();
            const sortType = this.getAttribute('data-sort');
            const rows = Array.from(document.querySelectorAll('#ordersTable tbody tr'));
            const tbody = document.querySelector('#ordersTable tbody');
            
            // 如果行沒有原始索引，添加它
            rows.forEach((row, index) => {
                if (!row.hasAttribute('data-original-index')) {
                    row.setAttribute('data-original-index', index);
                }
            });
            
            // 根據不同排序類型進行排序
            switch(sortType) {
                case 'newest':
                    rows.sort((a, b) => {
                        return parseInt(b.getAttribute('data-order-date')) - parseInt(a.getAttribute('data-order-date'));
                    });
                    break;
                case 'oldest':
                    rows.sort((a, b) => {
                        return parseInt(a.getAttribute('data-order-date')) - parseInt(b.getAttribute('data-order-date'));
                    });
                    break;
                case 'price-high':
                    rows.sort((a, b) => {
                        return parseFloat(b.getAttribute('data-order-price')) - parseFloat(a.getAttribute('data-order-price'));
                    });
                    break;
                case 'price-low':
                    rows.sort((a, b) => {
                        return parseFloat(a.getAttribute('data-order-price')) - parseFloat(b.getAttribute('data-order-price'));
                    });
                    break;
                case 'name':
                    rows.sort((a, b) => {
                        return a.getAttribute('data-item-name').localeCompare(b.getAttribute('data-item-name'));
                    });
                    break;
                default:
                    return;
            }
            
            // 清空表格並按排序添加行
            tbody.innerHTML = '';
            rows.forEach(row => tbody.appendChild(row));
            
            // 重置分組按鈕
            const groupButton = document.getElementById('toggleGroupByUser');
            groupButton.setAttribute('data-grouped', 'false');
            groupButton.innerHTML = '<i class="fas fa-users"></i> 依用戶分組';
        });
    });
    
    // 複製到剪貼簿功能
    function copyToClipboard(text, element) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.setAttribute('readonly', '');
        textarea.style.position = 'absolute';
        textarea.style.left = '-9999px';
        document.body.appendChild(textarea);
        
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        
        // 顯示成功提示
        const originalTooltip = element.getAttribute('data-original-title');
        $(element).tooltip('hide')
            .attr('data-original-title', '已複製！')
            .tooltip('show');
            
        // 閃爍效果
        element.classList.add('text-success');
        setTimeout(() => {
            element.classList.remove('text-success');
            $(element).tooltip('hide')
                .attr('data-original-title', originalTooltip || '點擊複製');
        }, 1500);
    }
});
</script>

<?php include_once 'includes/footer.php'; ?>
