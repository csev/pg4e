


psql discuss pg4e

discuss=> SHOW SERVER_ENCODING;


select random(), random(), trunc(random()*100);
select repeat('Neon ', 5);
select generate_series(1,5);

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
