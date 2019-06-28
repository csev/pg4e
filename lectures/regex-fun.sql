
CREATE TABLE em (id serial, primary key(id), email text);

INSERT INTO em (email) VALUES ('csev@umich.edu');
INSERT INTO em (email) VALUES ('coleen@umich.edu');
INSERT INTO em (email) VALUES ('sally@uiuc.edu');
INSERT INTO em (email) VALUES ('ted@umuc.edu');
INSERT INTO em (email) VALUES ('glenn@apple.com');
INSERT INTO em (email) VALUES ('nbody@apple.com');

SELECT substring(email FROM '.+@(.*)$') FROM em;

SELECT DISTINCT substring(email FROM '.+@(.*)$') FROM em;

SELECT * FROM em where substring(email FROM '.+@(.*)$') = 'umich.edu';

CREATE TABLE tw (id serial, primary key(id), tweet text);

INSERT INTO tw (tweet) VALUES ('This is #SQL and #FUN stuff');
INSERT INTO tw (tweet) VALUES ('More people should learn #SQL from #UMSI');
INSERT INTO tw (tweet) VALUES ('#UMSI also teaches #PYTHON');

SELECT REGEXP_MATCHES(tweet,'#([A-Za-z0-9_]+)', 'g') FROM tw;

SELECT id, REGEXP_MATCHES(tweet,'#([A-Za-z0-9_]+)', 'g') FROM tw;

CREATE TABLE mbox (line TEXT);

\copy mbox FROM 'mbox-short.txt' with delimiter E'\007';

