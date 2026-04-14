ALTER TABLE payment ADD COLUMN transfer_issued_at DATETIME NULL DEFAULT NULL;
ALTER TABLE payment ADD COLUMN transfer_received_at DATETIME NULL DEFAULT NULL;
ALTER TABLE farm ADD COLUMN email VARCHAR(255) NULL DEFAULT NULL;

ALTER TABLE payment ADD COLUMN transfer_validated_by int(11) NULL DEFAULT NULL;
ALTER TABLE payment ADD KEY transfer_validated_by (transfer_validated_by);
ALTER TABLE payment ADD CONSTRAINT payment_ibfk_4 FOREIGN KEY (transfer_validated_by) REFERENCES user (id_user) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE payment ADD COLUMN payment_type int(11) NULL DEFAULT NULL;

INSERT INTO payment_type (id_payment_type, label) VALUES (0, 'Inconnu');