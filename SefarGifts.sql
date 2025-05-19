-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: May 19, 2025 at 11:14 PM
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
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `created_at`) VALUES
(8, 8, '2025-05-19 18:38:25'),
(2, 7, '2025-04-29 17:15:44'),
(3, 12, '2025-05-05 17:33:16');

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
) ENGINE=MyISAM AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=MyISAM AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_name`, `email`, `address`, `total_price`, `order_status`, `created_at`, `phone`, `user_id`) VALUES
(16, 'fouzi slimani', 'anisanis@gmail.com', 'cite 11 decembre bt35 porte 2 boumerdes', 699.00, 'cancelled', '2025-05-18 19:41:29', '0793642323', 8),
(17, 'fouzi slimani', 'anisanis@gmail.com', 'cite 11 decembre bt35 porte 2 boumerdes', 100.00, 'cancelled', '2025-05-18 19:46:23', '0793642323', 8),
(18, 'fouzi slimani', 'anisanis@gmail.com', 'cite 11 decembre bt35 porte 2 boumerdes', 100.00, 'cancelled', '2025-05-18 22:18:45', '0793642323', 8),
(19, 'fouzi slimani', 'anisanis@gmail.com', 'cite 11 decembre bt35 porte 2 boumerdes', 300.00, 'processing', '2025-05-18 22:22:19', '0793642323', 8),
(20, 'fouzi slimani', 'anisanis@gmail.com', 'cite 11 decembre bt35 porte 2 boumerdes', 200.00, 'completed', '2025-05-19 12:36:25', '0793642323', 8);

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
) ENGINE=MyISAM AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
(27, 23, 150, 2, 3000.00, 'Casio');

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
) ENGINE=MyISAM AUTO_INCREMENT=158 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
(150, 'Casio', '20250410_1918_Heart-Themed Background_remix_01jrgep2cyes6sdhv3xecyvbn3.png', 3000.00, 3, 'Watches', NULL, 'Gifts for Him'),
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
(127, 'Turtle', '64.jpg', 459.00, 0, 'Soft Toys', NULL, 'Gifts for Her'),
(151, 'Casio Illuminator', 'pavlo-talpa-inhasepzxy4-unsplash.jpg', 100.00, 3, 'Watches', NULL, 'Gifts for Him'),
(154, 'Silver-Colored earring', 'earrings.jpg', 599.00, 5, 'Jewellery', 'Pair of Silver-Colored earring with blue gemstone', 'Gifts for Her'),
(155, 'Black Leather Bifold wallet', 'mason-supply--lN0HnySy7w-unsplash.jpg', 100.00, 0, 'Wallets', 'Black Leather Bifold wallet', 'Gifts for Him'),
(156, 'Wooden toy', 'sebastian-olivos-JfTDS6At7-8-unsplash.jpg', 4000.00, 5, 'Kids', 'a wooden toy with lots of colorful toys on top of it', 'Gifts for Kids'),
(157, 'Botanic Harmony Ceramic Plate Set', 'micheile-henderson-agyV2HOf5UM-unsplash.jpg', 3000.00, 14, 'Crockery', 'Elevate your dining experience with the Botanic Harmony plate set', 'Home & Decor');

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
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_order_review` (`user_id`,`order_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_user_id` (`user_id`)
) ;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `user_id`, `order_id`, `rating`, `comment`, `created_at`) VALUES
(1, 8, 20, 3, NULL, '2025-05-19 23:02:57');

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
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fname`, `lname`, `phone`, `email`, `username`, `password`, `role`, `created_at`) VALUES
(7, 'fouzi', 'sli', 793642323, 'fouzi75@gmail.com', 'fouzi', '$2y$10$e2XOF8zKkzXNX8wLJNDyleinxu0GNRrkVexJrpeoKK6FxOggQjpM.', 'admin', '2025-05-03 13:05:32'),
(8, 'fouzi', 'slimani', 793642323, 'anisanis@gmail.com', 'fou', '$2y$10$Lj1Y2Ww/WYlCEJy1gH7Z1Ov/5GzfDbB1nMfA0cXi0vdXlKloJlSfC', 'client', '2025-05-03 13:05:32'),
(10, 'hani', 'derradj', 793642323, 'hani@gmail.com', 'hannni', '$2y$10$Az7PFkeJ.p0obyZW6x/KjeEpf/61hK.AJv6mX0b6p3HtajQDjd2Va', 'client', '2025-05-04 12:11:07'),
(12, 'ramzy', 'tazekrit', 559469395, 'tazekrittramzy@gmail.com', '911', '$2y$10$ZC9yyUO6B20Nyytctc2tkOy1zDMV8fTNSm1B8LdtVtkFxch91Wf3i', 'client', '2025-05-05 17:23:59'),
(15, 'fouzi', 'fouzi', 793642323, 'fouzi.slimani75@gmail.com', 'fousli', '$2y$10$hQ0qWyforXHgSAaznBs6uO4qTpgaSLirew.pBlQuR9/ZpVsw/dFSm', 'client', '2025-05-19 23:12:17');

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
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`id`, `user_id`, `product_id`, `created_at`) VALUES
(12, 8, 155, '2025-05-19 18:49:02'),
(13, 12, 2, '2025-05-19 23:01:39');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
