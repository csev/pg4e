


psql discuss pg4e

CREATE TABLE cr1 (
  id SERIAL,
  url VARCHAR(128) UNIQUE,
  content TEXT
);

CREATE TABLE cr2 (
  id SERIAL,
  url TEXT,
  content TEXT
);

insert into cr2 (url)
select random() || repeat('Neon', 1000) || generate_series(1,100);

select pg_relation_size('cr2'), pg_indexes_size('cr2');

create unique index cr2_unique on cr2 (url);

select pg_relation_size('cr2'), pg_indexes_size('cr2');

CREATE TABLE cr3 (
  id SERIAL,
  url TEXT,
  content TEXT
);

insert into cr3 (url) select random() || repeat('Neon', 4000) || generate_series(1,10000);

select pg_relation_size('cr3'), pg_indexes_size('cr3');

create unique index cr3_md5 on cr3 (md5(url));

create unique index cr3_sha256 on cr3 (sha256(url::bytea));

select pg_relation_size('cr3'), pg_indexes_size('cr3');
 
explain select * from cr3 where url='bob';
explain select * from cr3 where md5(url)=md5('bob');
explain select * from cr3 where sha256(url::bytea)=sha256('bob'::bytea);

CREATE TABLE cr4 (
  id SERIAL,
  url TEXT,
  url_md5 uuid unique,
  content TEXT
);

insert into cr4 (url) select random() || repeat('Neon', 4000) || generate_series(1,10000);

select pg_relation_size('cr4'), pg_indexes_size('cr4');

update cr4 set url_md5 = md5(url)::uuid;

select pg_relation_size('cr4'), pg_indexes_size('cr4');

 pg_relation_size | pg_indexes_size
------------------+-----------------
          5455872 |          688128


