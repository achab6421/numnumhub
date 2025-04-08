<?php
require_once __DIR__ . '/functions.php';

// 如果用戶已登入，重定向到儀表板
if (isLoggedIn()) {
    header("Location: /dashboard.php");
    exit;
}

$error = '';
$success = '';

// 處理註冊表單提交
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirmPassword = trim($_POST['confirm_password']);
    
    // 基本驗證
    if (empty($name) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = "請填寫所有必填欄位";
    } elseif ($password !== $confirmPassword) {
        $error = "兩次輸入的密碼不一致";
    } elseif (strlen($password) < 6) {
        $error = "密碼長度必須至少為6個字符";
    } else {
        $result = registerUser($name, $email, $password);
        if ($result["success"]) {
            $success = $result["message"];
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
            <div class="card-header bg-success text-white">
                <h4 class="m-0">會員註冊</h4>
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
                        <label for="name">姓名</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">電子郵件</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="password">密碼</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <small class="form-text text-muted">密碼長度必須至少為6個字符</small>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">確認密碼</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-success btn-block">註冊</button>
                    </div>
                </form>
                <div class="text-center mt-3">
                    <p>已有帳號？ <a href="/auth/login.php">立即登入</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/footer.php'; ?>
