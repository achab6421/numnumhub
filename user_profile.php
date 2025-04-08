<?php
// 使用者個人資料頁面
require_once 'includes/init.php';
require_once 'tags/functions.php';

// 確保用戶已登入
if (!isLoggedIn()) {
    redirect('login');
}

// 獲取用戶資料
$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];
$userEmail = $_SESSION['user_email'];

// 獲取所有標籤
$allTags = getAllTags();

// 獲取用戶標籤
$userTags = getUserTags($userId);
$userTagIds = array_column($userTags, 'id');

$error = '';
$success = '';

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // 處理更新標籤
    if ($action === 'update_tags') {
        $tagIds = isset($_POST['tags']) ? $_POST['tags'] : [];
        
        // 處理新增標籤
        $newTag = trim($_POST['new_tag'] ?? '');
        if (!empty($newTag)) {
            $tagResult = createTag($newTag);
            if ($tagResult['success'] && isset($tagResult['id'])) {
                $tagIds[] = $tagResult['id'];
            }
        }
        
        // 更新用戶標籤
        if (updateUserTags($userId, $tagIds)) {
            $success = '偏好設定已成功更新';
            $userTags = getUserTags($userId);
            $userTagIds = array_column($userTags, 'id');
        } else {
            $error = '更新偏好設定時發生錯誤';
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
                    <a href="#profile" class="list-group-item list-group-item-action active" data-toggle="tab">個人資料</a>
                    <a href="#preferences" class="list-group-item list-group-item-action" data-toggle="tab">偏好設定</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-9">
        <div class="tab-content">
            <!-- 個人資料標籤 -->
            <div class="tab-pane fade show active" id="profile">
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
            <div class="tab-pane fade" id="preferences">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="m-0">偏好設定</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <form action="<?php echo url('user_profile'); ?>" method="post">
                            <input type="hidden" name="action" value="update_tags">
                            
                            <div class="form-group">
                                <label>我的美食偏好標籤</label>
                                <div class="card">
                                    <div class="card-body">
                                        <?php if (!empty($allTags)): ?>
                                            <div class="mb-3">
                                                <?php foreach($allTags as $tag): ?>
                                                <div class="custom-control custom-checkbox custom-control-inline">
                                                    <input type="checkbox" class="custom-control-input" id="tag_<?php echo $tag['id']; ?>" name="tags[]" value="<?php echo $tag['id']; ?>" <?php echo in_array($tag['id'], $userTagIds) ? 'checked' : ''; ?>>
                                                    <label class="custom-control-label" for="tag_<?php echo $tag['id']; ?>"><?php echo htmlspecialchars($tag['name']); ?></label>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="input-group">
                                            <input type="text" class="form-control" name="new_tag" placeholder="新增標籤">
                                            <div class="input-group-append">
                                                <span class="input-group-text"><i class="fas fa-plus"></i></span>
                                            </div>
                                        </div>
                                        <small class="form-text text-muted">您的偏好標籤將顯示在導航欄，幫助您更容易找到喜愛的食物</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">保存設定</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
