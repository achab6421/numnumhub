<?php
session_start();
require_once 'config.php';

// 使用者註冊函數
function registerUser($name, $email, $password) {
    global $conn;
    
    // 檢查電子郵件是否已存在
    $checkSql = "SELECT id FROM users WHERE email = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        return ["success" => false, "message" => "該電子郵件已被註冊"];
    }
    
    // 密碼雜湊處理
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // 插入新使用者
    $sql = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $name, $email, $hashedPassword);
    
    if ($stmt->execute()) {
        return ["success" => true, "message" => "註冊成功！請登入"];
    } else {
        return ["success" => false, "message" => "註冊失敗：" . $stmt->error];
    }
}

// 使用者登入函數
function loginUser($email, $password) {
    global $conn;
    
    $sql = "SELECT id, name, email, password FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            // 密碼正確，設置會話
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['logged_in'] = true;
            
            return ["success" => true, "message" => "登入成功！"];
        } else {
            return ["success" => false, "message" => "密碼不正確"];
        }
    } else {
        return ["success" => false, "message" => "查無此用戶"];
    }
}

// 檢查用戶是否已登入
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

// 確保使用者已登入，否則重定向到登入頁面
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}
?>
