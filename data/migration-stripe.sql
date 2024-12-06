ALTER TABLE user ADD COLUMN stripe_customer_id VARCHAR(255) NULL DEFAULT NULL;
ALTER TABLE user ADD COLUMN stripe_payment_method_id VARCHAR(255) NULL DEFAULT NULL;

ALTER TABLE farm ADD COLUMN stripe_account_id VARCHAR(255) NULL DEFAULT NULL;
ALTER TABLE farm ADD COLUMN stripe_account_link_url VARCHAR(255) NULL DEFAULT NULL;

INSERT INTO payment_type(id_payment_type, label) VALUES(3, "Virement");