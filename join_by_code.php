<?php
// 透過分享碼加入活動

// 確保用戶已登入

require_once 'includes/init.php';
if (!isLoggedIn()) {
    // 保留當前 URL 和查詢參數
    $current_url = $_SERVER['REQUEST_URI'];
    $login_url = 'https://achab6421phpaccount.free.nf/numnumhub/login?redirect=' . urlencode($current_url);
    header("Location: " . $login_url);
    exit;
}

$error = '';
$success = '';

// 檢查URL中是否有分享碼參數，如果有則先檢查用戶是否已經加入
if (isset($_GET['code'])) {
    $share_code = strtoupper(trim($_GET['code']));
    $user_id = $_SESSION['user_id'];
    
    if (!empty($share_code)) {
        global $conn;
        
        // 查找該分享碼對應的活動
        $stmt = $conn->prepare("SELECT id, title, creator_id, is_closed FROM events WHERE share_code = ?");
        $stmt->bind_param("s", $share_code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $event = $result->fetch_assoc();
            $event_id = $event['id'];
            
            // 如果是活動創建者，直接跳轉到點餐頁面
            if ($event['creator_id'] == $user_id) {
                header("Location: https://achab6421phpaccount.free.nf/numnumhub/order-system?id=" . $event_id);
                exit;
            }
            
            // 檢查是否已加入此活動
            $check_sql = "SELECT id FROM event_participants WHERE event_id = ? AND user_id = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("ii", $event_id, $user_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                // 如果已經加入此活動，直接重定向到點餐頁面
                header("Location: https://achab6421phpaccount.free.nf/numnumhub/order-system?id=" . $event_id);
                exit;
            }
            $check_stmt->close();
        }
        $stmt->close();
    }
}

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $share_code = strtoupper(trim($_POST['share_code'] ?? ''));
    $user_id = $_SESSION['user_id'];
    
    if (empty($share_code)) {
        $error = "請輸入分享碼";
    } else {
        global $conn;
        
        // 查找該分享碼對應的活動
        $stmt = $conn->prepare("SELECT id, title, creator_id, is_closed FROM events WHERE share_code = ?");
        $stmt->bind_param("s", $share_code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $error = "找不到此分享碼對應的活動";
        } else {
            $event = $result->fetch_assoc();
            $event_id = $event['id'];
            
            // 檢查活動是否已關閉
            if ($event['is_closed']) {
                $error = "此活動已關閉";
            } 
            // 檢查是否為活動創建者（創建者已自動加入）
            elseif ($event['creator_id'] == $user_id) {
                // 如果是活動創建者，直接跳轉到點餐頁面
                header("Location: https://achab6421phpaccount.free.nf/numnumhub/order-system?id=" . $event_id);
                exit;
            } else {
                // 檢查是否已加入此活動
                $check_sql = "SELECT id FROM event_participants WHERE event_id = ? AND user_id = ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("ii", $event_id, $user_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    // 如果已經加入此活動，直接重定向到點餐頁面
                    header("Location: https://achab6421phpaccount.free.nf/numnumhub/order-system?id=" . $event_id);
                    exit;
                } else {
                    // 加入活動
                    $join_sql = "INSERT INTO event_participants (event_id, user_id, joined_at) VALUES (?, ?, NOW())";
                    $join_stmt = $conn->prepare($join_sql);
                    $join_stmt->bind_param("ii", $event_id, $user_id);
                    
                    if ($join_stmt->execute()) {
                        $success = "您已成功加入「{$event['title']}」活動！";
                        // 重定向到活動詳情頁
                        header("Location: https://achab6421phpaccount.free.nf/numnumhub/order-system?id=" . $event_id);
                        exit;
                    } else {
                        $error = "加入活動時發生錯誤";
                    }
                    $join_stmt->close();
                }
                $check_stmt->close();
            }
        }
        $stmt->close();
    }
}

// 頁面標題
$pageTitle = "透過分享碼加入活動";
include_once 'includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="m-0">透過分享碼加入活動</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="post" action="https://achab6421phpaccount.free.nf/numnumhub/join-by-code">
                        <div class="form-group">
                            <label for="share_code">活動分享碼</label>
                            <input type="text" class="form-control form-control-lg text-center" id="share_code" name="share_code" 
                                   placeholder="請輸入6位分享碼" maxlength="6" style="letter-spacing: 5px;"  required>
                            <small class="form-text text-muted">請輸入6位活動分享碼，不區分大小寫</small>
                        </div>
                        
                        <div class="form-group text-center">
                            <button type="submit" class="btn btn-primary btn-lg">加入活動</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener("DOMContentLoaded", function () {
  const urlParams = new URLSearchParams(window.location.search);
  const code = urlParams.get('code'); // 抓取 ?code= 後的值
  if (code) {
    document.getElementById('share_code').value = code;
  }
});
</script>

<?php include_once 'includes/footer.php'; ?>
