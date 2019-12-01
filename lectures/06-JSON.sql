
-- Using Python

-- Get a book from Gutenberg

-- wget http://www.gutenberg.org/cache/epub/19337/pg19337.txt

-- wget https://www.pg4e.com/code/loadbook.py
-- wget https://www.pg4e.com/code/myutils.py
-- wget https://www.pg4e.com/code/hidden-dist.py
-- mv hidden-dist.py hidden.py
-- edit hidden.py and put in your credentials

-- python3 loadbook.py
-- Enter book file (i.e. pg19337.txt): pg19337.txt
-- DROP TABLE IF EXISTS pg19337 CASCADE;
-- CREATE TABLE pg19337 (id SERIAL, body TEXT);
-- 100 loaded...

-- Loaded 814 paragraphs 3853 lines 178898 characters

-- We could have done this before we did all the inserts..
CREATE INDEX pg19337_gin ON pg19337 USING gin(to_tsvector('english', body));

-- It might take a little while before explain uses the GIN
SELECT body FROM pg19337  WHERE to_tsquery('english', 'goose') @@ to_tsvector('english', body) LIMIT 5;
EXPLAIN ANALYZE SELECT body FROM pg19337  WHERE to_tsquery('english', 'goose') @@ to_tsvector('english', body);

SELECT count(body) FROM pg19337  WHERE to_tsquery('english', 'tiny <-> tim') @@ to_tsvector('english', body);
SELECT body FROM pg19337  WHERE to_tsquery('english', 'tiny <-> tim') @@ to_tsvector('english', body) LIMIT 5;

-- wget https://www.pg4e.com/code/swapi.py

-- python3 swapi.py

--- To restart the spider
DROP TABLE IF EXISTS swapi CASCADE;

SELECT url, status FROM SWAPI where URL like '%film%';

SELECT COUNT(url) FROM swapi;

SELECT url FROM swapi WHERE status != 200;

SELECT url FROM swapi WHERE body @> '{"director": "George Lucas"}';
EXPLAIN SELECT url FROM swapi WHERE body @> '{"director": "George Lucas"}';

CREATE INDEX swapi_gin ON swapi USING gin (body);
EXPLAIN SELECT url FROM swapi WHERE body @> '{"director": "George Lucas"}';

-- oops - Not what we meant
SELECT url FROM swapi WHERE NOT(body @> '{"director": "George Lucas"}'::jsonb);

-- We can fix that...
SELECT url FROM swapi WHERE body->>'url' LIKE 'https://swapi.co/api/films/%';
EXPLAIN SELECT url FROM swapi WHERE body->>'url' LIKE 'https://swapi.co/api/films/%';

-- But with no index...
SELECT url FROM swapi WHERE NOT(body @> '{"director": "George Lucas"}'::jsonb) AND body->>'url' LIKE 'https://swapi.co/api/films/%';


-- https://stackoverflow.com/questions/13615760/add-element-to-json-object-in-postgres

SELECT body->'url' FROM swapi LIMIT 1;
SELECT (body->'url')::text FROM swapi LIMIT 1;

-- Parenthesis matter - cast has higher precedence than ->
SELECT pg_typeof(body->'url'::text) FROM swapi LIMIT 1;
SELECT pg_typeof((body->'url')::text) FROM swapi LIMIT 1;

SELECT substring((body->'url')::text, 'https://swapi.co/api/([a-z]+)/') FROM swapi LIMIT 1;

SELECT ('{"type": "' || substring((body->'url')::text, 'https://swapi.co/api/([a-z]+)/') || '" }')
FROM swapi LIMIT 1;
SELECT ('{"type": "' || substring((body->'url')::text, 'https://swapi.co/api/([a-z]+)/') || '" }')::jsonb
FROM swapi LIMIT 1;
SELECT body || ('{"type": "' || substring((body->'url')::text, 'https://swapi.co/api/([a-z]+)/') || '" }')::jsonb
FROM swapi LIMIT 1;

-- Add the type field to all the records
UPDATE swapi SWT SET body = body || ('{"type": "' || substring((body->'url')::text, 'https://swapi.co/api/([a-z]+)/') || '" }')::jsonb;

SELECT url FROM swapi WHERE body @> '{"director": "George Lucas", "type": "films"}';
SELECT url FROM swapi WHERE body @> '{"type": "films"}' AND NOT(body @> '{"director": "George Lucas"}');

EXLPAIN SELECT url FROM swapi WHERE body @> '{"type": "films"}' AND NOT(body @> '{"director": "George Lucas"}');

