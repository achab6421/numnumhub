<?php
// 只有在沒有活動的 session 時才啟動 session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 在宣告函數前先檢查函數是否已存在
if (!function_exists('registerUser')) {
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
}

// 對其他可能重複的函數也應用相同的模式
if (!function_exists('loginUser')) {
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
}

if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
}

if (!function_exists('logoutUser')) {
    function logoutUser() {
        // 清除所有會話變數
        $_SESSION = array();

        // 如果有設置會話 cookie，則刪除
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // 銷毀會話
        session_destroy();
    }
}

if (!function_exists('ensureAuthenticated')) {
    function ensureAuthenticated() {
        if (!isLoggedIn()) {
            // 保存原始請求頁面，以便登入後重導向
            $_SESSION['redirect_after_login'] = getCurrentRoute();
            redirect('login');
        }
    }
}
?>
