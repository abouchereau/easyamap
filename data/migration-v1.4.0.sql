ALTER TABLE payment ADD COLUMN issued_at DATE NULL DEFAULT NULL;
ALTER TABLE farm ADD COLUMN email VARCHAR(255) NULL DEFAULT NULL;
ALTER TABLE farm ADD COLUMN phone VARCHAR(255) NULL DEFAULT NULL;
ALTER TABLE farm ADD COLUMN iban varchar(255) DEFAULT NULL;  
ALTER TABLE farm ADD INDEX email (`email`);


ALTER TABLE payment ADD COLUMN validated_by int(11) NULL DEFAULT NULL;
ALTER TABLE payment ADD KEY validated_by (validated_by);
ALTER TABLE payment ADD CONSTRAINT payment_ibfk_4 FOREIGN KEY (validated_by) REFERENCES user (id_user) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE payment ADD COLUMN payment_type int(1) DEFAULT 0;
ALTER TABLE payment ADD COLUMN reference VARCHAR(255) NULL DEFAULT NULL;


SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE payment_type;
ALTER TABLE payment_type MODIFY COLUMN id_payment_type INT NOT NULL;

INSERT INTO payment_type (id_payment_type, label) VALUES (0, 'Inconnu');
INSERT INTO payment_type (id_payment_type, label) VALUES (1, 'Chèque');
INSERT INTO payment_type (id_payment_type, label) VALUES (2, 'Espèces');
INSERT INTO payment_type (id_payment_type, label) VALUES (3, 'Virement');
INSERT INTO payment_type (id_payment_type, label) VALUES (4, 'Wero');
SET FOREIGN_KEY_CHECKS = 1;