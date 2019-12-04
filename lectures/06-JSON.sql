
-- https://www.pg4e.com/lectures/06-JSON.sql

DROP TABLE IF EXISTS jtrack;

CREATE TABLE IF NOT EXISTS jtrack (id SERIAL, body JSONB);

-- JSON import with copy, is odten easier with Python, but for 
-- simple JSON without embedded newlines in the content, this is good enough.

-- http://adpgtech.blogspot.com/2014/09/importing-json-data.html

\copy jtrack (body) FROM 'library.json' WITH CSV QUOTE E'\x01' DELIMITER E'\x02';

SELECT * FROM jtrack LIMIT 5;
SELECT pg_typeof(body) FROM jtrack LIMIT 1;

SELECT body->>'name' FROM JTRACK LIMIT 5;

-- Could we use parenthesis and cast to convert to text?
SELECT pg_typeof(body->'name') FROM JTRACK LIMIT 1;
SELECT pg_typeof(body->'name'::text) FROM JTRACK LIMIT 1;
SELECT pg_typeof(body->'name')::text FROM JTRACK LIMIT 1;
SELECT pg_typeof((body->'name')::text) FROM JTRACK LIMIT 1;

-- Yes we could, but why even try?
SELECT pg_typeof(body->>'name') FROM JTRACK LIMIT 1;

-- Pull data from an API

-- wget https://www.pg4e.com/code/swapi.py
-- wget https://www.pg4e.com/code/myitils.py

-- python3 swapi.py

--- To restart the spider
DROP TABLE IF EXISTS swapi CASCADE;

CREATE TABLE IF NOT EXISTS swapi
(id SERIAL, url VARCHAR(2048) UNIQUE, status INTEGER, body JSONB,
created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(), updated_at TIMESTAMPTZ);

SELECT url, status FROM SWAPI where URL like '%film%';

SELECT COUNT(url) FROM swapi;

SELECT url FROM swapi WHERE status != 200;
-- Load complete...

-- The "->>" implies get the attribute and convert to a string
SELECT body->>'url' FROM swapi LIMIT 1;

-- Parenthesis matter - cast has higher precedence than ->
SELECT pg_typeof(body->'url') FROM swapi LIMIT 1;
SELECT pg_typeof(body->'url'::text) FROM swapi LIMIT 1;  -- is jsonb, not text
SELECT pg_typeof((body->'url')::text) FROM swapi LIMIT 1;
SELECT pg_typeof(body->>'url') FROM swapi LIMIT 1; -- text and succinct

SELECT body->>'url' FROM swapi WHERE body @> '{"director": "George Lucas"}';
EXPLAIN SELECT body->>'url' FROM swapi WHERE body @> '{"director": "George Lucas"}';

CREATE INDEX swapi_gin ON swapi USING gin (body);
EXPLAIN SELECT body->>'url' FROM swapi WHERE body @> '{"director": "George Lucas"}';

-- oops - Not what we meant
SELECT body->>'url' FROM swapi WHERE NOT(body @> '{"director": "George Lucas"}'::jsonb);

-- We can fix that...
SELECT body->>'url' FROM swapi WHERE body->>'url' LIKE 'https://swapi.co/api/films/%';
EXPLAIN SELECT body->>'url' FROM swapi WHERE body->>'url' LIKE 'https://swapi.co/api/films/%';

-- We can do a sequential scan
SELECT body->>'url' FROM swapi WHERE NOT(body @> '{"director": "George Lucas"}'::jsonb) AND 
body->>'url' LIKE 'https://swapi.co/api/films/%';

SELECT body->>'url', body->>'name' FROM swapi LIMIT 10;

SELECT body->>'url', body->>'name' FROM swapi 
WHERE body->>'url' LIKE 'https://swapi.co/api/people/%' LIMIT 10;

SELECT body->>'url', body->>'name' FROM swapi 
WHERE body->>'url' LIKE 'https://swapi.co/api/species/%' LIMIT 10;

-- LIKE makes a sequential scan
EXPLAIN SELECT body->>'url', body->>'name' FROM swapi 
WHERE body->>'url' LIKE 'https://swapi.co/api/species/%' LIMIT 10;

-- Lets augment the JSON
-- https://stackoverflow.com/questions/13615760/add-element-to-json-object-in-postgres

SELECT substring(body->>'url', 'https://swapi.co/api/([a-z]+)/') FROM swapi LIMIT 1;

SELECT ('{"type": "' || substring(body->>'url', 'https://swapi.co/api/([a-z]+)/') || '" }')
FROM swapi LIMIT 1;
SELECT ('{"type": "' || substring(body->>'url', 'https://swapi.co/api/([a-z]+)/') || '" }')::jsonb
FROM swapi LIMIT 1;

-- Merge new json back into the body
SELECT body || ('{"type": "' || substring(body->>'url', 'https://swapi.co/api/([a-z]+)/') || '" }')::jsonb
FROM swapi LIMIT 1;

-- Add the type field to all the records
UPDATE swapi SWT SET body = body || ('{"type": "' || substring(body->>'url', 'https://swapi.co/api/([a-z]+)/') || '" }')::jsonb;

SELECT body->>'url', body->>'name' FROM swapi WHERE body @> '{"type": "species"}'  LIMIT 10;
EXPLAIN SELECT body->>'url', body->>'name' FROM swapi WHERE body @> '{"type": "species"}'  LIMIT 10;

-- Dang this does not use the GIN (after all that!)
SELECT url FROM swapi WHERE body @> '{"director": "George Lucas", "type": "films"}';
EXPLAIN SELECT url FROM swapi WHERE body @> '{"director": "George Lucas", "type": "films"}';


