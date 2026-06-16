/*
 Navicat Premium Data Transfer

 Source Server         : drama
 Source Server Type    : MySQL
 Source Server Version : 50744
 Source Host           : localhost:31306
 Source Schema         : drama

 Target Server Type    : MySQL
 Target Server Version : 50744
 File Encoding         : 65001

 Date: 12/06/2026 08:31:15
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for drama_episode_stats
-- ----------------------------
DROP TABLE IF EXISTS `drama_episode_stats`;
CREATE TABLE `drama_episode_stats`  (
  `episode_id` int(11) NOT NULL,
  `like_count` int(11) NOT NULL,
  `comment_count` int(11) NOT NULL,
  `share_count` int(11) NOT NULL,
  `play_count` int(11) NOT NULL,
  `favorite_count` int(11) NOT NULL,
  `created_at` datetime(0) NULL DEFAULT NULL,
  `updated_at` datetime(0) NOT NULL,
  PRIMARY KEY (`episode_id`) USING BTREE,
  CONSTRAINT `drama_episode_stats_ibfk_1` FOREIGN KEY (`episode_id`) REFERENCES `drama_episodes` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for drama_episodes
-- ----------------------------
DROP TABLE IF EXISTS `drama_episodes`;
CREATE TABLE `drama_episodes`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `drama_id` int(11) NOT NULL,
  `external_video_id` varchar(24) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `episode_no` int(11) NOT NULL,
  `title` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `play_url` varchar(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `poster_url` varchar(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `duration_seconds` int(11) NOT NULL,
  `sort_order` int(11) NOT NULL,
  `status` int(11) NOT NULL,
  `display_nickname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `loop` int(11) NOT NULL,
  `play_ing` int(11) NOT NULL,
  `muted` int(11) NOT NULL,
  `is_playing` int(11) NOT NULL,
  `show_title_arrow` int(11) NOT NULL,
  `show_look_all_btn` int(11) NOT NULL,
  `look_all_btn_text` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `show_bottom_area` int(11) NOT NULL,
  `bottom_area_btn_text` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tool_info_json` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime(0) NULL DEFAULT NULL,
  `updated_at` datetime(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `dramaepisode_drama_id_episode_no`(`drama_id`, `episode_no`) USING BTREE,
  UNIQUE INDEX `dramaepisode_external_video_id`(`external_video_id`) USING BTREE,
  INDEX `dramaepisode_drama_id`(`drama_id`) USING BTREE,
  INDEX `dramaepisode_sort_order`(`sort_order`) USING BTREE,
  INDEX `dramaepisode_status`(`status`) USING BTREE,
  CONSTRAINT `drama_episodes_ibfk_1` FOREIGN KEY (`drama_id`) REFERENCES `dramas` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE = InnoDB AUTO_INCREMENT = 201 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for dramas
-- ----------------------------
DROP TABLE IF EXISTS `dramas`;
CREATE TABLE `dramas`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `external_drama_id` varchar(24) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_author_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `author_user_id` int(11) NOT NULL,
  `total_episodes` int(11) NOT NULL,
  `cover_url` varchar(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `vip_free` int(11) NOT NULL,
  `status` int(11) NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `category` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '推荐',
  `tags` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `play_count` int(11) NOT NULL DEFAULT 0,
  `follow_count` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime(0) NULL DEFAULT NULL,
  `updated_at` datetime(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `drama_external_drama_id`(`external_drama_id`) USING BTREE,
  INDEX `drama_author_user_id`(`author_user_id`) USING BTREE,
  INDEX `drama_category`(`category`) USING BTREE,
  INDEX `drama_status`(`status`) USING BTREE,
  CONSTRAINT `dramas_ibfk_1` FOREIGN KEY (`author_user_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE = InnoDB AUTO_INCREMENT = 51 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for episode_comments
-- ----------------------------
DROP TABLE IF EXISTS `episode_comments`;
CREATE TABLE `episode_comments`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `episode_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` int(11) NOT NULL,
  `created_at` datetime(0) NULL DEFAULT NULL,
  `updated_at` datetime(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `episodecomment_episode_id`(`episode_id`) USING BTREE,
  INDEX `episodecomment_status`(`status`) USING BTREE,
  INDEX `episodecomment_user_id`(`user_id`) USING BTREE,
  CONSTRAINT `episode_comments_ibfk_1` FOREIGN KEY (`episode_id`) REFERENCES `drama_episodes` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `episode_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for episode_shares
-- ----------------------------
DROP TABLE IF EXISTS `episode_shares`;
CREATE TABLE `episode_shares`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `episode_id` int(11) NOT NULL,
  `user_id` int(11) NULL DEFAULT NULL,
  `channel` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime(0) NOT NULL,
  `updated_at` datetime(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `episodeshare_episode_id`(`episode_id`) USING BTREE,
  INDEX `episodeshare_user_id`(`user_id`) USING BTREE,
  CONSTRAINT `episode_shares_ibfk_1` FOREIGN KEY (`episode_id`) REFERENCES `drama_episodes` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `episode_shares_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for user_drama_favorites
-- ----------------------------
DROP TABLE IF EXISTS `user_drama_favorites`;
CREATE TABLE `user_drama_favorites`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `drama_id` int(11) NOT NULL,
  `created_at` datetime(0) NOT NULL,
  `updated_at` datetime(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `userdramafavorite_user_id_drama_id`(`user_id`, `drama_id`) USING BTREE,
  INDEX `userdramafavorite_drama_id`(`drama_id`) USING BTREE,
  INDEX `userdramafavorite_user_id`(`user_id`) USING BTREE,
  CONSTRAINT `user_drama_favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `user_drama_favorites_ibfk_2` FOREIGN KEY (`drama_id`) REFERENCES `dramas` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for user_episode_likes
-- ----------------------------
DROP TABLE IF EXISTS `user_episode_likes`;
CREATE TABLE `user_episode_likes`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `episode_id` int(11) NOT NULL,
  `created_at` datetime(0) NOT NULL,
  `updated_at` datetime(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `userepisodelike_user_id_episode_id`(`user_id`, `episode_id`) USING BTREE,
  INDEX `userepisodelike_episode_id`(`episode_id`) USING BTREE,
  INDEX `userepisodelike_user_id`(`user_id`) USING BTREE,
  CONSTRAINT `user_episode_likes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `user_episode_likes_ibfk_2` FOREIGN KEY (`episode_id`) REFERENCES `drama_episodes` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for user_episode_progress
-- ----------------------------
DROP TABLE IF EXISTS `user_episode_progress`;
CREATE TABLE `user_episode_progress`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `episode_id` int(11) NOT NULL,
  `position_seconds` int(11) NOT NULL,
  `is_finished` int(11) NOT NULL,
  `created_at` datetime(0) NULL DEFAULT NULL,
  `updated_at` datetime(0) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `userepisodeprogress_user_id_episode_id`(`user_id`, `episode_id`) USING BTREE,
  INDEX `userepisodeprogress_episode_id`(`episode_id`) USING BTREE,
  INDEX `userepisodeprogress_user_id`(`user_id`) USING BTREE,
  CONSTRAINT `user_episode_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `user_episode_progress_ibfk_2` FOREIGN KEY (`episode_id`) REFERENCES `drama_episodes` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE = InnoDB AUTO_INCREMENT = 11 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for user_follows
-- ----------------------------
DROP TABLE IF EXISTS `user_follows`;
CREATE TABLE `user_follows`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `follower_user_id` int(11) NOT NULL,
  `followed_user_id` int(11) NOT NULL,
  `created_at` datetime(0) NOT NULL,
  `updated_at` datetime(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `userfollow_follower_user_id_followed_user_id`(`follower_user_id`, `followed_user_id`) USING BTREE,
  INDEX `userfollow_followed_user_id`(`followed_user_id`) USING BTREE,
  INDEX `userfollow_follower_user_id`(`follower_user_id`) USING BTREE,
  CONSTRAINT `user_follows_ibfk_1` FOREIGN KEY (`follower_user_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `user_follows_ibfk_2` FOREIGN KEY (`followed_user_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `external_user_id` varchar(24) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nickname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `avatar_url` varchar(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` int(11) NOT NULL,
  `created_at` datetime(0) NULL DEFAULT NULL,
  `updated_at` datetime(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `user_external_user_id`(`external_user_id`) USING BTREE,
  INDEX `user_status`(`status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 68 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

SET FOREIGN_KEY_CHECKS = 1;
