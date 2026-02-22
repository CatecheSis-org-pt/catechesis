-- --
-- When importing this file in phpMyAdmin, use the ANSI dialect
-- --

DROP TABLE IF EXISTS ul_blocked_ips;
CREATE TABLE ul_blocked_ips (
  ip varchar(39) CHARACTER SET ascii NOT NULL,
  block_expires varchar(26) CHARACTER SET ascii NOT NULL,
  PRIMARY KEY (ip)
)
CHARACTER SET utf8 COLLATE utf8_general_ci;


DROP TABLE IF EXISTS ul_log;
CREATE TABLE ul_log (
  timestamp varchar(26) CHARACTER SET ascii NOT NULL,
  action varchar(20) CHARACTER SET ascii NOT NULL,
  comment varchar(255) CHARACTER SET ascii NOT NULL DEFAULT '',
  user varchar(400) COLLATE utf8_general_ci NOT NULL,
  ip varchar(39) CHARACTER SET ascii NOT NULL
)
CHARACTER SET utf8 COLLATE utf8_general_ci;


DROP TABLE IF EXISTS ul_logins;
CREATE TABLE ul_logins (
  id int(11) NOT NULL AUTO_INCREMENT,
  username varchar(400) COLLATE utf8_general_ci NOT NULL,
  password varchar(2048) CHARACTER SET ascii NOT NULL,
  date_created varchar(26) CHARACTER SET ascii NOT NULL,
  last_login varchar(26) CHARACTER SET ascii NOT NULL,
  block_expires varchar(26) CHARACTER SET ascii NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY username (username(255))
) 
AUTO_INCREMENT=1 
CHARACTER SET utf8 COLLATE utf8_general_ci;


DROP TABLE IF EXISTS ul_nonces;
CREATE TABLE ul_nonces (
  code varchar(100) CHARACTER SET ascii NOT NULL,
  action varchar(850) CHARACTER SET ascii NOT NULL,
  nonce_expires varchar(26) CHARACTER SET ascii NOT NULL,
  PRIMARY KEY (code),
  UNIQUE KEY action (action(255))
)
CHARACTER SET utf8 COLLATE utf8_general_ci;


DROP TABLE IF EXISTS ul_sessions;
CREATE TABLE ul_sessions (
  id varchar(128) CHARACTER SET ascii NOT NULL DEFAULT '',
  data blob NOT NULL,
  session_expires varchar(26) CHARACTER SET ascii NOT NULL,
  lock_expires varchar(26) CHARACTER SET ascii NOT NULL,
  PRIMARY KEY (id)
)
CHARACTER SET utf8 COLLATE utf8_general_ci;
