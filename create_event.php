<?php
// 建立活動頁面
require_once 'includes/init.php';
require_once 'restaurants/functions.php';

// 確保用戶已登入
if (!isLoggedIn()) {
    redirect('login');
}

// 預設選中的餐廳ID（如果有從URL參數傳入）
$selected_restaurant_id = isset($_GET['restaurant_id']) ? (int)$_GET['restaurant_id'] : 0;

// 處理表單提交
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 表單處理邏輯將在這裡實現
    // ...
}

// 頁面標題
$pageTitle = "建立活動";
include_once 'includes/header.php';
?>

<!-- 引入 Leaflet CSS 和 JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>

<style>
    #map-container {
        position: sticky;
        top: 20px;
        height: 500px;
    }
    #map {
        height: 100%;
        border-radius: 5px;
    }
    .restaurant-item {
        cursor: pointer;
        transition: background-color 0.3s;
    }
    .restaurant-item:hover {
        background-color: #f5f5f5;
    }
    .restaurant-item.active {
        background-color: #e2f0ff;
        border-left: 3px solid #007bff;
    }
    .filter-section {
        margin-bottom: 15px;
        padding: 15px;
        border-radius: 5px;
        background-color: #f8f9fa;
    }
</style>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url('dashboard'); ?>">首頁</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url('events'); ?>">活動管理</a></li>
        <li class="breadcrumb-item active" aria-current="page">建立活動</li>
    </ol>
</nav>

<div class="row">
    <!-- 左側：餐廳清單和活動表單 -->
    <div class="col-md-6">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="m-0">建立新活動</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form id="event-form" action="<?php echo url('create-event'); ?>" method="post">
                    <div class="form-group">
                        <label for="title">活動名稱 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">活動描述</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="date">活動日期 <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="date" name="date" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="time">截止時間 <span class="text-danger">*</span></label>
                        <input type="time" class="form-control" id="time" name="time" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="restaurant_id">選擇餐廳 <span class="text-danger">*</span></label>
                        <input type="hidden" id="restaurant_id" name="restaurant_id" value="<?php echo $selected_restaurant_id; ?>">
                        <div id="selected-restaurant-display" class="alert alert-info <?php echo $selected_restaurant_id ? '' : 'd-none'; ?>">
                            請從下方列表或地圖選擇餐廳
                        </div>
                    </div>
                    
                    <div class="form-group text-center mt-4">
                        <button type="submit" class="btn btn-primary" id="submit-button" disabled>建立活動</button>
                        <a href="<?php echo url('events'); ?>" class="btn btn-secondary">取消</a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card shadow">
            <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                <h5 class="m-0">餐廳列表</h5>
                <a href="<?php echo url('create-restaurant'); ?>" class="btn btn-sm btn-light">
                    <i class="fas fa-plus"></i> 新增餐廳
                </a>
            </div>
            <div class="card-body p-0">
                <div class="filter-section">
                    <div class="input-group">
                        <input type="text" id="restaurant-search" class="form-control" placeholder="搜尋餐廳...">
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="button" id="search-button">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mt-2" id="tag-filters">
                        <!-- 標籤篩選按鈕會動態生成 -->
                    </div>
                </div>
                <div id="restaurant-list" class="list-group list-group-flush">
                    <!-- 餐廳列表會動態生成 -->
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">載入中...</span>
                        </div>
                        <p class="mt-2">載入餐廳資料中...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 右側：地圖顯示 -->
    <div class="col-md-6">
        <div id="map-container" class="card shadow">
            <div id="map"></div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 初始化變數
    let map;
    let markers = {};
    let restaurants = [];
    let selectedRestaurantId = <?php echo $selected_restaurant_id ?: 0; ?>;
    let activeFilters = new Set();
    
    // 初始化地圖
    function initMap() {
        map = L.map('map').setView([25.0330, 121.5654], 13); // 預設顯示台北市中心
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
        
        // 載入餐廳資料
        loadRestaurants();
    }
    
    // 載入餐廳資料
    function loadRestaurants() {
        fetch('<?php echo url(''); ?>get_restaurants.php?user_id=<?php echo $_SESSION['user_id']; ?>')
            .then(response => response.json())
            .then(data => {
                console.log("載入的餐廳數據:", data); // 調試日誌
                restaurants = data.restaurants;
                const tags = data.tags || [];
                
                // 渲染餐廳列表
                renderRestaurantList(restaurants);
                
                // 渲染標籤篩選器
                renderTagFilters(tags);
                
                // 地理編碼所有缺少經緯度的餐廳
                const geocodingPromises = [];
                restaurants.forEach(restaurant => {
                    if ((!restaurant.lat || !restaurant.lng) && restaurant.address) {
                        const promise = geocodeAddress(restaurant);
                        geocodingPromises.push(promise);
                    }
                });
                
                // 等待所有地理編碼完成
                Promise.all(geocodingPromises).then(() => {
                    // 在地圖上添加標記
                    addMarkersToMap(restaurants);
                    
                    // 如果有預選的餐廳，選中它
                    if (selectedRestaurantId > 0) {
                        selectRestaurant(selectedRestaurantId);
                    } else if (restaurants.length > 0) {
                        // 調整地圖以顯示所有餐廳標記
                        fitMapToMarkers();
                    }
                });
            })
            .catch(error => {
                console.error('Error loading restaurants:', error);
                document.getElementById('restaurant-list').innerHTML = 
                    '<div class="alert alert-danger m-3">載入餐廳資料時發生錯誤</div>';
            });
    }
    
    // 渲染餐廳列表
    function renderRestaurantList(restaurantsToRender) {
        const listContainer = document.getElementById('restaurant-list');
        
        if (restaurantsToRender.length === 0) {
            listContainer.innerHTML = '<div class="alert alert-info m-3">沒有符合條件的餐廳</div>';
            return;
        }
        
        let html = '';
        restaurantsToRender.forEach(restaurant => {
            const isActive = restaurant.id === selectedRestaurantId ? 'active' : '';
            html += `
                <div class="restaurant-item list-group-item list-group-item-action ${isActive}" 
                     data-id="${restaurant.id}" 
                     data-lat="${restaurant.lat || ''}" 
                     data-lng="${restaurant.lng || ''}">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-1">${restaurant.name}</h6>
                        ${restaurant.tags && restaurant.tags.length > 0 ? restaurant.tags.map(tag => 
                            `<span class="badge badge-info tag-${tag.id}">${tag.name}</span>`
                        ).join(' ') : ''}
                    </div>
                    <p class="mb-1 small text-muted">${restaurant.address || '無地址資訊'}</p>
                </div>
            `;
        });
        
        listContainer.innerHTML = html;
        
        // 添加點擊事件
        document.querySelectorAll('.restaurant-item').forEach(item => {
            item.addEventListener('click', function() {
                const id = parseInt(this.getAttribute('data-id'));
                selectRestaurant(id);
            });
        });
    }
    
    // 渲染標籤篩選器
    function renderTagFilters(tags) {
        if (!tags || tags.length === 0) return;
        
        const filterContainer = document.getElementById('tag-filters');
        let html = '<div class="btn-group btn-group-sm flex-wrap">';
        
        html += `<button type="button" class="btn btn-outline-secondary mb-1 mr-1 filter-btn" data-filter="all">全部</button>`;
        
        tags.forEach(tag => {
            html += `
                <button type="button" class="btn btn-outline-secondary mb-1 mr-1 filter-btn" 
                        data-filter="tag-${tag.id}">
                    ${tag.name}
                </button>
            `;
        });
        
        html += '</div>';
        filterContainer.innerHTML = html;
        
        // 添加篩選事件
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const filter = this.getAttribute('data-filter');
                
                if (filter === 'all') {
                    // 重置所有篩選
                    activeFilters.clear();
                    document.querySelectorAll('.filter-btn').forEach(b => 
                        b.classList.remove('active'));
                    this.classList.add('active');
                } else {
                    // 移除 "全部" 的選中狀態
                    document.querySelector('.filter-btn[data-filter="all"]')
                        .classList.remove('active');
                    
                    // 切換此篩選的狀態
                    this.classList.toggle('active');
                    
                    if (this.classList.contains('active')) {
                        activeFilters.add(filter);
                    } else {
                        activeFilters.delete(filter);
                    }
                    
                    // 如果沒有活躍的篩選，選中 "全部"
                    if (activeFilters.size === 0) {
                        document.querySelector('.filter-btn[data-filter="all"]')
                            .classList.add('active');
                    }
                }
                
                // 應用篩選
                applyFilters();
            });
        });
        
        // 默認選中 "全部"
        document.querySelector('.filter-btn[data-filter="all"]').classList.add('active');
    }
    
    // 應用篩選
    function applyFilters() {
        const searchText = document.getElementById('restaurant-search').value.toLowerCase();
        
        let filteredRestaurants = restaurants;
        
        // 如果有活躍的標籤篩選
        if (activeFilters.size > 0) {
            filteredRestaurants = filteredRestaurants.filter(restaurant => {
                if (!restaurant.tags || restaurant.tags.length === 0) return false;
                
                return restaurant.tags.some(tag => 
                    activeFilters.has(`tag-${tag.id}`));
            });
        }
        
        // 如果有搜尋文字
        if (searchText) {
            filteredRestaurants = filteredRestaurants.filter(restaurant => 
                restaurant.name.toLowerCase().includes(searchText) || 
                (restaurant.address && restaurant.address.toLowerCase().includes(searchText)));
        }
        
        // 更新顯示
        renderRestaurantList(filteredRestaurants);
    }
    
    // 在地圖上添加標記
    function addMarkersToMap(restaurantsToMark) {
        // 清除舊的標記
        for (let id in markers) {
            if (markers.hasOwnProperty(id)) {
                map.removeLayer(markers[id]);
                delete markers[id];
            }
        }
        
        // 添加新的標記
        let validMarkerCount = 0;
        restaurantsToMark.forEach(restaurant => {
            // 檢查是否有經緯度資訊
            if (!restaurant.lat || !restaurant.lng) {
                console.log(`餐廳 ${restaurant.name} 沒有有效的經緯度`);
                return;
            }
            
            // 創建標記
            const marker = L.marker([restaurant.lat, restaurant.lng])
                .addTo(map)
                .bindPopup(`
                    <strong>${restaurant.name}</strong><br>
                    ${restaurant.address || '無地址資訊'}<br>
                    <button class="btn btn-sm btn-primary mt-2 select-restaurant" 
                            data-id="${restaurant.id}">
                        選擇此餐廳
                    </button>
                `);
            
            // 點擊標記時彈出信息窗
            marker.on('click', function() {
                this.openPopup();
            });
            
            // 存儲標記引用
            markers[restaurant.id] = marker;
            validMarkerCount++;
        });
        
        console.log(`成功添加了 ${validMarkerCount} 個餐廳標記`);
        
        // 如果有有效的標記，調整地圖範圍
        if (validMarkerCount > 0) {
            fitMapToMarkers();
        }
        
        // 添加彈出窗口中的選擇餐廳按鈕點擊事件
        map.on('popupopen', function(e) {
            const selectButtons = document.querySelectorAll('.select-restaurant');
            selectButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const id = parseInt(this.getAttribute('data-id'));
                    selectRestaurant(id);
                    e.popup._source.closePopup();
                });
            });
        });
    }
    
    // 調整地圖範圍以顯示所有標記
    function fitMapToMarkers() {
        const markerArray = Object.values(markers);
        if (markerArray.length > 0) {
            const group = L.featureGroup(markerArray);
            map.fitBounds(group.getBounds().pad(0.1));
        }
    }
    
    // 從地址獲取經緯度
    function geocodeAddress(restaurant) {
        return new Promise((resolve, reject) => {
            // 檢查是否已有經緯度
            if (restaurant.lat && restaurant.lng) {
                resolve(restaurant);
                return;
            }
            
            // 檢查是否有地址
            if (!restaurant.address) {
                console.log(`餐廳 ${restaurant.name} 沒有地址，無法進行地理編碼`);
                resolve(restaurant);
                return;
            }
            
            const address = encodeURIComponent(restaurant.address);
            console.log(`正在為 ${restaurant.name} 進行地理編碼: ${restaurant.address}`);
            
            // 使用 OpenStreetMap Nominatim API 進行地理編碼
            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${address}&limit=1`)
                .then(response => response.json())
                .then(data => {
                    if (data && data.length > 0) {
                        // 更新餐廳經緯度
                        restaurant.lat = parseFloat(data[0].lat);
                        restaurant.lng = parseFloat(data[0].lon);
                        
                        console.log(`已更新 ${restaurant.name} 的經緯度: ${restaurant.lat}, ${restaurant.lng}`);
                        
                        // 需要更新數據庫中的經緯度（這裡只做了前端更新）
                        updateRestaurantCoordinates(restaurant.id, restaurant.lat, restaurant.lng);
                        
                        resolve(restaurant);
                    } else {
                        console.log(`無法為 ${restaurant.name} 找到經緯度`);
                        resolve(restaurant);
                    }
                })
                .catch(error => {
                    console.error(`地理編碼失敗: ${error}`);
                    resolve(restaurant);
                });
        });
    }
    
    // 更新餐廳經緯度到數據庫
    function updateRestaurantCoordinates(restaurantId, lat, lng) {
        fetch('<?php echo url(''); ?>update_restaurant_coordinates_ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${restaurantId}&lat=${lat}&lng=${lng}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log(`餐廳 ${restaurantId} 座標已成功保存到數據庫`);
            } else {
                console.error(`保存座標失敗: ${data.message}`);
            }
        })
        .catch(error => console.error('Error updating coordinates:', error));
    }
    
    // 選擇餐廳
    function selectRestaurant(id) {
        // 更新選中狀態
        selectedRestaurantId = id;
        document.getElementById('restaurant_id').value = id;
        
        // 更新 UI
        document.querySelectorAll('.restaurant-item').forEach(item => {
            item.classList.remove('active');
            if (parseInt(item.getAttribute('data-id')) === id) {
                item.classList.add('active');
                item.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
        
        // 顯示選中的餐廳信息
        const restaurant = restaurants.find(r => r.id === id);
        if (restaurant) {
            document.getElementById('selected-restaurant-display').textContent = 
                `已選擇: ${restaurant.name}`;
            document.getElementById('selected-restaurant-display').classList.remove('d-none');
            
            // 啟用提交按鈕
            document.getElementById('submit-button').disabled = false;
            
            // 如果地圖已初始化且有該餐廳的標記
            if (map && markers[id]) {
                // 將標記置於地圖中心
                map.setView(markers[id].getLatLng(), 15);
                
                // 打開餐廳的彈出窗口
                markers[id].openPopup();
                
                // 添加彈跳動畫效果
                const markerElement = markers[id]._icon;
                if (markerElement) {
                    // 先移除之前的動畫
                    markerElement.classList.remove('marker-bounce');
                    
                    // 強制重繪
                    void markerElement.offsetWidth;
                    
                    // 添加動畫
                    markerElement.classList.add('marker-bounce');
                    
                    // 動畫結束後移除類別
                    setTimeout(() => {
                        markerElement.classList.remove('marker-bounce');
                    }, 1500);
                } else {
                    console.error('找不到標記元素!');
                }
            } else {
                console.log(`未找到餐廳 ${id} 的標記`);
            }
        }
    }
    
    // 搜尋功能
    document.getElementById('search-button').addEventListener('click', function() {
        applyFilters();
    });
    
    document.getElementById('restaurant-search').addEventListener('keyup', function(e) {
        if (e.key === 'Enter') {
            applyFilters();
        }
    });
    
    // CSS 樣式用於標記彈跳效果
    const style = document.createElement('style');
    style.textContent = `
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-20px); }
            60% { transform: translateY(-10px); }
        }
        .marker-bounce {
            animation: bounce 0.8s ease-in-out;
        }
    `;
    document.head.appendChild(style);
    
    // 初始化地圖
    initMap();
});
</script>

<?php include_once 'includes/footer.php'; ?>
