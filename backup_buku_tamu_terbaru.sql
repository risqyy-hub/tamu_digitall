-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: buku_tamu_digital
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
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `role` varchar(10) DEFAULT NULL,
  `aktivitas` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_logs_user` (`user_id`),
  KEY `idx_logs_created` (`created_at`),
  CONSTRAINT `fk_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=233 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (1,1,'admin','admin','Setup akun via CLI','127.0.0.1',NULL,'2026-10-13 14:20:56'),(2,2,'petugas','petugas','Setup akun via CLI','127.0.0.1',NULL,'2026-10-13 14:20:56'),(3,NULL,'admin','admin','Login berhasil: admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; id-ID) WindowsPowerShell/5.1.26100.9444','2026-10-13 14:21:09'),(4,NULL,NULL,NULL,'Percobaan login gagal untuk: admin','::1','','2026-10-13 15:08:15'),(5,NULL,'admin','admin','Login berhasil: admin','::1','','2026-10-13 15:08:15'),(6,1,'admin','admin','Menambahkan tamu Budi Santoso (TM-261013-FB92)','::1','','2026-10-13 15:08:15'),(7,1,'admin','admin','Menambahkan mitra PT Maju Jaya (MT-261013-FD64)','::1','','2026-10-13 15:08:15'),(8,1,'admin','admin','Mendaftarkan tamu Budi Santoso sebagai tamu terdaftar (#1)','::1','','2026-10-13 15:08:15'),(9,1,'admin','admin','Menerbitkan QR #1 untuk tamu Budi Santoso','::1','','2026-10-13 15:08:15'),(10,NULL,'admin','admin','Login berhasil: admin','::1','','2026-10-13 15:09:19'),(11,1,'admin','admin','Menerbitkan QR #2 untuk tamu Budi Santoso','::1','','2026-10-13 15:09:19'),(12,NULL,NULL,NULL,'Percobaan login gagal untuk: admin','::1','','2026-10-13 15:12:03'),(13,NULL,'admin','admin','Login berhasil: admin','::1','','2026-10-13 15:12:03'),(14,1,'admin','admin','Menambahkan tamu Budi Santoso (TM-261013-CB2F)','::1','','2026-10-13 15:12:03'),(15,1,'admin','admin','Menambahkan mitra PT Maju Jaya (MT-261013-181C)','::1','','2026-10-13 15:12:03'),(16,1,'admin','admin','Menerbitkan QR #3 untuk tamu Budi Santoso','::1','','2026-10-13 15:12:03'),(17,NULL,'petugas','petugas','Login berhasil: petugas','::1','','2026-10-13 15:12:04'),(18,2,'petugas','petugas','Check-in kunjungan KT-261013-040B - Budi Santoso','::1','','2026-10-13 15:12:04'),(19,2,'petugas','petugas','Check-in kunjungan KT-261013-4604 - Budi Santoso (via QR)','::1','','2026-10-13 15:12:04'),(20,2,'petugas','petugas','Check-in kunjungan KT-261013-5D04 - Budi Santoso (via QR)','::1','','2026-10-13 15:12:04'),(21,2,'petugas','petugas','Menerima jaminan #1 (KTP) untuk kunjungan KT-261013-040B','::1','','2026-10-13 15:12:04'),(22,2,'petugas','petugas','Check-out DITOLAK (jaminan aktif): KT-261013-040B - KTP (1)','::1','','2026-10-13 15:12:04'),(23,2,'petugas','petugas','Mengembalikan jaminan #1 (KTP)','::1','','2026-10-13 15:12:04'),(24,2,'petugas','petugas','Check-out kunjungan KT-261013-040B (durasi 0 menit)','::1','','2026-10-13 15:12:04'),(25,2,'petugas','petugas','Check-out kunjungan KT-261013-4604 (durasi 0 menit)','::1','','2026-10-13 15:12:04'),(26,1,'admin','admin','Mengunduh CSV laporan kunjungan (3 baris, periode 2026-10-01 s.d. 2026-10-13)','::1','','2026-10-13 15:12:04'),(27,1,'admin','admin','Mengunduh CSV laporan kunjungan (2 baris, periode 2000-01-01 s.d. 2099-12-31)','::1','','2026-10-13 15:12:04'),(28,1,'admin','admin','Memperbarui pengaturan aplikasi','::1','','2026-10-13 15:12:04'),(29,1,'admin','admin','Logout: admin','::1','','2026-10-13 15:12:04'),(30,NULL,'admin','admin','Login berhasil: admin','::1','','2026-10-13 15:13:12'),(31,NULL,'admin','admin','Login berhasil: admin','::1','','2026-10-13 15:13:36'),(32,NULL,NULL,NULL,'Percobaan login gagal untuk: admin','::1','','2026-10-13 15:14:06'),(33,NULL,'admin','admin','Login berhasil: admin','::1','','2026-10-13 15:14:06'),(34,1,'admin','admin','Menambahkan tamu Budi Santoso (TM-261013-0D4E)','::1','','2026-10-13 15:14:06'),(35,1,'admin','admin','Menambahkan mitra PT Maju Jaya (MT-261013-E089)','::1','','2026-10-13 15:14:06'),(36,1,'admin','admin','Menerbitkan QR #7 untuk tamu Budi Santoso','::1','','2026-10-13 15:14:06'),(37,NULL,'petugas','petugas','Login berhasil: petugas','::1','','2026-10-13 15:14:06'),(38,2,'petugas','petugas','Check-in kunjungan KT-261013-91A2 - Budi Santoso','::1','','2026-10-13 15:14:07'),(39,2,'petugas','petugas','Check-in kunjungan KT-261013-023F - Budi Santoso (via QR)','::1','','2026-10-13 15:14:07'),(40,NULL,NULL,NULL,'Percobaan login gagal untuk: admin','::1','','2026-10-13 15:17:39'),(41,NULL,'admin','admin','Login berhasil: admin','::1','','2026-10-13 15:17:39'),(42,1,'admin','admin','Menambahkan tamu Budi Santoso (TM-261013-3888)','::1','','2026-10-13 15:17:39'),(43,1,'admin','admin','Menambahkan mitra PT Maju Jaya (MT-261013-0575)','::1','','2026-10-13 15:17:39'),(44,1,'admin','admin','Mendaftarkan tamu Budi Santoso sebagai tamu terdaftar (#2)','::1','','2026-10-13 15:17:39'),(45,1,'admin','admin','Menerbitkan QR #9 untuk tamu Budi Santoso','::1','','2026-10-13 15:17:39'),(46,NULL,'petugas','petugas','Login berhasil: petugas','::1','','2026-10-13 15:17:39'),(47,2,'petugas','petugas','Check-in kunjungan KT-261013-8641 - Budi Santoso','::1','','2026-10-13 15:17:39'),(48,2,'petugas','petugas','Check-in kunjungan KT-261013-65DE - Budi Santoso (via QR)','::1','','2026-10-13 15:17:39'),(49,2,'petugas','petugas','Check-in kunjungan KT-261013-064C - Budi Santoso (via QR)','::1','','2026-10-13 15:17:39'),(50,2,'petugas','petugas','Menerima jaminan #2 (KTP) untuk kunjungan KT-261013-8641','::1','','2026-10-13 15:17:39'),(51,2,'petugas','petugas','Check-out DITOLAK (jaminan aktif): KT-261013-8641 - KTP (2)','::1','','2026-10-13 15:17:39'),(52,2,'petugas','petugas','Mengembalikan jaminan #2 (KTP)','::1','','2026-10-13 15:17:39'),(53,2,'petugas','petugas','Check-out kunjungan KT-261013-8641 (durasi 0 menit)','::1','','2026-10-13 15:17:39'),(54,2,'petugas','petugas','Check-out kunjungan KT-261013-65DE (durasi 0 menit)','::1','','2026-10-13 15:17:39'),(55,1,'admin','admin','Mengunduh CSV laporan kunjungan (8 baris, periode 2026-10-01 s.d. 2026-10-13)','::1','','2026-10-13 15:17:39'),(56,1,'admin','admin','Mengunduh CSV laporan kunjungan (4 baris, periode 2000-01-01 s.d. 2099-12-31)','::1','','2026-10-13 15:17:39'),(57,1,'admin','admin','Memperbarui pengaturan aplikasi','::1','','2026-10-13 15:17:40'),(58,1,'admin','admin','Logout: admin','::1','','2026-10-13 15:17:40'),(59,NULL,'admin','admin','Login berhasil: admin','::1','','2026-10-13 15:17:55'),(60,NULL,'admin','admin','Login berhasil: admin','::1','','2026-10-13 15:18:03'),(61,NULL,NULL,NULL,'Percobaan login gagal untuk: admin','::1','','2026-10-13 15:19:06'),(62,NULL,'admin','admin','Login berhasil: admin','::1','','2026-10-13 15:19:06'),(63,1,'admin','admin','Menambahkan tamu Budi Santoso (TM-261013-E975)','::1','','2026-10-13 15:19:06'),(64,1,'admin','admin','Menambahkan mitra PT Maju Jaya (MT-261013-9F5F)','::1','','2026-10-13 15:19:06'),(65,1,'admin','admin','Mendaftarkan tamu Budi Santoso sebagai tamu terdaftar (#3)','::1','','2026-10-13 15:19:06'),(66,1,'admin','admin','Menerbitkan QR #13 untuk tamu Budi Santoso','::1','','2026-10-13 15:19:06'),(67,NULL,'petugas','petugas','Login berhasil: petugas','::1','','2026-10-13 15:19:06'),(68,2,'petugas','petugas','Check-in kunjungan KT-261013-5DD0 - Budi Santoso','::1','','2026-10-13 15:19:06'),(69,2,'petugas','petugas','Check-in kunjungan KT-261013-8190 - Budi Santoso (via QR)','::1','','2026-10-13 15:19:06'),(70,2,'petugas','petugas','Check-in kunjungan KT-261013-508D - Budi Santoso (via QR)','::1','','2026-10-13 15:19:06'),(71,2,'petugas','petugas','Menerima jaminan #3 (KTP) untuk kunjungan KT-261013-5DD0','::1','','2026-10-13 15:19:06'),(72,2,'petugas','petugas','Check-out DITOLAK (jaminan aktif): KT-261013-5DD0 - KTP (3)','::1','','2026-10-13 15:19:06'),(73,2,'petugas','petugas','Mengembalikan jaminan #3 (KTP)','::1','','2026-10-13 15:19:06'),(74,2,'petugas','petugas','Check-out kunjungan KT-261013-5DD0 (durasi 0 menit)','::1','','2026-10-13 15:19:06'),(75,2,'petugas','petugas','Check-out kunjungan KT-261013-8190 (durasi 0 menit)','::1','','2026-10-13 15:19:06'),(76,1,'admin','admin','Mengunduh CSV laporan kunjungan (11 baris, periode 2026-10-01 s.d. 2026-10-13)','::1','','2026-10-13 15:19:06'),(77,1,'admin','admin','Mengunduh CSV laporan kunjungan (6 baris, periode 2000-01-01 s.d. 2099-12-31)','::1','','2026-10-13 15:19:06'),(78,1,'admin','admin','Memperbarui pengaturan aplikasi','::1','','2026-10-13 15:19:06'),(79,1,'admin','admin','Mengganti password sendiri','::1','','2026-10-13 15:19:07'),(80,1,'admin','admin','Logout: admin','::1','','2026-10-13 15:19:07'),(81,NULL,'admin','admin','Login berhasil: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/153.0.0.0 Safari/537.36','2026-10-13 15:27:55'),(82,NULL,'admin','admin','Login berhasil: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/153.0.0.0 Safari/537.36','2026-10-13 15:30:49'),(83,NULL,'admin','admin','Login berhasil: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/153.0.0.0 Safari/537.36','2026-10-13 15:31:53'),(84,NULL,'admin','admin','Login berhasil: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/153.0.0.0 Safari/537.36','2026-10-13 15:33:56'),(85,NULL,'admin','admin','Login berhasil: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/153.0.0.0 Safari/537.36','2026-10-13 15:35:32'),(86,NULL,'admin','admin','Login berhasil: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/153.0.0.0 Safari/537.36','2026-10-13 15:37:30'),(87,NULL,'admin','admin','Login berhasil: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/153.0.0.0 Safari/537.36','2026-10-13 15:44:44'),(88,NULL,'admin','admin','Login berhasil: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/153.0.0.0 Safari/537.36','2026-10-13 15:46:10'),(89,NULL,'admin','admin','Login berhasil: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/153.0.0.0 Safari/537.36','2026-10-13 15:47:28'),(90,1,'admin','admin','Logout: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/153.0.0.0 Safari/537.36','2026-10-13 15:49:07'),(91,NULL,'petugas','petugas','Login berhasil: petugas','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/153.0.0.0 Safari/537.36','2026-10-13 15:49:19'),(92,NULL,'admin','admin','Login berhasil: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-13 19:04:17'),(93,1,'admin','admin','Logout: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-13 19:05:40'),(94,NULL,'petugas','petugas','Login berhasil: petugas','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-13 19:06:01'),(95,NULL,'petugas','petugas','Login berhasil: petugas','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-14 01:04:47'),(96,2,'petugas','petugas','Logout: petugas','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-14 01:06:38'),(97,NULL,'admin','admin','Login berhasil: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-14 01:06:52'),(98,1,'admin','admin','Logout: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-14 02:17:02'),(99,NULL,'petugas','petugas','Login berhasil: petugas','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-14 02:17:19'),(100,2,'petugas','petugas','Logout: petugas','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-14 03:22:26'),(101,NULL,'admin','admin','Login berhasil: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-14 03:22:46'),(102,1,'admin','admin','Logout: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-14 03:23:05'),(103,NULL,'petugas','petugas','Login berhasil: petugas','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-14 03:23:21'),(104,2,'petugas','petugas','Check-in kunjungan KT-261014-320E - Budi Santoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-14 03:24:21'),(105,2,'petugas','petugas','Check-out kunjungan KT-261014-320E (durasi 0 menit)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-14 03:24:39'),(106,NULL,'admin','admin','Login berhasil: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/153.0.0.0 Safari/537.36','2026-10-14 04:12:02'),(107,NULL,'admin','admin','Login berhasil: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/153.0.0.0 Safari/537.36','2026-10-14 04:13:27'),(108,1,'admin','admin','Logout: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/153.0.0.0 Safari/537.36','2026-10-14 04:13:38'),(109,NULL,'petugas','petugas','Login berhasil: petugas','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/153.0.0.0 Safari/537.36','2026-10-14 04:13:38'),(110,NULL,'admin','admin','Login berhasil: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/153.0.0.0 Safari/537.36','2026-10-14 04:19:43'),(111,NULL,'admin','admin','Login berhasil: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/153.0.0.0 Safari/537.36','2026-10-14 04:21:15'),(112,NULL,'admin','admin','Login berhasil: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/153.0.0.0 Safari/537.36','2026-10-14 04:22:35'),(113,2,'petugas','petugas','Check-in kunjungan KT-261014-15A3 - Hendra Gunawan','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-14 04:29:24'),(114,2,'petugas','petugas','Menerima jaminan #3 (KTP) untuk kunjungan KT-261014-15A3','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-14 04:29:40'),(115,NULL,'petugas','petugas','Login berhasil: petugas','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/153.0.0.0 Safari/537.36','2026-10-14 04:46:14'),(116,2,'petugas','petugas','Logout: petugas','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/153.0.0.0 Safari/537.36','2026-10-14 04:46:20'),(117,NULL,'admin','admin','Login berhasil: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/153.0.0.0 Safari/537.36','2026-10-14 04:46:20'),(118,2,'petugas','petugas','Logout: petugas','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-14 05:33:27'),(119,NULL,'admin','admin','Login berhasil: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-14 05:33:39'),(120,1,'admin','admin','Menerbitkan QR #3 untuk tamu Rina Kartika','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-14 05:37:12'),(121,NULL,'admin','admin','Login berhasil: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/153.0.0.0 Safari/537.36','2026-10-14 05:53:57'),(122,NULL,'admin','admin','Login berhasil: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/153.0.0.0 Safari/537.36','2026-10-14 05:56:15'),(123,1,'admin','admin','Logout: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-14 05:59:48'),(124,NULL,'petugas','petugas','Login berhasil: petugas','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-14 06:00:00'),(125,2,'petugas','petugas','Mengembalikan jaminan #1 (KTP)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-14 06:01:43'),(126,2,'petugas','petugas','Check-out kunjungan KT-DEMO-0005 (durasi 15 jam 23 menit)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-14 06:01:50'),(127,2,'petugas','petugas','Logout: petugas','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-14 06:04:48'),(128,NULL,'petugas','petugas','Login berhasil: petugas','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-14 06:05:02'),(129,NULL,'admin','admin','Login berhasil: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-14 12:11:58'),(130,1,'admin','admin','Logout: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-14 12:16:41'),(131,NULL,'petugas','petugas','Login berhasil: petugas','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-14 12:17:10'),(132,NULL,'admin','admin','Login berhasil: admin','::1','','2026-10-14 12:41:23'),(133,NULL,'petugas','petugas','Login berhasil: petugas','::1','','2026-10-14 12:41:23'),(134,2,'petugas','petugas','Check-in kunjungan KT-261014-4135 - Tester Hasil Kunjungan','::1','','2026-10-14 12:41:23'),(135,2,'petugas','petugas','Perbarui hasil kunjungan KT-261014-4135','::1','','2026-10-14 12:41:23'),(136,2,'petugas','petugas','Check-in kunjungan KT-261014-6D03 - Tester Hasil Kunjungan','::1','','2026-10-14 12:41:23'),(137,2,'petugas','petugas','Check-out kunjungan KT-261014-6D03 (durasi 0 menit)','::1','','2026-10-14 12:41:23'),(138,2,'petugas','petugas','Check-in kunjungan KT-261014-DBEA - Tester Hasil Kunjungan','::1','','2026-10-14 12:41:23'),(139,2,'petugas','petugas','Perbarui hasil kunjungan KT-261014-DBEA','::1','','2026-10-14 12:41:24'),(140,2,'petugas','petugas','Check-out kunjungan KT-261014-DBEA (durasi 0 menit)','::1','','2026-10-14 12:41:24'),(141,2,'petugas','petugas','Perbarui hasil kunjungan KT-261014-4135','::1','','2026-10-14 12:41:24'),(142,2,'petugas','petugas','Perbarui hasil kunjungan KT-261014-4135','::1','','2026-10-14 12:41:24'),(143,1,'admin','admin','Mengunduh CSV laporan kunjungan (10 baris, periode 2000-01-01 s.d. 2099-12-31)','::1','','2026-10-14 12:41:24'),(144,NULL,'admin','admin','Login berhasil: admin','::1','','2026-10-14 12:47:54'),(145,NULL,'petugas','petugas','Login berhasil: petugas','::1','','2026-10-14 12:47:54'),(146,2,'petugas','petugas','Check-in kunjungan KT-261014-1EA1 - Tester Hasil Kunjungan','::1','','2026-10-14 12:47:54'),(147,2,'petugas','petugas','Perbarui hasil kunjungan KT-261014-1EA1','::1','','2026-10-14 12:47:54'),(148,2,'petugas','petugas','Check-in kunjungan KT-261014-5995 - Tester Hasil Kunjungan','::1','','2026-10-14 12:47:54'),(149,2,'petugas','petugas','Check-out kunjungan KT-261014-5995 (durasi 0 menit)','::1','','2026-10-14 12:47:55'),(150,2,'petugas','petugas','Check-in kunjungan KT-261014-B6E8 - Tester Hasil Kunjungan','::1','','2026-10-14 12:47:55'),(151,2,'petugas','petugas','Perbarui hasil kunjungan KT-261014-B6E8','::1','','2026-10-14 12:47:55'),(152,2,'petugas','petugas','Check-out kunjungan KT-261014-B6E8 (durasi 0 menit)','::1','','2026-10-14 12:47:55'),(153,2,'petugas','petugas','Perbarui hasil kunjungan KT-261014-1EA1','::1','','2026-10-14 12:47:55'),(154,2,'petugas','petugas','Perbarui hasil kunjungan KT-261014-1EA1','::1','','2026-10-14 12:47:55'),(155,1,'admin','admin','Mengunduh CSV laporan kunjungan (10 baris, periode 2000-01-01 s.d. 2099-12-31)','::1','','2026-10-14 12:47:55'),(156,NULL,NULL,NULL,'Percobaan login gagal untuk: admin','::1','','2026-10-14 12:48:08'),(157,NULL,'admin','admin','Login berhasil: admin','::1','','2026-10-14 12:48:09'),(158,1,'admin','admin','Menambahkan tamu Budi Santoso (TM-261014-AB7D)','::1','','2026-10-14 12:48:09'),(159,1,'admin','admin','Menambahkan mitra PT Maju Jaya (MT-261014-72DC)','::1','','2026-10-14 12:48:09'),(160,1,'admin','admin','Mendaftarkan tamu Budi Santoso sebagai tamu terdaftar (#4)','::1','','2026-10-14 12:48:09'),(161,1,'admin','admin','Menerbitkan QR #4 untuk tamu Budi Santoso','::1','','2026-10-14 12:48:09'),(162,NULL,'petugas','petugas','Login berhasil: petugas','::1','','2026-10-14 12:48:10'),(163,2,'petugas','petugas','Check-in kunjungan KT-261014-1220 - Budi Santoso','::1','','2026-10-14 12:48:10'),(164,2,'petugas','petugas','Check-in kunjungan KT-261014-CDD4 - Budi Santoso (via QR)','::1','','2026-10-14 12:48:11'),(165,2,'petugas','petugas','Check-in kunjungan KT-261014-C539 - Budi Santoso (via QR)','::1','','2026-10-14 12:48:11'),(166,2,'petugas','petugas','Menerima jaminan #4 (KTP) untuk kunjungan KT-261014-1220','::1','','2026-10-14 12:48:11'),(167,2,'petugas','petugas','Check-out DITOLAK (jaminan aktif): KT-261014-1220 - KTP (4)','::1','','2026-10-14 12:48:11'),(168,2,'petugas','petugas','Mengembalikan jaminan #4 (KTP)','::1','','2026-10-14 12:48:11'),(169,2,'petugas','petugas','Check-out kunjungan KT-261014-1220 (durasi 0 menit)','::1','','2026-10-14 12:48:11'),(170,2,'petugas','petugas','Check-out kunjungan KT-261014-CDD4 (durasi 0 menit)','::1','','2026-10-14 12:48:11'),(171,1,'admin','admin','Mengunduh CSV laporan kunjungan (10 baris, periode 2026-10-01 s.d. 2026-10-14)','::1','','2026-10-14 12:48:11'),(172,1,'admin','admin','Mengunduh CSV laporan kunjungan (8 baris, periode 2000-01-01 s.d. 2099-12-31)','::1','','2026-10-14 12:48:11'),(173,1,'admin','admin','Memperbarui pengaturan aplikasi','::1','','2026-10-14 12:48:11'),(174,1,'admin','admin','Mengganti password sendiri','::1','','2026-10-14 12:48:12'),(175,1,'admin','admin','Logout: admin','::1','','2026-10-14 12:48:12'),(176,NULL,'admin','admin','Login berhasil: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/153.0.0.0 Safari/537.36','2026-10-14 12:51:45'),(177,NULL,'admin','admin','Login berhasil: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/153.0.0.0 Safari/537.36','2026-10-14 12:52:47'),(178,NULL,'admin','admin','Login berhasil: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/153.0.0.0 Safari/537.36','2026-10-14 12:53:47'),(179,NULL,'admin','admin','Login berhasil: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/153.0.0.0 Safari/537.36','2026-10-14 12:56:06'),(180,NULL,'admin','admin','Login berhasil: admin','::1','','2026-10-14 13:03:02'),(181,NULL,'petugas','petugas','Login berhasil: petugas','::1','','2026-10-14 13:03:03'),(182,2,'petugas','petugas','Check-in kunjungan KT-261014-6B31 - Tester Hasil Kunjungan','::1','','2026-10-14 13:03:03'),(183,2,'petugas','petugas','Perbarui hasil kunjungan KT-261014-6B31','::1','','2026-10-14 13:03:03'),(184,2,'petugas','petugas','Check-in kunjungan KT-261014-02AE - Tester Hasil Kunjungan','::1','','2026-10-14 13:03:03'),(185,2,'petugas','petugas','Check-out kunjungan KT-261014-02AE (durasi 0 menit)','::1','','2026-10-14 13:03:03'),(186,2,'petugas','petugas','Check-in kunjungan KT-261014-4E48 - Tester Hasil Kunjungan','::1','','2026-10-14 13:03:03'),(187,2,'petugas','petugas','Perbarui hasil kunjungan KT-261014-4E48','::1','','2026-10-14 13:03:03'),(188,2,'petugas','petugas','Check-out kunjungan KT-261014-4E48 (durasi 0 menit)','::1','','2026-10-14 13:03:03'),(189,2,'petugas','petugas','Perbarui hasil kunjungan KT-261014-6B31','::1','','2026-10-14 13:03:03'),(190,2,'petugas','petugas','Perbarui hasil kunjungan KT-261014-6B31','::1','','2026-10-14 13:03:03'),(191,1,'admin','admin','Mengunduh CSV laporan kunjungan (13 baris, periode 2000-01-01 s.d. 2099-12-31)','::1','','2026-10-14 13:03:04'),(192,NULL,NULL,NULL,'Percobaan login gagal untuk: admin','::1','','2026-10-14 13:03:24'),(193,NULL,'admin','admin','Login berhasil: admin','::1','','2026-10-14 13:03:24'),(194,1,'admin','admin','Menambahkan tamu Budi Santoso (TM-261014-2CDA)','::1','','2026-10-14 13:03:24'),(195,1,'admin','admin','Menambahkan mitra PT Maju Jaya (MT-261014-6848)','::1','','2026-10-14 13:03:24'),(196,1,'admin','admin','Mendaftarkan tamu Budi Santoso sebagai tamu terdaftar (#5)','::1','','2026-10-14 13:03:25'),(197,1,'admin','admin','Menerbitkan QR #8 untuk tamu Budi Santoso','::1','','2026-10-14 13:03:25'),(198,NULL,'petugas','petugas','Login berhasil: petugas','::1','','2026-10-14 13:03:25'),(199,2,'petugas','petugas','Check-in kunjungan KT-261014-BD86 - Budi Santoso','::1','','2026-10-14 13:03:25'),(200,2,'petugas','petugas','Check-in kunjungan KT-261014-748F - Budi Santoso (via QR)','::1','','2026-10-14 13:03:25'),(201,2,'petugas','petugas','Check-in kunjungan KT-261014-94DC - Budi Santoso (via QR)','::1','','2026-10-14 13:03:25'),(202,2,'petugas','petugas','Menerima jaminan #5 (KTP) untuk kunjungan KT-261014-BD86','::1','','2026-10-14 13:03:25'),(203,2,'petugas','petugas','Check-out DITOLAK (jaminan aktif): KT-261014-BD86 - KTP (5)','::1','','2026-10-14 13:03:25'),(204,2,'petugas','petugas','Mengembalikan jaminan #5 (KTP)','::1','','2026-10-14 13:03:25'),(205,2,'petugas','petugas','Check-out kunjungan KT-261014-BD86 (durasi 0 menit)','::1','','2026-10-14 13:03:25'),(206,2,'petugas','petugas','Check-out kunjungan KT-261014-748F (durasi 0 menit)','::1','','2026-10-14 13:03:25'),(207,1,'admin','admin','Mengunduh CSV laporan kunjungan (13 baris, periode 2026-10-01 s.d. 2026-10-14)','::1','','2026-10-14 13:03:26'),(208,1,'admin','admin','Mengunduh CSV laporan kunjungan (10 baris, periode 2000-01-01 s.d. 2099-12-31)','::1','','2026-10-14 13:03:26'),(209,1,'admin','admin','Memperbarui pengaturan aplikasi','::1','','2026-10-14 13:03:26'),(210,1,'admin','admin','Mengganti password sendiri','::1','','2026-10-14 13:03:27'),(211,1,'admin','admin','Logout: admin','::1','','2026-10-14 13:03:28'),(212,NULL,NULL,NULL,'Percobaan login gagal untuk: admin','::1','','2026-10-14 13:03:54'),(213,NULL,'admin','admin','Login berhasil: admin','::1','','2026-10-14 13:03:54'),(214,1,'admin','admin','Menambahkan tamu Budi Santoso (TM-261014-5A82)','::1','','2026-10-14 13:03:54'),(215,1,'admin','admin','Menambahkan mitra PT Maju Jaya (MT-261014-C868)','::1','','2026-10-14 13:03:54'),(216,1,'admin','admin','Mendaftarkan tamu Budi Santoso sebagai tamu terdaftar (#6)','::1','','2026-10-14 13:03:55'),(217,1,'admin','admin','Menerbitkan QR #12 untuk tamu Budi Santoso','::1','','2026-10-14 13:03:55'),(218,NULL,'petugas','petugas','Login berhasil: petugas','::1','','2026-10-14 13:03:55'),(219,2,'petugas','petugas','Check-in kunjungan KT-261014-3A56 - Budi Santoso','::1','','2026-10-14 13:03:55'),(220,2,'petugas','petugas','Check-in kunjungan KT-261014-50C0 - Budi Santoso (via QR)','::1','','2026-10-14 13:03:55'),(221,2,'petugas','petugas','Check-in kunjungan KT-261014-F6E8 - Budi Santoso (via QR)','::1','','2026-10-14 13:03:55'),(222,2,'petugas','petugas','Menerima jaminan #6 (KTP) untuk kunjungan KT-261014-3A56','::1','','2026-10-14 13:03:55'),(223,2,'petugas','petugas','Check-out DITOLAK (jaminan aktif): KT-261014-3A56 - KTP (6)','::1','','2026-10-14 13:03:55'),(224,2,'petugas','petugas','Mengembalikan jaminan #6 (KTP)','::1','','2026-10-14 13:03:55'),(225,2,'petugas','petugas','Check-out kunjungan KT-261014-3A56 (durasi 0 menit)','::1','','2026-10-14 13:03:55'),(226,2,'petugas','petugas','Check-out kunjungan KT-261014-50C0 (durasi 0 menit)','::1','','2026-10-14 13:03:55'),(227,1,'admin','admin','Mengunduh CSV laporan kunjungan (16 baris, periode 2026-10-01 s.d. 2026-10-14)','::1','','2026-10-14 13:03:56'),(228,1,'admin','admin','Mengunduh CSV laporan kunjungan (12 baris, periode 2000-01-01 s.d. 2099-12-31)','::1','','2026-10-14 13:03:56'),(229,1,'admin','admin','Memperbarui pengaturan aplikasi','::1','','2026-10-14 13:03:56'),(230,1,'admin','admin','Mengganti password sendiri','::1','','2026-10-14 13:03:57'),(231,1,'admin','admin','Logout: admin','::1','','2026-10-14 13:03:58'),(232,2,'petugas','petugas','Logout: petugas','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-14 13:07:19');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `guarantees`
--

DROP TABLE IF EXISTS `guarantees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `guarantees` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `visit_id` int(10) unsigned NOT NULL,
  `barang` varchar(150) NOT NULL,
  `jumlah` int(10) unsigned NOT NULL DEFAULT 1,
  `keterangan` text DEFAULT NULL,
  `bukti_file` varchar(255) DEFAULT NULL,
  `status` varchar(15) NOT NULL DEFAULT 'aktif' CHECK (`status` in ('aktif','dikembalikan')),
  `diterima_oleh` int(10) unsigned DEFAULT NULL,
  `dikembalikan_oleh` int(10) unsigned DEFAULT NULL,
  `diterima_at` datetime NOT NULL,
  `dikembalikan_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_guar_visit` (`visit_id`),
  KEY `idx_guar_status` (`status`),
  KEY `fk_guar_diterima` (`diterima_oleh`),
  KEY `fk_guar_kembali` (`dikembalikan_oleh`),
  CONSTRAINT `fk_guar_diterima` FOREIGN KEY (`diterima_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_guar_kembali` FOREIGN KEY (`dikembalikan_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_guar_visit` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `guarantees`
--

LOCK TABLES `guarantees` WRITE;
/*!40000 ALTER TABLE `guarantees` DISABLE KEYS */;
INSERT INTO `guarantees` VALUES (1,5,'KTP',1,'KTP dititipkan saat menunggu proses berkas',NULL,'dikembalikan',2,2,'2026-10-13 21:43:35','2026-10-14 13:01:43','2026-10-13 15:23:35','2026-10-14 06:01:43'),(2,2,'SIM A',1,'SIM dititipkan',NULL,'dikembalikan',2,2,'2026-10-13 17:23:35','2026-10-13 17:58:35','2026-10-13 15:23:35','2026-10-13 15:23:35'),(3,7,'KTP',1,NULL,NULL,'aktif',2,NULL,'2026-10-14 11:29:40',NULL,'2026-10-14 04:29:40','2026-10-14 04:29:40'),(4,14,'KTP',1,'KTP dititipkan',NULL,'dikembalikan',2,2,'2026-10-14 19:48:11','2026-10-14 19:48:11','2026-10-14 12:48:11','2026-10-14 12:48:11'),(5,22,'KTP',1,'KTP dititipkan',NULL,'dikembalikan',2,2,'2026-10-14 20:03:25','2026-10-14 20:03:25','2026-10-14 13:03:25','2026-10-14 13:03:25'),(6,25,'KTP',1,'KTP dititipkan',NULL,'dikembalikan',2,2,'2026-10-14 20:03:55','2026-10-14 20:03:55','2026-10-14 13:03:55','2026-10-14 13:03:55');
/*!40000 ALTER TABLE `guarantees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `judul` varchar(150) NOT NULL,
  `pesan` text DEFAULT NULL,
  `tipe` varchar(10) NOT NULL DEFAULT 'info' CHECK (`tipe` in ('info','success','warning','danger')),
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_notif_user` (`user_id`,`is_read`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,NULL,'Kunjungan baru','KT-261014-320E - Budi Santoso check-in pukul 10:24','info',0,'2026-10-14 03:24:21'),(2,NULL,'Kunjungan baru','KT-261014-15A3 - Hendra Gunawan check-in pukul 11:29','info',0,'2026-10-14 04:29:24'),(3,NULL,'QR Code diterbitkan','QR untuk Rina Kartika berlaku s.d. 28/11/2026 12:37','info',0,'2026-10-14 05:37:12'),(4,NULL,'Kunjungan baru','KT-261014-4135 - Tester Hasil Kunjungan check-in pukul 19:41','info',0,'2026-10-14 12:41:23'),(5,NULL,'Kunjungan baru','KT-261014-6D03 - Tester Hasil Kunjungan check-in pukul 19:41','info',0,'2026-10-14 12:41:23'),(6,NULL,'Kunjungan baru','KT-261014-DBEA - Tester Hasil Kunjungan check-in pukul 19:41','info',0,'2026-10-14 12:41:23'),(7,NULL,'Kunjungan baru','KT-261014-1EA1 - Tester Hasil Kunjungan check-in pukul 19:47','info',0,'2026-10-14 12:47:54'),(8,NULL,'Kunjungan baru','KT-261014-5995 - Tester Hasil Kunjungan check-in pukul 19:47','info',0,'2026-10-14 12:47:54'),(9,NULL,'Kunjungan baru','KT-261014-B6E8 - Tester Hasil Kunjungan check-in pukul 19:47','info',0,'2026-10-14 12:47:55'),(10,NULL,'QR Code diterbitkan','QR untuk Budi Santoso berlaku s.d. 28/11/2026 19:48','info',0,'2026-10-14 12:48:09'),(11,NULL,'Kunjungan baru','KT-261014-1220 - Budi Santoso check-in pukul 19:48','info',0,'2026-10-14 12:48:10'),(12,NULL,'Kunjungan baru','KT-261014-CDD4 - Budi Santoso check-in pukul 19:48','info',0,'2026-10-14 12:48:11'),(13,NULL,'Kunjungan baru','KT-261014-C539 - Budi Santoso check-in pukul 19:48','info',0,'2026-10-14 12:48:11'),(14,NULL,'Check-out ditolak','KT-261014-1220 masih memiliki jaminan aktif: KTP (4)','warning',0,'2026-10-14 12:48:11'),(15,NULL,'Kunjungan baru','KT-261014-6B31 - Tester Hasil Kunjungan check-in pukul 20:03','info',0,'2026-10-14 13:03:03'),(16,NULL,'Kunjungan baru','KT-261014-02AE - Tester Hasil Kunjungan check-in pukul 20:03','info',0,'2026-10-14 13:03:03'),(17,NULL,'Kunjungan baru','KT-261014-4E48 - Tester Hasil Kunjungan check-in pukul 20:03','info',0,'2026-10-14 13:03:03'),(18,NULL,'QR Code diterbitkan','QR untuk Budi Santoso berlaku s.d. 28/11/2026 20:03','info',0,'2026-10-14 13:03:25'),(19,NULL,'Kunjungan baru','KT-261014-BD86 - Budi Santoso check-in pukul 20:03','info',0,'2026-10-14 13:03:25'),(20,NULL,'Kunjungan baru','KT-261014-748F - Budi Santoso check-in pukul 20:03','info',0,'2026-10-14 13:03:25'),(21,NULL,'Kunjungan baru','KT-261014-94DC - Budi Santoso check-in pukul 20:03','info',0,'2026-10-14 13:03:25'),(22,NULL,'Check-out ditolak','KT-261014-BD86 masih memiliki jaminan aktif: KTP (5)','warning',0,'2026-10-14 13:03:25'),(23,NULL,'QR Code diterbitkan','QR untuk Budi Santoso berlaku s.d. 28/11/2026 20:03','info',0,'2026-10-14 13:03:55'),(24,NULL,'Kunjungan baru','KT-261014-3A56 - Budi Santoso check-in pukul 20:03','info',0,'2026-10-14 13:03:55'),(25,NULL,'Kunjungan baru','KT-261014-50C0 - Budi Santoso check-in pukul 20:03','info',0,'2026-10-14 13:03:55'),(26,NULL,'Kunjungan baru','KT-261014-F6E8 - Budi Santoso check-in pukul 20:03','info',0,'2026-10-14 13:03:55'),(27,NULL,'Check-out ditolak','KT-261014-3A56 masih memiliki jaminan aktif: KTP (6)','warning',0,'2026-10-14 13:03:55');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `partner_companies`
--

DROP TABLE IF EXISTS `partner_companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `partner_companies` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `kode_mitra` varchar(30) NOT NULL,
  `nama_perusahaan` varchar(150) NOT NULL,
  `alamat` text DEFAULT NULL,
  `telepon` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `pic_nama` varchar(100) DEFAULT NULL,
  `pic_telepon` varchar(20) DEFAULT NULL,
  `status` varchar(10) NOT NULL DEFAULT 'aktif' CHECK (`status` in ('aktif','nonaktif')),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_mitra_kode` (`kode_mitra`),
  KEY `idx_mitra_nama` (`nama_perusahaan`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `partner_companies`
--

LOCK TABLES `partner_companies` WRITE;
/*!40000 ALTER TABLE `partner_companies` DISABLE KEYS */;
INSERT INTO `partner_companies` VALUES (1,'MT-250101','PT Maju Jaya','Jl. Merdeka No. 10, Jakarta','021-5551001','admin@majujaya.test','Bapak Hendra','08121112221','aktif','2026-10-13 15:23:35','2026-10-13 15:23:35'),(2,'MT-250102','PT Sejahtera','Jl. Sudirman No. 45, Jakarta','021-5551002','info@sejahtera.test','Ibu Lestari','08121112222','aktif','2026-10-13 15:23:35','2026-10-13 15:23:35'),(3,'MT-250103','Koperasi Rakyat','Jl. Gatot Subroto No. 3, Bandung','022-5551003','koperasi@rakyat.test','Pak Yusuf','08121112223','nonaktif','2026-10-13 15:23:35','2026-10-13 15:23:35'),(4,'MT-261014-72DC','PT Maju Jaya','Jl. Merdeka 1',NULL,NULL,NULL,NULL,'aktif','2026-10-14 12:48:09','2026-10-14 12:48:09'),(5,'MT-261014-6848','PT Maju Jaya','Jl. Merdeka 1',NULL,NULL,NULL,NULL,'aktif','2026-10-14 13:03:24','2026-10-14 13:03:24'),(6,'MT-261014-C868','PT Maju Jaya','Jl. Merdeka 1',NULL,NULL,NULL,NULL,'aktif','2026-10-14 13:03:54','2026-10-14 13:03:54');
/*!40000 ALTER TABLE `partner_companies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `registered_visitors`
--

DROP TABLE IF EXISTS `registered_visitors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `registered_visitors` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `visitor_id` int(10) unsigned NOT NULL,
  `mitra_id` int(10) unsigned DEFAULT NULL,
  `no_kartu` varchar(50) DEFAULT NULL,
  `status` varchar(10) NOT NULL DEFAULT 'aktif' CHECK (`status` in ('aktif','nonaktif')),
  `registered_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reg_no_kartu` (`no_kartu`),
  KEY `idx_reg_visitor` (`visitor_id`),
  KEY `idx_reg_mitra` (`mitra_id`),
  CONSTRAINT `fk_reg_mitra` FOREIGN KEY (`mitra_id`) REFERENCES `partner_companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_reg_visitor` FOREIGN KEY (`visitor_id`) REFERENCES `visitors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `registered_visitors`
--

LOCK TABLES `registered_visitors` WRITE;
/*!40000 ALTER TABLE `registered_visitors` DISABLE KEYS */;
INSERT INTO `registered_visitors` VALUES (1,1,1,'KRT-0001','aktif','2026-09-13 15:23:35','2026-10-13 15:23:35','2026-10-13 15:23:35'),(2,2,2,'KRT-0002','aktif','2026-09-23 15:23:35','2026-10-13 15:23:35','2026-10-13 15:23:35'),(3,4,NULL,'KRT-0003','aktif','2026-10-03 15:23:35','2026-10-13 15:23:35','2026-10-13 15:23:35'),(4,1,1,'KRT-194809','aktif','2026-10-14 12:48:09','2026-10-14 12:48:09','2026-10-14 12:48:09'),(5,1,1,'KRT-200324','aktif','2026-10-14 13:03:24','2026-10-14 13:03:24','2026-10-14 13:03:24'),(6,1,1,'KRT-200355','aktif','2026-10-14 13:03:55','2026-10-14 13:03:55','2026-10-14 13:03:55');
/*!40000 ALTER TABLE `registered_visitors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `key_name` varchar(50) NOT NULL,
  `value` text DEFAULT NULL,
  `deskripsi` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_settings_key` (`key_name`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'nama_instansi','Kantor Pusat Contoh','Nama instansi / kantor','2026-10-13 15:12:04'),(2,'alamat_instansi','Jl. Sudirman 10','Alamat instansi','2026-10-13 15:12:04'),(3,'kontak_instansi','021-555-0123','Nomor telepon instansi','2026-10-13 15:12:04'),(4,'jam_buka','08:00','Jam mulai layanan buku tamu','2026-10-13 14:20:38'),(5,'jam_tutup','16:30','Jam selesai layanan buku tamu','2026-10-13 15:12:04'),(6,'qr_default_days','45','Masa berlaku default QR (hari)','2026-10-13 15:12:04');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telepon` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(10) NOT NULL DEFAULT 'petugas' CHECK (`role` in ('admin','petugas')),
  `foto` varchar(255) DEFAULT NULL,
  `status` varchar(10) NOT NULL DEFAULT 'aktif' CHECK (`status` in ('aktif','nonaktif')),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_username` (`username`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Administrator Sistem','admin','admin@local.test',NULL,'$2y$12$sjEn8Z7EBPgwHLNDM61apOAFh3vJRQiz.Z7vJJsO6EO5ulEtPR416','admin',NULL,'aktif','2026-10-13 14:20:56','2026-10-14 13:03:57'),(2,'Petugas Resepsionis','petugas','petugas@local.test',NULL,'$2y$10$EI.PSoQ0cA0oLZ5E.BRR/eWjc1s/079LDOM9fd3bpLmlnoUsq8QzO','petugas',NULL,'aktif','2026-10-13 14:20:56','2026-10-13 14:20:56');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `visitor_qr_codes`
--

DROP TABLE IF EXISTS `visitor_qr_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `visitor_qr_codes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `registered_visitor_id` int(10) unsigned NOT NULL,
  `token_hash` char(64) NOT NULL,
  `status` varchar(10) NOT NULL DEFAULT 'aktif' CHECK (`status` in ('aktif','dicabut')),
  `valid_from` datetime NOT NULL,
  `valid_until` datetime DEFAULT NULL,
  `issued_by` int(10) unsigned DEFAULT NULL,
  `revoked_at` datetime DEFAULT NULL,
  `revoked_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_qr_token_hash` (`token_hash`),
  KEY `idx_qr_registered` (`registered_visitor_id`),
  KEY `idx_qr_status` (`status`),
  KEY `fk_qr_issued_by` (`issued_by`),
  KEY `fk_qr_revoked_by` (`revoked_by`),
  CONSTRAINT `fk_qr_issued_by` FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_qr_registered` FOREIGN KEY (`registered_visitor_id`) REFERENCES `registered_visitors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_qr_revoked_by` FOREIGN KEY (`revoked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `visitor_qr_codes`
--

LOCK TABLES `visitor_qr_codes` WRITE;
/*!40000 ALTER TABLE `visitor_qr_codes` DISABLE KEYS */;
INSERT INTO `visitor_qr_codes` VALUES (1,1,'2e8058ad339096aabc4c778e8263bbd6df5abf24b8a201b8eb4e610a8f009b67','aktif','2026-09-13 22:23:35','2026-12-12 22:23:35',1,NULL,NULL,'2026-10-13 15:23:35','2026-10-13 15:23:35'),(2,2,'8aded2a4865981257bbc39e919aeb5e140cb906a62f47d3328058ff765e3824c','dicabut','2026-09-23 22:23:35',NULL,1,NULL,NULL,'2026-10-13 15:23:35','2026-10-13 15:23:35'),(3,3,'feddf917d4c2d87a54c95b7e3061529805475c6e933ad1f17cbfd135d24f824c','aktif','2026-10-14 12:37:00','2026-11-28 12:37:00',1,NULL,NULL,'2026-10-14 05:37:12','2026-10-14 05:37:12'),(4,4,'83a6496a181af7792fa04405a4869360e865a89d6bec160e951e61c2824bdc7d','aktif','2026-10-14 19:48:09','2026-11-28 19:48:09',1,NULL,NULL,'2026-10-14 12:48:09','2026-10-14 12:48:09'),(5,4,'e27fee9ea1e2bcd0910329f711f82af6d364564f1afb406fec449966e2d0ee38','aktif','2026-10-14 19:48:10',NULL,1,NULL,NULL,'2026-10-14 12:48:10','2026-10-14 12:48:10'),(6,4,'4f9233695542bad803d04920e3c52509058d552a97e8612464f9d15c68ae3f89','aktif','2026-10-14 17:48:11','2026-10-14 18:48:11',1,NULL,NULL,'2026-10-14 12:48:11','2026-10-14 12:48:11'),(7,4,'591d6603cea7911cdec9f0ff1c24ea9e6b2d08df0cc1744ea4f0fcd39859df9e','dicabut','2026-10-14 19:48:11',NULL,1,NULL,NULL,'2026-10-14 12:48:11','2026-10-14 12:48:11'),(8,5,'deced8e066b47bc437056275e334f950d0fed6d9cffc5afbf665a97ddefe7108','aktif','2026-10-14 20:03:25','2026-11-28 20:03:25',1,NULL,NULL,'2026-10-14 13:03:25','2026-10-14 13:03:25'),(9,5,'7a2523b4d7751f21f6590d3fccf16ad82061844109d646d93045a7b5f60ca7fe','aktif','2026-10-14 20:03:25',NULL,1,NULL,NULL,'2026-10-14 13:03:25','2026-10-14 13:03:25'),(10,5,'6eead977e297532e73542d31ae5eb9b8e779302539c81aff0d9ebfe885cefcca','aktif','2026-10-14 18:03:25','2026-10-14 19:03:25',1,NULL,NULL,'2026-10-14 13:03:25','2026-10-14 13:03:25'),(11,5,'baec4fc19b3da730c2e781da6032e8102c2b2bbf4f4ab51ec4a6f64e2063db5f','dicabut','2026-10-14 20:03:25',NULL,1,NULL,NULL,'2026-10-14 13:03:25','2026-10-14 13:03:25'),(12,6,'0be8a785351404518c9543d3ba09bb11a4c0ccc929886191b1f261e9e96cbc9a','aktif','2026-10-14 20:03:55','2026-11-28 20:03:55',1,NULL,NULL,'2026-10-14 13:03:55','2026-10-14 13:03:55'),(13,6,'53a61a396eec89ea76cf8b8d88ff3dd2d11def13a37161228b60a8de61bc44d7','aktif','2026-10-14 20:03:55',NULL,1,NULL,NULL,'2026-10-14 13:03:55','2026-10-14 13:03:55'),(14,6,'632e4dff66005bfa038684c8998130e4d10330798c39b6c58f7030152208e4ec','aktif','2026-10-14 18:03:55','2026-10-14 19:03:55',1,NULL,NULL,'2026-10-14 13:03:55','2026-10-14 13:03:55'),(15,6,'f44a9f1ea57bb9e2b912bd65ddaae8289db64c31cd8d53be06dbe258adb90746','dicabut','2026-10-14 20:03:55',NULL,1,NULL,NULL,'2026-10-14 13:03:55','2026-10-14 13:03:55');
/*!40000 ALTER TABLE `visitor_qr_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `visitors`
--

DROP TABLE IF EXISTS `visitors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `visitors` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `kode_tamu` varchar(30) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `jenis_identitas` varchar(30) NOT NULL DEFAULT 'KTP' CHECK (`jenis_identitas` in ('KTP','SIM','Paspor','Kartu Pegawai','Lainnya')),
  `no_identitas` varchar(50) DEFAULT NULL,
  `telepon` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_visitors_kode` (`kode_tamu`),
  KEY `idx_visitors_nama` (`nama`),
  KEY `idx_visitors_identitas` (`no_identitas`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `visitors`
--

LOCK TABLES `visitors` WRITE;
/*!40000 ALTER TABLE `visitors` DISABLE KEYS */;
INSERT INTO `visitors` VALUES (1,'TM-2501001','Budi Santoso','KTP','3201234567890001','081234567890','budi@mail.test','Jl. Anggrek 1, Jakarta','2026-10-13 15:23:35','2026-10-13 15:23:35'),(2,'TM-2501002','Siti Aminah','KTP','3201234567890002','081234567891','siti@mail.test','Jl. Melati 2, Bekasi','2026-10-13 15:23:35','2026-10-13 15:23:35'),(3,'TM-2501003','Andi Wijaya','SIM','3201234567890003','081234567892','','Jl. Kenanga 3, Depok','2026-10-13 15:23:35','2026-10-13 15:23:35'),(4,'TM-2501004','Rina Kartika','Kartu Pegawai','PGW-0099','081234567893','rina@mail.test','Perum Griya Asri, Bogor','2026-10-13 15:23:35','2026-10-13 15:23:35'),(5,'TM-2501005','Hendra Gunawan','Paspor','C1234567','081234567894','','Jl. Cendana 5, Tangerang','2026-10-13 15:23:35','2026-10-13 15:23:35'),(8,'TM-261014-AB7D','Budi Santoso','KTP','3201234567890001','081234567890',NULL,NULL,'2026-10-14 12:48:09','2026-10-14 12:48:09'),(9,'TM-XC743','Korban Manipulasi','KTP',NULL,NULL,NULL,NULL,'2026-10-14 12:48:11','2026-10-14 12:48:11'),(13,'TM-261014-2CDA','Budi Santoso','KTP','3201234567890001','081234567890',NULL,NULL,'2026-10-14 13:03:24','2026-10-14 13:03:24'),(14,'TM-X2F73','Korban Manipulasi','KTP',NULL,NULL,NULL,NULL,'2026-10-14 13:03:25','2026-10-14 13:03:25'),(15,'TM-261014-5A82','Budi Santoso','KTP','3201234567890001','081234567890',NULL,NULL,'2026-10-14 13:03:54','2026-10-14 13:03:54'),(16,'TM-X44C0','Korban Manipulasi','KTP',NULL,NULL,NULL,NULL,'2026-10-14 13:03:55','2026-10-14 13:03:55');
/*!40000 ALTER TABLE `visitors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `visits`
--

DROP TABLE IF EXISTS `visits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `visits` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `kode_kunjungan` varchar(30) NOT NULL,
  `visitor_id` int(10) unsigned NOT NULL,
  `registered_visitor_id` int(10) unsigned DEFAULT NULL,
  `mitra_id` int(10) unsigned DEFAULT NULL,
  `qr_id` int(10) unsigned DEFAULT NULL,
  `instansi` varchar(150) DEFAULT NULL,
  `tujuan` varchar(200) NOT NULL,
  `pegawai_ditemui` varchar(120) NOT NULL,
  `keperluan` text DEFAULT NULL,
  `jam_masuk` datetime NOT NULL,
  `jam_keluar` datetime DEFAULT NULL,
  `durasi_menit` int(10) unsigned DEFAULT NULL,
  `status` varchar(15) NOT NULL DEFAULT 'berlangsung' CHECK (`status` in ('berlangsung','selesai')),
  `petugas_masuk_id` int(10) unsigned DEFAULT NULL,
  `petugas_keluar_id` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `hasil_kunjungan` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_visits_kode` (`kode_kunjungan`),
  KEY `idx_visits_visitor` (`visitor_id`),
  KEY `idx_visits_registered` (`registered_visitor_id`),
  KEY `idx_visits_mitra` (`mitra_id`),
  KEY `idx_visits_qr` (`qr_id`),
  KEY `idx_visits_status` (`status`),
  KEY `idx_visits_masuk` (`jam_masuk`),
  KEY `fk_visits_pm` (`petugas_masuk_id`),
  KEY `fk_visits_pk` (`petugas_keluar_id`),
  CONSTRAINT `fk_visits_mitra` FOREIGN KEY (`mitra_id`) REFERENCES `partner_companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_visits_pk` FOREIGN KEY (`petugas_keluar_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_visits_pm` FOREIGN KEY (`petugas_masuk_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_visits_qr` FOREIGN KEY (`qr_id`) REFERENCES `visitor_qr_codes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_visits_reg` FOREIGN KEY (`registered_visitor_id`) REFERENCES `registered_visitors` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_visits_visitor` FOREIGN KEY (`visitor_id`) REFERENCES `visitors` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `visits`
--

LOCK TABLES `visits` WRITE;
/*!40000 ALTER TABLE `visits` DISABLE KEYS */;
INSERT INTO `visits` VALUES (1,'KT-DEMO-0001',2,2,2,NULL,NULL,'Bidang Keuangan','Ibu Sari Wahyuni','Penyerahan dokumen faktur','2026-10-13 19:23:35','2026-10-13 20:23:35',60,'selesai',2,2,'2026-10-13 15:23:35','2026-10-13 15:23:35',NULL),(2,'KT-DEMO-0002',3,NULL,NULL,NULL,'Sendiri','Bidang Umum','Bapak Andi Pratama','Konsultasi perizinan','2026-10-13 17:23:35','2026-10-13 18:03:35',40,'selesai',2,2,'2026-10-13 15:23:35','2026-10-13 15:23:35',NULL),(3,'KT-DEMO-0003',5,NULL,1,NULL,NULL,'Direksi','Bapak Direktur','Rapat koordinasi tahunan','2026-10-12 09:00:00','2026-10-12 11:00:00',120,'selesai',1,1,'2026-10-13 15:23:35','2026-10-13 15:23:35',NULL),(4,'KT-DEMO-0004',1,NULL,1,NULL,NULL,'Bidang Teknologi Informasi','Ibu Rina Kartika','Perbaikan jaringan','2026-10-10 10:00:00','2026-10-10 12:30:00',150,'selesai',2,2,'2026-10-13 15:23:35','2026-10-13 15:23:35',NULL),(5,'KT-DEMO-0005',1,1,1,NULL,NULL,'Bidang SDM','Ibu Maya Putri','Pengajuan berkas karyawan baru','2026-10-13 21:38:35','2026-10-14 13:01:50',923,'selesai',2,2,'2026-10-13 15:23:35','2026-10-14 06:01:50',NULL),(6,'KT-261014-320E',1,NULL,1,NULL,NULL,'Bidang Umum','Risqy',NULL,'2026-10-14 10:24:21','2026-10-14 10:24:39',0,'selesai',2,2,'2026-10-14 03:24:21','2026-10-14 03:24:39',NULL),(7,'KT-261014-15A3',5,NULL,2,NULL,NULL,'Direksi','Risqy',NULL,'2026-10-14 11:29:24',NULL,NULL,'berlangsung',2,NULL,'2026-10-14 04:29:24','2026-10-14 04:29:24',NULL),(14,'KT-261014-1220',1,NULL,NULL,NULL,NULL,'Bidang Keuangan','Ibu Sari','Administrasi','2026-10-14 19:48:10','2026-10-14 19:48:11',0,'selesai',2,2,'2026-10-14 12:48:10','2026-10-14 12:48:11',NULL),(15,'KT-261014-CDD4',1,4,1,5,'PT Maju Jaya','Bidang Umum','Pak Andi',NULL,'2026-10-14 19:48:10','2026-10-14 19:48:11',0,'selesai',2,2,'2026-10-14 12:48:10','2026-10-14 12:48:11',NULL),(16,'KT-261014-C539',1,4,1,5,'PT Maju Jaya','Bidang SDM','Ibu Rina',NULL,'2026-10-14 19:48:11',NULL,NULL,'berlangsung',2,NULL,'2026-10-14 12:48:11','2026-10-14 12:48:11',NULL),(22,'KT-261014-BD86',1,NULL,NULL,NULL,NULL,'Bidang Keuangan','Ibu Sari','Administrasi','2026-10-14 20:03:25','2026-10-14 20:03:25',0,'selesai',2,2,'2026-10-14 13:03:25','2026-10-14 13:03:25',NULL),(23,'KT-261014-748F',1,5,1,9,'PT Maju Jaya','Bidang Umum','Pak Andi',NULL,'2026-10-14 20:03:25','2026-10-14 20:03:25',0,'selesai',2,2,'2026-10-14 13:03:25','2026-10-14 13:03:25',NULL),(24,'KT-261014-94DC',1,5,1,9,'PT Maju Jaya','Bidang SDM','Ibu Rina',NULL,'2026-10-14 20:03:25',NULL,NULL,'berlangsung',2,NULL,'2026-10-14 13:03:25','2026-10-14 13:03:25',NULL),(25,'KT-261014-3A56',1,NULL,NULL,NULL,NULL,'Bidang Keuangan','Ibu Sari','Administrasi','2026-10-14 20:03:55','2026-10-14 20:03:55',0,'selesai',2,2,'2026-10-14 13:03:55','2026-10-14 13:03:55',NULL),(26,'KT-261014-50C0',1,6,1,13,'PT Maju Jaya','Bidang Umum','Pak Andi',NULL,'2026-10-14 20:03:55','2026-10-14 20:03:55',0,'selesai',2,2,'2026-10-14 13:03:55','2026-10-14 13:03:55',NULL),(27,'KT-261014-F6E8',1,6,1,13,'PT Maju Jaya','Bidang SDM','Ibu Rina',NULL,'2026-10-14 20:03:55',NULL,NULL,'berlangsung',2,NULL,'2026-10-14 13:03:55','2026-10-14 13:03:55',NULL);
/*!40000 ALTER TABLE `visits` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-14 21:00:43
