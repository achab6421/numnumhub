<?php
// 編輯餐廳頁面
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../tags/functions.php';

// 確保用戶已登入
if (!isLoggedIn()) {
    redirect('login');
}

// 獲取餐廳ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 檢查餐廳是否存在
$restaurant = getRestaurant($id);
if (!$restaurant) {
    setFlashMessage('找不到指定的餐廳', 'danger');
    redirect('restaurants');
}

// 檢查用戶是否有權限編輯此餐廳
// 注意：這裡改為使用canManageRestaurant函數來檢查
if (!canManageRestaurant($id, $_SESSION['user_id'])) {
    setFlashMessage('您沒有權限編輯此餐廳', 'danger');
    redirect('restaurants');
}

// 獲取當前使用者ID
$userId = $_SESSION['user_id'];

// 修改為只獲取使用者自己的標籤
// 原程式碼可能是: $tagOptions = getAllTags();
$tagOptions = getTags($userId);

// 獲取餐廳已有標籤
$restaurantTags = getRestaurantTags($id);
$restaurantTagIds = array_column($restaurantTags, 'id');

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
    $latitude = isset($_POST['latitude']) ? (float)$_POST['latitude'] : null;
    $longitude = isset($_POST['longitude']) ? (float)$_POST['longitude'] : null;
    $tagIds = isset($_POST['tags']) ? $_POST['tags'] : [];
    
    // 處理新增標籤
    $newTag = trim($_POST['new_tag'] ?? '');
    if (!empty($newTag)) {
        $tagResult = createTag($newTag);
        if ($tagResult['success'] && isset($tagResult['id'])) {
            $tagIds[] = $tagResult['id'];
        }
    }
    
    // 基本驗證
    if (empty($name)) {
        $error = '餐廳名稱不能為空';
    } else {
        // 更新餐廳數據
        $restaurantData = [
            'name' => $name,
            'address' => $address,
            'phone' => $phone,
            'link' => $link,
            'note' => $note,
            'latitude' => $latitude,
            'longitude' => $longitude
        ];
        
        // 嘗試更新餐廳
        $result = updateRestaurant($id, $restaurantData);
        
        if ($result['success']) {
            // 更新餐廳標籤
            updateRestaurantTags($id, $tagIds);
            
            $success = $result['message'];
            // 重新獲取更新後的餐廳數據
            $restaurant = getRestaurant($id);
            $restaurantTags = getRestaurantTags($id);
            $restaurantTagIds = array_column($restaurantTags, 'id');
        } else {
            $error = $result['message'];
        }
    }
}

// 設置頁面標題
$pageTitle = "編輯餐廳";
include_once __DIR__ . '/../includes/header.php';
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url('restaurants'); ?>">餐廳管理</a></li>
        <li class="breadcrumb-item active" aria-current="page">編輯餐廳</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="m-0">編輯餐廳</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form id="restaurantForm" action="<?php echo url('edit-restaurant', ['id' => $id]); ?>" method="post">
                    <div class="form-group">
                        <label for="name">餐廳名稱 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($restaurant['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">地址</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($restaurant['address'] ?? ''); ?>" placeholder="例如：雲林縣 虎尾 文化路 64號">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary" id="geocodeBtn">
                                    <i class="fas fa-map-marker-alt"></i> 獲取經緯度
                                </button>
                            </div>
                        </div>
                        <small class="form-text text-muted" id="geocodeStatus">
                            <?php if (isset($restaurant['latitude']) && isset($restaurant['longitude'])): ?>
                                目前經緯度: <?php echo number_format($restaurant['latitude'], 6); ?>, <?php echo number_format($restaurant['longitude'], 6); ?>
                            <?php else: ?>
                                請使用格式「縣市 區域 路名 門牌號」以提高準確度
                            <?php endif; ?>
                        </small>
                    </div>
                    
                    <!-- 隱藏的經緯度輸入欄位 -->
                    <input type="hidden" id="latitude" name="latitude" value="<?php echo $restaurant['latitude'] ?? ''; ?>">
                    <input type="hidden" id="longitude" name="longitude" value="<?php echo $restaurant['longitude'] ?? ''; ?>">
                    
                    <!-- 地圖預覽區域 -->
                    <div class="form-group">
                        <div id="mapPreview" style="height: 300px; <?php echo (isset($restaurant['latitude']) && isset($restaurant['longitude'])) ? '' : 'display: none;'; ?>" class="mb-3 border rounded"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">電話</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($restaurant['phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="link">店家連結</label>
                        <input type="url" class="form-control" id="link" name="link" value="<?php echo htmlspecialchars($restaurant['link'] ?? ''); ?>" placeholder="https://">
                        <small class="form-text text-muted">輸入餐廳官網、外送平台或社群媒體連結</small>
                    </div>
                    
                    <div class="form-group">
                        <label>標籤</label>
                        <div class="card">
                            <div class="card-body">
                                <?php if (!empty($tagOptions)): ?>
                                    <div class="mb-3">
                                        <?php foreach($tagOptions as $tag): ?>
                                        <div class="custom-control custom-checkbox custom-control-inline">
                                            <input type="checkbox" class="custom-control-input" id="tag_<?php echo $tag['id']; ?>" name="tags[]" value="<?php echo $tag['id']; ?>" <?php echo in_array($tag['id'], $restaurantTagIds) ? 'checked' : ''; ?>>
                                            <label class="custom-control-label" for="tag_<?php echo $tag['id']; ?>"><?php echo htmlspecialchars($tag['name']); ?></label>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="new_tag" placeholder="新增標籤">
                                    <div class="input-group-append">
                                        <span class="input-group-text"><i class="fas fa-plus"></i></span>
                                    </div>
                                </div>
                                <small class="form-text text-muted">新增的標籤將在保存餐廳時一併創建</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="note">備註</label>
                        <textarea class="form-control" id="note" name="note" rows="3"><?php echo htmlspecialchars($restaurant['note'] ?? ''); ?></textarea>
                        <small class="form-text text-muted">可填寫營業時間、菜單連結等資訊</small>
                    </div>
                    
                    <div class="form-group text-center">
                        <button type="submit" class="btn btn-primary">更新餐廳</button>
                        <a href="<?php echo url('restaurants'); ?>" class="btn btn-secondary">返回列表</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 引入 Leaflet CSS 和 JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 初始化變數
    let map;
    let marker;
    const mapPreview = document.getElementById('mapPreview');
    const addressInput = document.getElementById('address');
    const geocodeBtn = document.getElementById('geocodeBtn');
    const latitudeInput = document.getElementById('latitude');
    const longitudeInput = document.getElementById('longitude');
    const geocodeStatus = document.getElementById('geocodeStatus');
    
    // 如果已有經緯度，初始化地圖
    const initialLat = <?php echo isset($restaurant['latitude']) ? $restaurant['latitude'] : 'null'; ?>;
    const initialLng = <?php echo isset($restaurant['longitude']) ? $restaurant['longitude'] : 'null'; ?>;
    
    if (initialLat && initialLng) {
        initMap(initialLat, initialLng);
    }
    
    // 初始化地圖
    function initMap(lat, lng) {
        // 如果地圖尚未初始化
        if (!map) {
            mapPreview.style.display = 'block';
            map = L.map('mapPreview').setView([lat, lng], 15);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);
            
            // 創建標記
            marker = L.marker([lat, lng], {
                draggable: true // 允許拖動
            }).addTo(map);
            
            // 拖動結束時更新經緯度
            marker.on('dragend', function() {
                const position = marker.getLatLng();
                updateCoordinates(position.lat, position.lng);
            });
        } else {
            // 更新地圖視圖和標記位置
            map.setView([lat, lng], 15);
            marker.setLatLng([lat, lng]);
        }
    }
    
    // 更新經緯度輸入
    function updateCoordinates(lat, lng) {
        latitudeInput.value = lat;
        longitudeInput.value = lng;
        geocodeStatus.innerHTML = `經緯度已更新: ${lat.toFixed(6)}, ${lng.toFixed(6)}`;
        geocodeStatus.className = 'form-text text-success';
    }
    
    // 點擊獲取經緯度按鈕
    geocodeBtn.addEventListener('click', function() {
        const address = addressInput.value.trim();
        
        if (!address) {
            geocodeStatus.innerHTML = '請先輸入地址';
            geocodeStatus.className = 'form-text text-danger';
            return;
        }
        
        // 檢查地址格式並添加台灣為搜尋範圍
        let searchAddress = address;
        if (!address.includes('台灣') && !address.includes('Taiwan')) {
            searchAddress = '台灣 ' + address;
        }
        
        geocodeStatus.innerHTML = '正在獲取經緯度...';
        geocodeStatus.className = 'form-text text-info';
        
        // 使用 Nominatim API 進行地理編碼，提高限制與格式化
        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(searchAddress)}&limit=1&countrycodes=tw`)
            .then(response => response.json())
            .then(data => {
                if (data && data.length > 0) {
                    const lat = parseFloat(data[0].lat);
                    const lng = parseFloat(data[0].lon);
                    
                    // 更新經緯度輸入
                    updateCoordinates(lat, lng);
                    
                    // 初始化或更新地圖
                    initMap(lat, lng);
                    
                    // 顯示找到的完整地址
                    if (data[0].display_name) {
                        geocodeStatus.innerHTML = `經緯度已更新: ${lat.toFixed(6)}, ${lng.toFixed(6)}<br>找到地址: ${data[0].display_name}`;
                    }
                } else {
                    geocodeStatus.innerHTML = '找不到該地址的經緯度，請嘗試使用格式「縣市 區域 路名 門牌號」';
                    geocodeStatus.className = 'form-text text-danger';
                }
            })
            .catch(error => {
                console.error('地理編碼錯誤:', error);
                geocodeStatus.innerHTML = '獲取經緯度時發生錯誤，請稍後再試';
                geocodeStatus.className = 'form-text text-danger';
            });
    });
    
    // 表單提交前檢查
    document.getElementById('restaurantForm').addEventListener('submit', function(e) {
        const address = addressInput.value.trim();
        const lat = latitudeInput.value.trim();
        const lng = longitudeInput.value.trim();
        
        // 如果有地址但沒有經緯度，嘗試自動獲取
        if (address && (!lat || !lng)) {
            e.preventDefault(); // 阻止表單提交
            geocodeBtn.click(); // 觸發地理編碼
            
            // 顯示提示
            Swal.fire({
                title: '正在獲取經緯度',
                text: '請稍候，正在為您的地址獲取經緯度',
                icon: 'info',
                showConfirmButton: false,
                allowOutsideClick: false
            });
            
            // 2秒後關閉提示並繼續提交
            setTimeout(() => {
                Swal.close();
                if (latitudeInput.value && longitudeInput.value) {
                    this.submit(); // 經緯度已獲取，繼續提交
                } else {
                    Swal.fire({
                        title: '警告',
                        text: '無法獲取地址經緯度，是否繼續更新餐廳？',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: '是，繼續更新',
                        cancelButtonText: '否，返回修改'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            this.submit(); // 用戶確認繼續
                        }
                    });
                }
            }, 2000);
        }
    });
    
    // 新增地址格式轉換函數
    function formatTaiwanAddress(address) {
        // 移除郵遞區號 (3-6位數字開頭)
        address = address.replace(/^(\d{3,6})/, '');
        
        // 台灣地址格式轉換規則
        const countyPattern = /(台北市|臺北市|新北市|桃園市|台中市|臺中市|台南市|臺南市|高雄市|基隆市|新竹市|嘉義市|新竹縣|苗栗縣|彰化縣|南投縣|雲林縣|嘉義縣|屏東縣|宜蘭縣|花蓮縣|台東縣|臺東縣|澎湖縣|金門縣|連江縣)/;
        const districtPattern = /(中正區|大同區|中山區|松山區|大安區|萬華區|信義區|士林區|北投區|內湖區|南港區|文山區|板橋區|新莊區|中和區|永和區|土城區|樹林區|三峽區|鶯歌區|三重區|蘆洲區|五股區|泰山區|林口區|八里區|淡水區|三芝區|石門區|金山區|萬里區|汐止區|瑞芳區|貢寮區|平溪區|雙溪區|新店區|深坑區|石碇區|坪林區|烏來區|桃園區|中壢區|平鎮區|八德區|楊梅區|蘆竹區|大溪區|龍潭區|龜山區|大園區|觀音區|新屋區|復興區|中區|東區|南區|西區|北區|北屯區|西屯區|南屯區|太平區|大里區|霧峰區|烏日區|豐原區|后里區|石岡區|東勢區|和平區|新社區|潭子區|大雅區|神岡區|大肚區|沙鹿區|龍井區|梧棲區|清水區|大甲區|外埔區|大安區|中西區|安平區|安南區|東區|南區|北區|白河區|後壁區|鹽水區|新營區|柳營區|東山區|將軍區|學甲區|北門區|下營區|六甲區|官田區|麻豆區|佳里區|善化區|新市區|安定區|山上區|左鎮區|仁德區|歸仁區|關廟區|龍崎區|永康區|新化區|楠西區|玉井區|南化區|西港區|七股區|將軍區|佳里區|學甲區|北門區|新營區|後壁區|白河區|東山區|六甲區|下營區|柳營區|鹽水區|善化區|大內區|山上區|新市區|安定區|楠西區|玉井區|左鎮區|南化區|仁德區|歸仁區|關廟區|龍崎區|永康區|東區|南區|前鎮區|旗津區|鼓山區|三民區|鹽埕區|前金區|新興區|苓雅區|鹽埕區|前金區|新興區|苓雅區|三民區|鼓山區|前鎮區|旗津區|小港區|左營區|仁武區|大社區|岡山區|路竹區|阿蓮區|田寮區|燕巢區|橋頭區|梓官區|彌陀區|永安區|湖內區|茄萣區|旗山區|美濃區|內門區|杉林區|甲仙區|六龜區|茂林區|桃源區|那瑪夏區|中正區|信義區|仁愛區|中山區|安樂區|暖暖區|七堵區|東區|北區|香山區|東區|西區|太保市|朴子市|布袋鎮|大林鎮|民雄鄉|溪口鄉|新港鄉|六腳鄉|東石鄉|義竹鄉|鹿草鄉|水上鄉|中埔鄉|竹崎鄉|梅山鄉|番路鄉|大埔鄉|阿里山鄉|竹北市|竹東鎮|新埔鎮|關西鎮|湖口鄉|新豐鄉|峨眉鄉|寶山鄉|北埔鄉|芎林鄉|橫山鄉|尖石鄉|五峰鄉|苗栗市|頭份市|竹南鎮|後龍鎮|通霄鎮|苑裡鎮|卓蘭鎮|造橋鄉|西湖鄉|頭屋鄉|公館鄉|銅鑼鄉|三義鄉|大湖鄉|獅潭鄉|三灣鄉|南庄鄉|泰安鄉|彰化市|員林市|和美鎮|鹿港鎮|溪湖鎮|二林鎮|田中鎮|北斗鎮|花壇鄉|芬園鄉|大村鄉|永靖鄉|伸港鄉|線西鄉|福興鄉|秀水鄉|埔心鄉|埔鹽鄉|大城鄉|芳苑鄉|竹塘鄉|社頭鄉|二水鄉|田尾鄉|埤頭鄉|溪州鄉|南投市|埔里鎮|草屯鎮|竹山鎮|集集鎮|名間鄉|鹿谷鄉|中寮鄉|魚池鄉|國姓鄉|水里鄉|信義鄉|仁愛鄉|斗六市|斗南鎮|虎尾鎮|西螺鎮|土庫鎮|北港鎮|莿桐鄉|林內鄉|古坑鄉|大埤鄉|崙背鄉|二崙鄉|麥寮鄉|臺西鄉|東勢鄉|褒忠鄉|四湖鄉|口湖鄉|水林鄉|元長鄉)/;
        const streetPattern = /(.+?(路|街|道|大道|巷|弄|區|段))/;
        const numberPattern = /(\d+號)/;
        
        let county = '';
        let district = '';
        let street = '';
        let number = '';
        
        // 提取縣市
        const countyMatch = address.match(countyPattern);
        if (countyMatch) {
            county = countyMatch[1];
            address = address.substring(countyMatch[0].length);
        }
        
        // 提取鄉鎮市區
        const districtMatch = address.match(districtPattern);
        if (districtMatch) {
            district = districtMatch[1];
            address = address.substring(districtMatch[0].length);
        }
        
        // 提取街道
        const streetMatch = address.match(streetPattern);
        if (streetMatch) {
            street = streetMatch[1];
            address = address.substring(streetMatch[0].length);
        }
        
        // 提取門牌號碼
        const numberMatch = address.match(numberPattern);
        if (numberMatch) {
            number = numberMatch[1];
        }
        
        // 縮減鄉鎮市區名稱，只保留主要名稱
        if (district) {
            district = district.replace(/(市|鎮|區|鄉)$/, '');
        }
        
        // 合成為適合地理編碼的格式
        const formattedAddress = [county, district, street, number].filter(Boolean).join(' ');
        return formattedAddress;
    }
    
    // 地址輸入欄位失去焦點時自動格式化
    addressInput.addEventListener('blur', function() {
        const address = addressInput.value.trim();
        if (address && /^(?:\d{3,6})?[^\s]+(?:縣|市)/.test(address)) {
            const formattedAddress = formatTaiwanAddress(address);
            addressInput.value = formattedAddress;
            geocodeStatus.innerHTML = `已轉換地址格式為: ${formattedAddress}`;
            geocodeStatus.className = 'form-text text-success';
        }
    });
    
    // 修改點擊獲取經緯度按鈕邏輯
    geocodeBtn.addEventListener('click', function() {
        const address = addressInput.value.trim();
        
        if (!address) {
            geocodeStatus.innerHTML = '請先輸入地址';
            geocodeStatus.className = 'form-text text-danger';
            return;
        }
        
        // 先檢查地址是否為標準台灣地址格式，若是則自動轉換
        if (/^(?:\d{3,6})?[^\s]+(?:縣|市)/.test(address) && !/\s/.test(address)) {
            const formattedAddress = formatTaiwanAddress(address);
            addressInput.value = formattedAddress;
            geocodeStatus.innerHTML = `已轉換地址格式為: ${formattedAddress}`;
        }
        
        // 檢查地址格式並添加台灣為搜尋範圍
        let searchAddress = address;
        if (!address.includes('台灣') && !address.includes('Taiwan')) {
            searchAddress = '台灣 ' + address;
        }
        
        geocodeStatus.innerHTML = '正在獲取經緯度...';
        geocodeStatus.className = 'form-text text-info';
        
        // 使用 Nominatim API 進行地理編碼，提高限制與格式化
        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(searchAddress)}&limit=1&countrycodes=tw`)
            .then(response => response.json())
            .then(data => {
                if (data && data.length > 0) {
                    const lat = parseFloat(data[0].lat);
                    const lng = parseFloat(data[0].lon);
                    
                    // 更新經緯度輸入
                    updateCoordinates(lat, lng);
                    
                    // 初始化或更新地圖
                    initMap(lat, lng);
                    
                    // 顯示找到的完整地址
                    if (data[0].display_name) {
                        geocodeStatus.innerHTML = `經緯度已更新: ${lat.toFixed(6)}, ${lng.toFixed(6)}<br>找到地址: ${data[0].display_name}`;
                    }
                } else {
                    geocodeStatus.innerHTML = '找不到該地址的經緯度，請嘗試使用格式「縣市 區域 路名 門牌號」';
                    geocodeStatus.className = 'form-text text-danger';
                }
            })
            .catch(error => {
                console.error('地理編碼錯誤:', error);
                geocodeStatus.innerHTML = '獲取經緯度時發生錯誤，請稍後再試';
                geocodeStatus.className = 'form-text text-danger';
            });
    });
});
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
