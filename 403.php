<?php 
$pageTitle = "403 禁止訪問";
include 'includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <div class="error-template">
                <h1>很抱歉！</h1>
                <h2>403 - 禁止訪問</h2>
                <div class="error-details my-4">
                    您沒有權限訪問此頁面。
                </div>
                <div class="error-actions">
                    <a href="<?php echo url('index'); ?>" class="btn btn-primary btn-lg">
                        <i class="fa fa-home"></i> 回到首頁
                    </a>
                    <?php if (!isLoggedIn()): ?>
                    <a href="<?php echo url('login'); ?>" class="btn btn-success btn-lg">
                        <i class="fa fa-sign-in"></i> 登入
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
