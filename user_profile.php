<?php
// 使用者個人資料頁面
require_once 'includes/init.php';
// 在頂部引入標籤功能函數
require_once __DIR__ . '/tags/functions.php';

// 確保用戶已登入
if (!isLoggedIn()) {
    redirect('login');
}

// 獲取用戶資料
$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];
$userEmail = $_SESSION['user_email'];

// 正確獲取使用者標籤
$userTags = getTags($userId);
// 獲取使用者已選標籤的ID列表
$userTagIds = array_map(function($tag) {
    return $tag['id'];
}, $userTags);

// 獲取所有可用標籤
$allTags = getAllTags();

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 處理標籤偏好更新
    if (isset($_POST['update_preferences'])) {
        // 獲取選擇的標籤ID數組
        $selectedTags = isset($_POST['tags']) ? array_map('intval', $_POST['tags']) : [];
        
        // 更新使用者標籤關聯
        $result = updateUserTags($userId, $selectedTags);
        
        // 設置提示訊息
        $_SESSION['flash_message'] = $result['success'] ? '偏好設定已更新！' : '更新偏好設定失敗：' . $result['message'];
        $_SESSION['flash_type'] = $result['success'] ? 'success' : 'danger';
        
        // 使用完整URL重新導向，並包含錨點
        header('Location: ' . url('user_profile') . '#preferences');
        exit;
    }
    
    // 處理新增標籤
    if (isset($_POST['add_tag']) && isset($_POST['tag_name'])) {
        $tagName = trim($_POST['tag_name']);
        if (!empty($tagName)) {
            $result = createTag($tagName, $userId);
            
            $_SESSION['flash_message'] = $result['message'];
            $_SESSION['flash_type'] = $result['success'] ? 'success' : 'danger';
            
            // 重新導向以避免重複提交
            redirect('user_profile');
        }
    }
}

// 設置頁面標題
$pageTitle = "個人資料設定";
include 'includes/header.php';
?>

<div class="row">
    <div class="col-md-3">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="m-0">個人資料</h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <div class="avatar-placeholder rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center mx-auto" style="width: 100px; height: 100px; font-size: 40px;">
                        <?php echo strtoupper(substr($userName, 0, 1)); ?>
                    </div>
                </div>
                <h5 class="text-center"><?php echo htmlspecialchars($userName); ?></h5>
                <p class="text-center text-muted"><?php echo htmlspecialchars($userEmail); ?></p>
                
                <div class="list-group mt-4">
                    <a href="#profile-section" class="list-group-item list-group-item-action active" data-toggle="tab">個人資料</a>
                    <a href="#preferences-section" class="list-group-item list-group-item-action" data-toggle="tab">偏好設定</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-9">
        <div class="tab-content">
            <!-- 個人資料標籤 -->
            <div class="tab-pane fade show active" id="profile-section">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="m-0">個人資料設定</h5>
                    </div>
                    <div class="card-body">
                        <form>
                            <div class="form-group">
                                <label for="name">姓名</label>
                                <input type="text" class="form-control" id="name" value="<?php echo htmlspecialchars($userName); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label for="email">電子郵件</label>
                                <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($userEmail); ?>" readonly>
                            </div>
                            <p class="text-muted">目前暫不支援更改個人資料。</p>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- 偏好設定標籤 -->
            <div class="tab-pane fade" id="preferences-section">
                <!-- 標籤偏好設定 -->
                <div id="preferences" class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">標籤偏好設定</h5>
                    </div>
                    <div class="card-body">
                        <!-- 顯示當前標籤 -->
                        <div class="mb-3">
                            <h6>我的標籤:</h6>
                            <?php if (empty($userTags)): ?>
                                <p class="text-muted">尚未設定任何標籤偏好</p>
                            <?php else: ?>
                                <div class="d-flex flex-wrap">
                                    <?php foreach ($userTags as $tag): ?>
                                        <span class="badge badge-primary mr-2 mb-2">
                                            <?php echo htmlspecialchars($tag['name']); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- 新增標籤表單 -->
                        <form method="post" class="mb-4">
                            <h6>新增標籤:</h6>
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" name="tag_name" placeholder="輸入標籤名稱">
                                <div class="input-group-append">
                                    <button type="submit" name="add_tag" class="btn btn-outline-primary">新增</button>
                                </div>
                            </div>
                        </form>

                        <hr>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 添加頁面加載時自動滾動到錨點的JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 如果URL包含錨點，滾動到相應位置
    if (window.location.hash) {
        const targetElement = document.querySelector(window.location.hash);
        if (targetElement) {
            targetElement.scrollIntoView({
                behavior: 'smooth'
            });
            
            // 為目標區塊添加高亮效果
            targetElement.classList.add('highlight-section');
            setTimeout(function() {
                targetElement.classList.remove('highlight-section');
            }, 2000);
        }
        
        // 如果錨點為 #preferences，激活對應的標籤
        if (window.location.hash === '#preferences') {
            document.querySelector('a[href="#preferences-section"]').click();
        }
    }
});
</script>

<style>
/* 添加高亮效果的樣式 */
.highlight-section {
    animation: highlight 2s;
}
@keyframes highlight {
    0% { background-color: rgba(255, 255, 140, 0.5); }
    100% { background-color: transparent; }
}
</style>

<?php include 'includes/footer.php'; ?>
