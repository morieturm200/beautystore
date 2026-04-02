
CREATE TABLE `Admin` (
  `admin_id` int NOT NULL AUTO_INCREMENT,
  `admin_username` varchar(50) NOT NULL,
  `admin_password` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `email` (`email`)
);

CREATE TABLE `Cart` (
  `cart_id` int NOT NULL AUTO_INCREMENT,
  `customer_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `added_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cart_id`),
  FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`) ON DELETE CASCADE
);

CREATE TABLE `Images` (
  `image_id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `image_url` varchar(300) DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`image_id`),
  FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`) ON DELETE CASCADE
);

CREATE TABLE `Invoices` (
  `invoice_id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `invoice_no` varchar(20) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`invoice_id`),
  UNIQUE KEY `invoice_no` (`invoice_no`),
  FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE
);

CREATE TABLE `Order_Details` (
  `order_details_id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`order_details_id`),
  FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`) ON DELETE CASCADE
);


CREATE TABLE `Reviews` (
  `review_id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `customer_id` int NOT NULL,
  `rating` int NOT NULL,
  `comment` text,
  `review_date` datetime NOT NULL,
  `admin_reply` text,
  PRIMARY KEY (`review_id`),
  FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`) ON DELETE CASCADE,
  FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE,
  CHECK (`rating` between 1 and 5)
);

CREATE TABLE `Support` (
  `support_id` int NOT NULL AUTO_INCREMENT,
  `customer_id` int NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `submitted_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('new','pending','resolved','closed') DEFAULT 'new',
  `admin_reply` text,
  PRIMARY KEY (`support_id`),
  FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE
);

CREATE TABLE `Wishlist` (
  `wishlist_id` int NOT NULL AUTO_INCREMENT,
  `customer_id` int NOT NULL,
  `product_id` int NOT NULL,
  `added_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`wishlist_id`),
  UNIQUE KEY `unique_customer_product` (`customer_id`,`product_id`),
  FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`) ON DELETE CASCADE
);

-- Таблиця: characteristics
CREATE TABLE `characteristics` (
  `characteristic_id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `characteristic_name` varchar(100) NOT NULL,
  `characteristic_value` varchar(255) NOT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `group_name` varchar(255) DEFAULT NULL,
  `sort_order` int DEFAULT '0',
  `characteristic_icon` varchar(50) DEFAULT NULL,
  `is_visible_in_short` tinyint(1) DEFAULT '0',
  `is_filterable` tinyint(1) DEFAULT '1',
  `description_long` text,
  PRIMARY KEY (`characteristic_id`),
  FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`) ON DELETE CASCADE
);

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
);

CREATE TABLE `orders` (
  `order_id` int NOT NULL AUTO_INCREMENT,
  `customer_id` int DEFAULT NULL,
  `order_date` datetime NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` varchar(50) DEFAULT 'Прийнято',
  PRIMARY KEY (`order_id`),
  FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE SET NULL
);

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
);
