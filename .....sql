-- Створення бази даних (якщо ще не створена)
CREATE DATABASE IF NOT EXISTS `beautystore` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `beautystore`;

-- 1. Таблиця адміністраторів
CREATE TABLE `Admin` (
  `admin_id` int NOT NULL AUTO_INCREMENT,
  `admin_username` varchar(50) NOT NULL,
  `admin_password` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Таблиця клієнтів
CREATE TABLE `customer` (
  `customer_id` int NOT NULL AUTO_INCREMENT,
  `customer_username` varchar(50) DEFAULT NULL,
  `customer_password` varchar(255) DEFAULT NULL,
  `discount` tinyint(1) DEFAULT '0',
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `address` varchar(255) NOT NULL,
  `gender` enum('Чоловік','Жінка','Інше') DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `phone_number` varchar(13) NOT NULL,
  PRIMARY KEY (`customer_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



-- 3. Таблиця товарів
CREATE TABLE `product` (
  `product_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `sale_price` decimal(10,2) DEFAULT NULL,
  `is_sale` tinyint(1) DEFAULT '0',
  `category` varchar(50) NOT NULL,
  `subcategory` varchar(100) DEFAULT NULL,
  `manufacturer` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `stock` int NOT NULL,
  `color` varchar(50) DEFAULT NULL,
  `weight` decimal(6,2) DEFAULT NULL,
  `warranty` varchar(50) DEFAULT NULL,
  `badge` varchar(50) DEFAULT NULL,
  `old_price` decimal(10,2) DEFAULT NULL,
  `is_giveaway_participant` tinyint(1) DEFAULT '0',
  `promo_type` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Таблиця зображень
CREATE TABLE `Images` (
  `image_id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `image_url` varchar(300) DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`image_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `Images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Таблиця кошика
CREATE TABLE `Cart` (
  `cart_id` int NOT NULL AUTO_INCREMENT,
  `customer_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `added_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cart_id`),
  CONSTRAINT `Cart_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE,
  CONSTRAINT `Cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Таблиця замовлень
CREATE TABLE `orders` (
  `order_id` int NOT NULL AUTO_INCREMENT,
  `customer_id` int DEFAULT NULL,
  `order_date` datetime NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` varchar(50) DEFAULT 'Прийнято',
  PRIMARY KEY (`order_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Деталі замовлень
CREATE TABLE `Order_Details` (
  `order_details_id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`order_details_id`),
  CONSTRAINT `fk_details_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_details_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Характеристики (ВИПРАВЛЕНО PHP "Deprecated" сміття)
CREATE TABLE `characteristics` (
  `characteristic_id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `characteristic_name` varchar(100) NOT NULL,
  `characteristic_value` varchar(255) NOT NULL,
  `group_name` varchar(255) DEFAULT 'Загальне',
  PRIMARY KEY (`characteristic_id`),
  CONSTRAINT `characteristics_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `characteristics` (`product_id`, `characteristic_name`, `characteristic_value`, `group_name`) VALUES 
(46, 'Рік випуску', '2018', 'Основні'),
(46, 'Тип', 'Східні, Квіткові', 'Аромат'),
(2, 'Тип шкіри', 'Суха', 'Параметри'),
(3, 'Ефект', 'Обʼєм', 'Результат');

-- 9. Список бажань
CREATE TABLE `Wishlist` (
  `wishlist_id` int NOT NULL AUTO_INCREMENT,
  `customer_id` int NOT NULL,
  `product_id` int NOT NULL,
  `added_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`wishlist_id`),
  UNIQUE KEY `unique_customer_product` (`customer_id`,`product_id`),
  CONSTRAINT `fk_wishlist_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_wishlist_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Підтримка та відгуки
CREATE TABLE `Support` (
  `support_id` int NOT NULL AUTO_INCREMENT,
  `customer_id` int NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `submitted_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('new','pending','resolved','closed') DEFAULT 'new',
  `admin_reply` text,
  PRIMARY KEY (`support_id`),
  CONSTRAINT `Support_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `Reviews` (
  `review_id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `customer_id` int NOT NULL,
  `rating` int NOT NULL,
  `comment` text,
  `review_date` datetime NOT NULL,
  PRIMARY KEY (`review_id`),
  CONSTRAINT `Reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`) ON DELETE CASCADE,
  CONSTRAINT `Reviews_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
