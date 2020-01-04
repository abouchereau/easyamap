ALTER TABLE `user` ADD COLUMN `created_at` DATE DEFAULT NULL AFTER `is_active`;
ALTER TABLE `setting` ADD COLUMN `use_report` tinyint(1) NOT NULL DEFAULT 0 AFTER register_distribution;