
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


