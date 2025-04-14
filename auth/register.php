<?php
// 會員註冊頁面
require_once dirname(__DIR__) . '/includes/init.php';

// 如果已經登入，跳轉到首頁
if (isLoggedIn()) {
    redirect('index');
}

// 處理註冊表單提交
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // 驗證輸入
    if (empty($name)) {
        $errors[] = '請輸入您的姓名';
    }
    
    if (empty($email)) {
        $errors[] = '請輸入您的電子郵件';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = '請輸入有效的電子郵件地址';
    }
    
    if (empty($password)) {
        $errors[] = '請輸入密碼';
    } elseif (strlen($password) < 6) {
        $errors[] = '密碼至少需要6個字符';
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = '兩次輸入的密碼不一致';
    }
    
    // 如果驗證通過，嘗試註冊
    if (empty($errors)) {
        $result = registerUser($name, $email, $password);
        
        if ($result['success']) {
            // 註冊成功，設置閃存訊息並跳轉到登入頁面
            $_SESSION['flash_message'] = $result['message'];
            $_SESSION['flash_type'] = 'success';
            redirect('login');
        } else {
            $errors[] = $result['message'];
        }
    }
}

// 設置頁面標題
$pageTitle = '會員註冊';
include_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card mt-5">
                <div class="card-header">
                    <h2 class="text-center">會員註冊</h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="<?php echo url('register'); ?>">
                        <div class="form-group">
                            <label for="name">姓名</label>
                            <input type="text" class="form-control" id="name" name="name" required
                                   value="<?php echo htmlspecialchars($name ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">電子郵件</label>
                            <input type="email" class="form-control" id="email" name="email" required
                                   value="<?php echo htmlspecialchars($email ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="password">密碼</label>
                            <input type="password" class="form-control" id="password" name="password" required
                                   minlength="6">
                            <small class="form-text text-muted">密碼至少需要6個字符</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">確認密碼</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary btn-block">註冊</button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center">
                    已有帳號? <a href="<?php echo url('login'); ?>">登入</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once dirname(__DIR__) . '/includes/footer.php'; ?>
