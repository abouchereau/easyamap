-- Adminer 4.7.5 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `booth`;
CREATE TABLE `booth` (
  `id_booth` int(11) NOT NULL AUTO_INCREMENT,
  `route` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `params` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `fk_user` int(11) NOT NULL,
  `started_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id_booth`),
  KEY `booth_ibfk_1` (`fk_user`),
  CONSTRAINT `booth_ibfk_1` FOREIGN KEY (`fk_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `contract`;
CREATE TABLE `contract` (
  `id_contract` int(11) NOT NULL AUTO_INCREMENT,
  `fk_user` int(11) NOT NULL,
  `label` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 0,
  `is_visible` tinyint(1) NOT NULL DEFAULT 1,
  `period_start` date DEFAULT NULL,
  `period_end` date DEFAULT NULL,
  `fill_date_end` date DEFAULT NULL,
  `description` text COLLATE utf8_bin DEFAULT NULL,
  `count_purchase_since` date DEFAULT NULL,
  PRIMARY KEY (`id_contract`),
  KEY `fk_user` (`fk_user`),
  CONSTRAINT `contract_ibfk_1` FOREIGN KEY (`fk_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `contract_product`;
CREATE TABLE `contract_product` (
  `id_contract_product` int(11) NOT NULL AUTO_INCREMENT,
  `fk_contract` int(11) NOT NULL,
  `fk_product` int(11) NOT NULL,
  PRIMARY KEY (`id_contract_product`),
  KEY `fk_contract` (`fk_contract`),
  KEY `fk_product` (`fk_product`),
  CONSTRAINT `contract_product_ibfk_1` FOREIGN KEY (`fk_contract`) REFERENCES `contract` (`id_contract`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `contract_product_ibfk_2` FOREIGN KEY (`fk_product`) REFERENCES `product` (`id_product`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `distribution`;
CREATE TABLE `distribution` (
  `id_distribution` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `info_livraison` text COLLATE utf8_bin DEFAULT NULL,
  `info_distribution` text COLLATE utf8_bin DEFAULT NULL,
  `info_divers` text COLLATE utf8_bin DEFAULT NULL,
  PRIMARY KEY (`id_distribution`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `farm`;
CREATE TABLE `farm` (
  `id_farm` int(11) NOT NULL AUTO_INCREMENT,
  `sequence` int(11) NOT NULL DEFAULT 0,
  `label` varchar(255) COLLATE utf8_bin NOT NULL,
  `product_type` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `check_payable_to` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `description` text COLLATE utf8_bin DEFAULT NULL,
  `link` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `equitable` tinyint(1) NOT NULL DEFAULT 0,
  `fk_user` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_farm`),
  UNIQUE KEY `label` (`label`),
  KEY `fk_user` (`fk_user`),
  CONSTRAINT `farm_ibfk_1` FOREIGN KEY (`fk_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `farm_payment_freq`;
CREATE TABLE `farm_payment_freq` (
  `id_farm_payment_freq` int(11) NOT NULL AUTO_INCREMENT,
  `fk_farm` int(11) NOT NULL,
  `fk_payment_freq` int(11) NOT NULL,
  PRIMARY KEY (`id_farm_payment_freq`),
  KEY `fk_farm` (`fk_farm`),
  KEY `fk_payment_freq` (`fk_payment_freq`),
  CONSTRAINT `farm_payment_freq_ibfk_1` FOREIGN KEY (`fk_farm`) REFERENCES `farm` (`id_farm`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `farm_payment_freq_ibfk_2` FOREIGN KEY (`fk_payment_freq`) REFERENCES `payment_freq` (`id_payment_freq`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `farm_payment_type`;
CREATE TABLE `farm_payment_type` (
  `id_farm_payment_type` int(11) NOT NULL AUTO_INCREMENT,
  `fk_farm` int(11) NOT NULL,
  `fk_payment_type` int(11) NOT NULL,
  PRIMARY KEY (`id_farm_payment_type`),
  KEY `fk_farm` (`fk_farm`),
  KEY `fk_payment_type` (`fk_payment_type`),
  CONSTRAINT `farm_payment_type_ibfk_1` FOREIGN KEY (`fk_farm`) REFERENCES `farm` (`id_farm`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `farm_payment_type_ibfk_2` FOREIGN KEY (`fk_payment_type`) REFERENCES `payment_type` (`id_payment_type`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `participation`;
CREATE TABLE `participation` (
  `id_participation` int(11) NOT NULL AUTO_INCREMENT,
  `fk_task` int(11) NOT NULL,
  `fk_user` int(11) NOT NULL,
  `fk_distribution` int(11) NOT NULL,
  PRIMARY KEY (`id_participation`),
  KEY `fk_task` (`fk_task`),
  KEY `fk_user` (`fk_user`),
  KEY `fk_distribution` (`fk_distribution`),
  CONSTRAINT `participation_ibfk_1` FOREIGN KEY (`fk_task`) REFERENCES `task` (`id_task`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `participation_ibfk_2` FOREIGN KEY (`fk_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `participation_ibfk_3` FOREIGN KEY (`fk_distribution`) REFERENCES `distribution` (`id_distribution`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `payment`;
CREATE TABLE `payment` (
  `id_payment` int(11) NOT NULL AUTO_INCREMENT,
  `fk_user` int(11) NOT NULL,
  `fk_farm` int(11) NOT NULL,
  `fk_contract` int(11) DEFAULT NULL,
  `amount` float DEFAULT NULL,
  `received` float NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `description_old` text DEFAULT NULL,
  `received_at` date DEFAULT NULL,
  PRIMARY KEY (`id_payment`),
  KEY `fk_user` (`fk_user`),
  KEY `fk_contract` (`fk_contract`),
  KEY `fk_farm` (`fk_farm`),
  CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`fk_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `payment_ibfk_2` FOREIGN KEY (`fk_farm`) REFERENCES `farm` (`id_farm`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `payment_ibfk_3` FOREIGN KEY (`fk_contract`) REFERENCES `contract` (`id_contract`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `payment_freq`;
CREATE TABLE `payment_freq` (
  `id_payment_freq` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(255) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id_payment_freq`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

INSERT INTO `payment_freq` (`id_payment_freq`, `label`) VALUES
(1,	'Toutes les distributions'),
(2,	'Tous les mois'),
(3,	'Tous les 2 mois'),
(4,	'Tous les 3 mois'),
(5,	'Tous les 4 mois'),
(6,	'Tous les 6 mois'),
(7,	'Tous les ans');

DROP TABLE IF EXISTS `payment_save`;
CREATE TABLE `payment_save` (
  `id_payment` int(11) NOT NULL AUTO_INCREMENT,
  `fk_user` int(11) NOT NULL,
  `fk_farm` int(11) NOT NULL,
  `fk_contract` int(11) DEFAULT NULL,
  `amount` float DEFAULT NULL,
  `received` float NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `received_at` date DEFAULT NULL,
  PRIMARY KEY (`id_payment`),
  KEY `fk_user` (`fk_user`),
  KEY `fk_contract` (`fk_contract`),
  KEY `fk_farm` (`fk_farm`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `payment_split`;
CREATE TABLE `payment_split` (
  `id_payment_split` int(11) NOT NULL AUTO_INCREMENT,
  `fk_payment` int(11) NOT NULL,
  `amount` float NOT NULL,
  `date` date NOT NULL,
  PRIMARY KEY (`id_payment_split`),
  KEY `id_payment_split` (`id_payment_split`),
  KEY `payment_split_ibfk_1` (`fk_payment`),
  CONSTRAINT `payment_split_ibfk_1` FOREIGN KEY (`fk_payment`) REFERENCES `payment` (`id_payment`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `payment_type`;
CREATE TABLE `payment_type` (
  `id_payment_type` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(255) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id_payment_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

INSERT INTO `payment_type` (`id_payment_type`, `label`) VALUES
(1,	'Chèque'),
(2,	'Espèces');

DROP TABLE IF EXISTS `product`;
CREATE TABLE `product` (
  `id_product` int(11) NOT NULL AUTO_INCREMENT,
  `fk_farm` int(11) NOT NULL,
  `sequence` int(11) NOT NULL DEFAULT 0,
  `label` varchar(255) COLLATE utf8_bin NOT NULL,
  `unit` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `description` text COLLATE utf8_bin DEFAULT NULL,
  `base_price` float DEFAULT NULL,
  `ratio` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `is_subscription` int(1) NOT NULL DEFAULT 0,
  `is_certified` int(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id_product`),
  UNIQUE KEY `label` (`label`,`unit`,`fk_farm`),
  KEY `fk_farm` (`fk_farm`),
  CONSTRAINT `product_ibfk_1` FOREIGN KEY (`fk_farm`) REFERENCES `farm` (`id_farm`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `product_distribution`;
CREATE TABLE `product_distribution` (
  `id_product_distribution` bigint(20) NOT NULL AUTO_INCREMENT,
  `fk_product` int(11) NOT NULL,
  `fk_distribution` int(11) NOT NULL,
  `price` float NOT NULL,
  `max_quantity` int(11) DEFAULT NULL,
  `max_per_user` int(11) DEFAULT NULL,
  `fk_distribution_shift` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_product_distribution`),
  KEY `fk_product` (`fk_product`),
  KEY `fk_distribution` (`fk_distribution`),
  KEY `fk_distribution_shift` (`fk_distribution_shift`),
  CONSTRAINT `product_distribution_ibfk_1` FOREIGN KEY (`fk_product`) REFERENCES `product` (`id_product`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `product_distribution_ibfk_2` FOREIGN KEY (`fk_distribution`) REFERENCES `distribution` (`id_distribution`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `product_distribution_ibfk_3` FOREIGN KEY (`fk_distribution_shift`) REFERENCES `distribution` (`id_distribution`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `purchase`;
CREATE TABLE `purchase` (
  `id_purchase` int(11) NOT NULL AUTO_INCREMENT,
  `fk_user` int(11) NOT NULL,
  `fk_product_distribution` bigint(20) NOT NULL,
  `fk_contract` int(11) DEFAULT NULL,
  `fk_payment` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_purchase`),
  KEY `fk_user` (`fk_user`),
  KEY `fk_availability` (`fk_product_distribution`),
  KEY `fk_contract` (`fk_contract`),
  KEY `fk_payment` (`fk_payment`),
  CONSTRAINT `fk_contract` FOREIGN KEY (`fk_contract`) REFERENCES `contract` (`id_contract`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_payment` FOREIGN KEY (`fk_payment`) REFERENCES `payment` (`id_payment`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `purchase_ibfk_2` FOREIGN KEY (`fk_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `purchase_ibfk_3` FOREIGN KEY (`fk_product_distribution`) REFERENCES `product_distribution` (`id_product_distribution`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `referent`;
CREATE TABLE `referent` (
  `id_referent` int(11) NOT NULL AUTO_INCREMENT,
  `fk_user` int(11) NOT NULL,
  `fk_farm` int(11) NOT NULL,
  PRIMARY KEY (`id_referent`),
  KEY `fk_user` (`fk_user`),
  KEY `fk_farm` (`fk_farm`),
  CONSTRAINT `referent_ibfk_3` FOREIGN KEY (`fk_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `referent_ibfk_4` FOREIGN KEY (`fk_farm`) REFERENCES `farm` (`id_farm`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `setting`;
CREATE TABLE `setting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `use_address` tinyint(1) NOT NULL DEFAULT 1,
  `register_distribution` tinyint(1) NOT NULL DEFAULT 1,
  `use_report` tinyint(1) NOT NULL DEFAULT 0,
  `name` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `link` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `logo_large_url` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `logo_small_url` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `logo_secondary` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `text_register_distribution` text COLLATE utf8_bin DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

INSERT INTO `setting` (`id`, `use_address`, `register_distribution`, `use_report`, `name`, `link`, `logo_large_url`, `logo_small_url`, `logo_secondary`, `text_register_distribution`) VALUES
(1,	1,	1,	0,	'Nom de l''AMAP',	NULL,	'https://www.easyamap.fr/bundles/amapportal/images/logo-easy-amap-256.png',	'https://www.easyamap.fr/bundles/amapportal/images/logo-easy-amap-90.png',	'https://www.easyamap.fr/bundles/amaporder/images/AB_90px.png',	'A chaque distribution il faut au moins :\r\n- à définir\r\n');

DROP TABLE IF EXISTS `task`;
CREATE TABLE `task` (
  `id_task` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `min` int(11) DEFAULT NULL,
  `max` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_task`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

INSERT INTO `task` (`id_task`, `label`, `is_active`, `min`, `max`) VALUES
(1,	'Accueil',	1,	1,	1),
(2,	'Préparation',	1,	2,	2),
(3,	'Distribution',	1,	2,	2),
(4,	'Ménage',	1,	1,	2);

DROP TABLE IF EXISTS `temp_farm_order`;
CREATE TABLE `temp_farm_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_farm` int(11) NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `temp_product_order`;
CREATE TABLE `temp_product_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_product` int(11) NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id_user` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `username` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `firstname` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `lastname` varchar(255) COLLATE utf8_bin NOT NULL,
  `password` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `roles` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `is_adherent` int(1) NOT NULL DEFAULT 1,
  `is_admin` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` date DEFAULT NULL,
  `last_connection` date DEFAULT NULL,
  `tel1` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `tel2` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `address` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `zipcode` varchar(5) COLLATE utf8_bin DEFAULT NULL,
  `town` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `lastname` (`lastname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

INSERT INTO `user` (`id_user`, `email`, `username`, `firstname`, `lastname`, `password`, `roles`, `is_adherent`, `is_admin`, `is_active`, `created_at`, `last_connection`, `tel1`, `tel2`, `address`, `zipcode`, `town`) VALUES
(1,	NULL,	'admin',	'admin',	'admin',	'admin',	NULL,	1,	1,	1,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL);





CREATE OR REPLACE VIEW view_contract_purchaser AS
SELECT c.id_contract, pu.fk_user
FROM contract c
LEFT JOIN purchase pu ON pu.fk_contract =  c.id_contract
WHERE pu.fk_user IS NOT NULL
GROUP BY c.id_contract, pu.fk_user
ORDER BY c.id_contract;


CREATE OR REPLACE VIEW view_contract_nb_purchaser AS
SELECT c.id_contract, COUNT(DISTINCT(pu.fk_user)) AS nb_purchaser
FROM contract c
LEFT JOIN purchase pu ON pu.fk_contract =  c.id_contract
GROUP BY c.id_contract
ORDER BY c.id_contract;


CREATE OR REPLACE VIEW view_contract_conflict AS 
SELECT c1.id_contract AS id_contract,c2.label AS contrat,d.date AS distribution,CONCAT(ifnull(p.label,''),' ',ifnull(p.unit,'')) AS produit 
FROM contract c1, contract c2, contract_product cp1, contract_product cp2, distribution d, product p
WHERE c1.id_contract <> c2.id_contract 
AND cp2.fk_contract = c2.id_contract 
AND cp1.fk_contract = c1.id_contract 
AND cp1.fk_product = cp2.fk_product 
AND p.id_product = cp1.fk_product 
AND (d.date BETWEEN GREATEST(c1.period_start,c2.period_start) AND LEAST(c1.period_end,c2.period_end)) 
AND (
(c1.period_start BETWEEN c2.period_start AND c2.period_end) 
OR 
(c1.period_end BETWEEN c2.period_start AND c2.period_end) 
OR 
((c1.period_start < c2.period_start) AND (c1.period_end > c2.period_end))
)
ORDER BY c1.id_contract;




CREATE OR REPLACE VIEW view_contract_distribution_product AS 
SELECT c.id_contract AS fk_contract,pd.fk_distribution AS fk_distribution,pd.fk_product AS fk_product 
FROM product_distribution pd 
LEFT JOIN distribution d ON d.id_distribution = pd.fk_distribution 
LEFT JOIN contract c ON (d.date BETWEEN c.period_start AND c.period_end) 
JOIN contract_product cp ON cp.fk_product = pd.fk_product AND cp.fk_contract = c.id_contract
GROUP BY c.id_contract,pd.fk_distribution,pd.fk_product 
ORDER BY c.id_contract,pd.fk_distribution,pd.fk_product;


CREATE OR REPLACE VIEW view_deletable_distribution AS 
SELECT d.id_distribution AS fk_distribution 
FROM distribution d 
LEFT JOIN product_distribution pd ON pd.fk_distribution = d.id_distribution
WHERE pd.fk_distribution IS NULL;



CREATE OR REPLACE VIEW view_deletable_farm AS 
SELECT f.id_farm AS fk_farm 
FROM farm f 
LEFT JOIN product p ON p.fk_farm = f.id_farm 
LEFT JOIN referent r ON r.fk_farm = f.id_farm 
WHERE p.fk_farm IS NULL 
AND r.fk_farm IS NULL;



CREATE OR REPLACE VIEW view_deletable_product AS 
SELECT p.id_product AS fk_product 
FROM product p 
LEFT JOIN product_distribution pd ON pd.fk_product = p.id_product 
LEFT JOIN contract_product cp ON cp.fk_product = p.id_product
WHERE pd.fk_product IS NULL AND cp.fk_product IS NULL;


CREATE OR REPLACE VIEW view_deletable_user AS 
SELECT u.id_user AS fk_user 
FROM user u 
LEFT JOIN referent r ON r.fk_user = u.id_user 
LEFT JOIN purchase p ON p.fk_user = u.id_user 
WHERE r.fk_user IS NULL 
AND p.fk_user IS NULL;

CREATE OR REPLACE VIEW view_overage AS
SELECT pd.id_product_distribution, pd.fk_product, (SUM(p.quantity) - pd.max_quantity) AS excedent, pd.max_quantity
FROM product_distribution pd
LEFT JOIN purchase p ON p.fk_product_distribution = pd.id_product_distribution
WHERE pd.max_quantity IS NOT NULL
GROUP BY pd.id_product_distribution
HAVING SUM(p.quantity) > pd.max_quantity ;

create or replace view view_deletable_contract as 
select id_contract as fk_contract from contract
where id_contract not in (
SELECT 
c.id_contract
FROM contract c
LEFT JOIN purchase p ON p.fk_contract =  c.id_contract
group by c.id_contract
having sum(p.quantity) is not null
and sum(p.quantity) >0);

CREATE OR REPLACE VIEW view_payment_purchase AS
select id_payment as fk_payment, pu.id_purchase as fk_purchase
from payment p
left join view_contract_distribution_product v1 on v1.fk_contract = p.fk_contract
left join product_distribution pd on pd.fk_distribution = v1.fk_distribution and pd.fk_product =  v1.fk_product
inner join purchase pu on pu.fk_product_distribution = pd.id_product_distribution and pu.fk_user = p.fk_user;



ALTER TABLE contract ADD COLUMN fill_date_start date DEFAULT NULL AFTER period_end;
ALTER TABLE contract ADD COLUMN auto_start_hour tinyint(12) DEFAULT NULL AFTER fill_date_end;
ALTER TABLE contract ADD COLUMN auto_end_hour tinyint(12) DEFAULT NULL AFTER auto_start_hour;

SET GLOBAL event_scheduler = ON;
CREATE EVENT contract_auto_open
ON SCHEDULE EVERY 1 HOUR
STARTS '2021-01-01 00:00:01'
DO
update contract set is_active = 1 where fill_date_start = CURDATE() and auto_start_hour = hour(now());

CREATE EVENT contract_auto_close
ON SCHEDULE EVERY 1 HOUR
STARTS '2021-01-01 00:00:11'
DO
update contract set is_active = 0 where fill_date_end = CURDATE() and auto_end_hour = hour(now());


CREATE OR REPLACE VIEW view_purchase_ratio_price AS
select pu.id_purchase, d.date, pu.fk_user, pay.fk_farm, (pay.amount-ifnull(j1.somme,0))/j2.nb_product_prix_poids as prix_estime
from purchase pu
         left join payment pay on pu.fk_payment = pay.id_payment
         join product_distribution pd on pd.id_product_distribution = pu.fk_product_distribution
         join distribution d on d.id_distribution = pd.fk_distribution
         join product pr on pr.id_product = pd.fk_product
         left join (
    select id_payment, fk_contract, fk_user, amount, round(sum(price),2) as somme from (
           select pay.id_payment, pay.fk_contract, pu.fk_user, pay.amount, pu.quantity*pd.price as price
           from purchase pu
                    left join payment pay on pu.fk_payment = pay.id_payment
                    left join product_distribution pd on pd.id_product_distribution = pu.fk_product_distribution
                    join product pr on pr.id_product = pd.fk_product
           where pr.ratio is null
       ) tt group by id_payment
) j1 on j1.id_payment = pay.id_payment and j1.fk_contract = pu.fk_contract
         left join (
    select pay.id_payment, pay.fk_contract, pay.fk_user, pay.amount, sum(pu.quantity) as nb_product_prix_poids
    from payment pay
             join purchase pu on pu.fk_payment = pay.id_payment
             join product_distribution pd on pd.id_product_distribution = pu.fk_product_distribution
             join product pr on pr.id_product = pd.fk_product
    where pr.ratio is not null
    group by pay.id_payment
) j2 on j2.id_payment = pay.id_payment and j2.fk_contract = pu.fk_contract
where pr.ratio is not null
  and pay.fk_farm is not null;