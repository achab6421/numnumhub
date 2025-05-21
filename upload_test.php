<?php
// 簡單的文件上傳診斷工具
require_once 'includes/init.php';

// 確保用戶已登入
if (!isLoggedIn()) {
    redirect('login');
}

$uploadOk = false;
$message = '';
$debugInfo = [];

// 獲取 PHP 配置信息
$debugInfo['post_max_size'] = ini_get('post_max_size');
$debugInfo['upload_max_filesize'] = ini_get('upload_max_filesize');
$debugInfo['max_file_uploads'] = ini_get('max_file_uploads');
$debugInfo['max_execution_time'] = ini_get('max_execution_time');
$debugInfo['memory_limit'] = ini_get('memory_limit');
$debugInfo['file_uploads'] = ini_get('file_uploads');
$debugInfo['upload_tmp_dir'] = ini_get('upload_tmp_dir');

// 檢查上傳目錄
$upload_dir = __DIR__ . '/uploads/test';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}
$debugInfo['upload_dir_exists'] = file_exists($upload_dir) ? 'Yes' : 'No';
$debugInfo['upload_dir_writable'] = is_writable($upload_dir) ? 'Yes' : 'No';

// 處理文件上傳
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_file'])) {
    if ($_FILES['test_file']['error'] === 0) {
        $file_name = $_FILES['test_file']['name'];
        $file_tmp = $_FILES['test_file']['tmp_name'];
        $file_dest = $upload_dir . '/' . $file_name;
        
        // 嘗試移動上傳的文件
        if (move_uploaded_file($file_tmp, $file_dest)) {
            $uploadOk = true;
            $message = '文件上傳成功！';
        } else {
            $message = '無法移動上傳的文件。';
        }
    } else {
        $message = '文件上傳錯誤：' . $_FILES['test_file']['error'];
    }
    
    // 添加文件信息到調試數據
    $debugInfo['file_uploaded'] = $_FILES['test_file'];
}

$pageTitle = '文件上傳診斷';
include_once 'includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">文件上傳測試工具</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo $uploadOk ? 'success' : 'danger'; ?>">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" enctype="multipart/form-data" action="">
                        <div class="form-group">
                            <label for="test_file">選擇要上傳的測試文件</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="test_file" name="test_file" required>
                                <label class="custom-file-label" for="test_file">選擇文件...</label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">測試上傳</button>
                    </form>

                    <hr>

                    <h5>PHP 上傳配置信息</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <tbody>
                                <?php foreach($debugInfo as $key => $value): ?>
                                    <tr>
                                        <th><?php echo htmlspecialchars($key); ?></th>
                                        <td>
                                            <?php 
                                            if (is_array($value)) {
                                                echo '<pre>' . print_r($value, true) . '</pre>';
                                            } else {
                                                echo htmlspecialchars($value);
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="text-center mt-3">
                        <a href="<?php echo url('restaurants'); ?>" class="btn btn-secondary">返回餐廳管理</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// 更新文件輸入標籤
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.querySelector('.custom-file-input');
    fileInput.addEventListener('change', function(e) {
        const fileName = this.files[0]?.name || '選擇文件...';
        const label = this.nextElementSibling;
        label.textContent = fileName;
    });
});
</script>

<?php include_once 'includes/footer.php'; ?>
