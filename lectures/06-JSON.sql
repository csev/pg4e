
-- Using Python

-- Get a book from Gutenberg

-- wget http://www.gutenberg.org/cache/epub/19337/pg19337.txt

-- wget https://www.pg4e.com/code/loadbook.py
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

