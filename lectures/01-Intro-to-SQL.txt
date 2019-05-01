Python for Everybody Database Handout

https://www.pg4e.com/lectures/01-Intro-to-SQL.txt

Setup - Making a Database / User:

Note:  --- is the start of a comment - do not include these

sudo -u postgres psql postgres
\l       -- list databases
CREATE USER pg4e WITH PASSWORD 'secret';
CREATE DATABASE people WITH OWNER 'pg4e' ENCODING 'UTF8';
\q       -- quit

Running SQL Commands:

psql people pg4e

\dt      -- List relations (tables)

CREATE TABLE users( name VARCHAR(128), email VARCHAR(128) );

INSERT INTO users (name, email) VALUES ('Chuck', 'csev@umich.edu');
INSERT INTO users (name, email) VALUES ('Colleen', 'cvl@umich.edu');
INSERT INTO users (name, email) VALUES ('Ted', 'ted@umich.edu');
INSERT INTO users (name, email) VALUES ('Sally', 'a1@umich.edu');
INSERT INTO users (name, email) VALUES ('Ted', 'ted@umich.edu');
INSERT INTO users (name, email) VALUES ('Kristen', 'kf@umich.edu');

DELETE FROM users WHERE email='ted@umich.edu';

UPDATE users SET name="Charles" WHERE email='csev@umich.edu';

SELECT * FROM users;

SELECT * FROM users WHERE email='csev@umich.edu';

SELECT * FROM users ORDER BY email;

SELECT * FROM users ORDER BY name DESC;

SELECT * FROM users WHERE name LIKE '%e%';

SELECT * FROM users ORDER BY email DESC LIMIT 2;
SELECT * FROM users ORDER BY email OFFSET 1 LIMIT 2;

SELECT COUNT(*) FROM users;
SELECT COUNT(*) FROM users WHERE email='csev@umich.edu';

DROP TABLE users;

CREATE TABLE users (
  id SERIAL, 
  name VARCHAR(128), 
  email VARCHAR(128) UNIQUE,
  PRIMARY KEY(id)
);

INSERT INTO users (name, email) VALUES ('Chuck', 'csev@umich.edu');
INSERT INTO users (name, email) VALUES ('Colleen', 'cvl@umich.edu');
INSERT INTO users (name, email) VALUES ('Ted', 'ted@umich.edu');

-- Note the SERIAL field auto-supplied
SELECT * from users;

-- Watch for failure due to UNIQUE
INSERT INTO users (name, email) VALUES ('Ted', 'ted@umich.edu');

