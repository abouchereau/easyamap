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