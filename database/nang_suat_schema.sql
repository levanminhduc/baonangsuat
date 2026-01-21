-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: nang_suat
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `bao_cao_nang_suat`
--

DROP TABLE IF EXISTS `bao_cao_nang_suat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bao_cao_nang_suat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ngay_bao_cao` date NOT NULL,
  `line_id` int(11) NOT NULL,
  `ca_id` int(11) NOT NULL,
  `ma_hang_id` int(11) NOT NULL,
  `so_lao_dong` int(11) NOT NULL DEFAULT 0,
  `ctns` int(11) NOT NULL DEFAULT 0,
  `ct_gio` decimal(10,2) DEFAULT 0.00,
  `tong_phut_hieu_dung` int(11) DEFAULT 0,
  `ghi_chu` text DEFAULT NULL,
  `trang_thai` enum('draft','submitted','approved','locked','completed') DEFAULT 'draft',
  `version` int(11) DEFAULT 1,
  `tao_boi` varchar(50) DEFAULT NULL,
  `tao_luc` timestamp NOT NULL DEFAULT current_timestamp(),
  `cap_nhat_luc` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `hoan_tat_luc` timestamp NULL DEFAULT NULL,
  `hoan_tat_boi` varchar(50) DEFAULT NULL,
  `ket_qua_luy_ke` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ket_qua_luy_ke`)),
  `routing_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`routing_snapshot`)),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_bao_cao` (`ngay_bao_cao`,`line_id`,`ca_id`,`ma_hang_id`),
  KEY `ca_id` (`ca_id`),
  KEY `ma_hang_id` (`ma_hang_id`),
  KEY `idx_ngay` (`ngay_bao_cao`),
  KEY `idx_line` (`line_id`),
  KEY `idx_trang_thai` (`trang_thai`),
  CONSTRAINT `bao_cao_nang_suat_ibfk_1` FOREIGN KEY (`line_id`) REFERENCES `line` (`id`),
  CONSTRAINT `bao_cao_nang_suat_ibfk_2` FOREIGN KEY (`ca_id`) REFERENCES `ca_lam` (`id`),
  CONSTRAINT `bao_cao_nang_suat_ibfk_3` FOREIGN KEY (`ma_hang_id`) REFERENCES `ma_hang` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ca_lam`
--

DROP TABLE IF EXISTS `ca_lam`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ca_lam` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ma_ca` varchar(20) NOT NULL,
  `ten_ca` varchar(100) NOT NULL,
  `gio_bat_dau` time DEFAULT NULL,
  `gio_ket_thuc` time DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ma_ca` (`ma_ca`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cong_doan`
--

DROP TABLE IF EXISTS `cong_doan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cong_doan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ma_cong_doan` varchar(50) DEFAULT NULL,
  `ten_cong_doan` varchar(200) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `la_cong_doan_thanh_pham` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ma_cong_doan` (`ma_cong_doan`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `line`
--

DROP TABLE IF EXISTS `line`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `line` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ma_line` varchar(50) NOT NULL,
  `ten_line` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ma_line` (`ma_line`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `line_moc_gio_set`
--

DROP TABLE IF EXISTS `line_moc_gio_set`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `line_moc_gio_set` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `line_id` int(11) NOT NULL,
  `ca_id` int(11) NOT NULL,
  `set_id` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_line_ca` (`line_id`,`ca_id`),
  KEY `ca_id` (`ca_id`),
  KEY `idx_set_id` (`set_id`),
  CONSTRAINT `line_moc_gio_set_ibfk_1` FOREIGN KEY (`line_id`) REFERENCES `line` (`id`),
  CONSTRAINT `line_moc_gio_set_ibfk_2` FOREIGN KEY (`ca_id`) REFERENCES `ca_lam` (`id`),
  CONSTRAINT `line_moc_gio_set_ibfk_3` FOREIGN KEY (`set_id`) REFERENCES `moc_gio_set` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ma_hang`
--

DROP TABLE IF EXISTS `ma_hang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ma_hang` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ma_hang` varchar(50) NOT NULL,
  `ten_hang` varchar(200) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ma_hang` (`ma_hang`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ma_hang_cong_doan`
--

DROP TABLE IF EXISTS `ma_hang_cong_doan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ma_hang_cong_doan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ma_hang_id` int(11) NOT NULL,
  `line_id` int(11) DEFAULT NULL,
  `cong_doan_id` int(11) NOT NULL,
  `thu_tu` int(11) NOT NULL DEFAULT 0,
  `bat_buoc` tinyint(1) DEFAULT 1,
  `la_cong_doan_tinh_luy_ke` tinyint(1) DEFAULT 0,
  `hieu_luc_tu` date DEFAULT NULL,
  `hieu_luc_den` date DEFAULT NULL,
  `ghi_chu` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `line_id` (`line_id`),
  KEY `cong_doan_id` (`cong_doan_id`),
  KEY `idx_ma_hang_line` (`ma_hang_id`,`line_id`),
  KEY `idx_thu_tu` (`thu_tu`),
  CONSTRAINT `ma_hang_cong_doan_ibfk_1` FOREIGN KEY (`ma_hang_id`) REFERENCES `ma_hang` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ma_hang_cong_doan_ibfk_2` FOREIGN KEY (`line_id`) REFERENCES `line` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ma_hang_cong_doan_ibfk_3` FOREIGN KEY (`cong_doan_id`) REFERENCES `cong_doan` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=84 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `moc_gio`
--

DROP TABLE IF EXISTS `moc_gio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `moc_gio` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ca_id` int(11) NOT NULL,
  `line_id` int(11) DEFAULT NULL,
  `set_id` int(11) DEFAULT NULL,
  `gio` time NOT NULL,
  `thu_tu` int(11) NOT NULL DEFAULT 0,
  `so_phut_hieu_dung_luy_ke` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_moc_gio_ca_line_gio` (`ca_id`,`line_id`,`gio`),
  KEY `idx_ca_thu_tu` (`ca_id`,`thu_tu`),
  KEY `idx_moc_gio_line` (`line_id`),
  KEY `idx_moc_gio_ca_line` (`ca_id`,`line_id`),
  KEY `idx_set_id_thu_tu` (`set_id`,`thu_tu`),
  CONSTRAINT `fk_moc_gio_line` FOREIGN KEY (`line_id`) REFERENCES `line` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_moc_gio_set` FOREIGN KEY (`set_id`) REFERENCES `moc_gio_set` (`id`),
  CONSTRAINT `moc_gio_ibfk_1` FOREIGN KEY (`ca_id`) REFERENCES `ca_lam` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `moc_gio_set`
--

DROP TABLE IF EXISTS `moc_gio_set`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `moc_gio_set` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ca_id` int(11) NOT NULL,
  `ten_set` varchar(100) NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ca_id` (`ca_id`),
  KEY `idx_is_default` (`ca_id`,`is_default`),
  CONSTRAINT `moc_gio_set_ibfk_1` FOREIGN KEY (`ca_id`) REFERENCES `ca_lam` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `nhap_lieu_nang_suat`
--

DROP TABLE IF EXISTS `nhap_lieu_nang_suat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nhap_lieu_nang_suat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bao_cao_id` int(11) NOT NULL,
  `cong_doan_id` int(11) NOT NULL,
  `moc_gio_id` int(11) NOT NULL,
  `so_luong` int(11) NOT NULL DEFAULT 0,
  `kieu_nhap` enum('tang_them','luy_ke') DEFAULT 'tang_them',
  `ghi_chu` text DEFAULT NULL,
  `nhap_boi` varchar(50) DEFAULT NULL,
  `nhap_luc` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_entry` (`bao_cao_id`,`cong_doan_id`,`moc_gio_id`),
  KEY `moc_gio_id` (`moc_gio_id`),
  KEY `idx_bao_cao` (`bao_cao_id`),
  KEY `idx_cong_doan` (`cong_doan_id`),
  CONSTRAINT `nhap_lieu_nang_suat_ibfk_1` FOREIGN KEY (`bao_cao_id`) REFERENCES `bao_cao_nang_suat` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nhap_lieu_nang_suat_ibfk_2` FOREIGN KEY (`cong_doan_id`) REFERENCES `cong_doan` (`id`),
  CONSTRAINT `nhap_lieu_nang_suat_ibfk_3` FOREIGN KEY (`moc_gio_id`) REFERENCES `moc_gio` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=952 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `nhap_lieu_nang_suat_audit`
--

DROP TABLE IF EXISTS `nhap_lieu_nang_suat_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nhap_lieu_nang_suat_audit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entry_id` int(11) NOT NULL,
  `old_value` int(11) DEFAULT NULL,
  `new_value` int(11) DEFAULT NULL,
  `updated_by` varchar(50) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reason` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_entry` (`entry_id`),
  KEY `idx_updated_at` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `phong_ban_line`
--

DROP TABLE IF EXISTS `phong_ban_line`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phong_ban_line` (
  `phong_ban_ma` varchar(50) NOT NULL,
  `line_id` int(11) NOT NULL,
  PRIMARY KEY (`phong_ban_ma`,`line_id`),
  KEY `line_id` (`line_id`),
  CONSTRAINT `phong_ban_line_ibfk_1` FOREIGN KEY (`line_id`) REFERENCES `line` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_line`
--

DROP TABLE IF EXISTS `user_line`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_line` (
  `ma_nv` varchar(50) NOT NULL,
  `line_id` int(11) NOT NULL,
  PRIMARY KEY (`ma_nv`,`line_id`),
  KEY `line_id` (`line_id`),
  CONSTRAINT `user_line_ibfk_1` FOREIGN KEY (`line_id`) REFERENCES `line` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-01-20 16:39:38
