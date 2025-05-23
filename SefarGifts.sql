-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: May 23, 2025 at 12:45 AM
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
) ENGINE=MyISAM AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `created_at`) VALUES
(15, 8, '2025-05-22 12:52:18'),
(2, 7, '2025-04-29 17:15:44'),
(3, 12, '2025-05-05 17:33:16'),
(16, 16, '2025-05-22 13:22:10');

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
) ENGINE=MyISAM AUTO_INCREMENT=69 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `cart_items`
--

INSERT INTO `cart_items` (`id`, `cart_id`, `product_id`, `quantity`) VALUES
(62, 15, 154, 2),
(61, 14, 157, 1),
(60, 8, 157, 1),
(59, 13, 162, 2),
(55, 12, 150, 1),
(54, 11, 158, 1),
(53, 11, 62, 1),
(52, 11, 162, 1),
(51, 10, 60, 4),
(49, 9, 151, 3),
(68, 16, 158, 1),
(64, 17, 154, 1);

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
  `order_status` enum('processing','validated','completed','cancelled','returned') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'processing',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `phone` varchar(20) DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_name`, `email`, `address`, `total_price`, `order_status`, `created_at`, `phone`, `user_id`) VALUES
(16, 'fouzi slimani', 'anisanis@gmail.com', 'cite 11 decembre bt35 porte 2 boumerdes', 699.00, 'cancelled', '2025-05-18 19:41:29', '0793642323', 8),
(17, 'fouzi slimani', 'anisanis@gmail.com', 'cite 11 decembre bt35 porte 2 boumerdes', 100.00, 'cancelled', '2025-05-18 19:46:23', '0793642323', 8),
(18, 'fouzi slimani', 'anisanis@gmail.com', 'cite 11 decembre bt35 porte 2 boumerdes', 100.00, 'cancelled', '2025-05-18 22:18:45', '0793642323', 8),
(19, 'fouzi slimani', 'anisanis@gmail.com', 'cite 11 decembre bt35 porte 2 boumerdes', 300.00, 'returned', '2025-05-18 22:22:19', '0793642323', 8),
(20, 'fouzi slimani', 'anisanis@gmail.com', 'cite 11 decembre bt35 porte 2 boumerdes', 200.00, 'completed', '2025-05-19 12:36:25', '0793642323', 8),
(25, 'Ait Ali Idir', 'aitaliidir666@gmail.com', '0', 4496.00, 'completed', '2025-05-21 13:51:16', '0558625177', 16),
(24, 'Ait Ali Idir', 'aitaliidir666@gmail.com', '0', 300.00, 'completed', '2025-05-20 17:31:30', '0558625177', 16),
(26, 'fouzi slimani', 'anisanis@gmail.com', 'cite 11 decembre bt35 porte 2 boumerdes', 100.00, 'cancelled', '2025-05-18 19:46:23', '0793642323', 8),
(27, 'Ait Ali Idir', 'aitaliidir666@gmail.com', '0', 505000.00, 'returned', '2025-05-21 18:47:34', '0558625177', 16),
(28, 'Ait Ali Idir', 'aitaliidir666@gmail.com', '0', 3500.00, 'processing', '2025-05-21 23:16:10', '0558625177', 16),
(29, 'Ait Ali Idir', 'aitaliidir666@gmail.com', '0', 6000.00, 'cancelled', '2025-05-22 09:51:27', '0558625177', 16),
(30, 'fouzi slimani', 'anisanis@gmail.com', '0', 1500.00, 'returned', '2025-05-22 09:52:52', '0793642323', 8),
(31, 'fouzi slimani', 'anisanis@gmail.com', 'cite 11 decembre bt35 porte 2 boumerdes', 1500.00, 'completed', '2025-05-22 09:55:50', '0793642323', 8),
(32, 'merdas manel', 'manel20052017@gmail.com', 'cite 11 decembre bt35 porte 2 boumerdes', 599.00, 'completed', '2025-05-22 18:21:59', '0793642323', 18);

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
) ENGINE=MyISAM AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `unit_price`, `product_name`) VALUES
(20, 17, 151, 1, 100.00, 'Casio Illuminator'),
(19, 16, 155, 1, 100.00, 'Black Leather Bifold wallet'),
(18, 16, 78, 1, 599.00, 'Captain'),
(21, 18, 151, 1, 100.00, 'Casio Illuminator'),
(22, 19, 155, 3, 100.00, 'Black Leather Bifold wallet'),
(23, 20, 155, 2, 100.00, 'Black Leather Bifold wallet'),
(24, 21, 155, 2, 100.00, 'Black Leather Bifold wallet'),
(25, 22, 155, 1, 100.00, 'Black Leather Bifold wallet'),
(26, 23, 151, 2, 100.00, 'Casio Illuminator'),
(27, 23, 150, 2, 3000.00, 'Casio'),
(28, 24, 151, 3, 100.00, 'Casio Illuminator'),
(29, 25, 60, 4, 999.00, 'Pearl ring'),
(30, 27, 158, 1, 500000.00, 'ROLEX Oyster Perpetual DATEJUST'),
(31, 27, 62, 1, 1500.00, 'barbie'),
(32, 27, 162, 1, 3000.00, 'Cup of tea'),
(33, 28, 150, 1, 3000.00, 'Casio'),
(34, 29, 162, 2, 3000.00, 'Cup of tea'),
(35, 30, 157, 1, 1500.00, 'Botanic Harmony Ceramic'),
(36, 31, 157, 1, 1500.00, 'Botanic Harmony Ceramic'),
(37, 32, 154, 1, 599.00, 'Silver-Colored earring');

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
) ENGINE=MyISAM AUTO_INCREMENT=164 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `image`, `price`, `stock`, `category`, `description`, `gift_category`) VALUES
(1, 'Watch1', '1.jpg', 10500.00, 10, 'Watches', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Him'),
(2, 'watch2', '2.jpg', 8000.00, 4, 'Watches', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Him'),
(3, 'Watch3', '3.jpg', 1499.00, 10, 'Watches', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Him'),
(4, 'Watch  4', '4.jpg', 1999.00, 10, 'Watches', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Him'),
(5, 'Watch 5', '5.jpg', 2499.00, 10, 'Watches', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Him'),
(6, 'Watch 6', '6.jpg', 1299.00, 10, 'Watches', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Him'),
(7, 'Watch 7', '7.jpg', 6999.00, 10, 'Watches', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Him'),
(8, 'Watch 8', '8.jpg', 999.00, 10, 'Watches', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Him'),
(9, 'Watch 9', '9.jpg', 2999.00, 10, 'Watches', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Him'),
(10, 'Watch 10', '10.jpg', 3599.00, 10, 'Watches', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Him'),
(11, 'Watch 11', '11.jpg', 899.00, 10, 'Watches', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Him'),
(12, 'Watch 12', '12.jpg', 1899.00, 10, 'Watches', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Him'),
(13, 'Watch 13', '13.jpg', 1299.00, 10, 'Watches', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Him'),
(14, 'Watch 14', '14.jpg', 1399.00, 10, 'Watches', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Him'),
(15, 'Watch 15', '15.jpg', 1649.00, 10, 'Watches', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Him'),
(16, 'Watch 16', '16.jpg', 1700.00, 10, 'Watches', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Him'),
(17, 'Leather wallet', '98.jpg', 800.00, 10, 'Wallets', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Him'),
(18, 'Clutch wallet', '99.jpg', 600.00, 10, 'Wallets', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Him'),
(19, 'Reebok wallet', '100.jpg', 1200.00, 10, 'Wallets', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Him'),
(20, 'Continental wallet', '101.jpg', 2099.00, 10, 'Wallets', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Him'),
(21, 'Bonia wallet', '102.jpg', 900.00, 10, 'Wallets', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Him'),
(22, 'Pallas wallet', '103.jpg', 750.00, 10, 'Wallets', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Him'),
(23, 'Brahmin wallet', '104.jpg', 500.00, 10, 'Wallets', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Him'),
(24, 'Coach wallet', '105.jpg', 100.00, 10, 'Wallets', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Him'),
(25, 'calvin klein', '106.jpg', 2500.00, 10, 'Wallets', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Him'),
(26, 'Monogram wallet', '107.jpg', 850.00, 10, 'Wallets', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Him'),
(27, 'Checks wallet', '108.jpg', 900.00, 10, 'Wallets', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Him'),
(28, 'Louis vuitton', '109.jpg', 1200.00, 10, 'Wallets', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Him'),
(29, 'Red handbag', '110.jpg', 600.00, 10, 'Wallets', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Him'),
(30, 'Vegan wallet', '111.jpg', 400.00, 10, 'Wallets', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Him'),
(31, 'Leather handbag', '112.jpg', 550.00, 10, 'Wallets', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Him'),
(32, 'Coin purse', '113.jpg', 100.00, 3, 'Wallets', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Him'),
(150, 'Casio', 'product_682fab75768ad8.48239921.jpg', 3000.00, 2, 'Watches', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Him'),
(49, 'bracelet', 'bracelet.jpg', 699.00, 10, 'Jewellery', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Her'),
(50, 'silver pearl earings', '2.jpg', 559.00, 10, 'Jewellery', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Her'),
(51, 'blue woolen earing', '3.jpg', 59.00, 10, 'Jewellery', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Her'),
(52, 'peacock necklace', '4.jpg', 499.00, 10, 'Jewellery', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Her'),
(53, 'butterfly pendant', '5.jpg', 399.00, 10, 'Jewellery', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Her'),
(54, 'nosering', '6.jpg', 99.00, 10, 'Jewellery', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Her'),
(55, 'golden bangles', '7.jpg', 1099.00, 10, 'Jewellery', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Her'),
(56, 'gold plated bangles', '8.jpg', 2099.00, 10, 'Jewellery', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Her'),
(58, 'diamond earing', '10.jpg', 3050.00, 10, 'Jewellery', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Her'),
(59, 'Infinity earing', '11.jpg', 650.00, 10, 'Jewellery', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Her'),
(60, 'Pearl ring', '12.jpg', 999.00, 16, 'Jewellery', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Her'),
(61, 'kidstable', 'table.jpg', 2000.00, 10, 'Kids', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Kids'),
(62, 'barbie', 'princess.jpg', 1500.00, 8, 'Kids', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Kids'),
(63, 'doctorset', 'doctor1.jpg', 1200.00, 10, 'Kids', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Kids'),
(64, 'jwellarybox', 'jwelary.jpg', 1800.00, 10, 'Kids', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Kids'),
(65, 'fruit basket', 'fruitbasket.jpg', 800.00, 10, 'Kids', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Kids'),
(66, 'puzzle', 'puzzle.jpg', 400.00, 10, 'Kids', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Kids'),
(67, 'spinner', 'spinner.jpg', 350.00, 10, 'Kids', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Kids'),
(68, 'Toy Car', 'toycar.jpg', 900.00, 10, 'Kids', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Kids'),
(69, 'Animal Plate', 'animal.jpg', 699.00, 10, 'Kids', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Kids'),
(70, 'White Board', 'board.jpg', 899.00, 10, 'Kids', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Kids'),
(71, 'Key Chain', 'keychain.jpg', 449.00, 10, 'Kids', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Kids'),
(73, 'phonecase1', 'case1.jpg', 499.00, 10, 'PhoneCase', 'A beautiful and unique gift, perfect for any occasion!', 'Tech Gifts'),
(74, 'Wooden', 'case2.jpg', 499.00, 10, 'PhoneCase', 'A beautiful and unique gift, perfect for any occasion!', 'Tech Gifts'),
(75, 'Marble ', 'case3.jpg', 599.00, 10, 'PhoneCase', 'A beautiful and unique gift, perfect for any occasion!', 'Tech Gifts'),
(76, 'Black', 'case4.jpg', 299.00, 10, 'PhoneCase', 'A beautiful and unique gift, perfect for any occasion!', 'Tech Gifts'),
(77, 'Pink Case', 'case5.jpg', 555.00, 10, 'PhoneCase', 'A beautiful and unique gift, perfect for any occasion!', 'Tech Gifts'),
(78, 'Captain', 'case6.jpg', 599.00, 5, 'PhoneCase', 'A beautiful and unique gift, perfect for any occasion!', 'Tech Gifts'),
(79, 'Oval Lamp', 'lamp.jpg', 999.00, 10, 'Home Decor', 'A beautiful and unique gift, perfect for any occasion!', 'Home & Decor'),
(80, 'KeyHolder', 'keyholder.jpg', 699.00, 10, 'Home Decor', 'A beautiful and unique gift, perfect for any occasion!', 'Home & Decor'),
(81, 'Card', 'card.jpg', 699.00, 10, 'Home Decor', 'A beautiful and unique gift, perfect for any occasion!', 'Home & Decor'),
(82, 'Holder', 'candle.jpg', 399.00, 10, 'Home Decor', 'A beautiful and unique gift, perfect for any occasion!', 'Home & Decor'),
(83, 'Flowers', 'flowers.jpg', 499.00, 10, 'Home Decor', 'A beautiful and unique gift, perfect for any occasion!', 'Home & Decor'),
(84, 'Bonsai', 'bonsai.jpg', 899.00, 10, 'Home Decor', 'A beautiful and unique gift, perfect for any occasion!', 'Home & Decor'),
(85, 'Lamp', 'lamp1.jpg', 799.00, 10, 'Home Decor', 'A beautiful and unique gift, perfect for any occasion!', 'Home & Decor'),
(86, 'Cycle', 'cycle.jpg', 1999.00, 10, 'Home Decor', 'A beautiful and unique gift, perfect for any occasion!', 'Home & Decor'),
(87, 'ShowPiece', 'show.jpg', 699.00, 10, 'Home Decor', 'A beautiful and unique gift, perfect for any occasion!', 'Home & Decor'),
(88, 'PortRait', 'port.jpg', 599.00, 10, 'Home Decor', 'A beautiful and unique gift, perfect for any occasion!', 'Home & Decor'),
(89, 'Owl Holder', 'owl.jpg', 999.00, 10, 'Home Decor', 'A beautiful and unique gift, perfect for any occasion!', 'Home & Decor'),
(90, 'DinnerSet', 'dinner.jpg', 1499.00, 10, 'Home Decor', 'A beautiful and unique gift, perfect for any occasion!', 'Home & Decor'),
(91, 'BirdHouse', 'birdhouse.jpg', 499.00, 10, 'Home Decor', 'A beautiful and unique gift, perfect for any occasion!', 'Home & Decor'),
(92, 'Night Lamp', 'nightlamp.jpg', 399.00, 10, 'Home Decor', 'A beautiful and unique gift, perfect for any occasion!', 'Home & Decor'),
(93, 'PhotoFrame', 'photo.jpg', 499.00, 10, 'Home Decor', 'A beautiful and unique gift, perfect for any occasion!', 'Home & Decor'),
(94, 'Frame', 'frame.jpg', 699.00, 10, 'Home Decor', 'A beautiful and unique gift, perfect for any occasion!', 'Home & Decor'),
(95, 'kitten mug', '81.jpg', 150.00, 10, 'Crockery', 'A beautiful and unique gift, perfect for any occasion!', 'Home & Decor'),
(96, 'kuksa', '82.jpg', 250.00, 10, 'Crockery', 'A beautiful and unique gift, perfect for any occasion!', 'Home & Decor'),
(97, '3D mug', '83.jpg', 300.00, 10, 'Crockery', 'A beautiful and unique gift, perfect for any occasion!', 'Home & Decor'),
(98, 'wooden mug', '85.jpg', 350.00, 10, 'Crockery', 'A beautiful and unique gift, perfect for any occasion!', 'Home & Decor'),
(99, 'Flamingo', '86.jpg', 300.00, 10, 'Crockery', 'A beautiful and unique gift, perfect for any occasion!', 'Home & Decor'),
(100, 'Dinnerware', '87.jpg', 700.00, 10, 'Crockery', 'A beautiful and unique gift, perfect for any occasion!', 'Home & Decor'),
(101, 'Casserole', '88.jpg', 999.00, 10, 'Crockery', 'A beautiful and unique gift, perfect for any occasion!', 'Home & Decor'),
(102, 'Glaze', '89.jpg', 1050.00, 10, 'Crockery', 'A beautiful and unique gift, perfect for any occasion!', 'Home & Decor'),
(103, 'Rubik Mug', '90.jpg', 399.00, 10, 'Crockery', 'A beautiful and unique gift, perfect for any occasion!', 'Home & Decor'),
(104, 'Pottery Mu', '91.jpg', 300.00, 10, 'Crockery', 'A beautiful and unique gift, perfect for any occasion!', 'Home & Decor'),
(149, 'monkey top', 'product_682fb34f9e97c5.71806251.jpg', 400.00, 10, 'Kids', 'monkey top', 'Gifts for Kids'),
(134, 'Dog', '71.jpg', 350.00, 10, 'Soft Toys', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Her'),
(133, 'Fluffy Cat', '70.jpg', 699.00, 10, 'Soft Toys', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Her'),
(132, 'ladybird pillow', '69.jpg', 449.00, 10, 'Soft Toys', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Her'),
(131, 'Cookie plush', '68.jpg', 199.00, 10, 'Soft Toys', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Her'),
(130, 'Pokemon Toy', '67.jpg', 450.00, 10, 'Soft Toys', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Her'),
(129, 'Rainbow cushion', '66.jpg', 349.00, 10, 'Soft Toys', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Her'),
(128, 'Peach plush', '65.jpg', 200.00, 10, 'Soft Toys', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Her'),
(127, 'Turtle', '64.jpg', 459.00, 10, 'Soft Toys', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Her'),
(151, 'Casio Illuminator', 'pavlo-talpa-inhasepzxy4-unsplash.jpg', 100.00, 10, 'Watches', 'A beautiful and unique gift, perfect for any occasion!', 'Gifts for Him'),
(154, 'Silver-Colored earring', 'earrings.jpg', 599.00, 4, 'Jewellery', 'Pair of Silver-Colored earring with blue gemstone', 'Gifts for Her'),
(155, 'Black Leather Bifold wallet', 'mason-supply--lN0HnySy7w-unsplash.jpg', 100.00, 10, 'Wallets', 'Black Leather Bifold wallet', 'Gifts for Him'),
(156, 'Wooden toy', 'sebastian-olivos-JfTDS6At7-8-unsplash.jpg', 4000.00, 5, 'Kids', 'a wooden toy with lots of colorful toys on top of it', 'Gifts for Kids'),
(157, 'Botanic Harmony Ceramic', 'micheile-henderson-agyV2HOf5UM-unsplash.jpg', 1500.00, 13, 'Crockery', 'A beautiful and unique gift, perfect for any occasion!', 'Home & Decor'),
(158, 'ROLEX Oyster Perpetual DATEJUST', 'yash-parashar-LWPPpkn6NEQ-unsplash.jpg', 500000.00, 2, 'Watches', 'FANTASTIC LUXURY WATCH', 'Gifts for Him'),
(161, 'AirPods Pro 3', 'omid-armin-gSZCLsE7ysc-unsplash.jpg', 40000.00, 6, 'Phone Accessories', 'airpods 3', 'Tech Gifts'),
(162, 'Cup of tea', 'tamara-malaniy-KRHZvXiZZXQ-unsplash.jpg', 3000.00, 10, 'Home Decor', 'cup of tea', 'For Birthdays'),
(163, 'The Bride and The Groom', 'duong-ngan-Yna5kkk8sJ8-unsplash.jpg', 5000.00, 5, 'Weddings', 'Special gifts!', 'Wedding Gifts');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `order_id` int NOT NULL,
  `rating` int DEFAULT NULL,
  `comment` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_featured` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_order_review` (`user_id`,`order_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_user_id` (`user_id`)
) ;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `user_id`, `order_id`, `rating`, `comment`, `created_at`, `is_featured`) VALUES
(1, 8, 20, 3, 'Fast delivery, great packaging, and wonderful customer service.', '2025-05-19 23:02:57', 1),
(4, 16, 24, 5, 'wlh ghir l3alamiya', '2025-05-21 13:47:47', 1),
(3, 8, 19, 5, 'fantastic service, thanks!!', '2025-05-20 16:31:26', 1);

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
  `role` enum('admin','client') CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT 'client',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fname`, `lname`, `phone`, `email`, `username`, `password`, `role`, `created_at`) VALUES
(7, 'fouzi', 'sli', 793642323, 'feliz.fe75@gmail.com', 'fouzi', '$2y$10$kCXozF7wJtgPzx3/TSzz6eNUJUADfh2cP0ZI6QsYu/B8BkW/qsDQm', 'admin', '2025-05-03 13:05:32'),
(8, 'fouzi', 'slimani', 793642323, 'anisanis@gmail.com', 'fou', '$2y$10$Lj1Y2Ww/WYlCEJy1gH7Z1Ov/5GzfDbB1nMfA0cXi0vdXlKloJlSfC', 'client', '2025-05-03 13:05:32'),
(16, 'Ait Ali', 'Idir', 558625177, 'aitaliidir666@gmail.com', 'idir03', '$2y$10$oPCwBjPmm9WdRe.3dkTlsOCPH3jwq4KLUe1h8krEnaJ/eeWFJrDUa', 'client', '2025-05-20 17:28:00');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

DROP TABLE IF EXISTS `wishlist`;
CREATE TABLE IF NOT EXISTS `wishlist` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `product_id` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `product_id` (`product_id`)
) ENGINE=MyISAM AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`id`, `user_id`, `product_id`, `created_at`) VALUES
(15, 8, 156, '2025-05-20 17:28:07'),
(13, 12, 2, '2025-05-19 23:01:39'),
(27, 18, 155, '2025-05-22 19:24:49');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
