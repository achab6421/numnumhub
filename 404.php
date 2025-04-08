<?php 
$pageTitle = "404 頁面不存在";
include 'includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <div class="error-template">
                <h1>糟糕！</h1>
                <h2>404 - 找不到頁面</h2>
                <div class="error-details my-4">
                    您請求的頁面不存在或已被移除。
                </div>
                <div class="error-actions">
                    <a href="<?php echo url('index'); ?>" class="btn btn-primary btn-lg">
                        <i class="fa fa-home"></i> 回到首頁
                    </a>
                    <?php if (isLoggedIn()): ?>
                    <a href="<?php echo url('dashboard'); ?>" class="btn btn-info btn-lg">
                        <i class="fa fa-user"></i> 我的主頁
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
