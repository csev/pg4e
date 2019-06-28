
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
SELECT upper(content) FROM textfun WHERE content LIKE '%150000%';
SELECT lower(content) FROM textfun WHERE content LIKE '%150000%';
SELECT right(content, 4) FROM textfun WHERE content LIKE '%150000%';
SELECT left(content, 4) FROM textfun WHERE content LIKE '%150000%';
SELECT strpos(content, 'ttps://') FROM textfun WHERE content LIKE '%150000%';
SELECT substr(content, 2, 4) FROM textfun WHERE content LIKE '%150000%';
SELECT split_part(content, '/', 4) FROM textfun WHERE content LIKE '%150000%';
SELECT translate(content, 'th.p/', 'TH!P_') FROM textfun WHERE content LIKE '%150000%';

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

