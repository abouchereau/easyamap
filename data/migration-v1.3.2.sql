ALTER TABLE contract ADD COLUMN fill_date_start date DEFAULT NULL AFTER period_end;
ALTER TABLE contract ADD COLUMN auto_start_hour tinyint(12) DEFAULT NULL AFTER fill_date_end;
ALTER TABLE contract ADD COLUMN auto_end_hour tinyint(12) DEFAULT NULL AFTER auto_start_hour;