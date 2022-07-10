
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for book
-- ----------------------------
DROP TABLE IF EXISTS `book`;
CREATE TABLE `book`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `price` decimal(10, 2) NULL DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `create_time` datetime(0) NULL DEFAULT NULL,
  `update_time` datetime(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 11 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Records of book
-- ----------------------------
INSERT INTO `book` VALUES (1, 15.23, '哈利波特', '0000-00-00 00:00:00', '0000-00-00 00:00:00');
INSERT INTO `book` VALUES (2, 15.23, '哈利波特', '0000-00-00 00:00:00', '0000-00-00 00:00:00');
INSERT INTO `book` VALUES (3, 15.23, '哈利波特', '0000-00-00 00:00:00', '0000-00-00 00:00:00');
INSERT INTO `book` VALUES (4, 15.23, '哈利波特', '0000-00-00 00:00:00', '0000-00-00 00:00:00');
INSERT INTO `book` VALUES (5, 15.23, '哈利波特', '0000-00-00 00:00:00', '0000-00-00 00:00:00');
INSERT INTO `book` VALUES (6, 15.23, '哈利波特', '0000-00-00 00:00:00', '0000-00-00 00:00:00');
INSERT INTO `book` VALUES (7, 15.23, '哈利波特', '0000-00-00 00:00:00', '0000-00-00 00:00:00');
INSERT INTO `book` VALUES (8, 15.23, '哈利波特', '0000-00-00 00:00:00', '0000-00-00 00:00:00');
INSERT INTO `book` VALUES (9, 15.23, '哈利波特', '0000-00-00 00:00:00', '0000-00-00 00:00:00');
INSERT INTO `book` VALUES (10, 15.23, '哈利波特', '0000-00-00 00:00:00', '0000-00-00 00:00:00');

-- ----------------------------
-- Table structure for user
-- ----------------------------
DROP TABLE IF EXISTS `user`;
CREATE TABLE `user`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `age` int(11) NULL DEFAULT NULL,
  `sex` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `status` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `create_time` datetime(0) NULL DEFAULT NULL,
  `update_time` datetime(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

SET FOREIGN_KEY_CHECKS = 1;
