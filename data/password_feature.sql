DROP TABLE IF EXISTS reset_password_request;
CREATE TABLE reset_password_request
(
    id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id int(11),
    selector varchar(20) COLLATE utf8_bin DEFAULT NULL,
    hashed_token varchar(100) COLLATE utf8_bin DEFAULT NULL,
    requested_at datetime DEFAULT NULL,
    expires_at datetime DEFAULT NULL,
    CONSTRAINT `reset_password_request_fk_user_1` FOREIGN KEY (user_id) REFERENCES user(id_user)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;