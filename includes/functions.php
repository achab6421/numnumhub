<?php
// 路由和URL相關功能

// 增加調試功能
function debug($data) {
    echo '<pre>';
    print_r($data);
    echo '</pre>';
}

// 動態偵測基礎URL路徑
function getBaseUrl() {
    $scriptName = $_SERVER['SCRIPT_NAME'];
    $dirName = dirname($scriptName);
    
    // 確保路徑以斜線結尾
    if ($dirName != '/') {
        $dirName = $dirName . '/';
    }
    
    // 如果在專案根目錄，則使用 BASE_URL 常數
    if ($dirName == '/' || $dirName == '\\') {
        return BASE_URL;
    }
    
    return $dirName;
}

/**
 * 處理當前請求的路由並提取參數
 * @return array 包含路由名稱和參數的關聯數組
 */
function processRoute() {
    $uri = $_SERVER['REQUEST_URI'];
    
    // 移除查詢字串
    $queryString = '';
    if (strpos($uri, '?') !== false) {
        list($uri, $queryString) = explode('?', $uri, 2);
    }
    
    // 解析查詢字串為參數數組
    $params = [];
    if (!empty($queryString)) {
        parse_str($queryString, $params);
    }
    
    // 移除 BASE_URL 前綴以獲取實際路由
    $baseUrlPath = parse_url(BASE_URL, PHP_URL_PATH);
    $uri = str_replace($baseUrlPath, '', $uri);
    
    // 移除前導和尾隨斜線
    $uri = trim($uri, '/');
    
    // 分割路徑段落，提取主路由和路由參數
    $pathSegments = explode('/', $uri);
    $mainRoute = !empty($pathSegments[0]) ? $pathSegments[0] : 'index';
    
    // 如果有更多路徑段落，將它們作為命名參數添加
    if (count($pathSegments) > 1) {
        // Laravel 風格的路由參數: users/123 => route=users, id=123
        if (count($pathSegments) === 2) {
            // 簡單情況：第二個段落作為 id 參數
            $params['id'] = $pathSegments[1];
        } else {
            // 複雜情況：嘗試將段落解析為鍵值對
            for ($i = 1; $i < count($pathSegments); $i += 2) {
                if (isset($pathSegments[$i + 1])) {
                    $params[$pathSegments[$i]] = $pathSegments[$i + 1];
                } else {
                    $params['param' . $i] = $pathSegments[$i];
                }
            }
        }
    }
    
    return [
        'route' => $mainRoute,
        'params' => $params
    ];
}

// 取得當前請求的路由
function getCurrentRoute() {
    $uri = $_SERVER['REQUEST_URI'];
    
    // 偵錯信息（可選，解決問題後刪除）
    // echo "Original URI: " . $uri . "<br>";
    
    // 移除 BASE_URL 前綴以取得真正的路由
    $baseUrlPath = parse_url(BASE_URL, PHP_URL_PATH);
    $uri = str_replace($baseUrlPath, '', $uri);
    
    // 偵錯信息（可選，解決問題後刪除）
    // echo "After removing base: " . $uri . "<br>";
    
    // 移除查詢字串
    if (strpos($uri, '?') !== false) {
        $uri = substr($uri, 0, strpos($uri, '?'));
    }
    
    // 移除前導和尾隨斜線
    $uri = trim($uri, '/');
    
    // 如果為空字串，設為默認頁面 (首頁)
    if (empty($uri)) {
        return 'index';
    }
    
    // 只取第一個路徑段落作為路由名稱
    $pathParts = explode('/', $uri);
    return $pathParts[0];
}

/**
 * 根據路由名稱和參數生成URL
 * @param string $route 路由名稱
 * @param array $params URL參數
 * @return string 格式化的URL
 */
function url($route, $params = []) {
    // 修正：確保使用正確的BASE_URL常數
    if (!defined('BASE_URL')) {
        define('BASE_URL', '/'+"numnumhub"+'/');
    }
    
    // 基礎URL
    $url = BASE_URL . $route;
    
    // 如果有ID參數，使用 /{id} 格式（RESTful風格）
    if (isset($params['id'])) {
        $id = $params['id'];
        unset($params['id']);
        $url .= '/' . $id;
    }
    
    // 添加其他查詢參數
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    return $url;
}

/**
 * 頁面重導向
 * @param string $route 目標路由
 * @param array $params URL參數
 */
function redirect($route, $params = []) {
    header('Location: ' . url($route, $params));
    exit;
}

/**
 * 檢查路由是否有效
 * @param string $route 路由名稱
 * @return bool 路由是否有效
 */
function isValidRoute($route) {
    global $routes;
    
    // 修正：若$routes未定義，使用緊急備用路由
    if (!isset($routes) || empty($routes)) {
        // 緊急備用路由表
        $routes = [
            'index' => ['file' => 'index.php', 'auth' => false],
            'login' => ['file' => 'login.php', 'auth' => false],
            'register' => ['file' => 'register.php', 'auth' => false],
            'logout' => ['file' => 'logout.php', 'auth' => false],
            'dashboard' => ['file' => 'dashboard.php', 'auth' => true]
        ];
    }
    
    return isset($routes[$route]);
}

/**
 * 檢查路由是否需要認證
 * @param string $route 路由名稱
 * @return bool 是否需要認證
 */
function routeRequiresAuth($route) {
    global $routes;
    
    if (!isValidRoute($route)) {
        return false;
    }
    
    return $routes[$route]['auth'] ?? false;
}

/**
 * 獲取路由對應的檔案路徑
 * @param string $route 路由名稱
 * @return string 對應的檔案路徑
 */
function getRouteFilePath($route) {
    global $routes;
    
    if (!isValidRoute($route)) {
        return '404.php';
    }
    
    $file = $routes[$route]['file'];
    
    // 修正：如果路徑不是絕對路徑，則加上基礎路徑
    if (strpos($file, 'c:\\') !== 0 && strpos($file, '/') !== 0) {
        // 嘗試在pages目錄、根目錄尋找檔案
        $possiblePaths = [
            __DIR__ . '/../pages/' . $file,
            __DIR__ . '/../' . $file
        ];
        
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
    }
    
    return $file;
}

/**
 * 載入路由對應的控制器或視圖
 * @param string $route 路由名稱
 * @param array $params 路由參數
 */
function loadRoute($route, $params = []) {
    // 將參數設為全局變數，便於路由處理程式使用
    if (!empty($params)) {
        foreach ($params as $key => $value) {
            $GLOBALS[$key] = $value;
        }
    }
    
    // 載入對應的檔案
    $filePath = getRouteFilePath($route);
    if (file_exists($filePath)) {
        include $filePath;
    } else {
        show404();
    }
}

/**
 * 顯示404頁面
 */
function show404() {
    header('HTTP/1.0 404 Not Found');
    include BASE_PATH . '/404.php';
    exit;
}

/**
 * 顯示403頁面 (未授權)
 */
function show403() {
    header('HTTP/1.0 403 Forbidden');
    include '403.php';
    exit;
}
?>
