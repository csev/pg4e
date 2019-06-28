

psql discuss pg4e

-- Generate Data

select random(), random(), trunc(random()*100);
select repeat('Neon ', 5);
select generate_series(1,5);
select 'Neon' || generate_series(1,5);

-- [ 'Neon' + str(x) for x in range(1,6) ]


-- Text Functions

discuss=> SHOW SERVER_ENCODING;


CREATE TABLE textfun (
  content TEXT
);

-- BTree Index is Default
CREATE INDEX textfun_b ON textfun (content);

SELECT pg_relation_size('textfun'), pg_indexes_size('textfun');

SELECT (CASE WHEN (random() < 0.5)
         THEN 'https://www.pg4e.com/neon/'
         ELSE 'http://www.pg4e.com/LEMONS/'
         END) || generate_series(1000,1005);

INSERT INTO textfun (content)
SELECT (CASE WHEN (random() < 0.5)
         THEN 'https://www.pg4e.com/neon/'
         ELSE 'http://www.pg4e.com/LEMONS/'
         END) || generate_series(100000,200000);

SELECT pg_relation_size('textfun'), pg_indexes_size('textfun');

SELECT content FROM textfun WHERE content LIKE '%150000%';
--  https://www.pg4e.com/neon/150000
SELECT upper(content) FROM textfun WHERE content LIKE '%150000%';
--  HTTPS://WWW.PG4E.COM/NEON/150000
SELECT lower(content) FROM textfun WHERE content LIKE '%150000%';
--  https://www.pg4e.com/neon/150000
SELECT right(content, 4) FROM textfun WHERE content LIKE '%150000%';
-- 0000
SELECT left(content, 4) FROM textfun WHERE content LIKE '%150000%';
-- http
SELECT strpos(content, 'ttps://') FROM textfun WHERE content LIKE '%150000%';
-- 2
SELECT substr(content, 2, 4) FROM textfun WHERE content LIKE '%150000%';
-- ttps
SELECT split_part(content, '/', 4) FROM textfun WHERE content LIKE '%150000%';
-- neon
SELECT translate(content, 'th.p/', 'TH!P_') FROM textfun WHERE content LIKE '%150000%';
--  HTTPs:__www!Pg4e!com_neon_150000


SELECT content FROM textfun WHERE content LIKE '%150000%';
SELECT content FROM textfun WHERE content LIKE '%15__00%' LIMIT 3;
SELECT content FROM textfun WHERE content LIKE 'https://%';
SELECT content FROM textfun WHERE content ILIKE 'racing%';

explain analyze SELECT content FROM textfun WHERE content LIKE 'racing%';
explain analyze SELECT content FROM textfun WHERE content LIKE '%racing%';
-- ILIKE Can't use B-tree index
explain analyze SELECT content FROM textfun WHERE content ILIKE 'racing%';

SELECT content FROM textfun WHERE content IN ('http://www.pg4e.com/neon/150000', 'https://www.pg4e.com/neon/150000');
explain analyze SELECT content FROM textfun WHERE content IN ('http://www.pg4e.com/neon/150000', 'https://www.pg4e.com/neon/150000');

SELECT content FROM textfun WHERE content IN (SELECT content FROM textfun WHERE content LIKE '%150000%');
explain analyze SELECT content FROM textfun WHERE content IN (SELECT content FROM textfun WHERE content LIKE '%150000%');
explain analyze SELECT content FROM textfun WHERE content IN (SELECT content FROM textfun LIKE '%150000%');


--- Advanced Indexes

select 'https://sql4e.com/neon/' || trunc(random()*1000000) || repeat('Lemon', 5) || generate_series(1,5);

CREATE TABLE cr1 (
  id SERIAL,
  url VARCHAR(128) UNIQUE,
  content TEXT
);

insert into cr1(url)
select repeat('Neon', 1000) || generate_series(1,5000);

CREATE TABLE cr2 (
  id SERIAL,
  url TEXT,
  content TEXT
);

insert into cr2 (url)
select repeat('Neon', 1000) || generate_series(1,5000);

select pg_relation_size('cr2'), pg_indexes_size('cr2');

create unique index cr2_unique on cr2 (url);

select pg_relation_size('cr2'), pg_indexes_size('cr2');

drop index cr2_unique;

select pg_relation_size('cr2'), pg_indexes_size('cr2');

create unique index cr2_md5 on cr2 (md5(url));

select pg_relation_size('cr2'), pg_indexes_size('cr2');

explain select * from cr2 where url='lemons';

explain select * from cr2 where md5(url)=md5('lemons');

drop index cr2_md5;

create unique index cr2_sha256 on cr2 (sha256(url::bytea));

explain select * from cr2 where sha256(url::bytea)=sha256('bob'::bytea);

CREATE TABLE cr3 (
  id SERIAL,
  url TEXT,
  url_md5 uuid unique,
  content TEXT
);

insert into cr3 (url)
select repeat('Neon', 1000) || generate_series(1,5000);

select pg_relation_size('cr3'), pg_indexes_size('cr3');

update cr3 set url_md5 = md5(url)::uuid;

select pg_relation_size('cr3'), pg_indexes_size('cr3');

explain analyze select * from cr3 where url_md5=md5('lemons')::uuid;

CREATE TABLE cr4 (
  id SERIAL,
  url TEXT,
  content TEXT
);

insert into cr4 (url)
select repeat('Neon', 1000) || generate_series(1,5000);

create index cr4_hash on cr4 using hash (url);

select pg_relation_size('cr4'), pg_indexes_size('cr4');

explain analyze select * from cr5 where url='lemons';

--- Regex

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

SELECT line FROM mbox WHERE line ~ '^From ';
SELECT substring(line, ' (.+@[^ ]+) ') FROM mbox WHERE line ~ '^From ';

SELECT substring(line, ' (.+@[^ ]+) '), count(substring(line, ' (.+@[^ ]+) ')) FROM mbox WHERE line ~ '^From ' GROUP BY substring(line, ' (.+@[^ ]+) ') ORDER BY count(substring(line, ' (.+@[^ ]+) ')) DESC;

SELECT email, count(email) FROM
( SELECT substring(line, ' (.+@[^ ]+) ') AS email FROM mbox WHERE line ~ '^From '
) AS badsub
GROUP BY email ORDER BY count(email) DESC;

