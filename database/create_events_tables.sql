-- 活動資料表
CREATE TABLE IF NOT EXISTS `events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL COMMENT '活動名稱',
  `description` text DEFAULT NULL COMMENT '活動描述',
  `restaurant_id` int(11) NOT NULL COMMENT '關聯餐廳ID',
  `creator_id` int(11) NOT NULL COMMENT '建立者ID',
  `deadline` datetime DEFAULT NULL COMMENT '截止時間',
  `is_closed` tinyint(1) DEFAULT 0 COMMENT '是否關閉',
  `share_code` varchar(10) DEFAULT NULL COMMENT '活動分享碼',
  `created_at` datetime NOT NULL COMMENT '建立時間',
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最後更新時間',
  PRIMARY KEY (`id`),
  UNIQUE KEY `share_code` (`share_code`),
  KEY `restaurant_id` (`restaurant_id`),
  KEY `creator_id` (`creator_id`),
  CONSTRAINT `events_restaurant_fk` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `events_creator_fk` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 活動參與者資料表
CREATE TABLE IF NOT EXISTS `event_participants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) NOT NULL COMMENT '活動ID',
  `user_id` int(11) NOT NULL COMMENT '使用者ID',
  `joined_at` datetime NOT NULL COMMENT '加入時間',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_participant` (`event_id`,`user_id`), -- 確保每位使用者只能參加一次
  KEY `event_id` (`event_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `participants_event_fk` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `participants_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 活動留言/討論資料表 (選用)
CREATE TABLE IF NOT EXISTS `event_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) NOT NULL COMMENT '活動ID',
  `user_id` int(11) NOT NULL COMMENT '留言者ID',
  `content` text NOT NULL COMMENT '留言內容',
  `created_at` datetime NOT NULL COMMENT '留言時間',
  PRIMARY KEY (`id`),
  KEY `event_id` (`event_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `comments_event_fk` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comments_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- 活動點餐記錄資料表
CREATE TABLE IF NOT EXISTS `event_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) NOT NULL COMMENT '活動ID',
  `user_id` int(11) NOT NULL COMMENT '點餐者ID',
  `menu_item` varchar(255) NOT NULL COMMENT '餐點名稱',
  `price` decimal(10,2) DEFAULT 0 COMMENT '餐點價格',
  `quantity` int(11) DEFAULT 1 COMMENT '數量',
  `note` varchar(255) DEFAULT NULL COMMENT '備註',
  `is_paid` tinyint(1) DEFAULT 0 COMMENT '是否已付款',
  `created_at` datetime NOT NULL COMMENT '建立時間',
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最後更新時間',
  PRIMARY KEY (`id`),
  KEY `event_id` (`event_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `orders_event_fk` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `orders_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;