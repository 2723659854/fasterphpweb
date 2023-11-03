/*
 Navicat Premium Data Transfer

 Source Server         : localhost
 Source Server Type    : MySQL
 Source Server Version : 50726
 Source Host           : 127.0.0.1:3306
 Source Schema         : go_gin_chat

 Target Server Type    : MySQL
 Target Server Version : 50726
 File Encoding         : 65001

 Date: 03/11/2023 11:53:51
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for book
-- ----------------------------
DROP TABLE IF EXISTS `book`;
CREATE TABLE `book`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `room_id` int(11) NOT NULL COMMENT '房间ID',
  `to_user_id` int(11) NULL DEFAULT 0 COMMENT '私聊用户ID',
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '聊天内容',
  `image_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '图片URL',
  `created_at` datetime(0) NULL DEFAULT NULL,
  `updated_at` datetime(0) NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(0),
  `deleted_at` datetime(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_user_id`(`user_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for messages
-- ----------------------------
DROP TABLE IF EXISTS `messages`;
CREATE TABLE `messages`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `room_id` int(11) NOT NULL COMMENT '房间ID',
  `to_user_id` int(11) NULL DEFAULT 0 COMMENT '私聊用户ID',
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '聊天内容',
  `image_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '图片URL',
  `created_at` datetime(0) NULL DEFAULT NULL,
  `updated_at` datetime(0) NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(0),
  `deleted_at` datetime(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_user_id`(`user_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 13 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of messages
-- ----------------------------
INSERT INTO `messages` VALUES (1, 1, 1, 0, '我是你爸爸1', '', '2022-12-26 18:11:08', '2023-06-25 16:20:27', NULL);
INSERT INTO `messages` VALUES (2, 2, 1, 8, '我是你爸爸2', '', '2022-12-26 18:11:38', '2023-06-25 16:22:52', NULL);
INSERT INTO `messages` VALUES (3, 1, 1, 0, '我是你爸爸3', '', '2022-12-26 18:11:48', '2023-06-25 16:20:31', NULL);
INSERT INTO `messages` VALUES (5, 1, 1, 0, '我是你爸爸4', '', '2022-12-26 18:12:49', '2023-06-25 16:20:35', NULL);
INSERT INTO `messages` VALUES (6, 2, 1, 0, '我是你爸爸5', '', '2022-12-26 18:12:55', '2023-06-25 16:20:37', NULL);
INSERT INTO `messages` VALUES (7, 1, 1, 2, '我是你爸爸6', '', '2022-12-26 18:13:36', '2023-06-25 16:20:40', NULL);
INSERT INTO `messages` VALUES (8, 1, 1, 2, '我是你爸爸7', '', '2022-12-26 18:13:48', '2023-06-25 16:20:42', NULL);
INSERT INTO `messages` VALUES (9, 2, 1, 1, '我是你爸爸8', '', '2022-12-26 18:14:06', '2023-06-25 16:20:44', NULL);
INSERT INTO `messages` VALUES (10, 1, 1, 2, '我是你爸爸9', '', '2022-12-26 18:14:12', '2023-06-25 16:20:46', NULL);
INSERT INTO `messages` VALUES (11, 2, 1, 1, '我是你爸爸10', '', '2022-12-26 18:14:23', '2023-06-25 16:20:50', NULL);
INSERT INTO `messages` VALUES (12, 2, 1, 23, '就是一个测试', '', NULL, NULL, NULL);

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '昵称',
  `password` varchar(125) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '密码',
  `avatar_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '1' COMMENT '头像ID',
  `created_at` datetime(0) NULL DEFAULT NULL,
  `updated_at` datetime(0) NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(0),
  `deleted_at` datetime(0) NULL DEFAULT NULL,
  `sex` int(2) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `username`(`username`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 101 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of users
-- ----------------------------
INSERT INTO `users` VALUES (1, 'mmlady', 'e10adc3949ba59abbe56e057f20f883e', '1', '2022-12-26 18:11:01', '2022-12-26 18:36:09', NULL, NULL);
INSERT INTO `users` VALUES (2, '绿巨人', 'e10adc3949ba59abbe56e057f20f883e', '7', '2022-12-26 18:11:31', '2023-05-23 15:53:29', NULL, NULL);
INSERT INTO `users` VALUES (3, '牛魔王', '', '1', NULL, '2023-04-21 14:31:16', NULL, NULL);
INSERT INTO `users` VALUES (4, '2897', '05b65649d6716e1104ae98cf1f94a9fa', '2', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (5, '212', '937dde00896361279b69591d7ce32c6a', '6', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (6, '6204', '2a48626b5e8a5b07b3ecf6c67942a02a', '10', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (7, '9369', 'ee75020f6ba74d8f8815fa7595397a31', '3', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (8, '5485', '7a5c054ff716f03198bf359da5c804c3', '2', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (9, '1027', '25b9b8eecb3d6b70c780b88055c4537e', '10', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (10, '9453', '172156c4cf9062b003ee199fe6f7ee09', '4', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (11, '404', '2de87e5fa5e44e121a48bc7ee762a68c', '8', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (12, '1526', 'd9b52d208f52793c829f1ddecd2fda12', '7', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (13, '1515', '9ed8187ae0fa4c5ed0722602ae2c4774', '8', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (14, '2473', '6edf68bda5e0b8a831917e93052a53a2', '2', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (15, '4478', '73d485022a9312a27ae237bbf8cb035e', '10', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (16, '7692', '419b341ca7c3363d4eace8670c85ed12', '10', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (17, '7313', '42b115a3de690ca4e7cc53b7fddc747e', '4', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (18, '2739', 'c0ef00e953959aff75f570f1eebe61ef', '8', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (19, '7483', '64043db0b2b55a176fa8559aa33234c7', '10', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (20, '5324', 'd4d7d6db13f8dcaa0f05c121ba816787', '3', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (21, '7672', 'aff29ef6e0d39e0345db4ad459eed232', '9', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (22, '4889', 'dbb72b053e0161bad20c1b7185dd2026', '6', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (23, '2127', 'b3d2a5ff8bdef102ca4a0cff485084c1', '10', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (24, '3086', '0eca9e04fdeffd97ef637e706033ea76', '8', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (25, '8085', 'cf2f277d5070dfcf8c3f396ef37a7c06', '6', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (26, '6035', 'ad359adbdf761689fec786f1888335aa', '3', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (27, '7463', '4634a7bdb48d28afe52fa7dd2f4b206b', '5', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (28, '2373', 'e2d6d022bc5202c7eac34dcf63a1a679', '5', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (29, '6033', '447bf3666aba36bd193836bb2334a9cb', '6', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (30, '475', 'c571f561ebe1410a82d4d27aac819cde', '4', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (31, '4327', 'a63179043e36d465f82ac6f673760010', '1', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (32, '7275', 'c12743d76cfd3bdf642cc31bb2eb23d0', '10', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (33, '8158', 'c58fa8d249e9440d99bf6f66c983be24', '10', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (34, '2641', '4e918111868e88849f064d433931049f', '1', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (35, '9970', '6b75d1d9e4ec77a12755d132ea4ed599', '6', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (36, '6347', 'ec7a7b2dae9cb664d5c45801bd848406', '5', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (37, '3819', '9247b8ea166a12d63ce8ab2156c14c09', '8', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (38, '7104', 'e5efdd3f57855ac70f8ed90289da1fa1', '1', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (39, '4909', '503e00da9f811e5f897a87980438327d', '5', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (40, '8488', '57b2f077082152097b9c716a2be95e42', '10', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (41, '2462', 'a7e2225ca27206f71ae28593bcea5eac', '5', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (42, '2375', '4b8e3367ba85c7c0854b2099be28257a', '5', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (43, '3323', '823a4622266926d48c3b5da8169e0723', '6', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (44, '7249', '352219abc2e566b2ddc366cd6e0564fa', '10', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (45, '8268', '1e305d42007218d59e8199a2b0fde6d9', '4', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (46, '4858', '5ebaaa8b18fef6456f6eda68c44c558a', '10', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (47, '1010', 'f6dec7146501c059e099ec7e24649976', '3', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (48, '1041', 'e803579d328125eddd6f827feda31d0d', '3', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (49, '7355', '0c807feeaf50f6dcb4e0ea7c00272dac', '5', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (50, '3621', 'ed64d0367e7853619058aea3bff42f6a', '1', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (51, '2008', '451fe39835a44fbaa88e862c11271f5b', '4', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (52, '9481', '3ddb72c244840febcd878ae9e47232f8', '3', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (53, '1212', '1b959afa7bea1883de94e21527aa8918', '3', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (54, '6107', '3ddedac0364d7e9d7f2e6aca66c4d265', '5', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (55, '8912', '7abea6792ff77712b1217be801f4d100', '3', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (56, '5816', '134f145e9d4a2961a3be71f1ce21182c', '8', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (57, '8977', '3fe527db92a73fd01009ad67576a052b', '10', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (58, '7948', '39eb611b6304e1839753f35ee9e086f4', '6', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (59, '4366', 'a809379b4092e575aef4a9625174920f', '6', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (60, '2124', 'b656d600ebaa09576e583aaad6108eb3', '4', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (61, '9401', '4f61030afab4621a25c4279ed4137df6', '3', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (62, '7268', '12aae4c1a7ee5f40f0717d0188ddab9a', '2', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (63, '7942', '27d4ba2516a01b902bf520976cc39eaa', '7', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (64, '4995', '31ca3645ec2cca85284a53e98da6999e', '7', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (65, '9331', 'd7a5b5a87eb2be7e2b2173a9f84981eb', '8', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (66, '4154', '7f2b5d0b1421dad16c468026f8e03814', '10', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (67, '4077', '57e23bf012c3cf4b699aa92ba1cd6274', '5', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (68, '4866', '0d38f7ad1fa2262a665af045c6cf9436', '8', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (69, '6972', 'b4c2ec54f391e8d1894e6e745bef8fab', '6', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (70, '6355', 'bea94b6c82c41272e61c8a319ed26494', '7', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (71, '1181', 'b08ea58fc7a0fbba8980ecf7f914c011', '7', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (72, '7616', 'f990316048dfc4ed694276c061707fdb', '2', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (73, '6358', '57ccc1b0b65020f333c6b0b7d4b34eaf', '6', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (74, '331', '134812e1bf203688697dcda17ca29207', '9', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (75, '8269', 'e93ed6746f2ce9f2789516108d50acb5', '1', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (76, '3434', '85fdacfddec92f0649955460120ef05d', '10', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (77, '7524', '76461483f3110184fbce00c829cbd446', '9', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (78, '2785', '91b8b4a0e2f2368a0a9d3408e4e66e9a', '10', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (79, '5463', '496c8f3f4b495f43eb2ebb5d50992d44', '8', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (80, '9306', 'a93549a3b8616e6ff19d9989c4536b4e', '1', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (81, '5585', '782f817c84bec00d07ad3183e225b47e', '6', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (82, '7700', '55be2308a1319133a228658ae8dad7d6', '3', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (83, '1232', '0f1c145d470335643598cc57213ee742', '10', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (84, '9032', '49fe9d15080bdbe5eafc10a4352f7a2d', '8', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (85, '5939', '3b725a03c7fed7ec1900827cd30f6dd9', '10', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (86, '1401', '70460bd72a8a2db1d09d847ef5cbdf38', '5', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (87, '1484', 'f9e5ef2e7c5ea8837fd9ad1cd57056de', '6', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (88, '791', 'f87115a3419eca34d7de34a00902412d', '8', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (89, '9404', '3ccf7bdfe2afee63c679b614e0864423', '8', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (90, '3265', '8d15274c0bdbf8ffbf269bb7d64a659f', '6', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (91, '8677', '82707965d6b3b93f9d6f206e1407aa69', '6', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (92, '1891', '0b3ddd22ba6bb4293d71b20b3ad23b15', '4', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (93, '735', '026378ff34d24ab11256a61be340d594', '4', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (94, '5895', 'f2526f051883482bb635a13f5d8c00e0', '2', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (95, '905', 'ead9a8fa9f6bc7cac0f2c8967f6ef4c4', '3', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (96, '2111', '384f918efcd70dd3118fdfb3bedbd31d', '9', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (97, '1368', '54ab1e6608b6a054972a9d76b2da1f59', '10', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (98, '9403', '438553ef499616abd5b4102cc854be78', '9', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (99, '7230', 'da82b9ee4d2b5c5814b4e2bacf003224', '8', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES (100, '7532', '5b0d2b00a68088595b0032c7279e6d92', '5', NULL, NULL, NULL, NULL);

-- ----------------------------
-- Procedure structure for GreetWorld
-- ----------------------------
DROP PROCEDURE IF EXISTS `GreetWorld`;
delimiter ;;
CREATE PROCEDURE `GreetWorld`()
SELECT CONCAT(@greeting,' World')
;;
delimiter ;

-- ----------------------------
-- Procedure structure for p1
-- ----------------------------
DROP PROCEDURE IF EXISTS `p1`;
delimiter ;;
CREATE PROCEDURE `p1`()
SET @last_procedure='p1'
;;
delimiter ;

-- ----------------------------
-- Procedure structure for p2
-- ----------------------------
DROP PROCEDURE IF EXISTS `p2`;
delimiter ;;
CREATE PROCEDURE `p2`()
SELECT CONCAT('Last procedure was ',@last_procedure)
;;
delimiter ;

-- ----------------------------
-- Procedure structure for sp_demo_inout_parameter
-- ----------------------------
DROP PROCEDURE IF EXISTS `sp_demo_inout_parameter`;
delimiter ;;
CREATE PROCEDURE `sp_demo_inout_parameter`(INOUT p_inout INT)
BEGIN
SELECT p_inout;
SET p_inout=2;
SELECT p_inout;
END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for sp_demo_in_parameter
-- ----------------------------
DROP PROCEDURE IF EXISTS `sp_demo_in_parameter`;
delimiter ;;
CREATE PROCEDURE `sp_demo_in_parameter`(IN p_in INT)
BEGIN
SELECT p_in; 
SET p_in=2;
select p_in;
END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for sp_demo_out_parameter
-- ----------------------------
DROP PROCEDURE IF EXISTS `sp_demo_out_parameter`;
delimiter ;;
CREATE PROCEDURE `sp_demo_out_parameter`(OUT p_out INT)
BEGIN
SELECT p_out;
SET p_out=2;
SELECT p_out;
END
;;
delimiter ;

SET FOREIGN_KEY_CHECKS = 1;
