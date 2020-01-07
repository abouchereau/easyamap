ALTER TABLE `user` ADD COLUMN `created_at` DATE DEFAULT NULL AFTER `is_active`;
ALTER TABLE `setting` ADD COLUMN `use_report` tinyint(1) NOT NULL DEFAULT 0 AFTER register_distribution;
ALTER TABLE `distribution` ADD COLUMN `info_livraison` TEXT NULL DEFAULT NULL AFTER date;
ALTER TABLE `distribution` ADD COLUMN `info_distribution` TEXT NULL DEFAULT NULL AFTER info_livraison;
ALTER TABLE `distribution` ADD COLUMN `info_divers` TEXT NULL DEFAULT NULL AFTER info_distribution;