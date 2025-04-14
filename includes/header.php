<?php
require_once __DIR__ . '/init.php';

// 添加標籤函數引用
require_once dirname(__DIR__) . '/tags/functions.php';

$isLoggedIn = isLoggedIn();

// 如果是登入用戶，獲取使用者的標籤
$userTags = [];
if ($isLoggedIn && function_exists('getTags')) {
    $userId = $_SESSION['user_id'] ?? null;
    if ($userId) {
        $userTags = getTags($userId);
    }
}

// 確保有頁面標題
if (!isset($pageTitle)) {
    $pageTitle = SITE_NAME;
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo url(''); ?>assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <!-- 修改品牌連結，未登入時導向 index 而非 dashboard -->
            <a class="navbar-brand" href="<?php echo $isLoggedIn ? url('dashboard') : url('index'); ?>">
                <?php echo SITE_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <?php if (!empty($userTags)): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="tagsDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-tags"></i> 我的標籤
                            </a>
                            <div class="dropdown-menu" aria-labelledby="tagsDropdown">
                                <?php foreach($userTags as $tag): ?>
                                <a class="dropdown-item" href="<?php echo url('restaurants', ['tag' => $tag['id']]); ?>">
                                    <?php echo htmlspecialchars($tag['name']); ?>
                                </a>
                                <?php endforeach; ?>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="<?php echo url('user_profile'); ?>#preferences">
                                    <i class="fas fa-cog"></i> 管理標籤
                                </a>
                            </div>
                        </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav ml-auto">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo url('dashboard'); ?>">我的主頁</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo url('restaurants'); ?>">餐廳管理</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo url('events'); ?>">活動管理</a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="profileDropdown">
                                <a class="dropdown-item" href="<?php echo url('user_profile'); ?>">
                                    <i class="fas fa-user"></i> 個人資料
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="<?php echo url('logout'); ?>">
                                    <i class="fas fa-sign-out-alt"></i> 登出
                                </a>
                            </div>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo url('login'); ?>">登入</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo url('register'); ?>">註冊</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container mt-4">
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible fade show">
                <?php echo $_SESSION['flash_message']; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php 
            // 顯示後清除閃存訊息
            unset($_SESSION['flash_message']);
            unset($_SESSION['flash_type']);
            ?>
        <?php endif; ?>
