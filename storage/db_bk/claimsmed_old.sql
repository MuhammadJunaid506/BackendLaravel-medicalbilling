-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 16, 2022 at 02:00 PM
-- Server version: 10.4.24-MariaDB
-- PHP Version: 8.1.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `claimsmed`
--

-- --------------------------------------------------------

--
-- Table structure for table `cm_admins`
--

CREATE TABLE `cm_admins` (
  `id` int(11) NOT NULL,
  `user_id` bigint(20) NOT NULL DEFAULT 0,
  `type` int(11) NOT NULL,
  `access_token` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `cm_admins`
--

INSERT INTO `cm_admins` (`id`, `user_id`, `type`, `access_token`, `created_at`, `updated_at`) VALUES
(6, 0, 1, 'lUzufQXdLwvoOgLqNnA4tHifWIeOi0tK9h36o2MK', '2022-07-15 14:23:33', '2022-07-15 21:23:33'),
(7, 8, 1, 'RDglCk8rKMz6XaYaAOzlKX8F4EELdFZfzwbgdOhB', '2022-07-18 12:19:49', '2022-07-18 12:19:49'),
(8, 9, 1, 'prCNhdOdZl2CxVme44pOBfR7p58L38ifkolso7rJ', '2022-07-18 13:30:12', '2022-07-18 13:30:12'),
(9, 17, 0, 'oR9xgeQbkvUz6w2oogd7ag3IWlzs0LZXJK5po2fI', '2022-07-20 14:46:43', '2022-07-20 14:46:43'),
(10, 18, 0, 'hkTEAuzsx2pMadTCdeHCJA3dkG4FZDfTHz6uYual', '2022-07-20 15:03:25', '2022-07-20 15:03:25'),
(11, 22, 0, 'aXbgTdobPjxpULvvykTEUyioDcXPLboMdyI1EEza', '2022-07-21 13:14:47', '2022-07-21 13:14:47'),
(12, 23, 0, 'GMm6BBPEuK8HclTl6ZdUxKDB13Td99IkAmjMItJQ', '2022-07-22 14:41:33', '2022-07-22 14:41:33'),
(13, 31, 1, 'tMeAxqxPpidRUToJItsCterzRZe3ifPZ6KFiwIjp', '2022-09-01 09:45:56', '2022-09-01 09:45:56'),
(14, 33, 1, 'JQKEI3rV3dElHPWiBmj90OPaTTs7hvuA3SXlTkol', '2022-09-01 23:18:40', '2022-09-01 23:18:40');

-- --------------------------------------------------------

--
-- Table structure for table `cm_assign_credentialingtask`
--

CREATE TABLE `cm_assign_credentialingtask` (
  `id` bigint(20) NOT NULL,
  `company_id` int(11) NOT NULL DEFAULT 0,
  `provider_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL DEFAULT 0,
  `task_id` bigint(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `cm_assign_credentialingtask`
--

INSERT INTO `cm_assign_credentialingtask` (`id`, `company_id`, `provider_id`, `user_id`, `task_id`, `created_at`, `updated_at`) VALUES
(5, 1, 41, 40, 39, '2022-09-07 08:50:40', '2022-09-07 13:50:40'),
(7, 1, 41, 37, 39, '2022-09-07 10:23:06', '2022-09-07 15:23:06'),
(22, 1, 50, 37, 59, '2022-09-11 10:37:43', '2022-09-11 10:37:43'),
(23, 1, 50, 37, 60, '2022-09-11 10:37:43', '2022-09-11 10:37:43'),
(24, 1, 50, 37, 61, '2022-09-11 10:37:43', '2022-09-11 10:37:43'),
(25, 1, 50, 37, 62, '2022-09-11 10:37:43', '2022-09-11 10:37:43'),
(26, 1, 50, 37, 63, '2022-09-11 10:37:43', '2022-09-11 10:37:43'),
(27, 1, 50, 37, 64, '2022-09-11 10:37:43', '2022-09-11 10:37:43'),
(28, 1, 50, 40, 59, '2022-09-11 10:37:51', '2022-09-11 10:37:51'),
(29, 1, 50, 40, 60, '2022-09-11 10:37:51', '2022-09-11 10:37:51'),
(30, 1, 50, 40, 61, '2022-09-11 10:37:51', '2022-09-11 10:37:51'),
(31, 1, 50, 40, 62, '2022-09-11 10:37:51', '2022-09-11 10:37:51'),
(32, 1, 50, 40, 63, '2022-09-11 10:37:51', '2022-09-11 10:37:51'),
(33, 1, 50, 40, 64, '2022-09-11 10:37:51', '2022-09-11 10:37:51');

-- --------------------------------------------------------

--
-- Table structure for table `cm_assign_providers`
--

CREATE TABLE `cm_assign_providers` (
  `id` bigint(20) NOT NULL,
  `company_id` int(11) NOT NULL DEFAULT 0,
  `provider_id` bigint(20) NOT NULL,
  `operational_m_id` bigint(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `cm_assign_providers`
--

INSERT INTO `cm_assign_providers` (`id`, `company_id`, `provider_id`, `operational_m_id`, `created_at`, `updated_at`) VALUES
(7, 1, 41, 34, '2022-09-07 02:06:56', '2022-09-07 07:06:56'),
(9, 1, 43, 34, '2022-09-07 11:16:40', '2022-09-07 16:16:40'),
(10, 1, 50, 34, '2022-09-11 03:52:05', '2022-09-11 08:52:05');

-- --------------------------------------------------------

--
-- Table structure for table `cm_attachments`
--

CREATE TABLE `cm_attachments` (
  `id` bigint(20) NOT NULL,
  `provider_id` bigint(20) NOT NULL DEFAULT 0,
  `dd_id` bigint(20) NOT NULL DEFAULT 0,
  `file_w9` varchar(255) NOT NULL,
  `file_voided_check` varchar(255) NOT NULL,
  `file_clia_waiver` varchar(255) NOT NULL,
  `file_other_doc` varchar(255) NOT NULL,
  `comments` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `cm_banking_info`
--

CREATE TABLE `cm_banking_info` (
  `id` bigint(20) NOT NULL,
  `provider_id` bigint(20) NOT NULL DEFAULT 0,
  `dd_id` bigint(20) NOT NULL DEFAULT 0,
  `bank_name` varchar(255) NOT NULL,
  `routing_number` varchar(255) NOT NULL,
  `account_number` varchar(255) NOT NULL,
  `bank_address` varchar(255) NOT NULL,
  `bank_phone` varchar(255) NOT NULL,
  `contact_person_name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `update_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `cm_buisnessinfo`
--

CREATE TABLE `cm_buisnessinfo` (
  `id` bigint(20) NOT NULL,
  `provider_id` bigint(20) NOT NULL DEFAULT 0,
  `dd_id` bigint(20) NOT NULL DEFAULT 0,
  `facility_npi` varchar(255) NOT NULL,
  `legal_business_name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `update_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `email_address` varchar(255) NOT NULL,
  `primary_correspondence_ddress` varchar(255) NOT NULL,
  `group_specialty` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `facility_tax_id` varchar(255) NOT NULL,
  `fax` varchar(255) NOT NULL,
  `established_date` varchar(255) NOT NULL,
  `federal_tax_classification` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `cm_buisnessinfo`
--

INSERT INTO `cm_buisnessinfo` (`id`, `provider_id`, `dd_id`, `facility_npi`, `legal_business_name`, `created_at`, `update_at`, `email_address`, `primary_correspondence_ddress`, `group_specialty`, `phone`, `facility_tax_id`, `fax`, `established_date`, `federal_tax_classification`) VALUES
(28, 49, 8, '1215330500', 'group-stablished', '2022-09-15 07:57:42', '2022-09-15 12:57:42', 'testcontract@yopmail.com', 'ggfggfgfgfgfg', '193200000X Multi-Specialty Group', '+452252243252', '12341211', '718-640-2713', '2022-09-06', 'Individual/sole proprietor or single-member LLC');

-- --------------------------------------------------------

--
-- Table structure for table `cm_capabilities`
--

CREATE TABLE `cm_capabilities` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `path` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `cm_capabilities`
--

INSERT INTO `cm_capabilities` (`id`, `title`, `description`, `path`, `created_at`, `updated_at`) VALUES
(1, 'capabilit 1', 'Can add the users', '', '2022-07-29 05:33:44', '2022-07-26 05:23:57'),
(2, 'capabilit 2', 'Can update the user', '', '2022-07-29 05:33:55', '2022-07-26 05:23:57'),
(3, 'capabilit 3', 'Can view user', '', '2022-07-29 05:34:01', '2022-07-26 05:23:57'),
(4, 'capabilit 4', 'Can delete user', '', '2022-07-29 05:34:11', '2022-07-26 05:23:57'),
(5, 'Test capability', 'test description', '', '2022-07-26 14:10:36', '2022-07-26 14:10:36');

-- --------------------------------------------------------

--
-- Table structure for table `cm_capabilities_capabilityactions_map`
--

CREATE TABLE `cm_capabilities_capabilityactions_map` (
  `id` bigint(20) NOT NULL,
  `capability_id` int(11) NOT NULL DEFAULT 0,
  `capability_action_id` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `cm_capabilities_capabilityactions_map`
--

INSERT INTO `cm_capabilities_capabilityactions_map` (`id`, `capability_id`, `capability_action_id`, `created_at`, `updated_at`) VALUES
(6, 1, 1, '2022-07-29 05:44:09', '2022-07-29 05:15:20');

-- --------------------------------------------------------

--
-- Table structure for table `cm_capability_actions`
--

CREATE TABLE `cm_capability_actions` (
  `id` int(11) NOT NULL,
  `action_name` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `cm_capability_actions`
--

INSERT INTO `cm_capability_actions` (`id`, `action_name`, `is_active`) VALUES
(1, 'Add', 1),
(2, 'Update', 1),
(3, 'Delete', 1),
(4, 'View', 1);

-- --------------------------------------------------------

--
-- Table structure for table `cm_companies`
--

CREATE TABLE `cm_companies` (
  `id` bigint(20) NOT NULL,
  `admin_id` bigint(20) NOT NULL DEFAULT 0,
  `owner_first_name` varchar(100) NOT NULL,
  `owner_last_name` varchar(100) NOT NULL,
  `company_name` varchar(100) NOT NULL,
  `company_address` varchar(255) NOT NULL,
  `company_type` varchar(100) NOT NULL,
  `company_country` varchar(100) NOT NULL,
  `company_copy_right` varchar(100) NOT NULL,
  `company_state` varchar(100) NOT NULL,
  `company_contact` varchar(50) NOT NULL,
  `company_logo` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `cm_companies`
--

INSERT INTO `cm_companies` (`id`, `admin_id`, `owner_first_name`, `owner_last_name`, `company_name`, `company_address`, `company_type`, `company_country`, `company_copy_right`, `company_state`, `company_contact`, `company_logo`, `created_at`, `updated_at`) VALUES
(1, 1, 'Atif', 'G', 'ClaimsMed', 'Karachi', '', 'USA', '', 'LA', '52255224', 'test', '2022-08-01 04:40:33', '2022-07-18 06:03:29'),
(2, 1, 'test', 'test', 'test', 'test', 'test', 'test', 'test', 'test', '032551425432', '62d93a6614a38_dummy_user_img.jfif', '2022-07-21 18:37:10', '2022-07-21 18:37:10'),
(4, 1, 'Faheem Co', 'www', 'wsww', 'sssssqqq', 'startup', 'pk', 'ddsdsdssd', 'sindh', '454544545', '', '2022-07-28 11:38:13', '2022-07-28 06:38:13');

-- --------------------------------------------------------

--
-- Table structure for table `cm_company_custom_fields`
--

CREATE TABLE `cm_company_custom_fields` (
  `id` bigint(20) NOT NULL,
  `company_id` bigint(20) NOT NULL DEFAULT 0,
  `fields_data` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `cm_contracts`
--

CREATE TABLE `cm_contracts` (
  `id` bigint(20) NOT NULL,
  `provider_id` bigint(20) NOT NULL DEFAULT 0,
  `company_id` bigint(20) NOT NULL DEFAULT 0,
  `contract_token` text NOT NULL,
  `contract_company_fields` text NOT NULL,
  `contract_recipient_fields` text NOT NULL,
  `is_sent` tinyint(1) NOT NULL DEFAULT 0,
  `is_view` tinyint(1) NOT NULL DEFAULT 0,
  `is_expired` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `cm_contracts`
--

INSERT INTO `cm_contracts` (`id`, `provider_id`, `company_id`, `contract_token`, `contract_company_fields`, `contract_recipient_fields`, `is_sent`, `is_view`, `is_expired`, `created_at`, `updated_at`) VALUES
(1, 32, 2, 'cm_7thyks7vwan5fgahbikblzooifzgdzqk', '{\"contract_date\":\"2022-08-16\",\"recipient_name\":\"Noor\",\"recipient_address\":\"Qasimabad Hyderabad\",\"credentialing_monthly_compensation\":\"8\",\"deposit_fee\":\"9\",\"billing_monthly_compensation\":\"10%\",\"patient_billing_monthly_compensation\":\"11%\",\"service\":\"credentialing_billing\"}', '{\"recipient_signature\":\"Shahid\",\"recipient_printname\":\"Shd\",\"recipient_title\":\"FrontEnd\",\"recipient_sign_date\":\"2022-08-23\"}', 0, 0, 0, '2022-08-23 14:54:19', '2022-08-23 09:54:19'),
(2, 31, 2, 'cm_torgbnmyz1vwm6amp56b430affblhsk8', '{\"contract_date\":\"2022-08-16\",\"recipient_name\":\"Faheems\",\"recipient_address\":\"hyderabadsd sindhsd\",\"insurance_monthly_compensation\":\"1%\",\"flat_monthly_compensation\":\"2\",\"patient_billing_monthly_compensation\":\"3%\",\"deposit_fee\":\"4\",\"service\":\"billing\"}', 'NULL', 0, 0, 0, '2022-08-16 10:08:15', '2022-08-16 05:08:15'),
(3, 30, 2, 'cm_g32zyufh7elmkktnvupgwv6zrrbfe50t', '{\"contract_date\":\"2022-08-16\",\"recipient_name\":\"Nael\",\"recipient_address\":\"address address 2\",\"service_compensation\":\"2\",\"deposit_fee\":\"5\",\"onetime_deposit_fee\":\"4\",\"service\":\"credentialing\"}', 'NULL', 0, 0, 0, '2022-08-16 03:08:59', '2022-08-16 03:08:59'),
(4, 37, 2, 'cm_jl5liqhkufznebph3qzn7lak3ujjvz2v', '{\"contract_date\":\"2022-08-19\",\"recipient_name\":\"fdfd\",\"recipient_address\":\"ggfggf gfgfgfg\",\"insurance_monthly_compensation\":\"1%\",\"flat_monthly_compensation\":\"2\",\"patient_billing_monthly_compensation\":\"3%\",\"deposit_fee\":\"4\",\"service\":\"billing\"}', '{\"recipient_signature\":\"Naeem\",\"recipient_printname\":\"Ali\",\"recipient_title\":\"SQA\",\"recipient_sign_date\":\"2022-08-23\"}', 1, 0, 0, '2022-09-09 10:12:45', '2022-09-09 05:12:45'),
(5, 40, 2, 'cm_alig6rgpktxghlulsaat1eylnby35sx0', '{\"contract_date\":\"2022-08-24\",\"recipient_name\":\"Email\",\"recipient_address\":\"Karachi Sindh\",\"service_compensation\":\"2\",\"onetime_deposit_fee\":\"4\",\"service\":\"credentialing\"}', 'NULL', 1, 0, 0, '2022-09-07 04:51:30', '2022-09-06 23:51:30'),
(6, 33, 2, 'cm_nadbiyzkcd1bravlaj6ygfnpgy043aow', '{\"contract_date\":\"2022-08-22\",\"recipient_name\":\"Manzoor\",\"recipient_address\":\"Hyderabad Sindh\",\"service_compensation\":\"2\",\"deposit_fee\":\"1\",\"onetime_deposit_fee\":\"4\",\"service\":\"credentialing\"}', '{\"recipient_signature\":\"faheem\",\"recipient_printname\":\"Manzoor\",\"recipient_title\":\"Developer\",\"recipient_sign_date\":\"2022-08-23\"}', 1, 1, 0, '2022-08-30 15:01:33', '2022-08-30 10:01:33'),
(7, 41, 2, 'cm_cetgm3utwjp302wqemlqf92fxxmldjrn', '{\"contract_date\":\"2022-09-02\",\"recipient_name\":\"Asif Aziz\",\"recipient_address\":\"Al Khaleej Towers karachi\",\"insurance_monthly_compensation\":\"1%\",\"flat_monthly_compensation\":\"2\",\"patient_billing_monthly_compensation\":\"3%\",\"deposit_fee\":\"4\",\"service\":\"billing\"}', 'NULL', 0, 1, 0, '2022-09-02 13:52:49', '2022-09-02 08:52:49'),
(8, 43, 1, 'cm_zbfx2ldprkgbmj1al2tnknfvjzaz9xt0', '{\"contract_date\":\"2022-09-08\",\"recipient_name\":\"ddsdsds\",\"recipient_address\":\"fdfd fdfd\",\"service_compensation\":\"45\",\"onetime_deposit_fee\":\"54\",\"service\":\"credentialing\"}', 'NULL', 0, 0, 0, '2022-09-08 00:13:49', '2022-09-08 00:13:49'),
(9, 44, 1, 'cm_6g6wbwnxdniricnx01ughvtvxrkk65iw', '{\"contract_date\":\"2022-09-08\",\"recipient_name\":\"Naeem\",\"recipient_address\":\"assasa sssas\",\"credentialing_monthly_compensation\":\"2\",\"deposit_fee\":\"2\",\"billing_monthly_compensation\":\"3%\",\"patient_billing_monthly_compensation\":\"3%\",\"service\":\"credentialing_billing\"}', 'NULL', 0, 0, 0, '2022-09-08 02:35:06', '2022-09-08 02:35:06'),
(10, 45, 1, 'cm_e504bq674ukrusgklgexc11q3xbrfmmw', '{\"contract_date\":\"2022-09-08\",\"recipient_name\":\"Saeed\",\"recipient_address\":\"sasasss sdddsd\",\"insurance_monthly_compensation\":\"4%\",\"flat_monthly_compensation\":\"3\",\"patient_billing_monthly_compensation\":\"2%\",\"deposit_fee\":\"44\",\"service\":\"billing\"}', 'NULL', 0, 0, 0, '2022-09-08 03:51:49', '2022-09-08 03:51:49'),
(11, 47, 1, 'cm_zxxnrkxqhiqukd415zxky6aqokgk7ypz', '{\"contract_date\":\"2022-09-08\",\"recipient_name\":\"dssdsd\",\"recipient_address\":\"aassssa ssddsds\",\"insurance_monthly_compensation\":\"3%\",\"flat_monthly_compensation\":\"4\",\"patient_billing_monthly_compensation\":\"4%\",\"deposit_fee\":\"4\",\"service\":\"billing\"}', 'NULL', 0, 0, 0, '2022-09-08 04:57:40', '2022-09-08 04:57:40'),
(12, 48, 1, 'cm_sjflen7s5kzlxvwiiiiafpa6jj7thwna', '{\"contract_date\":\"2022-09-09\",\"recipient_name\":\"fdfddfdf\",\"recipient_address\":\"dfdffd dffd\",\"service_compensation\":\"1\",\"onetime_deposit_fee\":\"2\",\"service\":\"credentialing\"}', 'NULL', 1, 0, 0, '2022-09-09 04:35:27', '2022-09-08 23:35:27'),
(13, 38, 1, 'cm_3kglmotrwnqa3tkiwh0pdcs0n5o48mqy', '{\"contract_date\":\"2022-09-09\",\"recipient_name\":\"Email\",\"recipient_address\":\"Karachi Sindh\",\"service_compensation\":\"2\",\"onetime_deposit_fee\":\"2\",\"service\":\"credentialing\"}', 'NULL', 0, 0, 0, '2022-09-09 08:32:57', '2022-09-09 08:32:57'),
(14, 36, 1, 'cm_sgboivgmbsccna55absxpx1nyzszd5vh', '{\"contract_date\":\"2022-09-09\",\"recipient_name\":\"dsddds\",\"recipient_address\":\"ffdfd fdfdf\",\"service_compensation\":\"2\",\"onetime_deposit_fee\":\"2\",\"service\":\"credentialing\"}', 'NULL', 0, 0, 0, '2022-09-09 08:34:07', '2022-09-09 08:34:07'),
(15, 49, 1, 'cm_ehdzofygwn2t2czkd5iokpopvhidxtyp', '{\"contract_date\":\"2022-09-10\",\"recipient_name\":\"Test\",\"recipient_address\":\"fdsffddf dfssdssaa\",\"insurance_monthly_compensation\":\"1%\",\"flat_monthly_compensation\":\"2\",\"patient_billing_monthly_compensation\":\"3%\",\"deposit_fee\":\"4\",\"service\":\"billing\"}', 'NULL', 1, 0, 0, '2022-09-10 10:30:49', '2022-09-10 05:30:49'),
(16, 50, 1, 'cm_1qyayiusd48v1sekuxsndtvzmfzefhrm', '{\"contract_date\":\"2022-09-11\",\"recipient_name\":\"bbbbb\",\"recipient_address\":\"address address 1\",\"insurance_monthly_compensation\":\"1%\",\"flat_monthly_compensation\":\"2\",\"patient_billing_monthly_compensation\":\"3%\",\"deposit_fee\":\"4\",\"service\":\"billing\"}', '{\"recipient_signature\":\"ghh\",\"recipient_printname\":\"hhhg\",\"recipient_title\":\"ghhh\",\"recipient_sign_date\":\"2022-09-11\"}', 1, 1, 0, '2022-09-11 07:58:27', '2022-09-11 02:58:27');

-- --------------------------------------------------------

--
-- Table structure for table `cm_credentialing_tasks`
--

CREATE TABLE `cm_credentialing_tasks` (
  `id` bigint(20) NOT NULL,
  `provider_id` bigint(20) NOT NULL DEFAULT 0,
  `insurance_id` bigint(20) NOT NULL DEFAULT 0,
  `group_provider` bigint(20) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `cm_credentialing_tasks`
--

INSERT INTO `cm_credentialing_tasks` (`id`, `provider_id`, `insurance_id`, `group_provider`, `created_at`, `updated_at`) VALUES
(23, 30, 1, 0, '2022-08-10 03:00:31', '2022-08-10 08:00:31'),
(24, 30, 2, 0, '2022-08-10 03:00:31', '2022-08-10 08:00:31'),
(25, 30, 1, 1, '2022-08-10 03:00:31', '2022-08-10 08:00:31'),
(26, 30, 1, 2, '2022-08-10 03:00:31', '2022-08-10 08:00:31'),
(27, 30, 1, 3, '2022-08-10 03:00:31', '2022-08-10 08:00:31'),
(28, 30, 2, 1, '2022-08-10 03:00:31', '2022-08-10 08:00:31'),
(29, 30, 2, 2, '2022-08-10 03:00:31', '2022-08-10 08:00:31'),
(30, 30, 2, 3, '2022-08-10 03:00:31', '2022-08-10 08:00:31'),
(31, 33, 1, 0, '2022-08-22 23:21:43', '2022-08-23 04:21:43'),
(32, 33, 2, 0, '2022-08-22 23:21:43', '2022-08-23 04:21:43'),
(33, 33, 1, 1, '2022-08-22 23:21:43', '2022-08-23 04:21:43'),
(34, 33, 1, 2, '2022-08-22 23:21:43', '2022-08-23 04:21:43'),
(35, 33, 2, 1, '2022-08-22 23:21:43', '2022-08-23 04:21:43'),
(36, 33, 2, 2, '2022-08-22 23:21:43', '2022-08-23 04:21:43'),
(37, 38, 1, 0, '2022-08-23 02:14:58', '2022-08-23 07:14:58'),
(38, 38, 2, 0, '2022-08-23 02:14:58', '2022-08-23 07:14:58'),
(39, 41, 1, 0, '2022-09-01 09:48:17', '2022-09-01 14:48:17'),
(40, 41, 2, 0, '2022-09-01 09:48:17', '2022-09-01 14:48:17'),
(45, 43, 1, 0, '2022-09-07 23:48:54', '2022-09-08 04:48:54'),
(46, 43, 2, 0, '2022-09-07 23:48:54', '2022-09-08 04:48:54'),
(47, 45, 1, 0, '2022-09-08 04:01:17', '2022-09-08 09:01:17'),
(48, 45, 2, 0, '2022-09-08 04:01:17', '2022-09-08 09:01:17'),
(49, 47, 1, 0, '2022-09-08 05:03:52', '2022-09-08 10:03:52'),
(50, 47, 2, 0, '2022-09-08 05:03:52', '2022-09-08 10:03:52'),
(51, 48, 1, 0, '2022-09-09 07:28:09', '2022-09-09 12:28:09'),
(52, 48, 2, 0, '2022-09-09 07:28:09', '2022-09-09 12:28:09'),
(57, 49, 1, 0, '2022-09-10 02:30:42', '2022-09-10 07:30:42'),
(58, 49, 2, 0, '2022-09-10 02:30:42', '2022-09-10 07:30:42'),
(59, 50, 1, 0, '2022-09-10 02:36:33', '2022-09-10 07:36:33'),
(60, 50, 1, 1, '2022-09-10 02:36:33', '2022-09-10 07:36:33'),
(61, 50, 1, 2, '2022-09-10 02:36:33', '2022-09-10 07:36:33'),
(62, 50, 2, 0, '2022-09-10 02:36:33', '2022-09-10 07:36:33'),
(63, 50, 2, 1, '2022-09-10 02:36:33', '2022-09-10 07:36:33'),
(64, 50, 2, 2, '2022-09-10 02:36:33', '2022-09-10 07:36:33');

-- --------------------------------------------------------

--
-- Table structure for table `cm_credentialing_task_logs`
--

CREATE TABLE `cm_credentialing_task_logs` (
  `id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL DEFAULT 0,
  `credentialing_task_id` bigint(20) NOT NULL,
  `last_follow_up` varchar(50) NOT NULL,
  `next_follow_up` varchar(50) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Not initiated',
  `details` text NOT NULL,
  `image` varchar(100) NOT NULL,
  `pdf` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `cm_credentialing_task_logs`
--

INSERT INTO `cm_credentialing_task_logs` (`id`, `user_id`, `credentialing_task_id`, `last_follow_up`, `next_follow_up`, `status`, `details`, `image`, `pdf`, `created_at`, `updated_at`) VALUES
(1, 0, 1, '8/9/2022', '9/9/2022', 'initiated', 'NULL', 'NULL', '', '2022-08-09 04:44:41', '2022-08-09 09:44:41'),
(2, 0, 2, '8/9/2022', '9/9/2022', 'initiated', 'NULL', '62f22cd8ebbee_Salary  - Manzoor Ali -JUL-2022,.pdf', '', '2022-08-09 04:46:00', '2022-08-09 09:46:00'),
(3, 0, 3, '8/9/2022', '9/9/2022', 'initiated', 'we have applied for the this service', '62f22d3602df7_Salary  - Manzoor Ali -JUL-2022,.pdf', '', '2022-08-09 04:47:34', '2022-08-09 09:47:34'),
(4, 20, 3, '8/9/2022', '9/9/2022', 'initiated', 'we have applied for the this service', 'NULL', '', '2022-08-09 10:58:36', '2022-08-09 10:13:50'),
(5, 1, 3, '8/9/2022', '9/9/2022', 'initiated', 'we have applied for the this service', '62f241ca56146_Salary  - Manzoor Ali -JUL-2022,.pdf', '', '2022-08-09 06:15:22', '2022-08-09 11:15:22'),
(6, 20, 23, '2022-08-10', '2022-08-10', 'Initiated', 'fdffdfddffdfdsdsssdssd', '62f3bc3cdb8ac_image.png', '62f3bc3cdc1e8_Salary  - Manzoor Ali -JUL-2022,.pdf', '2022-08-10 09:10:04', '2022-08-10 14:10:04'),
(7, 20, 23, '2022-09-01', '2022-09-02', 'Initiated', 'ffdfdfdfddfdf', 'NULL', 'NULL', '2022-08-10 09:25:43', '2022-08-10 14:25:43'),
(8, 20, 24, '2022-08-19', '2022-08-22', 'Initiated', 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.', 'NULL', '62f3c960aa482_Salary  - Manzoor Ali -JUL-2022,.pdf', '2022-08-10 10:06:08', '2022-08-10 15:06:08'),
(9, 20, 24, '2022-08-05', '2022-08-20', 'In process', 'It is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal distribution of letters, as opposed to using \'Content here, content here\', making it look like readable English. Many desktop publishing packages and web page editors now use Lorem Ipsum as their default model text, and a search for \'lorem ipsum\' will uncover many web sites still in their infancy. Various versions have evolved over the years, sometimes by accident, sometimes on purpose (injected humour and the like).', 'NULL', 'NULL', '2022-08-10 10:08:06', '2022-08-10 15:08:06'),
(10, 20, 25, '2022-08-17', '2022-08-16', 'Initiated', 'There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form, by injected humour, or randomised words which don\'t look even slightly believable. If you are going to use a passage of Lorem Ipsum, you need to be sure there isn\'t anything embarrassing hidden in the middle of text. All the Lorem Ipsum generators on the Internet tend to repeat predefined chunks as necessary, making this the first true generator on the Internet. It uses a dictionary of over 200 Latin words, combined with a handful of model sentence structures, to generate Lorem Ipsum which looks reasonable. The generated Lorem Ipsum is therefore always free from repetition, injected humour, or non-characteristic words etc.', '62f3caa76ccf3_47b035736726db2e9b915b3ac9001c3e.jpg', 'NULL', '2022-08-10 10:11:35', '2022-08-10 15:11:35'),
(11, 20, 25, '2022-08-16', '2022-08-15', 'Initiated', 'dsdffddffddffdfd', 'NULL', 'NULL', '2022-08-10 10:14:03', '2022-08-10 15:14:03'),
(12, 20, 25, '2022-08-17', '2022-08-17', 'In process', 'fdfdfddffdfdfd', '62f3cc1a96685_47b035736726db2e9b915b3ac9001c3e.jpg', 'NULL', '2022-08-10 10:17:46', '2022-08-10 15:17:46'),
(13, 8, 31, '2022-08-24', '2022-08-25', 'Initiated', 'This is my note', 'NULL', 'NULL', '2022-08-22 23:22:46', '2022-08-23 04:22:46'),
(14, 8, 31, '2022-08-25', '2022-08-26', 'In process', 'Adding an other note here', 'NULL', 'NULL', '2022-08-22 23:29:54', '2022-08-23 04:29:54'),
(15, 31, 49, '2022-09-09', '2022-09-09', 'In Review', 'dssddsdsd', 'NULL', 'NULL', '2022-09-08 07:47:43', '2022-09-08 12:47:43'),
(16, 31, 49, '2022-09-09', '2022-09-14', 'In Review', 'sddsdsds', 'NULL', 'NULL', '2022-09-08 08:03:44', '2022-09-08 13:03:44'),
(17, 31, 49, '2022-09-09', '2022-09-14', 'In Review', 'dffdfdfd', 'NULL', 'NULL', '2022-09-08 09:08:40', '2022-09-08 14:08:40'),
(18, 31, 0, '2022-09-09', '2022-09-14', 'In Review', 'xddsdsds', 'NULL', 'NULL', '2022-09-08 09:11:01', '2022-09-08 14:11:01'),
(19, 31, 49, '2022-09-09', '2022-09-14', 'In Review', 'ssasasas', 'NULL', 'NULL', '2022-09-08 09:11:39', '2022-09-08 14:11:39'),
(20, 31, 49, '2022-09-09', '2022-09-14', 'In Review', 'dddffdf', 'NULL', 'NULL', '2022-09-08 09:22:41', '2022-09-08 14:22:41'),
(21, 31, 0, '2022-09-09', '2022-09-14', 'In Review', 'dfdfddff', 'NULL', 'NULL', '2022-09-08 09:37:24', '2022-09-08 14:37:24'),
(22, 31, 0, '2022-09-09', '2022-09-15', 'In Review', 'dsdsdds', 'NULL', 'NULL', '2022-09-08 23:27:34', '2022-09-09 04:27:34'),
(23, 31, 51, '2022-09-10', '2022-09-16', 'Not initiated', 'NULL', 'NULL', 'NULL', '2022-09-10 01:21:17', '2022-09-10 06:21:17'),
(24, 31, 51, '2022-09-10', '2022-09-16', 'Not initiated', 'ddssddsds', 'NULL', 'NULL', '2022-09-10 01:28:42', '2022-09-10 06:28:42'),
(25, 31, 52, '2022-09-10', '2022-09-16', 'Not initiated', 'NULL', 'NULL', 'NULL', '2022-09-10 01:38:32', '2022-09-10 06:38:32'),
(26, 0, 57, '00/00/0000', '00/00/0000', 'Not initiated', 'NULL', 'NULL', 'NULL', '2022-09-10 07:30:42', '2022-09-10 07:30:42'),
(27, 0, 58, '00/00/0000', '00/00/0000', 'Not initiated', 'NULL', 'NULL', 'NULL', '2022-09-10 07:30:42', '2022-09-10 07:30:42'),
(28, 0, 59, '00/00/0000', '00/00/0000', 'Not initiated', 'NULL', 'NULL', 'NULL', '2022-09-10 07:36:33', '2022-09-10 07:36:33'),
(29, 0, 60, '00/00/0000', '00/00/0000', 'Not initiated', 'NULL', 'NULL', 'NULL', '2022-09-10 07:36:33', '2022-09-10 07:36:33'),
(30, 0, 61, '00/00/0000', '00/00/0000', 'Not initiated', 'NULL', 'NULL', 'NULL', '2022-09-10 07:36:33', '2022-09-10 07:36:33'),
(31, 0, 62, '00/00/0000', '00/00/0000', 'Not initiated', 'NULL', 'NULL', 'NULL', '2022-09-10 07:36:33', '2022-09-10 07:36:33'),
(32, 0, 63, '00/00/0000', '00/00/0000', 'Not initiated', 'NULL', 'NULL', 'NULL', '2022-09-10 07:36:33', '2022-09-10 07:36:33'),
(33, 0, 64, '00/00/0000', '00/00/0000', 'Not initiated', 'NULL', 'NULL', 'NULL', '2022-09-10 07:36:33', '2022-09-10 07:36:33'),
(38, 16, 61, '2022-09-10', '2022-09-16', 'Initiated', 'dsddssddsd', 'NULL', 'NULL', '2022-09-12 08:00:11', '2022-09-12 13:00:11'),
(39, 16, 61, '2022-09-16', '2022-09-22', 'Initiated', 'sdsddsds', 'NULL', 'NULL', '2022-09-12 08:00:20', '2022-09-12 13:00:20'),
(44, 40, 59, '2022-09-10', '2022-09-16', 'Initiated', 'saassas', 'NULL', 'NULL', '2022-09-13 01:26:48', '2022-09-13 06:26:48'),
(45, 31, 59, '2022-09-16', '2022-09-22', 'Initiated', 'assasa', 'NULL', 'NULL', '2022-09-13 01:47:07', '2022-09-13 06:47:07'),
(46, 40, 49, '2022-09-14', '2022-09-20', 'Initiated', 'dssddsds', 'NULL', 'NULL', '2022-09-13 07:47:43', '2022-09-13 12:47:43'),
(47, 40, 59, '2022-09-22', '2022-09-28', 'Initiated', 'assasa', 'NULL', 'NULL', '2022-09-13 07:52:08', '2022-09-13 12:52:08'),
(48, 40, 59, '2022-09-28', '2022-10-04', 'In Review', 'sasasa', 'NULL', 'NULL', '2022-09-13 07:52:59', '2022-09-13 12:52:59'),
(49, 40, 59, '2022-10-04', '2022-10-10', 'Initiated', 'ddsdsdd', 'NULL', 'NULL', '2022-09-13 07:58:08', '2022-09-13 12:58:08'),
(50, 40, 59, '2022-10-10', '2022-10-16', 'Initiated', 'dsdsds', 'NULL', 'NULL', '2022-09-13 07:59:32', '2022-09-13 12:59:32'),
(51, 40, 59, '2022-10-16', '2022-10-22', 'Initiated', 'gfgfgssds', 'NULL', 'NULL', '2022-09-13 08:14:54', '2022-09-13 13:14:54'),
(52, 40, 59, '2022-10-22', '2022-10-28', 'Initiated', 'ghggfer', 'NULL', 'NULL', '2022-09-13 08:17:04', '2022-09-13 13:17:04'),
(53, 40, 59, '2022-10-28', '2022-11-03', 'Initiated', 'sddsds', 'NULL', 'NULL', '2022-09-13 08:19:03', '2022-09-13 13:19:03'),
(54, 40, 59, '2022-11-03', '2022-11-09', 'Initiated', 'ssssasa', 'NULL', 'NULL', '2022-09-13 08:20:03', '2022-09-13 13:20:03'),
(55, 40, 59, '2022-11-09', '2022-11-15', 'In Review', 'NULL', 'NULL', 'NULL', '2022-09-13 08:20:47', '2022-09-13 13:20:47'),
(56, 40, 59, '2022-11-15', '2022-11-21', 'In Review', 'sassasaassa', 'NULL', 'NULL', '2022-09-13 08:33:26', '2022-09-13 13:33:26'),
(59, 37, 59, '2022-11-21', '2022-11-27', 'In Review', 'NULL', 'NULL', 'NULL', '2022-09-14 07:16:30', '2022-09-14 12:16:30'),
(60, 34, 59, '2022-11-27', '2022-12-03', 'In Review', 'NULL', 'NULL', 'NULL', '2022-09-14 07:37:25', '2022-09-14 12:37:25');

-- --------------------------------------------------------

--
-- Table structure for table `cm_discoverydocuments`
--

CREATE TABLE `cm_discoverydocuments` (
  `id` bigint(20) NOT NULL,
  `company_id` bigint(20) NOT NULL DEFAULT 0,
  `provider_id` bigint(20) NOT NULL DEFAULT 0,
  `dd_token` varchar(255) NOT NULL,
  `dd_data` text NOT NULL,
  `attachments` text DEFAULT NULL,
  `is_sent` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `cm_discoverydocuments`
--

INSERT INTO `cm_discoverydocuments` (`id`, `company_id`, `provider_id`, `dd_token`, `dd_data`, `attachments`, `is_sent`, `created_at`, `updated_at`) VALUES
(1, 1, 37, 'cm_0jb9oruuzgcymxppeyehrcvnya8b9esq', 'NULL', '', 1, '2022-09-09 10:12:45', '2022-09-09 05:12:45'),
(2, 2, 40, 'cm_yudbrtxactwgtjz7kkqbsaqgrb1w6ygb', 'NULL', '', 1, '2022-09-07 04:51:30', '2022-09-06 23:51:30'),
(3, 2, 33, 'cm_encwgh6qzlfuzdkxu0mooph5rhoot50l', '{\"payer_approve_attached_doc_1\":null,\"provider_first_name_1\":\"dfdffd\",\"provider_last_name_1\":\"dffdfd\",\"national_provider_id_1\":\"44343\",\"primary_speciality_1\":\"fdfddf\",\"secondary_speciality_1\":\"dfffd\",\"date_of_birth_provider_1\":\"2022-08-31\",\"cell_phone_1\":\"4433443\",\"provider_email_1\":\"dfdfffd@yopmail.com\",\"state_license_number_1\":\"43444\",\"state_license_issue_date_1\":\"2022-08-31\",\"state_license_expiration_date_1\":\"2022-09-01\",\"driver_license_number_1\":\"434344\",\"driver_license_number_issue_date_1\":\"2022-09-02\",\"driver_license_number_expiration_date_1\":\"2022-09-02\",\"dea_number_1\":\"443433\",\"dea_issue_date_1\":\"2022-09-08\",\"caqh_username_1\":\"fdfddf\",\"caqh_password_1\":\"434434\",\"state_of_birth_1\":\"dgfgf\",\"country_birth_1\":\"gfggf\",\"citizenship_1\":\"gfgfgf\",\"hospital_privilage_1\":\"gfgfgfgf\",\"provider_first_name_2\":null,\"provider_last_name_2\":null,\"national_provider_id_2\":null,\"primary_speciality_2\":null,\"secondary_speciality_2\":null,\"date_of_birth_provider_2\":null,\"cell_phone_2\":null,\"provider_email_2\":null,\"state_license_number_2\":null,\"state_license_issue_date_2\":null,\"state_license_expiration_date_2\":null,\"driver_license_number_2\":null,\"driver_license_number_issue_date_2\":null,\"driver_license_number_expiration_date_2\":null,\"dea_number_2\":null,\"dea_issue_date_2\":null,\"caqh_username_2\":null,\"caqh_password_2\":null,\"state_of_birth_2\":null,\"country_birth_2\":null,\"citizenship_2\":null,\"hospital_privilage_2\":null,\"payer_wishlist_1\":null,\"payer_1\":\"fdff\",\"payer_status_1\":\"inproccess\",\"payer__in_process_approx_innitiation_date_1\":\"2022-08-25\",\"payer_in_process_attached_1\":null,\"payer_approve_effective_date_1\":null,\"payer_approve_provider_id_1\":null,\"legal_business_name\":\"Faheem\",\"business_address\":\"HyderabadSindh\",\"business_phone\":\"+923453658887\",\"business_email\":\"manzoor@yopmail.com\",\"faxNo\":\"4343\",\"groupSpeciality\":\"ddsd\",\"dateBusinessEstalished\":\"undefined\",\"federalTaxClassification\":\"S Corporation\",\"taxId\":\"undefined\",\"principleOwnerFname\":\"fdfd\",\"principleOwnerLname\":\"fdfd\",\"principleOwnerDob\":\"2022-08-26\",\"principleOwnerbirthState\":\"fdfdfd\",\"principleOwnerbirthCountry\":\"fdfd\",\"principleOwnerSSN\":\"fdfd\",\"principleOwnerpercent\":\"4\",\"principleOwnerpartnershipAcquired\":\"2022-08-25\",\"secondOwnerFname\":\"dffdfd\",\"secondOwnerLname\":\"fdfd\",\"secondOwnerDob\":\"fdfd\",\"secondOwnerbirthState\":\"dffd\",\"secondOwnerbirthCountry\":\"fdfd\",\"secondOwnerSSN\":\"dffd\",\"secondOwnerpercent\":\"5\",\"secondOwnerpartnershipAcquired\":\"2022-08-26\",\"bankName\":\"dfdf\",\"routingNumber\":\"dffd\",\"accountingNumber\":\"fdfd4\",\"bankAddress\":\"ffdgfgfgf\",\"bankPhone\":\"54554\",\"bankContactPersonName\":\"fggfgfgf\",\"file_IRS_CP_575_1\":\"630f50ac1c24e_image.png\",\"file_general_liability_insurance1\":\"630f50ac1ce6e_47b035736726db2e9b915b3ac9001c3e.jpg\"}', '', 1, '2022-08-31 12:14:36', '2022-08-31 07:14:36'),
(4, 2, 32, 'cm_9do13i0h1p33qb5mm6fsgzz2w3mki7ta', 'NULL', '', 0, '2022-08-23 09:32:34', '2022-08-23 09:32:34'),
(5, 1, 41, 'cm_vqxvtndiwqb7hubijqnnjmczfkztrhog', 'NULL', '', 0, '2022-09-06 23:50:06', '2022-09-06 23:50:06'),
(6, 1, 48, 'cm_m1bfn6g30kzi6hzmbqhpxxit0x6wfnsb', '{\"payer_1\":\"select\",\"payer_status_1\":\"select\",\"payer__in_process_approx_innitiation_date_1\":null,\"payer_in_process_attached_1\":null,\"payer_approve_effective_date_1\":null,\"payer_approve_provider_id_1\":null,\"payer_approve_attached_doc_1\":\"undefined\",\"payer_wishlist_1\":null,\"providerFirstName\":\"undefined\",\"providerLastName\":\"undefined\",\"nationalProviderId\":\"undefined\",\"primarySpeciality\":\"undefined\",\"secondarySpeciality\":\"undefined\",\"dateOfBirth\":\"undefined\",\"cellPhone\":\"undefined\",\"providersEmail\":\"undefined\",\"stateLicenseNumber\":\"undefined\",\"stateLicenseIssuanceDate\":\"undefined\",\"stateLicenseExpirtaionDate\":\"undefined\",\"driverLicenseNumber\":\"undefined\",\"driverLicenseIssueDate\":\"undefined\",\"driverExpiryIssueDate\":\"undefined\",\"deaNumber\":\"undefined\",\"deaIssueDate\":\"undefined\",\"caqhUsername\":\"undefined\",\"caqhPassword\":\"undefined\",\"stateOfBirth\":\"undefined\",\"countryOfBirth\":\"undefined\",\"Citizenship\":\"undefined\",\"hospitalPrivillage\":\"undefined\",\"ownerName\":\"undefined\",\"ownerLastName\":\"undefined\",\"ownerDateOfBirth\":\"undefined\",\"ownerStateOfBirth\":\"undefined\",\"ownerCountryOfBirth\":\"undefined\",\"ownerSSN\":\"123456789\",\"emrPlanOnUsing\":\"undefined\",\"primaryCorresponadnceAdddress\":\"undefined\",\"phoneNo\":\"undefined\",\"faxNo\":null,\"practiseLocationEmail\":\"undefined\",\"number_of_payer\":\"[1]\",\"payerWishListCount\":\"[1]\",\"supervisorDetails\":null,\"Cahq\":\"CAHQ number\",\"bankName\":\"undefined\",\"routingNumber\":\"undefined\",\"accountingNumber\":\"undefined\",\"bankAddress\":\"undefined\",\"bankPhone\":\"undefined\",\"bankContactPersonName\":\"undefined\",\"comments\":\"undefined\"}', '', 1, '2022-09-09 05:43:43', '2022-09-09 00:43:43'),
(7, 1, 50, 'cm_mueots27rb7cf2l1x6dwxnd4lqrvehi1', 'NULL', '', 1, '2022-09-11 07:58:27', '2022-09-11 02:58:27'),
(8, 1, 49, 'cm_hpznkrcdnfcy3z7hp0ntvgs26nwfwmzp', 'NULL', '', 1, '2022-09-10 10:30:49', '2022-09-10 05:30:49');

-- --------------------------------------------------------

--
-- Table structure for table `cm_failed_jobs`
--

CREATE TABLE `cm_failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `connection` text COLLATE utf8_unicode_ci NOT NULL,
  `queue` text COLLATE utf8_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cm_group_provider_info`
--

CREATE TABLE `cm_group_provider_info` (
  `id` bigint(20) NOT NULL,
  `provider_id` bigint(20) NOT NULL DEFAULT 0,
  `dd_id` bigint(20) NOT NULL DEFAULT 0,
  `user_id` bigint(20) NOT NULL DEFAULT 0,
  `member_id` bigint(20) NOT NULL DEFAULT 0,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `national_provider_id` varchar(255) NOT NULL,
  `cahq_number` varchar(255) NOT NULL,
  `primary_speciality` varchar(255) NOT NULL,
  `secondary_speciality` varchar(255) NOT NULL,
  `date_of_birth` varchar(255) NOT NULL,
  `cell_phone` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `state_license_number` varchar(255) NOT NULL,
  `state_license_issue_date` varchar(255) NOT NULL,
  `state_license_expiration_date` varchar(255) NOT NULL,
  `file_irs_or_equivalent` varchar(255) DEFAULT NULL,
  `file_general_liability_insurance` varchar(255) DEFAULT NULL,
  `file_certificate_of_filing` varchar(255) DEFAULT NULL,
  `file_other_supporting_documents` varchar(255) DEFAULT NULL,
  `file_resume` varchar(255) DEFAULT NULL,
  `driver_license_number` varchar(255) NOT NULL,
  `driver_license_issue_date` varchar(255) NOT NULL,
  `driver_license_expiration_date` varchar(255) NOT NULL,
  `dae_number` varchar(255) NOT NULL,
  `dae_issue_date` varchar(255) NOT NULL,
  `caqh_user_name` varchar(255) NOT NULL,
  `caqh_password` varchar(255) NOT NULL,
  `state_of_birth` varchar(255) NOT NULL,
  `country_of_birth` varchar(255) NOT NULL,
  `citizenship` varchar(255) NOT NULL,
  `hospital_privileges` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `cm_group_provider_info`
--

INSERT INTO `cm_group_provider_info` (`id`, `provider_id`, `dd_id`, `user_id`, `member_id`, `first_name`, `last_name`, `national_provider_id`, `cahq_number`, `primary_speciality`, `secondary_speciality`, `date_of_birth`, `cell_phone`, `email`, `state_license_number`, `state_license_issue_date`, `state_license_expiration_date`, `file_irs_or_equivalent`, `file_general_liability_insurance`, `file_certificate_of_filing`, `file_other_supporting_documents`, `file_resume`, `driver_license_number`, `driver_license_issue_date`, `driver_license_expiration_date`, `dae_number`, `dae_issue_date`, `caqh_user_name`, `caqh_password`, `state_of_birth`, `country_of_birth`, `citizenship`, `hospital_privileges`, `created_at`, `updated_at`) VALUES
(1, 49, 8, 0, 0, 'Ubaid', 'Rehman', '2werftghjnm', 'NP', 'qwasdfvb', 'wsadxcv', '2022-09-13', '123456', 'notification@yopmail.com', 'WQESFDGVB', '2022-09-12', '2022-09-28', NULL, NULL, NULL, NULL, NULL, 'asdfcvbn', '2022-09-19', '2022-09-10', 'qwasdf', '2022-09-21', 'qasdfvc', 'wasdfbv', 'qASDCV', '123EWRTFGH', 'QWEDF', 'providerDataproviderDataproviderDataproviderDataproviderDataproviderDataproviderDataproviderDataproviderDataproviderDataproviderDataproviderDataproviderDataproviderDataproviderDataproviderDataproviderData', '2022-09-15 08:33:40', '2022-09-15 13:33:40'),
(2, 49, 8, 58, 2, 'c  cju86', 'c6grt4yj', '846', 'Md', 'cx 6g4jc8 x87', 'gc 47guc xxxxxxxxxxxxxxxxxxxx', '2022-09-22', '4476647', 'naushad3@yopmail.com', 'ju74 cx87', '2022-09-16', '2022-09-29', '6324016233069_image.png', NULL, NULL, NULL, NULL, 'j8u6', '2022-09-10', '2022-09-19', '6ju4 c', 'null', 'jgc x', '6j c48', 'ju678j', 'u7uju8', 'ju678', '4u6j87', '2022-09-15 23:53:56', '2022-09-16 04:53:56'),
(3, 49, 8, 61, 2, 'c  cju86', 'c6grt4yj', '846', 'Md', 'cx 6g4jc8 x87', 'gc 47guc xxxxxxxxxxxxxxxxxxxx', '2022-09-22', '4476647', 'naushad6@yopmail.com', 'ju74 cx87', '2022-09-16', '2022-09-29', '632403b5a0f53_image.png', NULL, NULL, NULL, NULL, 'j8u6', '2022-09-10', '2022-09-19', '6ju4 c', 'null', 'jgc x', '6j c48', 'ju678j', 'u7uju8', 'ju678', '4u6j87', '2022-09-16 00:03:52', '2022-09-16 05:03:52'),
(4, 49, 8, 62, 2, 'c  cju86', 'c6grt4yj', '846', 'Md', 'cx 6g4jc8 x87', 'gc 47guc xxxxxxxxxxxxxxxxxxxx', '2022-09-22', '4476647', 'naushad7@yopmail.com', 'ju74 cx87', '2022-09-16', '2022-09-29', '632406b1852d1_image.png', '632406b1855ad_image.png', '632406b18574a_image.png', '632406b185f4d_image.png', '632406b18623b_image.png', 'j8u6', '2022-09-10', '2022-09-19', '6ju4 c', 'null', 'jgc x', '6j c48', 'ju678j', 'u7uju8', 'ju678', '4u6j87', '2022-09-16 00:16:36', '2022-09-16 05:16:36');

-- --------------------------------------------------------

--
-- Table structure for table `cm_insurances`
--

CREATE TABLE `cm_insurances` (
  `id` bigint(20) NOT NULL,
  `payer_id` varchar(50) NOT NULL,
  `payer_name` varchar(100) NOT NULL,
  `po_box` varchar(100) NOT NULL,
  `fax_number` varchar(100) NOT NULL,
  `credentialing_duration` varchar(100) NOT NULL,
  `insurance_type` varchar(20) NOT NULL,
  `short_name` varchar(50) NOT NULL,
  `phone_number` varchar(50) NOT NULL,
  `country_name` varchar(50) NOT NULL,
  `state` varchar(50) NOT NULL,
  `zip_code` varchar(50) NOT NULL,
  `dependant_insurance` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `cm_insurances`
--

INSERT INTO `cm_insurances` (`id`, `payer_id`, `payer_name`, `po_box`, `fax_number`, `credentialing_duration`, `insurance_type`, `short_name`, `phone_number`, `country_name`, `state`, `zip_code`, `dependant_insurance`, `created_at`, `updated_at`) VALUES
(1, '4r4333', 'fdff', 'fdfd', 'dssd', 'ssss', 'sdd', 'dsds', '34434', 'pk', 'sng', '3444', 'fddd', '2022-07-25 12:56:17', '2022-07-25 12:56:17'),
(2, '123', 'abcd', '542', '542265', '7', 'dependent', 'abc', '1245265', 'Pakistan', 'Sindh', '4524', 'NULL', '2022-07-25 19:58:20', '2022-07-25 19:58:20');

-- --------------------------------------------------------

--
-- Table structure for table `cm_invoices`
--

CREATE TABLE `cm_invoices` (
  `id` bigint(20) NOT NULL,
  `company_id` bigint(20) NOT NULL DEFAULT 0,
  `provider_id` bigint(20) NOT NULL DEFAULT 0,
  `invoice_number` varchar(255) NOT NULL,
  `invoice_token` varchar(50) NOT NULL,
  `amount` int(11) NOT NULL DEFAULT 0,
  `details` text DEFAULT NULL,
  `issue_date` date NOT NULL,
  `due_date` date NOT NULL,
  `payment_status` varchar(20) NOT NULL,
  `is_recuring` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `cm_invoices`
--

INSERT INTO `cm_invoices` (`id`, `company_id`, `provider_id`, `invoice_number`, `invoice_token`, `amount`, `details`, `issue_date`, `due_date`, `payment_status`, `is_recuring`, `created_at`, `updated_at`) VALUES
(6, 1, 33, 'CM0001', 'cm_lt081retdthbj4tazkazzvjkjchkvnmw', 297, '{\"address\":\"Hyderabad Sindh\",\"name\":\"Manzoor\",\"business\":\"Faheem\",\"description\":\"Provider credentiality\",\"qty\":\"3\"}', '2022-08-30', '2022-09-02', 'paid', 0, '2022-08-31 12:25:16', '2022-08-31 07:25:16'),
(7, 1, 40, 'CM0002', 'cm_nxbxs3w2ba5fgrpqwqdhvldzcgrhc0dx', 99, '{\"address\":\"Karachi Sindh\",\"name\":\"Email\",\"description\":\"Credentialing only\",\"qty\":\"1\"}', '2022-08-30', '2022-09-02', 'pending', 0, '2022-08-30 02:36:06', '2022-08-30 07:36:06'),
(8, 1, 43, 'CM0003', 'cm_bytj38anraxp5cy48v90kfj47boafqif', 99, '{\"address\":\"fdfd fdfd\",\"name\":\"ddsdsds\",\"description\":\"Credentialing only\",\"qty\":\"1\"}', '2022-09-08', '2022-09-11', 'paid', 0, '2022-09-08 07:21:57', '2022-09-08 02:21:57'),
(9, 1, 44, 'CM0004', 'cm_qilesajrxn2eg6fec0rfppglqgopkuqo', 99, '{\"address\":\"assasa sssas\",\"name\":\"Naeem\",\"description\":\"Credentialing and billing\",\"qty\":\"1\"}', '2022-09-08', '2022-09-11', 'paid', 0, '2022-09-08 07:38:13', '2022-09-08 02:38:13'),
(10, 1, 45, 'CM0005', 'cm_fgks0jj1dy3wdi3qgnmopbqjo1ur5svk', 99, '{\"address\":\"sasasss sdddsd\",\"name\":\"Saeed\",\"description\":\"Billing only\",\"qty\":\"1\"}', '2022-09-08', '2022-09-11', 'paid', 0, '2022-09-08 09:01:17', '2022-09-08 04:01:17'),
(11, 1, 47, 'CM0006', 'cm_zdhkydjc4vtauocbqmxkidluhsmubcmb', 99, '{\"address\":\"aassssa ssddsds\",\"name\":\"dssdsd\",\"description\":\"Billing only\",\"qty\":\"1\"}', '2022-09-08', '2022-09-11', 'paid', 0, '2022-09-08 10:03:52', '2022-09-08 05:03:52'),
(12, 1, 46, 'CM0007', 'cm_snkkyfeuqs4zbrejxisjscz0cx2y3zph', 99, '{\"address\":\"sddsds sdddd\",\"name\":\"notification\",\"description\":\"Billing only\",\"qty\":\"1\"}', '2022-09-08', '2022-09-11', 'pending', 0, '2022-09-08 10:46:57', '2022-09-08 15:46:57'),
(13, 1, 48, 'CM0008', 'cm_vu9hr0wutpyxc2m2ghyvnl9fzkv1ztu0', 99, '{\"address\":\"dfdffd dffd\",\"name\":\"fdfddfdf\",\"business\":null,\"description\":\"Credentialing only\",\"qty\":1,\"per_person\":99}', '2022-09-10', '2022-09-14', 'paid', 0, '2022-09-10 06:39:43', '2022-09-10 01:39:43'),
(14, 1, 48, 'CM0009', 'cm_zmzpk39wzuywckupnpcce6p7q6ogbp6b', 99, '{\"address\":\"dfdffd dffd\",\"name\":\"fdfddfdf\",\"business\":null,\"description\":\"Credentialing only\",\"qty\":1,\"per_person\":99}', '2022-09-10', '2022-09-14', 'pending', 1, '2022-09-10 06:39:43', '2022-09-10 01:39:43'),
(15, 1, 49, 'CM0010', 'cm_wjr8g7x4j04twdrpmtz8ud1nzuegtibb', 99, '{\"address\":\"fdsffddf dfssdssaa\",\"name\":\"Test\",\"business\":null,\"description\":\"Billing only\",\"qty\":1,\"per_person\":99}', '2022-09-10', '2022-09-13', 'paid', 0, '2022-09-10 07:14:35', '2022-09-10 02:14:35'),
(16, 1, 49, 'CM0011', 'cm_kms2emte4nipwpepgwntjwiznesxhxbt', 99, '{\"address\":\"fdsffddf dfssdssaa\",\"name\":\"Test\",\"business\":null,\"description\":\"Billing only\",\"qty\":1,\"per_person\":99}', '2022-10-10', '2022-10-13', 'pending', 1, '2022-09-10 02:14:35', '2022-09-10 07:14:35'),
(17, 1, 50, 'CM0012', 'cm_iwmdk3wytdauurisbmyachq2mgx323n8', 198, '{\"address\":\"address address 1\",\"name\":\"bbbbb\",\"business\":\"test\",\"description\":\"Billing only\",\"qty\":2,\"per_person\":99}', '2022-09-10', '2022-09-13', 'paid', 0, '2022-09-10 07:36:33', '2022-09-10 02:36:33'),
(18, 1, 50, 'CM0013', 'cm_qdr3exwigkfazvracz72xxewgalfymuh', 198, '{\"address\":\"address address 1\",\"name\":\"bbbbb\",\"business\":\"test\",\"description\":\"Billing only\",\"qty\":2,\"per_person\":99}', '2022-10-10', '2022-10-13', 'pending', 1, '2022-09-10 02:36:33', '2022-09-10 07:36:33'),
(19, 1, 48, 'CM0014', 'cm_neeo2i5hbfz3w4bf3eesvvrrshdhvxh3', 99, '{\"address\":\"dfdffd dffd\",\"name\":\"fdfddfdf\",\"business\":null,\"description\":\"Credentialing only\",\"qty\":1,\"per_person\":99}', '2022-09-16', '2022-09-19', 'pending', 1, '2022-09-16 06:21:18', '2022-09-16 11:21:18'),
(20, 1, 49, 'CM0015', 'cm_doznh1imt5agt6r1g1wz87wyctzthzsk', 99, '{\"address\":\"fdsffddf dfssdssaa\",\"name\":\"Test\",\"business\":null,\"description\":\"Billing only\",\"qty\":1,\"per_person\":99}', '2022-09-16', '2022-09-19', 'pending', 1, '2022-09-16 06:21:18', '2022-09-16 11:21:18'),
(21, 1, 50, 'CM0016', 'cm_bhk9fypxbk2mxgbvxfnxvmrne9zf3ktm', 198, '{\"address\":\"address address 1\",\"name\":\"bbbbb\",\"business\":\"test\",\"description\":\"Billing only\",\"qty\":2,\"per_person\":99}', '2022-09-16', '2022-09-19', 'pending', 1, '2022-09-16 06:21:18', '2022-09-16 11:21:18');

-- --------------------------------------------------------

--
-- Table structure for table `cm_migrations`
--

CREATE TABLE `cm_migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `cm_migrations`
--

INSERT INTO `cm_migrations` (`id`, `migration`, `batch`) VALUES
(1, '2014_10_12_000000_create_users_table', 1),
(2, '2014_10_12_100000_create_password_resets_table', 1),
(3, '2019_08_19_000000_create_failed_jobs_table', 1),
(4, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(5, '2019_05_03_000001_create_customer_columns', 2),
(6, '2019_05_03_000002_create_subscriptions_table', 2),
(7, '2019_05_03_000003_create_subscription_items_table', 2);

-- --------------------------------------------------------

--
-- Table structure for table `cm_notifications`
--

CREATE TABLE `cm_notifications` (
  `id` bigint(20) NOT NULL,
  `provider_id` bigint(20) NOT NULL DEFAULT 0,
  `task_id` bigint(20) NOT NULL DEFAULT 0,
  `log_id` bigint(20) NOT NULL DEFAULT 0,
  `notify_type` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `message` text DEFAULT NULL,
  `heading` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `cm_notifications`
--

INSERT INTO `cm_notifications` (`id`, `provider_id`, `task_id`, `log_id`, `notify_type`, `details`, `message`, `heading`, `created_at`, `updated_at`) VALUES
(11, 48, 0, 0, 'new_provider', NULL, 'Request for Credentialing only recieved', 'Practice request', '2022-09-08 09:25:12', '2022-09-08 14:25:12'),
(12, 33, 0, 0, 'recuring_invoice', NULL, 'Invoice Reminder For month of September 2022', 'Manzoor', '2022-09-08 09:25:36', '2022-09-08 14:25:36'),
(13, 47, 0, 0, 'approve_task', '{\"date\":\"2022-09-13\",\"revalid\":\"no\",\"user_id\":\"31\",\"task_id\":\"49\",\"task_name\":\"fdff\",\"p_id\":\"4343433\"}', 'Atif G.Memon has just approved the activity of task \n Task belongs with fdff', 'Active has approved', '2022-09-08 09:37:24', '2022-09-08 14:37:24'),
(14, 47, 0, 0, 'approve_task', '{\"date\":\"2022-09-10\",\"revalid\":\"yes\",\"user_id\":\"31\",\"task_id\":\"49\",\"task_name\":\"fdff\",\"p_id\":\"444443\"}', 'Atif G.Memon has just approved the activity of task \n Task belongs with fdff', 'Activity has approved', '2022-09-08 23:27:34', '2022-09-09 04:27:34'),
(15, 33, 0, 0, 'recuring_invoice', NULL, 'Invoice Reminder For month of September 2022', 'Manzoor', '2022-09-09 04:12:22', '2022-09-09 09:12:22'),
(16, 33, 0, 0, 'recuring_invoice', NULL, 'Invoice Reminder For month of September 2022', 'Manzoor', '2022-09-09 04:20:04', '2022-09-09 09:20:04'),
(17, 49, 0, 0, 'new_provider', NULL, 'Request for Billing only recieved', 'Practice request', '2022-09-10 02:12:11', '2022-09-10 07:12:11'),
(18, 50, 0, 0, 'new_provider', NULL, 'Request for Billing only recieved', 'Practice request', '2022-09-10 02:34:11', '2022-09-10 07:34:11'),
(19, 50, 0, 0, 'approve_task', '{\"date\":\"2022-09-15\",\"revalid\":\"no\",\"user_id\":\"19\",\"task_id\":\"59\",\"task_name\":\"fdff\",\"p_id\":\"32233232\"}', 'Haris has just approved the activity of task \n Task belongs with fdff', 'Activity has approved', '2022-09-13 07:52:59', '2022-09-13 12:52:59'),
(20, 50, 0, 0, 'approve_task', '{\"date\":\"2022-09-14\",\"revalid\":\"no\",\"user_id\":\"19\",\"task_id\":\"59\",\"task_name\":\"fdff\",\"p_id\":\"43344343\",\"log_id\":\"55\"}', 'Haris has just approved the activity of task \n Task belongs with fdff', 'Activity has approved', '2022-09-13 08:20:48', '2022-09-13 13:20:48'),
(21, 50, 0, 0, 'approve_task', '{\"date\":\"2022-09-16\",\"revalid\":\"no\",\"user_id\":\"19\",\"task_id\":\"59\",\"task_name\":\"fdff\",\"p_id\":\"sasasa\",\"log_id\":\"56\"}', 'Haris has just approved the activity of task \n Task belongs with fdff', 'Activity has approved', '2022-09-13 08:33:26', '2022-09-13 13:33:26'),
(22, 50, 59, 57, 'approve_task', '{\"date\":\"2022-09-16\",\"revalid\":\"yes\",\"user_id\":\"19\",\"task_id\":\"59\",\"task_name\":\"fdff\",\"p_id\":\"3434343443\",\"log_id\":\"57\"}', 'Haris has just approved the activity of task \n Task belongs with fdff', 'Activity has approved', '2022-09-13 08:35:07', '2022-09-13 13:35:07'),
(23, 50, 59, 58, 'approve_task', '{\"date\":\"2022-09-14\",\"revalid\":\"no\",\"user_id\":\"19\",\"task_id\":\"59\",\"task_name\":\"fdff\",\"p_id\":\"6565566565\",\"log_id\":\"58\",\"file_name\":\"6320919ee3e94_image.png\"}', 'Haris has just approved the activity of task \n Task belongs with fdff', 'Activity has approved', '2022-09-13 09:20:14', '2022-09-13 14:20:14'),
(24, 50, 59, 59, 'approve_task', '{\"date\":\"2022-09-13\",\"revalid\":\"no\",\"user_id\":\"16\",\"task_id\":\"59\",\"task_name\":\"fdff\",\"p_id\":\"4455445dddd\",\"log_id\":\"59\"}', 'joe has just approved the activity of task \n Task belongs with fdff', 'Activity has approved', '2022-09-14 07:16:31', '2022-09-14 12:16:31'),
(25, 50, 59, 60, 'approve_task', '{\"date\":\"2022-09-16\",\"revalid\":\"yes\",\"user_id\":\"13\",\"task_id\":\"59\",\"task_name\":\"fdff\",\"p_id\":\"43433443fddfdfd\",\"log_id\":\"60\"}', 'Baber has just approved the activity of task \n Task belongs with fdff', 'Activity has approved', '2022-09-14 07:37:26', '2022-09-14 12:37:26'),
(26, 48, 0, 0, 'invoice_reminder', NULL, 'Invoice Reminder For month of September 2022', 'fdfddfdf', '2022-09-16 05:59:12', '2022-09-16 10:59:12'),
(27, 49, 0, 0, 'invoice_reminder', NULL, 'Invoice Reminder For month of September 2022', 'Test', '2022-09-16 05:59:18', '2022-09-16 10:59:18'),
(28, 50, 0, 0, 'invoice_reminder', NULL, 'Invoice Reminder For month of September 2022', 'bbbbb', '2022-09-16 05:59:20', '2022-09-16 10:59:20');

-- --------------------------------------------------------

--
-- Table structure for table `cm_ownership_info`
--

CREATE TABLE `cm_ownership_info` (
  `id` bigint(20) NOT NULL,
  `provider_id` bigint(20) NOT NULL DEFAULT 0,
  `dd_id` bigint(20) NOT NULL DEFAULT 0,
  `type_of_ownership` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `date_of_birth` varchar(255) DEFAULT NULL,
  `effective_date` varchar(255) DEFAULT NULL,
  `state_of_birth` varchar(255) DEFAULT NULL,
  `country_of_birth` varchar(255) DEFAULT NULL,
  `social_security_number` varchar(255) DEFAULT NULL,
  `ownership` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `cm_password_resets`
--

CREATE TABLE `cm_password_resets` (
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `cm_password_resets`
--

INSERT INTO `cm_password_resets` (`email`, `token`, `created_at`) VALUES
('faheem2@yopmail.com', '$2y$10$ToWAsuWrS7GMNG0lCYUu3ekLviG8AZUZjLIkWr5ahTzmpONcr/Hxa', '2022-08-01 06:09:08'),
('faheem@yopmail.com', '$2y$10$RNcaVdnIRFMVC0PSjJbDLuMU4FDG63ndFHKtL3fmXKMnB/JeQYq8u', '2022-08-09 10:57:17');

-- --------------------------------------------------------

--
-- Table structure for table `cm_payers`
--

CREATE TABLE `cm_payers` (
  `id` bigint(20) NOT NULL,
  `payer_name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `cm_payers`
--

INSERT INTO `cm_payers` (`id`, `payer_name`, `created_at`, `updated_at`) VALUES
(1, 'Blue Cross Blue Shield', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(2, 'Aetna', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(3, 'Cigna', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(4, 'United HealthCare', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(5, 'Texas Children Health Plan', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(6, 'Humana', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(7, 'Amerigroup', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(8, 'Community Health Choice', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(9, 'First Health', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(10, 'Health Smart', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(11, 'Multiplan', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(12, 'Managed Care Organization - MCO', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(13, 'Medicaid', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(14, 'Memorial Hermann', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(15, 'Molina Health', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(16, 'Superior Health', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(17, 'Tricare', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(18, 'Wellcare', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(19, 'Medicare Railroad', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(20, 'AvMed', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(21, 'Medicare', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(22, 'Galaxy Health', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(23, 'Tufts Health (Commercial)', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(24, 'Scott & White', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(25, 'Ambetter', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(26, 'Three Rivers Provider Network - TRPN', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(27, 'Driscoll', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(28, 'Texas Mutual Workers Comp', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(29, 'Christus Spohn', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(30, 'Cigna Healthspring', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(31, 'Multiplan Medicare Advantage Plan', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(32, 'UHC Plan (Texas Star Kids)', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(33, 'Anci Care', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(34, 'Benefit Management Administrators Inc.', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(35, 'TriWest', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(36, 'Healthwest', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(37, 'Paramount', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(38, 'Beacon Health Options', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(39, 'United Behavior Health', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(40, 'Tufts Health', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(41, 'Wellmed', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(42, 'Veterans Affairs', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(43, 'RightCare', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(44, 'AARP', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(45, 'Fallon Health', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(46, 'Mass Health', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(47, 'Magellan', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(48, 'ALLIED BENEFIT', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(49, 'BANKERS FIDELITY', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(50, 'POINT COMFORT - SWK', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(51, 'FIRST CARE', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(52, 'Great West (Cigna)', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(53, 'Tufts Public Plans', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(54, 'Commonwealth Care Alliance', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(55, 'MBHP (Mass Behavioral Health Partnership)', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(56, 'Blue Cross Medicare Advantage', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(57, 'Assured Benefits Administrators', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(58, 'Corvel', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(59, 'Renaissance Physicians', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(60, 'Molina Marketplace', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(61, 'Aetna Better Health', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(62, 'Prime Health Services', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(63, 'Kelsey-Seybold', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(64, 'CHRISTUS HEALTH PLAN', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(65, 'OSCAR HEALTH INSURANCE', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(66, 'COMMUNITY FIRST HEALTH PLAN', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(67, 'ALLIED NATIONAL', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(68, 'Health New England', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(69, 'Friday Health Insurance', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(70, 'El Paso Healthplan', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(71, 'Beacon Health Strategies', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(72, 'America\'s Choice Provider Network', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(73, 'Clear Spring Health Care', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(74, 'Colorado Access', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(75, 'Denver Health', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(76, 'Bright Health Management, Inc.', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(77, 'Friday Health Plan', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(78, 'ESSENCE', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(79, 'PROMINENCE', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(80, 'Veterans Evaluation Services (VES)', '2022-08-08 09:28:15', '2022-08-08 09:28:15'),
(81, 'Hoosier Insurance', '2022-08-08 09:28:15', '2022-08-08 09:28:15');

-- --------------------------------------------------------

--
-- Table structure for table `cm_payer_info`
--

CREATE TABLE `cm_payer_info` (
  `id` bigint(20) NOT NULL,
  `provider_id` bigint(20) NOT NULL DEFAULT 0,
  `dd_id` int(11) NOT NULL DEFAULT 0,
  `payer_id` int(11) NOT NULL DEFAULT 0,
  `status` varchar(255) NOT NULL,
  `payer_name` varchar(255) NOT NULL,
  `payer_status` varchar(255) NOT NULL,
  `initiation_date` varchar(255) NOT NULL,
  `file_attechment` varchar(255) NOT NULL,
  `effective_date` varchar(255) NOT NULL,
  `assigned_provider_id` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `cm_payment_logs`
--

CREATE TABLE `cm_payment_logs` (
  `id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL DEFAULT 0,
  `logs_data` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `cm_payment_logs`
--

INSERT INTO `cm_payment_logs` (`id`, `provider_id`, `logs_data`, `created_at`) VALUES
(1, 43, '{\"message\":\"The company id field is required.\"}', '2022-09-08 02:12:44'),
(2, 43, '{\"message\":\"The payment method you provided has already been attached to a customer.\"}', '2022-09-08 02:18:42'),
(3, 43, '{\"message\":\"The payment method you provided has already been attached to a customer.\"}', '2022-09-08 02:19:06'),
(4, 43, '{\"message\":\"SQLSTATE[HY000]: General error: 1364 Field \'details\' doesn\'t have a default value (SQL: insert into `cm_notifications` (`provider_id`, `notify_type`, `created_at`) values (43, on_board_provider, 2022-09-08 08:02:42))\"}', '2022-09-08 03:02:42'),
(5, 48, '{\"message\":\"SQLSTATE[23000]: Integrity constraint violation: 1048 Column \'company_id\' cannot be null (SQL: insert into `cm_invoices` (`company_id`, `provider_id`, `invoice_number`, `invoice_token`, `amount`, `payment_status`, `details`, `issue_date`, `due_date`, `is_recuring`, `created_at`) values (?, 48, CM0009, cm_d2wlgchieru4pvzttybyg7lkvmaw6gac, ?, pending, ?, 2022-10-09, 2022-10-12, 1, 2022-09-09 12:28:09))\"}', '2022-09-09 07:28:09');

-- --------------------------------------------------------

--
-- Table structure for table `cm_personal_access_tokens`
--

CREATE TABLE `cm_personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `cm_personal_access_tokens`
--

INSERT INTO `cm_personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `expires_at`, `last_used_at`, `created_at`, `updated_at`) VALUES
(4, 'App\\Models\\User', 4, 'API TOKEN', '13e2d4bb50d32aa3986f19c7518be86fe448b990d295874f4acb786dcd04f19d', '[\"*\"]', '2022-08-01 13:39:35', NULL, '2022-07-15 21:23:33', '2022-07-15 21:23:33'),
(5, 'App\\Models\\User', 5, 'API TOKEN', '68c775e32dd9b9a7b44e493af041ebac7beb89ce6a0c7452a76909636a9aa641', '[\"*\"]', '2022-08-01 13:39:35', NULL, '2022-07-18 12:12:31', '2022-07-18 12:12:31'),
(6, 'App\\Models\\User', 6, 'API TOKEN', 'a79c1baa5573233a938978738c12879f3e6ec021bdd7dd74afdbcb2266382290', '[\"*\"]', '2022-08-01 13:39:35', NULL, '2022-07-18 12:13:25', '2022-07-18 12:13:25'),
(7, 'App\\Models\\User', 7, 'API TOKEN', '22d204ebad718ef8b9fe7723a032db3133f81ffeeddd3921f2de7c97865a1d0b', '[\"*\"]', '2022-08-01 13:39:35', NULL, '2022-07-18 12:18:08', '2022-07-18 12:18:08'),
(8, 'App\\Models\\User', 8, 'API TOKEN', 'f1aafc9007861a0b9a2c2495817ec64ce77bc212908d63d10e62dabe82fe30e0', '[\"*\"]', '2022-08-01 13:39:35', NULL, '2022-07-18 12:19:49', '2022-07-18 12:19:49'),
(9, 'App\\Models\\User', 9, 'API TOKEN', '8575385ceb518813cd2e0e45cc434bc8f2595e5c59afda241994e1c6deb38880', '[\"*\"]', '2022-08-01 13:39:35', '2022-07-18 19:58:51', '2022-07-18 13:30:12', '2022-07-18 19:58:51'),
(10, 'App\\Models\\User', 10, 'Faheem Mahar Token', '9adc7ba5466cb9d277de3a8034f70d2f0674d67c2fa0a051f0dca3b63dd3706c', '[\"*\"]', '2022-08-01 13:39:35', NULL, '2022-07-18 16:28:48', '2022-07-18 16:28:48'),
(11, 'App\\Models\\User', 11, 'Faheem Mahar Token', 'e7931f5e6c65eada409e2d2848b6ed3667b4122d743704d40b9dcac642c95425', '[\"*\"]', '2022-08-01 13:39:35', NULL, '2022-07-18 16:29:25', '2022-07-18 16:29:25'),
(12, 'App\\Models\\User', 12, 'Ali Mahar Token', '91f118baf6409296df1aabf2d4e5bb24e64c68c1b650e95e892ea3efe5ea61e1', '[\"*\"]', '2022-08-01 13:39:35', NULL, '2022-07-18 19:52:13', '2022-07-18 19:52:13'),
(13, 'App\\Models\\User', 13, 'Ali Mahar Token', '32d7fd223c628c028c00f9b374c46027de6a8c0f4af8fc654badd98cb3d6ac6d', '[\"*\"]', '2022-08-01 13:39:35', NULL, '2022-07-18 19:54:53', '2022-07-18 19:54:53'),
(14, 'App\\Models\\User', 14, 'Ali Mahar Token', 'dc8336227565369a16481c77d32b48a6b50785f24eb323804471192016a0083a', '[\"*\"]', '2022-08-01 13:39:35', NULL, '2022-07-18 19:55:54', '2022-07-18 19:55:54'),
(15, 'App\\Models\\User', 15, 'Ali Mahar Token', 'dff86903d4bc297e0bd0bb340d29fce729173eecd9cb0672f009d3f9c5a418da', '[\"*\"]', '2022-08-01 13:39:35', NULL, '2022-07-18 19:58:41', '2022-07-18 19:58:41'),
(16, 'App\\Models\\User', 16, 'API TOKEN', '8a1be34a6d20454eec8710521e0c5579a0336cb2e7448425d50cd031db1c11fc', '[\"*\"]', '2022-08-01 13:39:35', NULL, '2022-07-20 14:46:08', '2022-07-20 14:46:08'),
(17, 'App\\Models\\User', 17, 'API TOKEN', '71ed35cdab4f17e86556b55cb90a80fc2f39fc41caa29b63fe00d869addf71de', '[\"*\"]', '2022-08-01 13:39:35', NULL, '2022-07-20 14:46:43', '2022-07-20 14:46:43'),
(18, 'App\\Models\\User', 18, 'API TOKEN', '5b2bfc933911fa3949aeebd73adf0de95b023a546986ebea501d48ac74a59e57', '[\"*\"]', '2022-08-01 13:39:35', '2022-07-26 17:37:40', '2022-07-20 15:03:25', '2022-07-26 17:37:40'),
(19, 'App\\Models\\User', 19, 'Faheeem Mahar Token', 'a8d9c236c28a002d86221b54c59ecc19beb8381babbaae8a6a302dc435c2bfbe', '[\"*\"]', '2022-08-01 13:39:35', NULL, '2022-07-20 16:24:41', '2022-07-20 16:24:41'),
(20, 'App\\Models\\User', 20, 'Faheeem Mahar Token', '3a279246411e36638957aed3771e686406b024f16457b9cef91917fb3b49cec4', '[\"*\"]', '2022-08-01 13:39:35', NULL, '2022-07-20 16:26:34', '2022-07-20 16:26:34'),
(21, 'App\\Models\\User', 21, 'Faheem Mahar Token', 'e4a47cbd869e2484e6ef0f26741d5e4c5a8b34aebfeda1f5b1ebbb6e78b7651e', '[\"*\"]', '2022-08-01 13:39:35', NULL, '2022-07-20 17:20:42', '2022-07-20 17:20:42'),
(22, 'App\\Models\\User', 22, 'API TOKEN', 'f00e208f3325f3e0ae194317745fea2d39f3d5308b714a17ef2a071b25108243', '[\"*\"]', '2022-08-01 13:39:35', NULL, '2022-07-21 13:14:46', '2022-07-21 13:14:46'),
(23, 'App\\Models\\User', 22, 'Admin TOKEN', '2da0cdaf112d9ebe7d851e05368234f1a643a464083f7ed99ade234971f932ef', '[\"*\"]', '2022-08-01 13:39:35', NULL, '2022-07-21 13:22:58', '2022-07-21 13:22:58'),
(24, 'App\\Models\\User', 22, 'Admin TOKEN', '1b380613e3e97d5285a16d4d3e53f0cd2e114b79bd102cafb3939eba25547a1e', '[\"*\"]', '2022-08-01 13:39:35', NULL, '2022-07-21 13:24:58', '2022-07-21 13:24:58'),
(25, 'App\\Models\\User', 22, 'Admin TOKEN', '1cf5e9253208c48f5233d440cc61787aee98831f8f6cfa95017a97cd605c9ffa', '[\"*\"]', '2022-08-01 13:39:35', NULL, '2022-07-21 13:25:33', '2022-07-21 13:25:33'),
(26, 'App\\Models\\User', 23, 'Admin TOKEN', 'a5aec187e5cd54caaa237fa20d83ecf053dc09d4ae705eadbe502dc81d8e0fa2', '[\"*\"]', '2022-08-01 13:39:35', NULL, '2022-07-22 14:41:33', '2022-07-22 14:41:33'),
(27, 'App\\Models\\User', 23, 'Admin TOKEN', '94aed1268be3f58cb0fb7fb19dc921299a594a787da30b7cbea107edc1d7e8c7', '[\"*\"]', '2022-08-01 13:39:35', NULL, '2022-07-22 16:05:08', '2022-07-22 16:05:08'),
(28, 'App\\Models\\User', 23, 'Admin TOKEN', 'a7d5fe39244fcfefaabeba73879ac878c19bc251d00d547076ee44b4ce0449f9', '[\"*\"]', '2022-08-01 13:39:35', NULL, '2022-07-22 16:08:41', '2022-07-22 16:08:41'),
(29, 'App\\Models\\User', 24, 'Faheeem12 Mahar12 Token', 'e0f85b14ab327d94a14c0d120ef068eedebc78651a226e485ef1490d3ac74195', '[\"*\"]', '2022-08-01 13:39:35', NULL, '2022-07-22 17:40:11', '2022-07-22 17:40:11'),
(30, 'App\\Models\\User', 23, 'Admin TOKEN', 'b86c1b59a3cdc9be58250c71f6d8052af8e43c914f67084056ba949afbdb7072', '[\"*\"]', '2022-08-01 13:39:35', NULL, '2022-07-22 17:41:42', '2022-07-22 17:41:42'),
(31, 'App\\Models\\User', 23, 'Admin TOKEN', '7e68972ec7174e83ec62fb2c7f5066fbe5f268c8dc05daff47bf728a259403cf', '[\"*\"]', '2022-08-01 13:39:35', NULL, '2022-07-22 17:43:11', '2022-07-22 17:43:11'),
(32, 'App\\Models\\User', 23, 'Admin TOKEN', '9709b24b445732ee76d13103cea0298b56c76b6b51274812582a8cb203faebdc', '[\"*\"]', '2022-08-01 13:39:35', NULL, '2022-07-22 17:44:47', '2022-07-22 17:44:47'),
(33, 'App\\Models\\User', 17, 'Admin TOKEN', '92c5402939223d73ddef019ba24c3fcc609cb1c5b3e53f3f1ae19395daa7f9d2', '[\"*\"]', '2022-08-01 13:39:42', NULL, '2022-08-01 08:39:42', '2022-08-01 08:39:42'),
(34, 'App\\Models\\User', 17, 'Admin TOKEN', '3e9f6f9807189ba5c3c208069d4a1fd8dc5a32019b640823a553150ebe793add', '[\"*\"]', '2022-08-01 13:41:45', NULL, '2022-08-01 08:41:45', '2022-08-01 08:41:45'),
(35, 'App\\Models\\User', 25, 'Faheem Token', 'af66d4615bbbc7a3b12deac1c300dd77037fb6bb5276ec1f334187c6a2b62bf0', '[\"*\"]', '2022-08-10 16:00:48', NULL, '2022-08-10 11:00:48', '2022-08-10 11:00:48'),
(36, 'App\\Models\\User', 27, 'Faheem Token', '9698eb831ad6fbdaf47db8353ba13796be2d349dd1d6f241bcdc76997760d3f9', '[\"*\"]', '2022-08-10 16:02:15', NULL, '2022-08-10 11:02:15', '2022-08-10 11:02:15'),
(37, 'App\\Models\\User', 28, 'Faheem Token', '69a82e5697fe76f9de2e2af8ac83e98d3255b7767cf91e664f187e97c6a6cc9f', '[\"*\"]', '2022-08-10 16:03:06', NULL, '2022-08-10 11:03:06', '2022-08-10 11:03:06'),
(38, 'App\\Models\\User', 29, 'Noor Token', '7fc91df14bda5b838d31655548c869114b131ed3434ea2417e60208d4269b434', '[\"*\"]', '2022-08-15 07:16:25', NULL, '2022-08-15 02:16:25', '2022-08-15 02:16:25'),
(39, 'App\\Models\\User', 30, 'Manzoor Token', '0056a300d42ecf1545ac6e1f1784aa3dda57e4c073cd331605fcdbf2a862a766', '[\"*\"]', '2022-08-24 13:04:28', NULL, '2022-08-24 08:04:28', '2022-08-24 08:04:28'),
(40, 'App\\Models\\User', 31, 'Admin TOKEN', '05d24d49a9a6ea169dde1149ef78c6eef6ecbdb7d3c7c9fa4528842ded06e351', '[\"*\"]', '2022-09-01 14:45:56', NULL, '2022-09-01 09:45:56', '2022-09-01 09:45:56'),
(41, 'App\\Models\\User', 32, 'Asif Aziz Token', 'ac820b46a06e93dc63960341b294e7759abcedd1a898765b98fb202c31c7f332', '[\"*\"]', '2022-09-01 14:48:33', NULL, '2022-09-01 09:48:33', '2022-09-01 09:48:33'),
(42, 'App\\Models\\User', 33, 'Admin TOKEN', 'c425d7f48376a97c6dac79707a8dbc87a61591f717182d5ec367cade9fe401a1', '[\"*\"]', '2022-09-02 04:18:40', NULL, '2022-09-01 23:18:40', '2022-09-01 23:18:40'),
(43, 'App\\Models\\User', 34, 'Baber azam Token', '205c44af22eea8f0c1edb63bdefab6b4abf08b2f0ac4e6fe508c40fa88709054', '[\"*\"]', '2022-09-02 04:36:10', NULL, '2022-09-01 23:36:10', '2022-09-01 23:36:10'),
(44, 'App\\Models\\User', 35, 'Muhammad rizwan Token', '8d03b0b4a8c1c2f58899a65f895a05a0550ee8baac7bde764684dc9b9512def5', '[\"*\"]', '2022-09-02 04:37:42', NULL, '2022-09-01 23:37:42', '2022-09-01 23:37:42'),
(45, 'App\\Models\\User', 36, 'AB de Villiers Token', '57b016cd538a3fd7159fda996ec680710330cec3422f136d48ed15c9a284ebf8', '[\"*\"]', '2022-09-02 04:42:20', NULL, '2022-09-01 23:42:20', '2022-09-01 23:42:20'),
(46, 'App\\Models\\User', 37, 'joe root Token', 'b2cf537bea4679c08895f1180fd6378f61663d86b71dd9c5253be7174ebc90e5', '[\"*\"]', '2022-09-02 04:44:40', NULL, '2022-09-01 23:44:40', '2022-09-01 23:44:40'),
(47, 'App\\Models\\User', 38, 'Mooen Ali Token', '7081de05b677309ad2214f0e7afc1cec8325f7265be92c3583b41b70cccaede6', '[\"*\"]', '2022-09-02 04:45:23', NULL, '2022-09-01 23:45:23', '2022-09-01 23:45:23'),
(48, 'App\\Models\\User', 39, 'Jack Leach Token', '43e386332d0ef646e6c82a28d8086c13f54d851dcb18663df24ced2568cf42d3', '[\"*\"]', '2022-09-02 04:46:29', NULL, '2022-09-01 23:46:29', '2022-09-01 23:46:29'),
(49, 'App\\Models\\User', 40, 'Haris Sohail Token', '31177d7f8bfa2247d5d8ce6e364bff0e13bb84fc9c324f7eae7102821580dbbf', '[\"*\"]', '2022-09-02 04:48:53', NULL, '2022-09-01 23:48:53', '2022-09-01 23:48:53'),
(50, 'App\\Models\\User', 41, 'Asif Ali Token', '0d9ee85244edd22e635e87a6c2d8b4ff33fe7e24882e3484a63eb4f6f04bcd92', '[\"*\"]', '2022-09-02 04:50:12', NULL, '2022-09-01 23:50:12', '2022-09-01 23:50:12'),
(51, 'App\\Models\\User', 42, 'wahab raiz Token', '4ab3de9e784ae5b1fb88d814cbed141b0c3efee1d66e77a70420d8896a1ce207', '[\"*\"]', '2022-09-02 04:50:55', NULL, '2022-09-01 23:50:55', '2022-09-01 23:50:55'),
(52, 'App\\Models\\User', 43, 'shoaib malik Token', 'b26a37c668a0d113c87da465b6d1cea7c8f922ccf735760558dd125b06df6ae6', '[\"*\"]', '2022-09-02 04:52:55', NULL, '2022-09-01 23:52:55', '2022-09-01 23:52:55'),
(53, 'App\\Models\\User', 44, 'ddsdsds Token', 'fd31e02fe74daac49f6a8b3cf0a334790da647c0b31eec3bd596973603dff48c', '[\"*\"]', '2022-09-08 07:21:53', NULL, '2022-09-08 02:21:53', '2022-09-08 02:21:53'),
(54, 'App\\Models\\User', 45, 'Naeem Token', 'd1972d0917b76599ac0fea5f7322d12093467600dc4bd17ae33153cbcfa93276', '[\"*\"]', '2022-09-08 07:38:06', NULL, '2022-09-08 02:38:06', '2022-09-08 02:38:06'),
(55, 'App\\Models\\User', 46, 'Saeed Token', 'cd2c89d95b5233d5215e08d5fb41b15abf65d8a298184444081a36cc8789ce56', '[\"*\"]', '2022-09-08 09:01:09', NULL, '2022-09-08 04:01:09', '2022-09-08 04:01:09'),
(56, 'App\\Models\\User', 47, 'dssdsd Token', '32022a0170341a2e699f4ca82e09e7d497f292e4ced6037fbef66f8b29d59e1b', '[\"*\"]', '2022-09-08 10:03:41', NULL, '2022-09-08 05:03:41', '2022-09-08 05:03:41'),
(57, 'App\\Models\\User', 48, 'fdfddfdf Token', 'd129ea69674b7cf2f95f28a18dad3c5cbcebca742229e220dcbbb538ef00851d', '[\"*\"]', '2022-09-09 12:28:03', NULL, '2022-09-09 07:28:03', '2022-09-09 07:28:03'),
(58, 'App\\Models\\User', 49, 'Test Token', 'bfa043d169a15f98bc6122518b0bbc8923233c8f98605f5df3c9fe92c5a1db81', '[\"*\"]', '2022-09-10 07:14:32', NULL, '2022-09-10 02:14:32', '2022-09-10 02:14:32'),
(59, 'App\\Models\\User', 50, 'bbbbb Token', '10d9252eb9d5448da33349f5229fe3faf401e1c028d6ad82bb3566c6179653d4', '[\"*\"]', '2022-09-10 07:36:30', NULL, '2022-09-10 02:36:30', '2022-09-10 02:36:30'),
(60, 'App\\Models\\User', 51, 'Ubaid Rehman Token', 'dbd0c1cae480fcd47f7e76162cbf0e69749c979c1ee28ae58385b0bdc919b5f3', '[\"*\"]', '2022-09-15 13:26:58', NULL, '2022-09-15 08:26:58', '2022-09-15 08:26:58'),
(61, 'App\\Models\\User', 52, 'c  cju86 c6grt4yj Token', '7a498fc56a49f76c9581d170bba19e156fd5b8a59b32c25e6dc933d46e114545', '[\"*\"]', '2022-09-16 04:46:43', NULL, '2022-09-15 23:46:43', '2022-09-15 23:46:43'),
(62, 'App\\Models\\User', 53, 'c  cju86 c6grt4yj Token', '6094f575346e5c3bc77f534480038fa4792a596202353a825ee6c621f292f21d', '[\"*\"]', '2022-09-16 04:48:22', NULL, '2022-09-15 23:48:22', '2022-09-15 23:48:22'),
(63, 'App\\Models\\User', 54, 'c  cju86 c6grt4yj Token', 'b384bfd6de064e11e4cc64231fdfb038f862a2032da6e3b21123ea23b83e34cb', '[\"*\"]', '2022-09-16 04:49:42', NULL, '2022-09-15 23:49:42', '2022-09-15 23:49:42'),
(64, 'App\\Models\\User', 55, 'c  cju86 c6grt4yj Token', '831be8ae9eb4fd7b6e10d0f6be457378161eef77e4d80b44dce9c82a321591ba', '[\"*\"]', '2022-09-16 04:51:11', NULL, '2022-09-15 23:51:11', '2022-09-15 23:51:11'),
(65, 'App\\Models\\User', 56, 'c  cju86 c6grt4yj Token', 'a8305f448fc7551ee4b6c23d0f3300e85bb69d9177c283964b17ca635f648a0f', '[\"*\"]', '2022-09-16 04:52:30', NULL, '2022-09-15 23:52:30', '2022-09-15 23:52:30'),
(66, 'App\\Models\\User', 57, 'c  cju86 c6grt4yj Token', 'd441752637e41f275f87613845d6fadb3c9506ad1ee0122c657301ca0ceb1c0e', '[\"*\"]', '2022-09-16 04:53:18', NULL, '2022-09-15 23:53:18', '2022-09-15 23:53:18'),
(67, 'App\\Models\\User', 58, 'c  cju86 c6grt4yj Token', 'a2f7bceff017307bedbd499a3e82e34b58e4c7e46b41aaff33c9e50c2fa4b0f1', '[\"*\"]', '2022-09-16 04:53:54', NULL, '2022-09-15 23:53:54', '2022-09-15 23:53:54'),
(68, 'App\\Models\\User', 59, 'c  cju86 c6grt4yj Token', '45456ac3772ae42d9c52c6a2356fcccb7394ac7851494882a56b740539f7a45a', '[\"*\"]', '2022-09-16 04:59:08', NULL, '2022-09-15 23:59:08', '2022-09-15 23:59:08'),
(69, 'App\\Models\\User', 60, 'c  cju86 c6grt4yj Token', 'a82bcffd6a3866120006871b28cd097cfa7477e53ceb15bac71e7a51b1f6e523', '[\"*\"]', '2022-09-16 05:00:40', NULL, '2022-09-16 00:00:40', '2022-09-16 00:00:40'),
(70, 'App\\Models\\User', 61, 'c  cju86 c6grt4yj Token', '00c0c7057d8aecc52e552db33fb506b384f35d42da20a215efe6eb356575bcfc', '[\"*\"]', '2022-09-16 05:03:49', NULL, '2022-09-16 00:03:49', '2022-09-16 00:03:49'),
(71, 'App\\Models\\User', 62, 'c  cju86 c6grt4yj Token', '1c0000d4fbb464de3c1994d3e72f1b6cf8c5ad0ba7718f23dc1d063d4afc3c50', '[\"*\"]', '2022-09-16 05:16:33', NULL, '2022-09-16 00:16:33', '2022-09-16 00:16:33');

-- --------------------------------------------------------

--
-- Table structure for table `cm_practice_location_info`
--

CREATE TABLE `cm_practice_location_info` (
  `id` bigint(20) NOT NULL,
  `provider_id` bigint(20) NOT NULL DEFAULT 0,
  `dd_id` bigint(20) NOT NULL DEFAULT 0,
  `emrs_using` varchar(255) NOT NULL,
  `primary_correspondence_address` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `fax` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `office_manager_name` varchar(255) DEFAULT NULL,
  `practise_address` varchar(255) DEFAULT NULL,
  `practise_phone` varchar(255) DEFAULT NULL,
  `practise_fax` varchar(255) DEFAULT NULL,
  `practise_email` varchar(255) DEFAULT NULL,
  `monday_from` varchar(255) DEFAULT NULL,
  `tuesday_from` varchar(255) DEFAULT NULL,
  `wednesday_from` varchar(255) DEFAULT NULL,
  `thursday_from` varchar(255) DEFAULT NULL,
  `friday_from` varchar(255) DEFAULT NULL,
  `saturday_from` varchar(255) DEFAULT NULL,
  `sunday_from` varchar(255) DEFAULT NULL,
  `monday_to` varchar(255) DEFAULT NULL,
  `tuesday_to` varchar(255) DEFAULT NULL,
  `wednesday_to` varchar(255) DEFAULT NULL,
  `thursday_to` varchar(255) DEFAULT NULL,
  `friday_to` varchar(255) DEFAULT NULL,
  `saturday_to` varchar(255) DEFAULT NULL,
  `sunday_to` varchar(255) DEFAULT NULL,
  `emr_using` varchar(255) DEFAULT NULL,
  `satisfied_with_emr` varchar(255) DEFAULT NULL,
  `emr_plan_on_using` varchar(255) DEFAULT NULL,
  `num_of_physical_location` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `cm_providers`
--

CREATE TABLE `cm_providers` (
  `id` bigint(20) NOT NULL,
  `provider_type` varchar(50) NOT NULL,
  `provider_name` varchar(100) DEFAULT NULL,
  `legal_business_name` varchar(100) DEFAULT NULL,
  `business_as` varchar(255) DEFAULT NULL,
  `num_of_provider` int(11) NOT NULL DEFAULT 0,
  `business_type` varchar(50) NOT NULL,
  `num_of_physical_locations` int(11) NOT NULL DEFAULT 0,
  `avg_pateints_day` int(11) NOT NULL DEFAULT 0,
  `seeking_service` varchar(50) NOT NULL,
  `practice_manage_software_name` varchar(50) DEFAULT NULL,
  `use_pms` varchar(4) NOT NULL DEFAULT '0',
  `electronic_health_record_software` varchar(50) DEFAULT NULL,
  `use_ehr` varchar(4) NOT NULL DEFAULT '0',
  `address` text DEFAULT NULL,
  `address_line_one` text DEFAULT NULL,
  `contact_person_name` varchar(100) DEFAULT NULL,
  `contact_person_designation` varchar(100) DEFAULT NULL,
  `contact_person_email` varchar(50) DEFAULT NULL,
  `contact_person_phone` varchar(50) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `zip_code` varchar(50) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `begining_date` varchar(50) DEFAULT NULL,
  `has_physical_location` varchar(4) NOT NULL DEFAULT '0',
  `is_active` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `cm_providers`
--

INSERT INTO `cm_providers` (`id`, `provider_type`, `provider_name`, `legal_business_name`, `business_as`, `num_of_provider`, `business_type`, `num_of_physical_locations`, `avg_pateints_day`, `seeking_service`, `practice_manage_software_name`, `use_pms`, `electronic_health_record_software`, `use_ehr`, `address`, `address_line_one`, `contact_person_name`, `contact_person_designation`, `contact_person_email`, `contact_person_phone`, `city`, `state`, `zip_code`, `comments`, `begining_date`, `has_physical_location`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'solo', '', 'test', 'test', 1, 'Startup', 1, 12, 'credentialing', 'test', '0', 'test', '1', 'ffddffdfd', 'ddssds', 'fdffd', 'fddfd', 'dsdds', 'contact_person_phone', 'dsdss', 'sddsds', 'dsds', 'fdfdfdfdfdfdfff', '2022-07-15', '0', 0, '2022-07-29 11:13:19', '2022-07-28 09:41:12'),
(3, 'group', NULL, 'dssddds', 'sddsdd', 43, 'established', 3, 4, 'Provider credentiality', 'dsddssdds', 'no', 'saassasa', 'no', 'sddsds', 'sdd', 'sdds', 'dsds', 'sdds', 'dsds', 'lahore', 'punjab', '455454', 'dsddds', NULL, 'yes', 0, '2022-07-29 09:52:35', '2022-07-29 09:52:35'),
(4, 'solo', 'dsdsdsdsdsdsd', NULL, NULL, 0, 'startup', 0, 0, 'Provider credentiality', NULL, '0', NULL, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2022-07-14', 'no', 0, '2022-07-29 10:00:22', '2022-07-29 10:00:22'),
(5, 'solo', 'sdssdsds', NULL, NULL, 0, 'established', 5, 4, 'Provider credentiality', 'fdfdfd', 'yes', 'fdfdfd', 'yes', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2022-07-21', 'no', 0, '2022-07-29 10:06:36', '2022-07-29 10:06:36'),
(6, 'solo', 'dsdssdd', NULL, NULL, 0, 'startup', 0, 0, 'Provider credentiality', NULL, '0', NULL, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2022-07-20', 'no', 0, '2022-07-29 10:07:24', '2022-07-29 10:07:24'),
(7, 'solo', 'test provider', NULL, NULL, 0, 'startup', 0, 0, 'Provider credentiality', NULL, '0', NULL, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2022-07-21', 'no', 0, '2022-07-29 10:08:31', '2022-07-29 10:08:31'),
(8, 'solo', 'ffdffddf', NULL, NULL, 0, 'startup', 0, 0, 'Provider credentiality', NULL, '0', NULL, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2022-08-17', 'no', 0, '2022-08-02 01:16:50', '2022-08-02 01:16:50'),
(9, 'solo', 'fdfdf', NULL, NULL, 0, 'startup', 0, 0, 'Provider credentiality', NULL, '0', NULL, '0', 'fdfdfd', 'fdfdf', 'dfdf', 'ffdfdf', 'dfdfdf', '+45456654545', 'karachi', 'sindh', '54454545', NULL, '2022-08-10', 'yes', 0, '2022-08-02 04:45:21', '2022-08-02 04:45:21'),
(10, 'solo', 'fddfrrrfd', NULL, NULL, 0, 'startup', 0, 0, 'Provider credentiality', NULL, '0', NULL, '0', 'vvcvccvvc', 'fdvcffxxf', 'vccvvc', 'ffffdfd', 'vcvcvv@yopmail.com', '+923553485457', 'lahore', 'punjab', '5555', 'dffddffddfdf', '2022-08-11', 'yes', 0, '2022-08-02 05:15:33', '2022-08-02 05:15:33'),
(11, 'solo', 'fffd', NULL, NULL, 0, 'startup', 0, 0, 'Provider credentiality', NULL, '0', NULL, '0', 'dffdfd', NULL, NULL, NULL, NULL, '+923322946965', NULL, NULL, NULL, NULL, '2022-08-17', 'no', 0, '2022-08-02 23:17:54', '2022-08-02 23:17:54'),
(12, 'NULL', 'NULL', 'NULL', 'NULL', 0, 'NULL', 0, 0, 'NULL', 'NULL', '0', 'NULL', '0', 'NULL', 'NULL', 'NULL', 'NULL', 'NULL', 'NULL', 'NULL', 'NULL', 'NULL', 'NULL', 'NULL', '0', 0, '2022-08-03 09:41:56', '2022-08-03 09:41:56'),
(13, 'NULL', 'NULL', 'NULL', 'NULL', 0, 'NULL', 0, 0, 'NULL', 'NULL', '0', 'NULL', '0', 'NULL', 'NULL', 'NULL', 'NULL', 'NULL', 'NULL', 'NULL', 'NULL', 'NULL', 'NULL', 'NULL', '0', 0, '2022-08-03 09:49:30', '2022-08-03 09:49:30'),
(14, 'NULL', 'NULL', 'NULL', 'NULL', 0, 'NULL', 0, 0, 'NULL', 'NULL', '0', 'NULL', '0', 'NULL', 'NULL', 'NULL', 'NULL', 'NULL', 'NULL', 'NULL', 'NULL', 'NULL', 'NULL', 'NULL', '0', 0, '2022-08-03 09:49:30', '2022-08-03 09:49:30'),
(15, 'solo', 'Testing the webs', NULL, NULL, 0, 'startup', 0, 0, 'Provider credentiality', NULL, '0', NULL, '0', NULL, NULL, 'Faheem', 'Developer', 'faheem@yopmail.com', '+923458989747', NULL, NULL, NULL, NULL, '2022-08-05', 'no', 0, '2022-08-05 11:11:00', '2022-08-05 06:11:00'),
(16, 'group', NULL, 'Faheem Businessgkkssssddd', 'Developersss', 1, 'established', 2, 5, 'Provider credentiality', 'MIT', 'no', 'DIT', 'yes', 'This is my address 1', 'This is my address 2', 'Faheem Mahar', 'Team Lead', 'faheem@yopmail.com', '+923453215455', 'karachi', 'sindh', '1234', 'This is my testing the text', NULL, 'yes', 0, '2022-08-08 12:04:13', '2022-08-08 01:16:56'),
(17, 'solo', 'Faheem SE', NULL, NULL, 0, 'startup', 0, 0, 'Provider credentiality', NULL, '0', NULL, '0', 'address 1', 'address 2', 'Faheem', 'SE', 'contact@yopmail.com', '+923452552694', 'karachi', 'sindh', '1230121232', NULL, '2022-08-11', 'no', 0, '2022-08-09 23:59:18', '2022-08-09 23:59:18'),
(18, 'solo', 'Faheem SE', NULL, NULL, 0, 'startup', 0, 0, 'Provider credentiality', NULL, '0', NULL, '0', 'address 1', 'address 2', 'Faheem', 'SE', 'contact@yopmail.com', '+923452552694', 'karachi', 'sindh', '1230121232', NULL, '2022-08-11', 'no', 0, '2022-08-10 00:17:25', '2022-08-10 00:17:25'),
(19, 'solo', 'Faheem SE', NULL, NULL, 0, 'startup', 0, 0, 'Provider credentiality', NULL, '0', NULL, '0', 'address 1', 'address 2', 'Faheem', 'SE', 'contact@yopmail.com', '+923452552694', 'karachi', 'sindh', '1230121232', NULL, '2022-08-11', 'no', 0, '2022-08-10 00:18:52', '2022-08-10 00:18:52'),
(20, 'solo', 'Faheem SE', NULL, NULL, 0, 'startup', 0, 0, 'Provider credentiality', NULL, '0', NULL, '0', 'address 1', 'address 2', 'Faheem', 'SE', 'contact@yopmail.com', '+923452552694', 'karachi', 'sindh', '1230121232', NULL, '2022-08-11', 'no', 0, '2022-08-10 00:24:07', '2022-08-10 00:24:07'),
(21, 'solo', 'GM Service', NULL, NULL, 0, 'established', 1, 1, 'Provider credentiality', 'adf', 'no', 'fds', 'no', 'address 1', 'address 2', 'GM', 'TM', 'gm@yopmail.com', '+923453625878', 'lahore', 'sindh', '0000225', NULL, NULL, 'no', 0, '2022-08-10 00:27:53', '2022-08-10 00:27:53'),
(22, 'solo', 'Test email', NULL, NULL, 0, 'startup', 0, 0, 'Provider credentiality', NULL, '0', NULL, '0', 'address 1', 'address 2', 'Naeem', 'Developer', 'naeem@yopmail.com', '+9234536256897', 'islamabad', 'sindh', '0002525', NULL, '2022-08-18', 'yes', 0, '2022-08-10 00:44:48', '2022-08-10 00:44:48'),
(23, 'solo', 'Test email', NULL, NULL, 0, 'startup', 0, 0, 'Provider credentiality', NULL, '0', NULL, '0', 'address 1', 'address 2', 'Naeem', 'Developer', 'naeem@yopmail.com', '+9234536256897', 'islamabad', 'sindh', '0002525', NULL, '2022-08-18', 'yes', 0, '2022-08-10 00:47:06', '2022-08-10 00:47:06'),
(24, 'solo', 'Test email', NULL, NULL, 0, 'startup', 0, 0, 'Provider credentiality', NULL, '0', NULL, '0', 'address 1', 'address 2', 'Naeem', 'Developer', 'naeem@yopmail.com', '+9234536256897', 'islamabad', 'sindh', '0002525', NULL, '2022-08-18', 'yes', 0, '2022-08-10 00:49:29', '2022-08-10 00:49:29'),
(25, 'solo', 'Test email', NULL, NULL, 0, 'startup', 0, 0, 'Provider credentiality', NULL, '0', NULL, '0', 'address 1', 'address 2', 'Naeem', 'Developer', 'naeem@yopmail.com', '+9234536256897', 'islamabad', 'sindh', '0002525', NULL, '2022-08-18', 'yes', 0, '2022-08-10 00:56:20', '2022-08-10 00:56:20'),
(26, 'solo', 'Test again', NULL, NULL, 0, 'established', 0, 12, 'Provider credentiality', 'pms softwate', 'no', 'electric health record', 'yes', 'address 1', 'address 2', 'Shafi', 'Tester', 'shafi@yopmail.com', '+923456289741', 'lahore', 'punjab', '00012', 'Testing the app', NULL, 'yes', 0, '2022-08-10 01:28:34', '2022-08-10 01:28:34'),
(27, 'solo', 'Test provider', NULL, NULL, 0, 'established', 13, 2, 'Provider credentiality', 'i am using pms', 'yes', 'i am using electronic health record', 'no', 'address 1', 'address 2', 'shayan', 'tester', 'shayan@yopmail.com', '+923458748744', 'lahore', 'punjab', '002002', 'test the data', NULL, 'yes', 0, '2022-08-10 01:37:35', '2022-08-10 01:37:35'),
(28, 'group', NULL, 'Faheem App', 'Developer', 4, 'startup', 0, 0, 'Provider credentiality', NULL, '0', NULL, '0', 'address 1', 'address 2', 'Shahid', 'Tester', 'shahid@yopmail.com', '+923453628585', NULL, NULL, NULL, 'test test test', '2022-08-11', 'yes', 0, '2022-08-10 01:51:28', '2022-08-10 01:51:28'),
(29, 'group', NULL, 'App dev', 'Dev', 2, 'established', 12, 4, 'Provider credentiality', 'dffdfdfd', 'no', 'dfddffd', 'no', 'address 1', 'address 2', 'Saeed', 'Tester', 'saeed@yopmail.com', '+923457898774', 'karachi', 'sindh', '000252', 'sddsdssddsds', NULL, 'yes', 0, '2022-08-10 01:54:29', '2022-08-10 01:54:29'),
(30, 'group', 'This is the testing textg', NULL, NULL, 1, 'startup', 0, 0, 'Provider credentiality', NULL, '0', NULL, '0', 'address', 'address 2', 'Nael', 'Dev', 'nael@yopmail.com', '+923453878454', 'karachi', 'sindh', '0001225', 'ssddsddd', '2022-08-12', 'no', 0, '2022-08-17 11:49:29', '2022-08-11 05:22:23'),
(31, 'solo', 'this is my business', NULL, NULL, 0, 'startup', 1, 2, 'Billing only', NULL, '0', NULL, 'yes', 'hyderabadsd', 'sindhsd', 'Faheems', 'Devsf', 'shanus@yopmail.com', '+03522525524552', 'karachi', 'sindh', '00000005', 'dddssdddddssssd thte', '2022-08-15', 'no', 0, '2022-08-12 10:58:19', '2022-08-12 05:58:19'),
(32, 'solo', 'Noor', NULL, NULL, 0, 'established', 0, 0, 'Provider credentiality and billing', NULL, '0', NULL, '0', 'Qasimabad', 'Hyderabad', 'Noor', 'Operator', 'noor@yopmail.com', '+922135353524', 'karachi', 'sindh', '055252', 'I need this service', '2022-08-17', 'yes', 0, '2022-08-17 11:20:50', '2022-08-15 02:15:08'),
(33, 'group', NULL, 'Faheem', 'Mahar', 2, 'startup', 0, 0, 'Provider credentiality', NULL, '0', NULL, '0', 'Hyderabad', 'Sindh', 'Manzoor', 'Developer', 'manzoor@yopmail.com', '+923453658887', 'lahore', 'punjab', '0245253', 'This is my testing text', '2022-08-17', 'no', 0, '2022-09-01 05:47:51', '2022-09-01 00:47:51'),
(34, 'solo', 'Solo_startup_address_no', NULL, NULL, 0, 'startup', 0, 0, 'Billing only', NULL, '0', NULL, '0', 'dssdddsds', 'dsdsds', 'Faheem', 'test', 'test@yopmail.com', '+92242553225222253322', 'karachi', 'sindh', '4555212', 'fgfgfgf', '2022-08-20', 'no', 0, '2022-08-19 11:06:08', '2022-08-19 06:06:08'),
(35, 'solo', 'Solo_establishe_address_yes', NULL, NULL, 0, 'established', 2, 4, 'Billing only', 'fffd', 'no', 'ddsds', 'yes', 'sddsdds', 'dsdsdsds', 'sddsdsd', 'rtest', 'sdssd@yopmail.com', '+452522223254', 'islamabad', 'sindh', '452252', 'fdffdfd', NULL, 'yes', 0, '2022-08-19 10:55:30', '2022-08-19 05:55:30'),
(36, 'group', NULL, 'group-startup', 'sddsddds', 1, 'established', 5, 2, 'Credentialing only', NULL, '0', NULL, 'yes', 'ffdfd', 'fdfdf', 'dsddds', 'sddsdsdssd', 'ddds@yopmail.com', '+12523325222', 'karachi', 'sindh', '12345', NULL, '2022-08-19', 'no', 0, '2022-09-01 05:17:21', '2022-09-01 00:17:21'),
(37, 'group', NULL, 'group-stablished', 'test', 2, 'established', 3, 4, 'Billing only', 'jhjhhjhNL', 'yes', 'hghhgtMK', 'no', 'ggfggf', 'gfgfgfg', 'fdfd', 'fdffd', 'testcontract@yopmail.com', '+452252243252', 'karachi', 'sindh', '5666', NULL, NULL, 'no', 0, '2022-08-23 14:00:59', '2022-08-22 05:52:48'),
(38, 'solo', 'Faheem test email', NULL, NULL, 0, 'startup', 0, 0, 'Credentialing only', NULL, '0', NULL, '0', 'Karachi', 'Sindh', 'Email', 'Test', 'emailtest@yopmail.com', '+155225422552', 'lahore', 'sindh', '08000', NULL, '2022-08-25', 'yes', 0, '2022-08-20 00:20:09', '2022-08-20 00:20:09'),
(39, 'solo', 'Faheem test email', NULL, NULL, 0, 'startup', 0, 0, 'Credentialing only', NULL, '0', NULL, '0', 'Karachi', 'Sindh', 'Email', 'Test', 'emailtest@yopmail.com', '+155225422552', 'lahore', 'sindh', '08000', NULL, '2022-08-25', 'yes', 0, '2022-08-20 00:21:05', '2022-08-20 00:21:06'),
(40, 'solo', 'Faheem test email', NULL, NULL, 0, 'startup', 0, 0, 'Credentialing only', NULL, '0', NULL, '0', 'Karachi', 'Sindh', 'Email', 'Test', 'emailtest@yopmail.com', '+155225422552', 'lahore', 'sindh', '08000', NULL, '2022-08-25', 'yes', 0, '2022-08-29 04:43:03', '2022-08-28 23:43:03'),
(41, 'solo', 'Asif Aziz', NULL, NULL, 0, 'startup', 0, 0, 'Billing only', NULL, '0', NULL, '0', 'Al Khaleej Towers', 'karachi', 'Asif Aziz', 'owner', 'aam@claimsmedinc.com', '+1864894156156', 'karachi', 'sindh', '88888', 'I need billing service', '2022-09-01', 'yes', 0, '2022-09-01 09:48:05', '2022-09-01 09:48:05'),
(43, 'solo', 'fddffd', NULL, NULL, 0, 'startup', 0, 0, 'Credentialing only', NULL, '0', NULL, '0', 'fdfd', 'fdfd', 'ddsdsds', 'Test', 'mytest@yopmail.com', '+134535253525', 'lahore', 'sindh', '52525250', 'sssasasa', '2022-09-15', 'yes', 0, '2022-09-08 05:10:14', '2022-09-08 00:10:14'),
(44, 'solo', 'Naeem', NULL, NULL, 0, 'startup', 0, 0, 'Credentialing and billing', NULL, '0', NULL, '0', 'assasa', 'sssas', 'Naeem', 'MR', 'mrnaeem@yopmail.com', '+15585452254', 'lahore', 'punjab', '2525254', 'sddsdssd', '2022-09-09', 'yes', 0, '2022-09-08 02:34:16', '2022-09-08 02:34:16'),
(45, 'solo', 'Saeed', NULL, NULL, 0, 'startup', 0, 0, 'Billing only', NULL, '0', NULL, '0', 'sasasss', 'sdddsd', 'Saeed', 'DG', 'saeed@yopmail.com', '+12553554245', 'karachi', 'punjab', '45242540', 'fdffd', '2022-09-15', 'yes', 0, '2022-09-08 03:50:35', '2022-09-08 03:50:35'),
(46, 'solo', 'Notification', NULL, NULL, 0, 'startup', 0, 0, 'Billing only', NULL, '0', NULL, '0', 'sddsds', 'sdddd', 'notification', 'DG', 'notification@yopmail.com', '+13453565224', 'karachi', 'sindh', '4522425', NULL, '2022-09-15', 'yes', 0, '2022-09-08 04:50:10', '2022-09-08 04:50:10'),
(47, 'solo', 'Notify', NULL, NULL, 0, 'startup', 0, 0, 'Billing only', NULL, '0', NULL, '0', 'aassssa', 'ssddsds', 'dssdsd', 'dsffdff', 'abc@yopmail.com', '+15424242122', 'karachi', 'punjab', '12524522', NULL, '2022-09-09', 'yes', 1, '2022-09-08 10:03:52', '2022-09-08 05:03:52'),
(48, 'solo', 'test noti msg', NULL, NULL, 0, 'startup', 0, 0, 'Credentialing only', NULL, '0', NULL, '0', 'dfdffd', 'dffd', 'fdfddfdf', 'test', 'abcd@yopmail.com', '+15255245252', 'lahore', 'punjab', '22222222', 'ssseee', '2022-09-16', 'yes', 1, '2022-09-09 12:30:33', '2022-09-09 07:30:33'),
(49, 'solo', 'Test dev', NULL, NULL, 0, 'startup', 0, 0, 'Billing only', NULL, '0', NULL, '0', 'fdsffddf', 'dfssdssaa', 'Test', 'test', 'testdev@yopmail.com', '+13456255525', 'karachi', 'punjab', '5242523', 'test the info', '2022-09-11', 'yes', 1, '2022-09-10 07:14:35', '2022-09-10 02:14:35'),
(50, 'group', NULL, 'test', 'bssdd', 2, 'startup', 0, 0, 'Billing only', NULL, '0', NULL, '0', 'address', 'address 1', 'bbbbb', 'bbb', 'bbb@yopmail.com', '+1525558555', 'lahore', 'punjab', '12345', 'ddsdsdsdsds', '2022-09-16', 'yes', 1, '2022-09-10 07:36:33', '2022-09-10 02:36:33'),
(51, 'NULL', 'NULL', 'NULL', 'NULL', 0, 'NULL', 0, 0, 'NULL', 'NULL', '0', 'NULL', '0', 'NULL', 'NULL', 'NULL', 'NULL', 'NULL', 'NULL', 'NULL', 'NULL', 'NULL', 'NULL', 'NULL', '0', 0, '2022-09-11 08:57:23', '2022-09-11 08:57:23');

-- --------------------------------------------------------

--
-- Table structure for table `cm_providers_companies_map`
--

CREATE TABLE `cm_providers_companies_map` (
  `id` bigint(20) NOT NULL,
  `company_id` bigint(20) NOT NULL DEFAULT 0,
  `provider_id` bigint(20) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `cm_providers_companies_map`
--

INSERT INTO `cm_providers_companies_map` (`id`, `company_id`, `provider_id`, `created_at`, `updated_at`) VALUES
(1, 1, 2, '2022-07-29 09:40:27', '2022-07-29 09:40:27'),
(2, 1, 3, '2022-07-29 09:52:35', '2022-07-29 09:52:35'),
(3, 1, 4, '2022-07-29 10:00:22', '2022-07-29 10:00:22'),
(4, 1, 5, '2022-07-29 10:06:36', '2022-07-29 10:06:36'),
(5, 1, 6, '2022-07-29 10:07:24', '2022-07-29 10:07:24'),
(6, 1, 7, '2022-07-29 10:08:31', '2022-07-29 10:08:31'),
(7, 1, 8, '2022-08-02 01:16:50', '2022-08-02 01:16:50'),
(8, 1, 9, '2022-08-02 04:45:21', '2022-08-02 04:45:21'),
(9, 1, 10, '2022-08-02 05:15:33', '2022-08-02 05:15:33'),
(10, 1, 11, '2022-08-02 23:17:54', '2022-08-02 23:17:54'),
(11, 0, 12, '2022-08-03 09:41:56', '2022-08-03 09:41:56'),
(12, 0, 13, '2022-08-03 09:49:30', '2022-08-03 09:49:30'),
(13, 0, 14, '2022-08-03 09:49:30', '2022-08-03 09:49:30'),
(14, 1, 15, '2022-08-04 00:10:19', '2022-08-04 00:10:19'),
(15, 1, 16, '2022-08-04 09:01:33', '2022-08-04 09:01:33'),
(16, 1, 17, '2022-08-09 23:59:18', '2022-08-09 23:59:18'),
(17, 1, 18, '2022-08-10 00:17:25', '2022-08-10 00:17:25'),
(18, 1, 19, '2022-08-10 00:18:52', '2022-08-10 00:18:52'),
(19, 1, 20, '2022-08-10 00:24:07', '2022-08-10 00:24:07'),
(20, 1, 21, '2022-08-10 00:27:53', '2022-08-10 00:27:53'),
(21, 1, 22, '2022-08-10 00:44:48', '2022-08-10 00:44:48'),
(22, 1, 23, '2022-08-10 00:47:06', '2022-08-10 00:47:06'),
(23, 1, 24, '2022-08-10 00:49:29', '2022-08-10 00:49:29'),
(24, 1, 25, '2022-08-10 00:56:20', '2022-08-10 00:56:20'),
(25, 1, 26, '2022-08-10 01:28:34', '2022-08-10 01:28:34'),
(26, 1, 27, '2022-08-10 01:37:35', '2022-08-10 01:37:35'),
(27, 1, 28, '2022-08-10 01:51:28', '2022-08-10 01:51:28'),
(28, 1, 29, '2022-08-10 01:54:29', '2022-08-10 01:54:29'),
(29, 1, 30, '2022-08-10 02:00:13', '2022-08-10 02:00:13'),
(30, 1, 1, '2022-08-10 17:35:19', '2022-08-10 17:35:19'),
(31, 1, 31, '2022-08-11 06:05:01', '2022-08-11 06:05:01'),
(32, 1, 32, '2022-08-15 02:15:08', '2022-08-15 02:15:08'),
(33, 1, 33, '2022-08-17 06:52:15', '2022-08-17 06:52:15'),
(34, 1, 34, '2022-08-18 02:15:10', '2022-08-18 02:15:10'),
(35, 1, 35, '2022-08-18 02:20:08', '2022-08-18 02:20:08'),
(36, 1, 36, '2022-08-18 03:01:18', '2022-08-18 03:01:18'),
(37, 1, 37, '2022-08-18 09:13:20', '2022-08-18 09:13:20'),
(38, 1, 38, '2022-08-20 00:20:09', '2022-08-20 00:20:09'),
(39, 1, 39, '2022-08-20 00:21:06', '2022-08-20 00:21:06'),
(40, 1, 40, '2022-08-20 00:22:25', '2022-08-20 00:22:25'),
(41, 1, 41, '2022-09-01 09:48:05', '2022-09-01 09:48:05'),
(42, 1, 42, '2022-09-07 10:04:19', '2022-09-07 10:04:19'),
(43, 1, 43, '2022-09-07 11:07:20', '2022-09-07 11:07:20'),
(44, 1, 44, '2022-09-08 02:34:16', '2022-09-08 02:34:16'),
(45, 1, 45, '2022-09-08 03:50:35', '2022-09-08 03:50:35'),
(46, 1, 46, '2022-09-08 04:50:10', '2022-09-08 04:50:10'),
(47, 1, 47, '2022-09-08 04:56:06', '2022-09-08 04:56:06'),
(48, 1, 48, '2022-09-08 09:25:07', '2022-09-08 09:25:07'),
(49, 1, 49, '2022-09-10 02:12:06', '2022-09-10 02:12:06'),
(50, 1, 50, '2022-09-10 02:34:08', '2022-09-10 02:34:08'),
(51, 0, 51, '2022-09-11 08:57:23', '2022-09-11 08:57:23');

-- --------------------------------------------------------

--
-- Table structure for table `cm_provider_members`
--

CREATE TABLE `cm_provider_members` (
  `id` bigint(20) NOT NULL,
  `provider_id` bigint(20) NOT NULL DEFAULT 0,
  `member_id` bigint(20) NOT NULL DEFAULT 0,
  `user_id` bigint(20) NOT NULL DEFAULT 0,
  `email` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(50) NOT NULL,
  `data` text NOT NULL,
  `attachments` text NOT NULL,
  `is_complete` tinyint(1) NOT NULL DEFAULT 0,
  `is_email_sent` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `cm_provider_members`
--

INSERT INTO `cm_provider_members` (`id`, `provider_id`, `member_id`, `user_id`, `email`, `name`, `token`, `data`, `attachments`, `is_complete`, `is_email_sent`, `created_at`, `updated_at`) VALUES
(2, 33, 1, 0, 'faheem@yopmail.com', '', 'cm_nsggsrpedtlsfeprlbhb9eqx0wre7qxr', '{\"cahq\":\"PA\",\"provider_first_name\":\"dfdf\",\"provider_last_name\":\"fdfd\",\"national_provider_id\":\"fdfd\",\"primary_speciality\":\"dsdds\",\"secondary_speciality\":\"sdds\",\"date_of_birth_provider\":\"2022-09-09\",\"cell_phone\":\"444334\",\"provider_email\":\"abc@yopmail.cm\",\"state_license_number\":\"5444545454\",\"state_license_issue_date\":\"2022-09-09\",\"state_license_expiration_date\":\"2022-09-22\",\"driver_license_number\":\"32434343\",\"driver_license_number_issue_date\":\"2022-09-08\",\"driver_license_number_expiration_date\":\"2022-09-16\",\"dea_number\":\"344334\",\"dea_issue_date\":\"2022-09-08\",\"caqh_username\":\"friend12@yopmail.com\",\"caqh_password\":\"123123\",\"state_of_birth\":\"sdsds\",\"country_birth\":\"sdds\",\"citizenship\":\"sdds\",\"hospital_privilage\":\"sddssd\",\"is_member\":\"1\",\"file_IRS_CP_575\":\"6310bcd43828a_image.png\"}', '', 1, 1, '2022-09-01 14:08:20', '2022-09-01 09:08:20');

-- --------------------------------------------------------

--
-- Table structure for table `cm_roles`
--

CREATE TABLE `cm_roles` (
  `id` int(11) NOT NULL,
  `admin_id` bigint(20) NOT NULL DEFAULT 0,
  `role_name` varchar(100) NOT NULL,
  `role_short_name` varchar(100) NOT NULL,
  `role_description` text NOT NULL,
  `role_capabilities` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `cm_roles`
--

INSERT INTO `cm_roles` (`id`, `admin_id`, `role_name`, `role_short_name`, `role_description`, `role_capabilities`, `created_at`, `updated_at`) VALUES
(2, 1, 'test1', 'test123', 'fdffdfddd', 'add,update,delete', '2022-07-20 08:17:40', '2022-07-20 15:17:40'),
(3, 1, 'test', 'test123', 'fffdffd', NULL, '2022-07-21 15:06:38', '2022-07-21 15:06:38'),
(4, 1, 'test2', 'test123', 'fffdffd', NULL, '2022-07-21 16:16:01', '2022-07-21 16:16:01'),
(5, 1, 'test3', 'test123', 'fffdffd', NULL, '2022-07-21 16:21:46', '2022-07-21 16:21:46'),
(6, 1, 'test4', 'test123', 'fffdffd', NULL, '2022-07-21 16:23:37', '2022-07-21 16:23:37'),
(7, 1, 'test5', 'test123', 'fffdffd', NULL, '2022-07-21 16:32:03', '2022-07-21 16:32:03'),
(8, 1, 'provider', 'provider', 'This is the  provider', '', '2022-08-10 15:33:00', '2022-08-10 15:31:24'),
(9, 1, 'operational manager', 'op', 'operational manager', NULL, '2022-09-01 23:27:09', '2022-09-01 23:27:09'),
(10, 1, 'Team Lead', 'TL', 'Team Lead', NULL, '2022-09-01 23:29:03', '2022-09-01 23:29:03'),
(11, 1, 'Team Member', 'TM', 'Team Member', NULL, '2022-09-01 23:29:34', '2022-09-01 23:29:34'),
(12, 1, 'Supervisor', 'SP', 'Supervisor', NULL, '2022-09-01 23:30:01', '2022-09-01 23:30:01'),
(13, 1, 'Provider Member', 'PM', 'Provider Member', NULL, '2022-09-14 08:07:58', '2022-09-14 08:07:29');

-- --------------------------------------------------------

--
-- Table structure for table `cm_roles_capabilities_map`
--

CREATE TABLE `cm_roles_capabilities_map` (
  `id` bigint(20) NOT NULL,
  `role_id` bigint(20) NOT NULL DEFAULT 0,
  `capability_id` bigint(20) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `cm_roles_capabilities_map`
--

INSERT INTO `cm_roles_capabilities_map` (`id`, `role_id`, `capability_id`, `created_at`, `updated_at`) VALUES
(1, 7, 1, '2022-07-21 16:32:03', '2022-07-21 09:32:03'),
(2, 7, 2, '2022-07-21 16:32:03', '2022-07-21 09:32:03'),
(3, 7, 3, '2022-07-21 16:32:03', '2022-07-21 09:32:03'),
(4, 8, 4, '2022-08-10 15:33:58', '2022-08-10 15:33:58');

-- --------------------------------------------------------

--
-- Table structure for table `cm_sys_logs`
--

CREATE TABLE `cm_sys_logs` (
  `id` int(11) NOT NULL,
  `user_id` bigint(20) NOT NULL DEFAULT 0,
  `action_taken` varchar(50) NOT NULL,
  `ts` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `cm_users`
--

CREATE TABLE `cm_users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `cm_users`
--

INSERT INTO `cm_users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(4, 'Faheem', 'faheem@yopmail.com', NULL, '$2y$10$X0s.Fc7jaLUdR19vsyX/8ec77Oj9e/bF4/iKx3/QWUlOSN2quFLpy', 'hE8X9m6Fav0d9TRHJK6RgVsskQLfZeqBSDeHDFWfhEV4riDRmzSBlRyrizat', '2022-07-15 21:23:32', '2022-08-03 09:16:49'),
(5, 'Faheem Mahar', 'faheem2@yopmail.com', NULL, '$2y$10$LEYskHvJMYRI9PlXzEGoae5rUAbaPadnvUDq/O/3Gk5KV1J1UfzuS', NULL, '2022-07-18 12:12:30', '2022-07-18 12:12:30'),
(6, 'Faheem Mahar', 'faheem23@yopmail.com', NULL, '$2y$10$03XEbRCuCbh5IP.fgrIEoOjCupxJDrvttHUyDw6LbfXj4dDD/SCZK', NULL, '2022-07-18 12:13:25', '2022-07-18 12:13:25'),
(7, 'Faheem Mahar', 'faheem237@yopmail.com', NULL, '$2y$10$8ASNY5AY40q/hKIIRpksGOTYmLtKfgxgKdG6vX0DfqnfqSYmTYyrK', NULL, '2022-07-18 12:18:07', '2022-07-18 12:18:07'),
(8, 'Faheem Mahar', 'faheem+1@yopmail.com', NULL, '$2y$10$NjH.q.TlH/d0m7UhrcAh.OY.0SzcAMqAV3Rf6f9o8MTxu6w/FhO4W', NULL, '2022-07-18 12:19:49', '2022-07-18 12:19:49'),
(9, 'Hassan Ali', 'hassanali@yopmail.com', NULL, '$2y$10$Sjc0UufH0vcc6oogrA3oJ.0QF/WUAfid3Eq3Xbx8TW9zqenhxm93i', NULL, '2022-07-18 13:30:10', '2022-07-18 13:30:10'),
(10, 'Faheem Mahar', 'hassanali+1@yopmail.com', NULL, '$2y$10$7cW/wDy2VI6VubFz35hVQuQvLoKcigpe5ElOn18Rs7OBXEh1bRq4u', NULL, '2022-07-18 16:28:48', '2022-07-18 16:28:48'),
(16, 'test', 'faheem2+1@yopmail.com', NULL, '$2y$10$u8nb3HV9oetVR9CH1ZjXaOkquXKB7uaNXt9ZrdxPfFn4FO/DYNQme', NULL, '2022-07-20 14:46:08', '2022-07-20 14:46:08'),
(17, 'test', 'faheem21@yopmail.com', NULL, '$2y$10$ipoJoM5lB0fTFH/Dw3i1nuiOLBWdRVIWL.9mhT5St4CAhgqvSloRC', 'BnpTS0B9TicU8MpqasuccCoN8tsyE8hZMZW22qhVkD4NoLcGbWurBAYSnTgp', '2022-07-20 14:46:43', '2022-08-01 07:57:00'),
(18, 'sub admin', 'faheemnice@yopmail.com', NULL, '$2y$10$n58Lnz8m7E6q6Wn2QQpBweGVwNEc2d0tdBxOD1t2sFDcqaW.wW3h6', 'B1OdRbjldBuFSCh2d0JGHrcuK8fs28bm2B5kpTELkTKez4i4MP8F0mwNFKWw', '2022-07-20 15:03:23', '2022-08-01 09:06:29'),
(19, 'Faheeem Mahar', 'friend@yopmail.com', NULL, '$2y$10$z6yixl1MrId1A4jEJX4tYe3YFlg1vpa9qUSZfQAlAubDGS9EAKQlu', NULL, '2022-07-20 16:24:41', '2022-07-20 16:24:41'),
(20, 'Faheeem Mahar', 'friend1@yopmail.com', NULL, '$2y$10$i1pA/0S3SlZsx.Xrkz4IlurNpxktdwq72T2qm/3Wy1l8cUxBuMO0q', NULL, '2022-07-20 16:26:34', '2022-07-20 16:26:34'),
(21, 'Faheem Mahar', 'friend2@yopmail.com', NULL, '$2y$10$qHGxAtmm7BdmQMxC2/UUjeG.eUgPfwTFjS1k/9KaySsQKf6HhHOAK', NULL, '2022-07-20 17:20:42', '2022-07-20 17:20:42'),
(22, 'test', 'faheem22@yopmail.com', NULL, '$2y$10$TGc1jAj6q4WpRgDAdvx31uHSJ8wgC8pJgYbFCOBekc197HFwkY1Ya', NULL, '2022-07-21 13:14:46', '2022-07-21 13:14:46'),
(23, 'test', 'faheem212@yopmail.com', NULL, '$2y$10$wWkmGaAc5kD7Eox6/8Ku1eGCPdXYG6X3HyBfdWuMdh0y70W307RIu', NULL, '2022-07-22 14:41:33', '2022-07-22 14:41:33'),
(24, 'Faheeem12 Mahar12', 'friend12@yopmail.com', NULL, '$2y$10$0AHp4CYwxEPvjQR1fKaGve/kUYQDE1/6f8IyEpcxJRTjQpCTmfK6O', '2dWeMIoIS3IQtdkKq4Rp2E6GHC5hlpuMzQUAQypXNTw7v4vH0WAj7XgGpL7f', '2022-07-22 17:40:11', '2022-08-11 03:25:38'),
(28, 'Faheem', 'contact@yopmail.com', NULL, '$2y$10$/XJoaYGcrtbRURSZB9jtSe/9c6xeW8UGq7x8VDQ6hZ3SnIp813qtC', NULL, '2022-08-10 11:03:06', '2022-08-10 11:03:06'),
(29, 'Noor', 'noor@yopmail.com', NULL, '$2y$10$OSQyT/32yI5Zaq3lYxcc.uqeHUh8PUrjV5la/W9QIoRuNWuMgt9cW', NULL, '2022-08-15 02:16:25', '2022-08-15 02:16:25'),
(30, 'Manzoor', 'manzoor@yopmail.com', NULL, '$2y$10$SU8vlPRGgmmwScIzBog.6O3KmWedOSFjk8vWm6g5hk01H79S8JgLW', NULL, '2022-08-24 08:04:28', '2022-08-24 09:16:57'),
(31, 'Atif G.Memon', 'agm@claimsmedinc.com', NULL, '$2y$10$7LIrJgc7LynYlWXBxs/Qyuiw56kMjXPH7YRwh2TC2ldbrSPUenHVu', NULL, '2022-09-01 09:45:56', '2022-09-01 09:45:56'),
(32, 'Asif Aziz', 'aam@claimsmedinc.com', NULL, '$2y$10$N8sAShLpYwcNa/66D6jWmuaq4jDzbUSa7ytJbXF0n8aiRt2C8DC1y', NULL, '2022-09-01 09:48:33', '2022-09-01 09:49:17'),
(33, 'Test User', 'test@yopmail.com', NULL, '$2y$10$O/9GxREAXrTQFNw/zdnRpOjjWzquYHGUANu/treGniNYWa4edGMsq', NULL, '2022-09-01 23:18:40', '2022-09-01 23:18:40'),
(34, 'Baber azam', 'baber@yopmail.com', NULL, '$2y$10$BtuoUQ4z7YqQMy40RMYUJ.odyphe3027ldl.KMqmCEVdRVSS2u676', 'iFYyKTIVOjdRFlCXga04bM8xMf8shABDkkZlmEgHmKLeklaJx5Ecar8TM8kC', '2022-09-01 23:36:10', '2022-09-07 02:10:21'),
(35, 'Muhammad rizwan', 'rizwan@yopmail.com', NULL, '$2y$10$GTWtBSCSesHE0PGkOM.rVuKBvcUy.zYAwsredxMQ1IW5AOJxsFkFu', NULL, '2022-09-01 23:37:42', '2022-09-01 23:37:42'),
(36, 'AB de Villiers', 'Villiers@yopmail.com', NULL, '$2y$10$ssiIsIkZM4z/tqRPJzD.y.Z5DHpCRfpD9j5ub.dajq76sK8I2fUoe', NULL, '2022-09-01 23:42:20', '2022-09-01 23:42:20'),
(37, 'joe root', 'joe@yopmail.com', NULL, '$2y$10$CxCnM1/6jIdXc1PRKp3rB.V/H56qZBpJV18aDdtZJKNsDusRRIOYK', 'rC7m6Is813FcGoRqc7oyNCD3cXzFzXQOqvRuw7FMmxwQ5n5KRyZ8LV4x2hqe', '2022-09-01 23:44:40', '2022-09-07 09:07:31'),
(38, 'Mooen Ali', 'Mooen@yopmail.com', NULL, '$2y$10$LOImmYsVoRLbIrofMA8TseoWp.hebigdr6vXIPshcqkhORR68YA5C', NULL, '2022-09-01 23:45:23', '2022-09-01 23:45:23'),
(39, 'Jack Leach', 'Jack@yopmail.com', NULL, '$2y$10$fjgOY.2R800aluTkQakXxu.Sprt3U3YcAlZGR9BKzOdG9erfNBSNm', NULL, '2022-09-01 23:46:29', '2022-09-01 23:46:29'),
(40, 'Haris Sohail', 'Sohail@yopmail.com', NULL, '$2y$10$9nRupyQLxlJcNWUD469cb.yoXn9AEpwCfLCDpAe9yY7bRReYhdnHW', 'i2AAipWUoesmmPvWQJEAnT7S9GSXZitPTuUzmYbIFgMNnm9pnSwWxf9rZSOO', '2022-09-01 23:48:53', '2022-09-07 09:20:49'),
(41, 'Asif Ali', 'Asif@yopmail.com', NULL, '$2y$10$h9SsS30QqDA2.iC9MoDeeuyMoSaMLWGlXvUq0xii2aiXgC7BxEyRy', NULL, '2022-09-01 23:50:12', '2022-09-01 23:50:12'),
(42, 'wahab raiz', 'wahab@yopmail.com', NULL, '$2y$10$CM09FcKpLM2/s.UszKXviOK2w4YzBYNbARHbAZjx0LG5wDcFO3w7e', NULL, '2022-09-01 23:50:55', '2022-09-01 23:50:55'),
(43, 'shoaib malik', 'shoaib@yopmail.com', NULL, '$2y$10$kBDks6Jutfo1RJUzeaa/Ku5y6sXH4IG1pgCgw1d1cVb5tvaBv0hkC', NULL, '2022-09-01 23:52:55', '2022-09-01 23:52:55'),
(44, 'ddsdsds', 'mytest@yopmail.com', NULL, '$2y$10$XuUQfyxRdSW31kI/Bf93QunCX2dPWrv1/pxgpYIk4bTNMoNSIi5FW', NULL, '2022-09-08 02:21:53', '2022-09-08 02:21:53'),
(45, 'Naeem', 'mrnaeem@yopmail.com', NULL, '$2y$10$ufgNn6AEnRBvlzZv1DSsEua/2YK/MmTf/zIALkAkzkYhOBrMmC/Cm', NULL, '2022-09-08 02:38:06', '2022-09-08 02:38:06'),
(46, 'Saeed', 'saeed@yopmail.com', NULL, '$2y$10$1Po3KQCVZdWn7dEWa0jPZeGQ8wHSFUd9AknITLQ1zNkfZ9LFt321u', NULL, '2022-09-08 04:01:09', '2022-09-08 04:01:09'),
(47, 'dssdsd', 'abc@yopmail.com', NULL, '$2y$10$4hDPoi1ZYiPG4OXy3yINr.nb1NO6ePaGV4Y4o.OJGlC8LBJGc6xYC', NULL, '2022-09-08 05:03:41', '2022-09-08 05:03:41'),
(48, 'fdfddfdf', 'abcd@yopmail.com', NULL, '$2y$10$KH4CqH5ZncInHQpnkvxRYu4WrEJZQNyUtBfPkWAQs9jGLC/X1E.ni', NULL, '2022-09-09 07:28:03', '2022-09-09 07:28:03'),
(49, 'Test', 'testdev@yopmail.com', NULL, '$2y$10$1ZiGQN8.W1mE6kyN9ooIBe95xrmk2gfrgYdB.JeFrQCQZnTgI/jfa', NULL, '2022-09-10 02:14:32', '2022-09-10 02:14:32'),
(50, 'bbbbb', 'bbb@yopmail.com', NULL, '$2y$10$8pbLSuZsu4YGHsgvJjmM5.qLSeZN017PQCrZhpMc1xYGlH7VErxd.', NULL, '2022-09-10 02:36:30', '2022-09-10 02:36:30'),
(51, 'Ubaid Rehman', 'notification@yopmail.com', NULL, '$2y$10$5aXUOvpz47/UUbcKOhlkPelZpbPfh8lwFw7TAbsNrfBUVwmazione', NULL, '2022-09-15 08:26:58', '2022-09-15 08:26:58'),
(52, 'c  cju86 c6grt4yj', 'gfuj87ju8@yopmail.com', NULL, '$2y$10$vFtt9TUnLfiSejFQ7l1NbuyOWQ93Dmf2DD.Nxunc6PV8R6dkEivLO', NULL, '2022-09-15 23:46:43', '2022-09-15 23:46:43'),
(53, 'c  cju86 c6grt4yj', 'gfuj87ju8dddd@yopmail.com', NULL, '$2y$10$oitRo51GN5M9CaAjXlMj2u.FMZ3LFJfcAUGdDRTyLc1T5B78eesku', NULL, '2022-09-15 23:48:22', '2022-09-15 23:48:22'),
(54, 'c  cju86 c6grt4yj', 'shaheen@yopmail.com', NULL, '$2y$10$tQWlio4iSZU4nJULFTJi1O9vpByMAqaaTHffsfnVWF6Aj2/PKaOdC', NULL, '2022-09-15 23:49:42', '2022-09-15 23:49:42'),
(55, 'c  cju86 c6grt4yj', 'naushad@yopmail.com', NULL, '$2y$10$mP1ThKALoz1Mhb7Mq2sucuFJFilDjd/P0Pvh8tTQV57j8o5BnTIwO', NULL, '2022-09-15 23:51:11', '2022-09-15 23:51:11'),
(56, 'c  cju86 c6grt4yj', 'naushad1@yopmail.com', NULL, '$2y$10$QikECFfM8uDYYeHETdCFD.SNG62chFaGBbESWaajXHNTMjsDxH/AO', NULL, '2022-09-15 23:52:30', '2022-09-15 23:52:30'),
(57, 'c  cju86 c6grt4yj', 'naushad2@yopmail.com', NULL, '$2y$10$ekWPdgI3NYZZJhc2/PnXZeOLhZLsIVbHg8fn5GXohQ13TzMNDKj6S', NULL, '2022-09-15 23:53:18', '2022-09-15 23:53:18'),
(58, 'c  cju86 c6grt4yj', 'naushad3@yopmail.com', NULL, '$2y$10$uZ3f1G5soATPhVyqU5yFf.6QgKNiHTR0/X0W3EzuqyeEMT8C9FIFC', NULL, '2022-09-15 23:53:54', '2022-09-15 23:53:54'),
(59, 'c  cju86 c6grt4yj', 'naushad4@yopmail.com', NULL, '$2y$10$a.ZPfkcGgc1cG7NBMmcOaOywSOMHpKoVHRbD37K.MEjMp3xQISNW2', NULL, '2022-09-15 23:59:08', '2022-09-15 23:59:08'),
(60, 'c  cju86 c6grt4yj', 'naushad5@yopmail.com', NULL, '$2y$10$/NW8OP2OeayKRRjhvkpjoO8BwvxAkLOkSVTrmniHH6Zpwb.NRNbr2', NULL, '2022-09-16 00:00:40', '2022-09-16 00:00:40'),
(61, 'c  cju86 c6grt4yj', 'naushad6@yopmail.com', NULL, '$2y$10$swjOuRCPjAz8t1VHT90Fo.GrjMGLpM6lzLgV0i/nS3OtZyGRmn3au', NULL, '2022-09-16 00:03:49', '2022-09-16 00:03:49'),
(62, 'c  cju86 c6grt4yj', 'naushad7@yopmail.com', NULL, '$2y$10$sXKJlxe3YkgZQDmX8ocnuentklODA8772tN/7ExDLtAimUv/D2uWa', NULL, '2022-09-16 00:16:33', '2022-09-16 00:16:33');

-- --------------------------------------------------------

--
-- Table structure for table `cm_users_profile`
--

CREATE TABLE `cm_users_profile` (
  `id` bigint(20) NOT NULL,
  `admin_id` bigint(20) NOT NULL DEFAULT 0,
  `user_id` bigint(20) NOT NULL DEFAULT 0,
  `company_id` bigint(20) NOT NULL DEFAULT 0,
  `role_id` bigint(20) NOT NULL DEFAULT 0,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `gender` varchar(20) NOT NULL,
  `contact_number` varchar(100) NOT NULL,
  `employee_number` varchar(100) NOT NULL,
  `cnic` varchar(20) NOT NULL,
  `picture` varchar(200) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `cm_users_profile`
--

INSERT INTO `cm_users_profile` (`id`, `admin_id`, `user_id`, `company_id`, `role_id`, `first_name`, `last_name`, `gender`, `contact_number`, `employee_number`, `cnic`, `picture`, `created_at`, `updated_at`) VALUES
(6, 1, 20, 434, 8, 'Faheeem', 'Mahar', 'Male', '0345365898', '02202121', '45232542212', '', '2022-09-02 10:01:51', '2022-07-20 16:26:34'),
(7, 1, 21, 2, 8, 'Faheem', 'Mahar', 'Female', '0345365898', '02202121', '45232542212', '62d7d6fa387a6_dummy_user_img.jfif', '2022-09-02 10:01:46', '2022-07-20 17:20:42'),
(8, 1, 24, 2, 8, 'Faheeem12', 'Mahar12', 'Male', '55224225212', '02202121', '45232542212', '', '2022-09-02 10:01:41', '2022-07-22 17:40:11'),
(9, 0, 28, 1, 8, 'Faheem', '', '', '+923452552694', '', '', '', '2022-08-10 11:03:06', '2022-08-10 11:03:06'),
(10, 0, 29, 1, 8, 'Noor', '', '', '+922135353524', '', '', '', '2022-08-15 02:16:25', '2022-08-15 02:16:25'),
(11, 0, 30, 1, 8, 'Manzoor Ali', 'Mahar', 'Male', '+923453658887', '', '', '', '2022-08-24 14:16:57', '2022-08-24 09:16:57'),
(12, 0, 32, 1, 8, 'Asif Aziz', '', '', '+1864894156156', '', '', '', '2022-09-01 14:49:17', '2022-09-01 09:49:16'),
(13, 1, 34, 1, 9, 'Baber', 'azam', 'male', '036398458485', '90001', '1425479632145', '', '2022-09-01 23:36:10', '2022-09-01 23:36:10'),
(14, 1, 35, 1, 9, 'Muhammad', 'rizwan', 'male', '0363984611814', '90002', '1425479636125', '', '2022-09-01 23:37:42', '2022-09-01 23:37:42'),
(15, 1, 36, 1, 9, 'AB de', 'Villiers', 'male', '03639846485', '90003', '142547963884', '', '2022-09-01 23:42:20', '2022-09-01 23:42:20'),
(16, 1, 37, 1, 10, 'joe', 'root', 'male', '03639646485', '90004', '142548143884', '', '2022-09-01 23:44:40', '2022-09-01 23:44:40'),
(17, 1, 38, 1, 10, 'Mooen', 'Ali', 'male', '03699646485', '90005', '142148143884', '', '2022-09-01 23:45:23', '2022-09-01 23:45:23'),
(18, 1, 39, 1, 10, 'Jack', 'Leach', 'male', '03699678485', '90006', '142196143884', '', '2022-09-01 23:46:29', '2022-09-01 23:46:29'),
(19, 1, 40, 1, 11, 'Haris', 'Sohail', 'male', '03699698485', '90007', '142194143884', '', '2022-09-01 23:48:53', '2022-09-01 23:48:53'),
(20, 1, 41, 1, 11, 'Asif', 'Ali', 'male', '03614698485', '90008', '142196143832', '', '2022-09-01 23:50:12', '2022-09-01 23:50:12'),
(21, 1, 42, 1, 11, 'wahab', 'raiz', 'male', '03611598485', '90009', '142165143832', '', '2022-09-01 23:50:55', '2022-09-01 23:50:55'),
(22, 1, 43, 1, 12, 'shoaib', 'malik', 'male', '03611628485', '900011', '14277843832', '', '2022-09-01 23:52:55', '2022-09-01 23:52:55'),
(23, 0, 44, 1, 8, 'ddsdsds', '', '', '+134535253525', '', '', '', '2022-09-08 02:21:53', '2022-09-08 02:21:53'),
(24, 0, 45, 1, 8, 'Naeem', '', '', '+15585452254', '', '', '', '2022-09-08 02:38:06', '2022-09-08 02:38:06'),
(25, 0, 46, 1, 8, 'Saeed', '', '', '+12553554245', '', '', '', '2022-09-08 04:01:09', '2022-09-08 04:01:09'),
(26, 0, 47, 1, 8, 'dssdsd', '', '', '+15424242122', '', '', '', '2022-09-08 05:03:41', '2022-09-08 05:03:41'),
(27, 0, 48, 1, 8, 'fdfddfdf', '', '', '+15255245252', '', '', '', '2022-09-09 07:28:03', '2022-09-09 07:28:03'),
(28, 0, 49, 1, 8, 'Test', '', '', '+13456255525', '', '', '', '2022-09-10 02:14:32', '2022-09-10 02:14:32'),
(29, 0, 50, 1, 8, 'bbbbb', '', '', '+1525558555', '', '', '', '2022-09-10 02:36:30', '2022-09-10 02:36:30'),
(30, 0, 51, 1, 13, 'Ubaid', 'Rehman', '', '', '', '', '', '2022-09-15 08:26:58', '2022-09-15 08:26:58'),
(31, 0, 52, 1, 13, 'c  cju86', 'c6grt4yj', '', '', '', '', '', '2022-09-15 23:46:43', '2022-09-15 23:46:43'),
(32, 0, 53, 1, 13, 'c  cju86', 'c6grt4yj', '', '', '', '', '', '2022-09-15 23:48:22', '2022-09-15 23:48:22'),
(33, 0, 54, 1, 13, 'c  cju86', 'c6grt4yj', '', '', '', '', '', '2022-09-15 23:49:42', '2022-09-15 23:49:42'),
(34, 0, 55, 1, 13, 'c  cju86', 'c6grt4yj', '', '', '', '', '', '2022-09-15 23:51:11', '2022-09-15 23:51:11'),
(35, 0, 56, 1, 13, 'c  cju86', 'c6grt4yj', '', '', '', '', '', '2022-09-15 23:52:30', '2022-09-15 23:52:30'),
(36, 0, 57, 1, 13, 'c  cju86', 'c6grt4yj', '', '', '', '', '', '2022-09-15 23:53:18', '2022-09-15 23:53:18'),
(37, 0, 58, 1, 13, 'c  cju86', 'c6grt4yj', '', '', '', '', '', '2022-09-15 23:53:54', '2022-09-15 23:53:54'),
(38, 0, 59, 1, 13, 'c  cju86', 'c6grt4yj', '', '', '', '', '', '2022-09-15 23:59:08', '2022-09-15 23:59:08'),
(39, 0, 60, 1, 13, 'c  cju86', 'c6grt4yj', '', '', '', '', '', '2022-09-16 00:00:40', '2022-09-16 00:00:40'),
(40, 0, 61, 1, 13, 'c  cju86', 'c6grt4yj', '', '', '', '', '', '2022-09-16 00:03:49', '2022-09-16 00:03:49'),
(41, 0, 62, 1, 13, 'c  cju86', 'c6grt4yj', '', '', '', '', '', '2022-09-16 00:16:33', '2022-09-16 00:16:33');

-- --------------------------------------------------------

--
-- Table structure for table `cm_w9form`
--

CREATE TABLE `cm_w9form` (
  `id` bigint(20) NOT NULL,
  `provider_id` bigint(20) NOT NULL DEFAULT 0,
  `form_data` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `cm_w9form`
--

INSERT INTO `cm_w9form` (`id`, `provider_id`, `form_data`, `created_at`, `updated_at`) VALUES
(1, 33, '{\"name\":\"Manzoor Ali\",\"buisness\":\"Sr Developer\",\"federal_tax_classification\":\"c-corporation\",\"limited_liability_company\":null,\"other\":\"1\",\"liability_txt\":\"testing the text\",\"exempt_payee\":\"123123\",\"fatca_code\":\"422545\",\"address\":\"hyd\",\"city_state_zip\":\"qabad,sindh,12024\",\"requester_name\":\"a\",\"requester_address\":\"b\",\"social_security_number\":\"123123123\",\"emp_identification_number\":\"123456789\"}', '2022-08-26 13:10:06', '2022-08-26 08:10:06'),
(2, 48, '{\"name\":\"fdfddfdf\",\"buisness\":\"\",\"federal_tax_classification\":\"Individual\\/sole proprietor or single-member LLC\",\"limited_liability_company\":null,\"other\":\"\",\"liability_txt\":\"\",\"exempt_payee\":\"45224552\",\"fatca_code\":\"45224522\",\"address\":\"dfdffd dffd\",\"city_state_zip\":\"lahore,punjab,22222222\",\"requester_name\":\"dsd\",\"requester_address\":\"dsds\",\"social_security_number\":\"123456789\",\"emp_identification_number\":\"\"}', '2022-09-09 07:00:53', '2022-09-09 02:00:53'),
(3, 50, '{\"name\":\"bbbbb\",\"buisness\":\"test\",\"federal_tax_classification\":\"Limited Liability Company\",\"limited_liability_company\":null,\"other\":\"ddsdsd\",\"liability_txt\":\"\",\"exempt_payee\":\"fddffdfd\",\"fatca_code\":\"dssdsd\",\"address\":\"address address 1\",\"city_state_zip\":\"lahore,punjab,12345\",\"requester_name\":\"dsdsds\",\"requester_address\":\"dsdds\",\"social_security_number\":\"undefined\",\"emp_identification_number\":\"\"}', '2022-09-10 10:38:39', '2022-09-10 05:38:39'),
(4, 49, '{\"name\":\"Test\",\"buisness\":\"\",\"federal_tax_classification\":\"Individual\\/sole proprietor or single-member LLC\",\"limited_liability_company\":null,\"other\":\"undefined\",\"liability_txt\":\"\",\"exempt_payee\":\"4443\",\"fatca_code\":\"444\",\"address\":\"fdsffddf dfssdssaa\",\"city_state_zip\":\"karachi,punjab,5242523\",\"requester_name\":\"fdfd\",\"requester_address\":\"dfdfd\",\"social_security_number\":\"\",\"emp_identification_number\":\"123456789\"}', '2022-09-10 10:34:11', '2022-09-10 05:34:11');

-- --------------------------------------------------------

--
-- Table structure for table `cm_wishlists`
--

CREATE TABLE `cm_wishlists` (
  `id` bigint(20) NOT NULL,
  `provider_id` bigint(20) NOT NULL DEFAULT 0,
  `dd_id` bigint(20) NOT NULL DEFAULT 0,
  `wishlist_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `update_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cm_admins`
--
ALTER TABLE `cm_admins`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cm_assign_credentialingtask`
--
ALTER TABLE `cm_assign_credentialingtask`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `provider_id` (`provider_id`);

--
-- Indexes for table `cm_assign_providers`
--
ALTER TABLE `cm_assign_providers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `provider_id` (`provider_id`),
  ADD KEY `operational_m_id` (`operational_m_id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `cm_attachments`
--
ALTER TABLE `cm_attachments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cm_banking_info`
--
ALTER TABLE `cm_banking_info`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cm_buisnessinfo`
--
ALTER TABLE `cm_buisnessinfo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `dd_id` (`dd_id`),
  ADD KEY `provider_id_2` (`provider_id`),
  ADD KEY `provider_id_3` (`provider_id`),
  ADD KEY `provider_id` (`provider_id`) USING BTREE,
  ADD KEY `dd_id_2` (`dd_id`) USING BTREE;

--
-- Indexes for table `cm_capabilities`
--
ALTER TABLE `cm_capabilities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `name` (`title`);

--
-- Indexes for table `cm_capabilities_capabilityactions_map`
--
ALTER TABLE `cm_capabilities_capabilityactions_map`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cm_capability_actions`
--
ALTER TABLE `cm_capability_actions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cm_companies`
--
ALTER TABLE `cm_companies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `business_owner` (`owner_first_name`),
  ADD KEY `business_ last_name` (`owner_last_name`),
  ADD KEY `business_name` (`company_name`);

--
-- Indexes for table `cm_company_custom_fields`
--
ALTER TABLE `cm_company_custom_fields`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cm_contracts`
--
ALTER TABLE `cm_contracts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `provider_id` (`provider_id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `cm_credentialing_tasks`
--
ALTER TABLE `cm_credentialing_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `provider_id` (`provider_id`),
  ADD KEY `insurance_id` (`insurance_id`);

--
-- Indexes for table `cm_credentialing_task_logs`
--
ALTER TABLE `cm_credentialing_task_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `credentialing_task_id` (`credentialing_task_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `cm_discoverydocuments`
--
ALTER TABLE `cm_discoverydocuments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `provider_id` (`provider_id`);

--
-- Indexes for table `cm_failed_jobs`
--
ALTER TABLE `cm_failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `cm_group_provider_info`
--
ALTER TABLE `cm_group_provider_info`
  ADD PRIMARY KEY (`id`),
  ADD KEY `provider_id` (`provider_id`),
  ADD KEY `dd_id` (`dd_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `cm_insurances`
--
ALTER TABLE `cm_insurances`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cm_invoices`
--
ALTER TABLE `cm_invoices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `provider_id` (`provider_id`);

--
-- Indexes for table `cm_migrations`
--
ALTER TABLE `cm_migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cm_notifications`
--
ALTER TABLE `cm_notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cm_ownership_info`
--
ALTER TABLE `cm_ownership_info`
  ADD PRIMARY KEY (`id`),
  ADD KEY `provider_id` (`provider_id`),
  ADD KEY `dd_id` (`dd_id`);

--
-- Indexes for table `cm_password_resets`
--
ALTER TABLE `cm_password_resets`
  ADD KEY `password_resets_email_index` (`email`);

--
-- Indexes for table `cm_payers`
--
ALTER TABLE `cm_payers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cm_payer_info`
--
ALTER TABLE `cm_payer_info`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cm_payment_logs`
--
ALTER TABLE `cm_payment_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cm_personal_access_tokens`
--
ALTER TABLE `cm_personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `cm_practice_location_info`
--
ALTER TABLE `cm_practice_location_info`
  ADD PRIMARY KEY (`id`),
  ADD KEY `provider_id` (`provider_id`),
  ADD KEY `dd_id` (`dd_id`);

--
-- Indexes for table `cm_providers`
--
ALTER TABLE `cm_providers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cm_providers_companies_map`
--
ALTER TABLE `cm_providers_companies_map`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cm_provider_members`
--
ALTER TABLE `cm_provider_members`
  ADD PRIMARY KEY (`id`),
  ADD KEY `provider_id` (`provider_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `cm_roles`
--
ALTER TABLE `cm_roles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `role_name` (`role_name`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `cm_roles_capabilities_map`
--
ALTER TABLE `cm_roles_capabilities_map`
  ADD PRIMARY KEY (`id`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `capability_id` (`capability_id`);

--
-- Indexes for table `cm_sys_logs`
--
ALTER TABLE `cm_sys_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cm_users`
--
ALTER TABLE `cm_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- Indexes for table `cm_users_profile`
--
ALTER TABLE `cm_users_profile`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `first_name` (`first_name`),
  ADD KEY `last_name` (`last_name`),
  ADD KEY `employee_number` (`employee_number`);

--
-- Indexes for table `cm_w9form`
--
ALTER TABLE `cm_w9form`
  ADD PRIMARY KEY (`id`),
  ADD KEY `provider_id` (`provider_id`);

--
-- Indexes for table `cm_wishlists`
--
ALTER TABLE `cm_wishlists`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cm_admins`
--
ALTER TABLE `cm_admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `cm_assign_credentialingtask`
--
ALTER TABLE `cm_assign_credentialingtask`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `cm_assign_providers`
--
ALTER TABLE `cm_assign_providers`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `cm_attachments`
--
ALTER TABLE `cm_attachments`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cm_banking_info`
--
ALTER TABLE `cm_banking_info`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cm_buisnessinfo`
--
ALTER TABLE `cm_buisnessinfo`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `cm_capabilities`
--
ALTER TABLE `cm_capabilities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `cm_capabilities_capabilityactions_map`
--
ALTER TABLE `cm_capabilities_capabilityactions_map`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `cm_capability_actions`
--
ALTER TABLE `cm_capability_actions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `cm_companies`
--
ALTER TABLE `cm_companies`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `cm_company_custom_fields`
--
ALTER TABLE `cm_company_custom_fields`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cm_contracts`
--
ALTER TABLE `cm_contracts`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `cm_credentialing_tasks`
--
ALTER TABLE `cm_credentialing_tasks`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `cm_credentialing_task_logs`
--
ALTER TABLE `cm_credentialing_task_logs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `cm_discoverydocuments`
--
ALTER TABLE `cm_discoverydocuments`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `cm_failed_jobs`
--
ALTER TABLE `cm_failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cm_group_provider_info`
--
ALTER TABLE `cm_group_provider_info`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `cm_insurances`
--
ALTER TABLE `cm_insurances`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `cm_invoices`
--
ALTER TABLE `cm_invoices`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `cm_migrations`
--
ALTER TABLE `cm_migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `cm_notifications`
--
ALTER TABLE `cm_notifications`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `cm_ownership_info`
--
ALTER TABLE `cm_ownership_info`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cm_payers`
--
ALTER TABLE `cm_payers`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT for table `cm_payer_info`
--
ALTER TABLE `cm_payer_info`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cm_payment_logs`
--
ALTER TABLE `cm_payment_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `cm_personal_access_tokens`
--
ALTER TABLE `cm_personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT for table `cm_practice_location_info`
--
ALTER TABLE `cm_practice_location_info`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cm_providers`
--
ALTER TABLE `cm_providers`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `cm_providers_companies_map`
--
ALTER TABLE `cm_providers_companies_map`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `cm_provider_members`
--
ALTER TABLE `cm_provider_members`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `cm_roles`
--
ALTER TABLE `cm_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `cm_roles_capabilities_map`
--
ALTER TABLE `cm_roles_capabilities_map`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `cm_sys_logs`
--
ALTER TABLE `cm_sys_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cm_users`
--
ALTER TABLE `cm_users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `cm_users_profile`
--
ALTER TABLE `cm_users_profile`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `cm_w9form`
--
ALTER TABLE `cm_w9form`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `cm_wishlists`
--
ALTER TABLE `cm_wishlists`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
