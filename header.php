<?php
require_once __DIR__ . '/auth/functions.php';
$isLoggedIn = isLoggedIn();
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NumNumHub - 線上點餐系統</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="/index.php">NumNumHub</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <?php if ($isLoggedIn): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/dashboard.php">我的主頁</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/restaurants.php">餐廳管理</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/events.php">活動管理</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/auth/logout.php">登出</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/auth/login.php">登入</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/auth/register.php">註冊</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container mt-4">
