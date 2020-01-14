ALTER TABLE `user` ADD COLUMN `created_at` DATE DEFAULT NULL AFTER `is_active`;
ALTER TABLE `setting` ADD COLUMN `use_report` tinyint(1) NOT NULL DEFAULT 0 AFTER register_distribution;
ALTER TABLE `distribution` ADD COLUMN `info_livraison` TEXT NULL DEFAULT NULL AFTER date;
ALTER TABLE `distribution` ADD COLUMN `info_distribution` TEXT NULL DEFAULT NULL AFTER info_livraison;
ALTER TABLE `distribution` ADD COLUMN `info_divers` TEXT NULL DEFAULT NULL AFTER info_distribution;
ALTER TABLE `product_distribution` ADD COLUMN `fk_distribution_shift` INT(11) NULL DEFAULT NULL AFTER `max_per_user`;
ALTER TABLE `product_distribution` ADD INDEX `fk_distribution_shift` (`fk_distribution_shift`), ADD CONSTRAINT `product_distribution_ibfk_3` FOREIGN KEY (`fk_distribution_shift`) REFERENCES `distribution` (`id_distribution`) ON UPDATE CASCADE ON DELETE CASCADE;

DROP VIEW IF EXISTS view_distribution_user_product;
DROP VIEW IF EXISTS view_distribution_farm_product;