-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Apr 29, 2025 at 10:34 PM
-- Server version: 8.3.0
-- PHP Version: 8.2.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `giftstore`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

DROP TABLE IF EXISTS `cart`;
CREATE TABLE IF NOT EXISTS `cart` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `created_at`) VALUES
(1, 8, '2025-04-29 15:15:57'),
(2, 7, '2025-04-29 17:15:44');

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

DROP TABLE IF EXISTS `cart_items`;
CREATE TABLE IF NOT EXISTS `cart_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cart_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `cart_id` (`cart_id`),
  KEY `product_id` (`product_id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `cart_items`
--

INSERT INTO `cart_items` (`id`, `cart_id`, `product_id`, `quantity`) VALUES
(1, 1, 32, 1);

-- --------------------------------------------------------

--
-- Table structure for table `checkout`
--

DROP TABLE IF EXISTS `checkout`;
CREATE TABLE IF NOT EXISTS `checkout` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` varchar(255) NOT NULL,
  `card_name` varchar(100) NOT NULL,
  `card_no` varchar(20) NOT NULL,
  `exp_year` varchar(4) NOT NULL,
  `exp_month` tinyint NOT NULL,
  `cvv` varchar(4) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `checkout`
--

INSERT INTO `checkout` (`id`, `name`, `email`, `phone`, `address`, `card_name`, `card_no`, `exp_year`, `exp_month`, `cvv`, `created_at`) VALUES
(1, 'Faouzi', 'fouzi.slimani75@gmail.com', '0793642323', 'cite 11 dzevzvz', 'giftstore', '8888888888884444', '2025', 15, '325', '2025-04-11 22:33:17');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_name` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` text,
  `total_price` decimal(10,2) DEFAULT NULL,
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `order_status` enum('processing','shipped','cancelled') DEFAULT 'processing',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `phone` varchar(20) DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_name`, `email`, `address`, `total_price`, `payment_status`, `order_status`, `created_at`, `phone`, `user_id`) VALUES
(8, 'ff sli', 'fouzi@gmail.com', 'gggg', 100.00, 'pending', 'cancelled', '2025-04-18 22:48:24', '0793642323', NULL),
(7, 'fou sli', 'fouzi@gmail.com', 'cite 11 decembre boumerdes', 8000.00, 'pending', 'cancelled', '2025-04-18 21:57:01', '0793642323', NULL),
(9, 'ff sli', 'fouzi@gmail.com', 'cite 11 decembre boumerdes', 6000.00, 'pending', 'shipped', '2025-04-22 17:48:30', '0793642323', NULL),
(10, 'Adel Derdiche', 'adelwwe50@gmail.com', '22248', 6000.00, 'pending', 'shipped', '2025-04-24 10:43:32', '0770235689', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `unit_price` decimal(10,2) NOT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `unit_price`, `product_name`) VALUES
(1, 3, 150, 1, 3000.00, 'Casio'),
(2, 3, 78, 1, 599.00, 'Captain'),
(3, 4, 32, 1, 100.00, 'Coin purse'),
(4, 4, 149, 4, 400.00, 'monkey top'),
(5, 5, 149, 1, 400.00, 'monkey top'),
(6, 7, 2, 1, 8000.00, 'watch2'),
(7, 8, 32, 1, 100.00, 'Coin purse'),
(8, 9, 150, 2, 3000.00, 'Casio'),
(9, 10, 150, 2, 3000.00, 'Casio');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
CREATE TABLE IF NOT EXISTS `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int DEFAULT '0',
  `category` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `gift_category` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=151 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `image`, `price`, `stock`, `category`, `description`, `gift_category`) VALUES
(1, 'Watch1', '1.jpg', 10500.00, 0, 'Watches', NULL, 'Gifts for Him'),
(2, 'watch2', '2.jpg', 8000.00, 4, 'Watches', NULL, 'Gifts for Him'),
(3, 'Watch3', '3.jpg', 1499.00, 0, 'Watches', NULL, 'Gifts for Him'),
(4, 'Watch  4', '4.jpg', 1999.00, 0, 'Watches', NULL, 'Gifts for Him'),
(5, 'Watch 5', '5.jpg', 2499.00, 0, 'Watches', NULL, 'Gifts for Him'),
(6, 'Watch 6', '6.jpg', 1299.00, 0, 'Watches', NULL, 'Gifts for Him'),
(7, 'Watch 7', '7.jpg', 6999.00, 0, 'Watches', NULL, 'Gifts for Him'),
(8, 'Watch 8', '8.jpg', 999.00, 0, 'Watches', NULL, 'Gifts for Him'),
(9, 'Watch 9', '9.jpg', 2999.00, 0, 'Watches', NULL, 'Gifts for Him'),
(10, 'Watch 10', '10.jpg', 3599.00, 0, 'Watches', NULL, 'Gifts for Him'),
(11, 'Watch 11', '11.jpg', 899.00, 0, 'Watches', NULL, 'Gifts for Him'),
(12, 'Watch 12', '12.jpg', 1899.00, 0, 'Watches', NULL, 'Gifts for Him'),
(13, 'Watch 13', '13.jpg', 1299.00, 0, 'Watches', NULL, 'Gifts for Him'),
(14, 'Watch 14', '14.jpg', 1399.00, 0, 'Watches', NULL, 'Gifts for Him'),
(15, 'Watch 15', '15.jpg', 1649.00, 0, 'Watches', NULL, 'Gifts for Him'),
(16, 'Watch 16', '16.jpg', 1700.00, 0, 'Watches', NULL, 'Gifts for Him'),
(17, 'Leather wallet', '98.jpg', 800.00, 0, 'Wallets', NULL, 'Gifts for Him'),
(18, 'Clutch wallet', '99.jpg', 600.00, 0, 'Wallets', NULL, 'Gifts for Him'),
(19, 'Reebok wallet', '100.jpg', 1200.00, 0, 'Wallets', NULL, 'Gifts for Him'),
(20, 'Continental wallet', '101.jpg', 2099.00, 0, 'Wallets', NULL, 'Gifts for Him'),
(21, 'Bonia wallet', '102.jpg', 900.00, 0, 'Wallets', NULL, 'Gifts for Him'),
(22, 'Pallas wallet', '103.jpg', 750.00, 0, 'Wallets', NULL, 'Gifts for Him'),
(23, 'Brahmin wallet', '104.jpg', 500.00, 0, 'Wallets', NULL, 'Gifts for Him'),
(24, 'Coach wallet', '105.jpg', 100.00, 0, 'Wallets', NULL, 'Gifts for Him'),
(25, 'calvin klein', '106.jpg', 2500.00, 0, 'Wallets', NULL, 'Gifts for Him'),
(26, 'Monogram wallet', '107.jpg', 850.00, 0, 'Wallets', NULL, 'Gifts for Him'),
(27, 'Checks wallet', '108.jpg', 900.00, 0, 'Wallets', NULL, 'Gifts for Him'),
(28, 'Louis vuitton', '109.jpg', 1200.00, 0, 'Wallets', NULL, 'Gifts for Him'),
(29, 'Red handbag', '110.jpg', 600.00, 0, 'Wallets', NULL, 'Gifts for Him'),
(30, 'Vegan wallet', '111.jpg', 400.00, 0, 'Wallets', NULL, 'Gifts for Him'),
(31, 'Leather handbag', '112.jpg', 550.00, 0, 'Wallets', NULL, 'Gifts for Him'),
(32, 'Coin purse', '113.jpg', 100.00, 3, 'Wallets', NULL, 'Gifts for Him'),
(150, 'Casio', '20250410_1918_Heart-Themed Background_remix_01jrgep2cyes6sdhv3xecyvbn3.png', 3000.00, 5, 'Watches', NULL, 'Gifts for Him'),
(49, 'bracelet', 'bracelet.jpg', 699.00, 0, 'Jewellery', NULL, 'Gifts for Her'),
(50, 'silver pearl earings', '2.jpg', 559.00, 0, 'Jewellery', NULL, 'Gifts for Her'),
(51, 'blue woolen earing', '3.jpg', 59.00, 0, 'Jewellery', NULL, 'Gifts for Her'),
(52, 'peacock necklace', '4.jpg', 499.00, 0, 'Jewellery', NULL, 'Gifts for Her'),
(53, 'butterfly pendant', '5.jpg', 399.00, 0, 'Jewellery', NULL, 'Gifts for Her'),
(54, 'nosering', '6.jpg', 99.00, 0, 'Jewellery', NULL, 'Gifts for Her'),
(55, 'golden bangles', '7.jpg', 1099.00, 0, 'Jewellery', NULL, 'Gifts for Her'),
(56, 'gold plated bangles', '8.jpg', 2099.00, 0, 'Jewellery', NULL, 'Gifts for Her'),
(58, 'diamond earing', '10.jpg', 3050.00, 0, 'Jewellery', NULL, 'Gifts for Her'),
(59, 'Infinity earing', '11.jpg', 650.00, 0, 'Jewellery', NULL, 'Gifts for Her'),
(60, 'Pearl ring', '12.jpg', 999.00, 20, 'Jewellery', NULL, 'Gifts for Her'),
(61, 'kidstable', 'table.jpg', 2000.00, 0, 'Kids', NULL, 'Gifts for Kids'),
(62, 'barbie', 'princess.jpg', 1500.00, 0, 'Kids', NULL, 'Gifts for Kids'),
(63, 'doctorset', 'doctor1.jpg', 1200.00, 0, 'Kids', NULL, 'Gifts for Kids'),
(64, 'jwellarybox', 'jwelary.jpg', 1800.00, 0, 'Kids', NULL, 'Gifts for Kids'),
(65, 'fruit basket', 'fruitbasket.jpg', 800.00, 0, 'Kids', NULL, 'Gifts for Kids'),
(66, 'puzzle', 'puzzle.jpg', 400.00, 0, 'Kids', NULL, 'Gifts for Kids'),
(67, 'spinner', 'spinner.jpg', 350.00, 0, 'Kids', NULL, 'Gifts for Kids'),
(68, 'Toy Car', 'toycar.jpg', 900.00, 0, 'Kids', NULL, 'Gifts for Kids'),
(69, 'Animal Plate', 'animal.jpg', 699.00, 0, 'Kids', NULL, 'Gifts for Kids'),
(70, 'White Board', 'board.jpg', 899.00, 0, 'Kids', NULL, 'Gifts for Kids'),
(71, 'Key Chain', 'keychain.jpg', 449.00, 0, 'Kids', NULL, 'Gifts for Kids'),
(73, 'phonecase1', 'case1.jpg', 499.00, 0, 'PhoneCase', NULL, 'Tech Gifts'),
(74, 'Wooden', 'case2.jpg', 499.00, 0, 'PhoneCase', NULL, 'Tech Gifts'),
(75, 'Marble ', 'case3.jpg', 599.00, 0, 'PhoneCase', NULL, 'Tech Gifts'),
(76, 'Black', 'case4.jpg', 299.00, 0, 'PhoneCase', NULL, 'Tech Gifts'),
(77, 'Pink Case', 'case5.jpg', 555.00, 0, 'PhoneCase', NULL, 'Tech Gifts'),
(78, 'Captain', 'case6.jpg', 599.00, 5, 'PhoneCase', NULL, 'Tech Gifts'),
(79, 'Oval Lamp', 'lamp.jpg', 999.00, 0, 'Home Decor', NULL, 'Home & Decor'),
(80, 'KeyHolder', 'keyholder.jpg', 699.00, 0, 'Home Decor', NULL, 'Home & Decor'),
(81, 'Card', 'card.jpg', 699.00, 0, 'Home Decor', NULL, 'Home & Decor'),
(82, 'Holder', 'candle.jpg', 399.00, 0, 'Home Decor', NULL, 'Home & Decor'),
(83, 'Flowers', 'flowers.jpg', 499.00, 0, 'Home Decor', NULL, 'Home & Decor'),
(84, 'Bonsai', 'bonsai.jpg', 899.00, 0, 'Home Decor', NULL, 'Home & Decor'),
(85, 'Lamp', 'lamp1.jpg', 799.00, 0, 'Home Decor', NULL, 'Home & Decor'),
(86, 'Cycle', 'cycle.jpg', 1999.00, 0, 'Home Decor', NULL, 'Home & Decor'),
(87, 'ShowPiece', 'show.jpg', 699.00, 0, 'Home Decor', NULL, 'Home & Decor'),
(88, 'PortRait', 'port.jpg', 599.00, 0, 'Home Decor', NULL, 'Home & Decor'),
(89, 'Owl Holder', 'owl.jpg', 999.00, 0, 'Home Decor', NULL, 'Home & Decor'),
(90, 'DinnerSet', 'dinner.jpg', 1499.00, 0, 'Home Decor', NULL, 'Home & Decor'),
(91, 'BirdHouse', 'birdhouse.jpg', 499.00, 0, 'Home Decor', NULL, 'Home & Decor'),
(92, 'Night Lamp', 'nightlamp.jpg', 399.00, 0, 'Home Decor', NULL, 'Home & Decor'),
(93, 'PhotoFrame', 'photo.jpg', 499.00, 0, 'Home Decor', NULL, 'Home & Decor'),
(94, 'Frame', 'frame.jpg', 699.00, 0, 'Home Decor', NULL, 'Home & Decor'),
(95, 'kitten mug', '81.jpg', 150.00, 0, 'Crockery', NULL, 'Home & Decor'),
(96, 'kuksa', '82.jpg', 250.00, 0, 'Crockery', NULL, 'Home & Decor'),
(97, '3D mug', '83.jpg', 300.00, 0, 'Crockery', NULL, 'Home & Decor'),
(98, 'wooden mug', '85.jpg', 350.00, 0, 'Crockery', NULL, 'Home & Decor'),
(99, 'Flamingo', '86.jpg', 300.00, 0, 'Crockery', NULL, 'Home & Decor'),
(100, 'Dinnerware', '87.jpg', 700.00, 0, 'Crockery', NULL, 'Home & Decor'),
(101, 'Casserole', '88.jpg', 999.00, 0, 'Crockery', NULL, 'Home & Decor'),
(102, 'Glaze', '89.jpg', 1050.00, 0, 'Crockery', NULL, 'Home & Decor'),
(103, 'Rubik Mug', '90.jpg', 399.00, 0, 'Crockery', NULL, 'Home & Decor'),
(104, 'Pottery Mu', '91.jpg', 300.00, 0, 'Crockery', NULL, 'Home & Decor'),
(105, 'Minion mug', '92.jpg', 450.00, 0, 'Crockery', NULL, 'Home & Decor'),
(107, 'Dish set', '94.jpg', 699.00, 0, 'Crockery', NULL, 'Home & Decor'),
(149, 'monkey top', 'gift.jpg', 400.00, 10, 'Kids', NULL, 'Gifts for Kids'),
(135, 'Elephant Toy', '72.jpg', 400.00, 0, 'Soft Toys', NULL, 'Gifts for Her'),
(134, 'Dog', '71.jpg', 350.00, 0, 'Soft Toys', NULL, 'Gifts for Her'),
(133, 'Fluffy Cat', '70.jpg', 699.00, 0, 'Soft Toys', NULL, 'Gifts for Her'),
(132, 'ladybird pillow', '69.jpg', 449.00, 0, 'Soft Toys', NULL, 'Gifts for Her'),
(131, 'Cookie plush', '68.jpg', 199.00, 0, 'Soft Toys', NULL, 'Gifts for Her'),
(130, 'Pokemon Toy', '67.jpg', 450.00, 0, 'Soft Toys', NULL, 'Gifts for Her'),
(129, 'Rainbow cushion', '66.jpg', 349.00, 0, 'Soft Toys', NULL, 'Gifts for Her'),
(128, 'Peach plush', '65.jpg', 200.00, 0, 'Soft Toys', NULL, 'Gifts for Her'),
(127, 'Turtle', '64.jpg', 459.00, 0, 'Soft Toys', NULL, 'Gifts for Her');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fname` varchar(10) NOT NULL,
  `lname` varchar(10) NOT NULL,
  `phone` int NOT NULL,
  `email` varchar(30) NOT NULL,
  `username` varchar(10) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('admin','client','delivery person') NOT NULL DEFAULT 'client',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fname`, `lname`, `phone`, `email`, `username`, `password`, `role`) VALUES
(7, 'fouzi', 'sli', 793642323, 'fouzi75@gmail.com', 'fouzi', '$2y$10$pdSEjqmbTuqValjgytSeeOB2uFBOv39mePLLidJKM2Zvp2wV/hCtm', 'admin'),
(8, 'fouzi', 'fouzi', 793642323, 'fouzi@gmail.com', 'fousli', '$2y$10$DZdMTtZPYx353qLZuUImyeKWfGFU7RJk8odeVtDtB6k4XTFrqr4.C', 'client'),
(9, 'Adel', 'Derdiche', 770235689, 'adelwwe50@gmail.com', 'dela3a', '$2y$10$qUbQhz4DseUmAqPFFtmtJ.fSrdPeChYGnJzVfrYKsQdvrEh3Ogype', 'client');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
