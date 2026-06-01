-- Migration: base MySQL et nom d'application « wifizones »
-- Exécuter avec un client MySQL (phpMyAdmin, mysql CLI).

-- 1) Créer la nouvelle base
CREATE DATABASE IF NOT EXISTS `wifizones`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

-- 2) Copier depuis l'ancienne base (ex. phpnuxbill)
-- mysqldump -u root -p --set-gtid-purged=OFF phpnuxbill | mysql -u root -p wifizones

-- 3) Nom affiché dans l'interface
USE `wifizones`;
UPDATE `tbl_appconfig`
SET `value` = 'wifizones'
WHERE `setting` = 'CompanyName';

-- 4) Vérifier config.php : $db_name = 'wifizones';
