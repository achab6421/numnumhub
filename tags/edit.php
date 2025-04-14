<?php
require_once dirname(__DIR__) . '/header.php';
require_once __DIR__ . '/functions.php';

// 確保用戶已登入
if (!isLoggedIn()) {
    redirect('login');
}

$userId = $_SESSION['user_id'];
$tagId = $_GET['id'] ?? 0;
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

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    // 驗證表單數據
    $errors = [];
    if (empty($name)) {
        $errors[] = "標籤名稱不能為空";
    }
    
    if (empty($errors)) {
        // 注意：修改了函數參數順序
        $result = updateTag($tagId, $name, $userId, $description);
        
        if ($result['success']) {
            $_SESSION['flash_message'] = $result['message'];
            $_SESSION['flash_type'] = 'success';
            redirect('tags');
        } else {
            $errors[] = $result['message'];
        }
    }
}

$tag = getTag($tagId, $userId);
?>

<div class="container">
    <h1 class="my-4">編輯標籤</h1>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <p><?php echo $error; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <form method="post">
        <div class="mb-3">
            <label for="name" class="form-label">標籤名稱</label>
            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($tag['name'] ?? ''); ?>">
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">描述</label>
            <textarea class="form-control" id="description" name="description"><?php echo htmlspecialchars($tag['description'] ?? ''); ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">儲存變更</button>
        <a href="<?php echo url('tags'); ?>" class="btn btn-secondary">取消</a>
    </form>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>