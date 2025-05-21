-- 餐廳菜單圖片資料表
CREATE TABLE IF NOT EXISTS `restaurant_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `restaurant_id` int(11) NOT NULL COMMENT '關聯餐廳ID',
  `image_path` varchar(255) NOT NULL COMMENT '圖片路徑',
  `description` varchar(255) DEFAULT NULL COMMENT '圖片說明',
  `is_menu` tinyint(1) DEFAULT 1 COMMENT '是否為菜單圖片',
  `created_at` datetime NOT NULL COMMENT '上傳時間',
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最後更新時間',
  PRIMARY KEY (`id`),
  KEY `restaurant_id` (`restaurant_id`),
  CONSTRAINT `restaurant_images_fk` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
