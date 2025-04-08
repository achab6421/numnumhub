<?php
require_once __DIR__ . '/functions.php';

// 如果用戶已登入，重定向到儀表板
if (isLoggedIn()) {
    header("Location: /dashboard.php");
    exit;
}

$error = '';
$success = '';

// 處理登入表單提交
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    // 基本驗證
    if (empty($email) || empty($password)) {
        $error = "請填寫所有必填欄位";
    } else {
        $result = loginUser($email, $password);
        if ($result["success"]) {
            $success = $result["message"];
            // 重定向到儀表板
            header("Location: /dashboard.php");
            exit;
        } else {
            $error = $result["message"];
        }
    }
}

include dirname(__DIR__) . '/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="m-0">會員登入</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-group">
                        <label for="email">電子郵件</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="password">密碼</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-block">登入</button>
                    </div>
                </form>
                <div class="text-center mt-3">
                    <p>還沒有帳號？ <a href="/auth/register.php">立即註冊</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/footer.php'; ?>
