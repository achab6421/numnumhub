<?php
// 新增餐廳頁面
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/functions.php';

// 確保用戶已登入
if (!isLoggedIn()) {
    redirect('login');
}

$error = '';
$success = '';

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 獲取並過濾表單數據
    $name = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $link = trim($_POST['link'] ?? '');
    $note = trim($_POST['note'] ?? '');
    
    // 基本驗證
    if (empty($name)) {
        $error = '餐廳名稱不能為空';
    } else {
        // 建立餐廳數據
        $restaurantData = [
            'name' => $name,
            'address' => $address,
            'phone' => $phone,
            'link' => $link,
            'note' => $note,
            'created_by' => $_SESSION['user_id']
        ];
        
        // 嘗試建立餐廳
        $result = createRestaurant($restaurantData);
        
        if ($result['success']) {
            // 設置閃存訊息
            setFlashMessage($result['message'], 'success');
            
            // 重定向到餐廳列表頁面
            redirect('restaurants');
        } else {
            $error = $result['message'];
        }
    }
}

// 設置頁面標題
$pageTitle = "新增餐廳";
include_once __DIR__ . '/../includes/header.php';
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url('restaurants'); ?>">餐廳管理</a></li>
        <li class="breadcrumb-item active" aria-current="page">新增餐廳</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="m-0">新增餐廳</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form action="<?php echo url('create-restaurant'); ?>" method="post">
                    <div class="form-group">
                        <label for="name">餐廳名稱 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">地址</label>
                        <input type="text" class="form-control" id="address" name="address">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">電話</label>
                        <input type="text" class="form-control" id="phone" name="phone">
                    </div>
                    
                    <div class="form-group">
                        <label for="link">店家連結</label>
                        <input type="url" class="form-control" id="link" name="link" placeholder="https://">
                        <small class="form-text text-muted">輸入餐廳官網、外送平台或社群媒體連結</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="note">備註</label>
                        <textarea class="form-control" id="note" name="note" rows="3"></textarea>
                        <small class="form-text text-muted">可填寫營業時間、菜單連結等資訊</small>
                    </div>
                    
                    <div class="form-group text-center">
                        <button type="submit" class="btn btn-primary">儲存餐廳</button>
                        <a href="<?php echo url('restaurants'); ?>" class="btn btn-secondary">取消</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
