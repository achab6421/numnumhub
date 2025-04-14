<?php
// 確保包含必要的初始化檔案
require_once 'includes/init.php';

// 設置頁面標題
$pageTitle = '頁面未找到';

// 引入頁面頭部
include_once 'includes/header.php';
?>

<div class="container mt-5 text-center">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card shadow">
                <div class="card-body py-5">
                    <h1 class="display-1 text-danger">404</h1>
                    <h2 class="mb-4">找不到頁面</h2>
                    <p class="lead mb-4">抱歉，您嘗試訪問的頁面不存在或已被移除。</p>
                    
                    <div class="mt-5">
                        
                        <?php if (isLoggedIn()): ?>
                        <a href="<?php echo url('dashboard'); ?>" class="btn btn-secondary btn-lg">
                            <i class="fas fa-user"></i> 回到主頁
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
