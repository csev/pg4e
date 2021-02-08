
-- https://www.pg4e.com/lectures/06-JSON.sql

DROP TABLE IF EXISTS jtrack CASCADE;

CREATE TABLE IF NOT EXISTS jtrack (id SERIAL, body JSONB);

-- JSON import with copy, is often easier with Python, but for 
-- simple JSON without embedded newlines in the JSON values, this is good enough.

-- http://adpgtech.blogspot.com/2014/09/importing-json-data.html

-- wget https://www.pg4e.com/code/library.jstxt

\copy jtrack (body) FROM 'library.jstxt' WITH CSV QUOTE E'\x01' DELIMITER E'\x02';

SELECT * FROM jtrack LIMIT 5;
SELECT pg_typeof(body) FROM jtrack LIMIT 1;

SELECT body->>'name' FROM jtrack LIMIT 5;

-- Could we use parenthesis and cast to convert to text?
SELECT pg_typeof(body->'name') FROM jtrack LIMIT 1;
SELECT pg_typeof(body->'name'::text) FROM jtrack LIMIT 1;
SELECT pg_typeof(body->'name')::text FROM jtrack LIMIT 1;
SELECT pg_typeof((body->'name')::text) FROM jtrack LIMIT 1;

-- Yes we could, but why even try?
SELECT pg_typeof(body->>'name') FROM jtrack LIMIT 1;

SELECT MAX((body->>'count')::int) FROM jtrack;

SELECT body->>'name' AS name FROM jtrack ORDER BY (body->>'count')::int DESC LIMIT 5;

-- Yes you need the cast even though it is an integer in the JSON :(
SELECT pg_typeof(body->'count') FROM jtrack LIMIT 1;
SELECT pg_typeof(body->>'count') FROM jtrack LIMIT 1;

-- Look into JSON for a value
SELECT COUNT(*) FROM jtrack WHERE body->>'name' = 'Summer Nights';

-- Ask if the body contains a key/value pair
SELECT COUNT(*) FROM jtrack WHERE body @> '{"name": "Summer Nights"}';
SELECT COUNT(*) FROM jtrack WHERE body @> ('{"name": "Summer Nights"}'::jsonb);

-- Adding something to the JSONB column
UPDATE jtrack SET body = body || '{"favorite": "yes"}' WHERE (body->'count')::int > 200;

-- Should see some with and without "favorite"
SELECT body FROM jtrack WHERE (body->'count')::int > 160 LIMIT 5;

-- We have an operator to check is a tag is present
SELECT COUNT(*) FROM jtrack WHERE body ? 'favorite';

-- https://bitnine.net/blog-postgresql/postgresql-internals-jsonb-type-and-its-indexes/

-- Lets throw some bulk into the table
INSERT INTO jtrack (body) 
SELECT ('{ "type": "Neon", "series": "24 Hours of Lemons", "number": ' || generate_series(1000,5000) || '}')::jsonb;

-- Prepare three indexes...
DROP INDEX jtrack_btree;
DROP INDEX jtrack_gin;
DROP INDEX jtrack_gin_path_ops;

CREATE INDEX jtrack_btree ON jtrack USING BTREE ((body->>'name'));
CREATE INDEX jtrack_gin ON jtrack USING gin (body);
CREATE INDEX jtrack_gin_path_ops ON jtrack USING gin (body jsonb_path_ops);

-- Might need to wait a little while while PostgreSQL catches up :)

-- See which query uses which index
EXPLAIN SELECT COUNT(*) FROM jtrack WHERE body->>'artist' = 'Queen';
EXPLAIN SELECT COUNT(*) FROM jtrack WHERE body->>'name' = 'Summer Nights';
EXPLAIN SELECT COUNT(*) FROM jtrack WHERE body ? 'favorite';
EXPLAIN SELECT COUNT(*) FROM jtrack WHERE body @> '{"name": "Summer Nights"}';
EXPLAIN SELECT COUNT(*) FROM jtrack WHERE body @> '{"artist": "Queen"}';
EXPLAIN SELECT COUNT(*) FROM jtrack WHERE body @> '{"name": "Folsom Prison Blues", "artist": "Johnny Cash"}';

-- https://stackoverflow.com/questions/30074452/how-to-update-a-jsonb-columns-field-in-postgresql

-- Updating a numeric field in JSONB

-- Failure and then success :)
SELECT (body->'count') + 1 FROM jtrack LIMIT 1;
SELECT (body->'count')::int + 1 FROM jtrack LIMIT 1;

SELECT (body->>'count')::int FROM jtrack WHERE body->>'name' = 'Summer Nights';
SELECT ( (body->>'count')::int + 1 ) FROM jtrack WHERE body->>'name' = 'Summer Nights';

UPDATE jtrack SET body = jsonb_set(body, '{ count }', ( (body->>'count')::int + 1 )::text::jsonb )
WHERE body->>'name' = 'Summer Nights';

-- Don't want to run out of space for data or indexes
DROP TABLE IF EXISTS jtrack CASCADE;

-- Pull data from a JSON API

-- https://swapi.py4e.com/

-- wget https://www.pg4e.com/code/swapi.py
-- wget https://www.pg4e.com/code/myutils.py
-- Make sure hidden.py is set up

-- python3 swapi.py

--- To restart the spider
DROP TABLE IF EXISTS swapi CASCADE;

-- swapi.py creates this if it does not exist
CREATE TABLE IF NOT EXISTS swapi
(id SERIAL, url VARCHAR(2048) UNIQUE, status INTEGER, body JSONB,
created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(), updated_at TIMESTAMPTZ);

SELECT url, status FROM SWAPI where URL like '%film%';

SELECT COUNT(url) FROM swapi;

-- While load is happening :)
SELECT url FROM swapi WHERE status = 200;
SELECT url FROM swapi WHERE status IS NULL;

-- Load completed...
SELECT body->>'url' FROM swapi LIMIT 1;

SELECT body->>'url' FROM swapi WHERE body @> '{"director": "George Lucas"}';
EXPLAIN SELECT body->>'url' FROM swapi WHERE body @> '{"director": "George Lucas"}';

-- Lets throw some race cars into the table to make sure PG finds indexes useful
INSERT INTO swapi (body) 
SELECT ('{ "type": "Neon", "series": "24 Hours of Lemons", "number": ' || generate_series(1000,5000) || '}')::jsonb;

CREATE INDEX swapi_gin ON swapi USING gin (body jsonb_path_ops);
EXPLAIN SELECT body->>'url' FROM swapi WHERE body @> '{"director": "George Lucas"}';

-- Try this ... oops - not what we meant
SELECT body->>'url' FROM swapi WHERE NOT(body @> '{"director": "George Lucas"}'::jsonb);

-- We can fix that NOT bit with a clever WHERE clause...
SELECT body->>'url' FROM swapi WHERE body->>'url' LIKE 'https://swapi.py4e.com/api/films/%';
EXPLAIN SELECT body->>'url' FROM swapi WHERE body->>'url' LIKE 'https://swapi.py4e.com/api/films/%';

-- We can do a sequential scan
SELECT body->>'url' FROM swapi WHERE NOT(body @> '{"director": "George Lucas"}'::jsonb) AND 
body->>'url' LIKE 'https://swapi.py4e.com/api/films/%';

SELECT body->>'url', body->>'name' FROM swapi LIMIT 10;

SELECT body->>'url', body->>'name' FROM swapi 
WHERE body->>'url' LIKE 'https://swapi.py4e.com/api/people/%' LIMIT 10;

SELECT body->>'url', body->>'name' FROM swapi 
WHERE body->>'url' LIKE 'https://swapi.py4e.com/api/species/%' LIMIT 10;

-- LIKE makes a sequential scan
EXPLAIN SELECT body->>'url', body->>'name' FROM swapi 
WHERE body->>'url' LIKE 'https://swapi.py4e.com/api/species/%' LIMIT 10;

-- Lets augment the JSON and add a "type" field using Regular Expressions

-- https://stackoverflow.com/questions/13615760/add-element-to-json-object-in-postgres

SELECT substring(body->>'url', 'https://swapi.py4e.com/api/([a-z]+)/') FROM swapi LIMIT 1;

SELECT ('{"type": "' || substring(body->>'url', 'https://swapi.py4e.com/api/([a-z]+)/') || '" }')
FROM swapi LIMIT 1;
SELECT ('{"type": "' || substring(body->>'url', 'https://swapi.py4e.com/api/([a-z]+)/') || '" }')::jsonb
FROM swapi LIMIT 1;

-- Merge new json back into the body
SELECT body || ('{"type": "' || substring(body->>'url', 'https://swapi.py4e.com/api/([a-z]+)/') || '" }')::jsonb
FROM swapi LIMIT 1;

-- Add the type field to all the records
UPDATE swapi SWT SET body = body || 
('{"type": "' || substring(body->>'url', 'https://swapi.py4e.com/api/([a-z]+)/') || '" }')::jsonb;

SELECT body->>'url', body->>'name' FROM swapi WHERE body @> '{"type": "species"}'  LIMIT 10;

EXPLAIN SELECT body->>'url', body->>'name' FROM swapi WHERE body @> '{"type": "species"}'  LIMIT 10;

-- The payoff
SELECT url FROM swapi WHERE body @> '{"type": "films"}' AND NOT(body @> '{"director": "George Lucas"}');
EXPLAIN SELECT url FROM swapi WHERE body @> '{"type": "films"}' AND NOT(body @> '{"director": "George Lucas"}');


