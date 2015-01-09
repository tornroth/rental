--
-- Table for users
--
DROP TABLE IF EXISTS rm_Users;

CREATE TABLE rm_Users
(
  id INT AUTO_INCREMENT PRIMARY KEY,
  acronym CHAR(12) UNIQUE NOT NULL,
  name VARCHAR(80),
  password CHAR(32),
  salt INT NOT NULL
) ENGINE INNODB CHARACTER SET utf8;

INSERT INTO rm_Users (acronym, name, salt) VALUES 
  ('guest', 'Gäst', unix_timestamp()),
  ('marcus', 'Marcus', unix_timestamp())
;

UPDATE rm_Users SET password = md5(concat('guest', salt)) WHERE acronym = 'guest';
UPDATE rm_Users SET password = md5(concat('marcus', salt)) WHERE acronym = 'marcus';