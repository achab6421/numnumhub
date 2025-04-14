<?php
require_once dirname(__DIR__) . '/header.php';
require_once __DIR__ . '/functions.php';

// 確保用戶已登入
if (!isLoggedIn()) {
    redirect('login');
}

$userId = $_SESSION['user_id'];
// 僅獲取當前使用者的標籤
$userTags = getTags($userId);

// 處理標籤刪除請求
if (isset($_POST['delete_tag']) && isset($_POST['tag_id'])) {
    $tagId = intval($_POST['tag_id']);
    $result = deleteTag($tagId, $userId);
    
    $_SESSION['flash_message'] = $result['message'];
    $_SESSION['flash_type'] = $result['success'] ? 'success' : 'danger';
    
    // 重新導向以避免表單重複提交
    redirect('tags');
}
?>

<div class="container">
    <h1 class="my-4">我的標籤管理</h1>
    
    <div class="mb-4">
        <a href="<?php echo url('create-tag'); ?>" class="btn btn-primary">
            <i class="fas fa-plus"></i> 新增標籤
        </a>
    </div>
    
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">我的標籤</h5>
        </div>
        <div class="card-body">
            <?php if (empty($userTags)): ?>
                <p class="text-muted">您尚未建立任何標籤</p>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($userTags as $tag): ?>
                        <div class="col-md-4 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($tag['name']); ?></h5>
                                    <p class="card-text text-muted"><?php echo htmlspecialchars($tag['description'] ?? ''); ?></p>
                                    
                                    <div class="btn-group">
                                        <a href="<?php echo url('edit-tag', ['id' => $tag['id']]); ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i> 編輯
                                        </a>
                                        
                                        <form method="post" class="d-inline" onsubmit="return confirm('確定要刪除此標籤嗎？');">
                                            <input type="hidden" name="tag_id" value="<?php echo $tag['id']; ?>">
                                            <button type="submit" name="delete_tag" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash"></i> 刪除
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
