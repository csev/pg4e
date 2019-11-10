
-- wget https://www.pg4e.com/code/gmane.py
-- python3 game.py
-- Pulls data and puts it into messages table

CREATE TABLE docs (id SERIAL, doc TEXT, PRIMARY KEY(id));
INSERT INTO docs (doc) VALUES
('This is SQL and Python and other fun teaching stuff'),
('More people should learn SQL from UMSI'),
('UMSI also teaches Python and also SQL');
SELECT * FROM docs;

INSERT INTO docs (doc) SELECT 'Neon ' || generate_series(10000,20000);

--- https://stackoverflow.com/questions/29419993/split-column-into-multiple-rows-in-postgres

SELECT id, s.keyword AS keyword
FROM   docs AS D, unnest(string_to_array(D.doc, ' ')) s(keyword)
ORDER BY id;

SELECT DISTINCT id, s.keyword AS keyword
FROM   docs AS D, unnest(string_to_array(D.doc, ' ')) s(keyword)
ORDER BY id;

CREATE TABLE docs_gin (
  keyword TEXT,
  doc_id INTEGER REFERENCES docs(id) ON DELETE CASCADE
);

INSERT INTO docs_gin (doc_id, keyword) 
SELECT DISTINCT id, s.keyword AS keyword
FROM   docs AS D, unnest(string_to_array(D.doc, ' ')) s(keyword)
ORDER BY id;

SELECT * FROM docs_gin ORDER BY doc_id;

SELECT doc FROM docs AS D
JOIN docs_gin AS G ON D.id = G.doc_id
WHERE G.keyword = 'UMSI';

SELECT doc FROM docs AS D
JOIN docs_gin AS G ON D.id = G.doc_id
WHERE G.keyword in ('SQL', 'Python');

SELECT DISTINCT doc FROM docs AS D
JOIN docs_gin AS G ON D.id = G.doc_id
WHERE G.keyword in ('SQL', 'Python');

-- This is a basic Inverted Index
-- Not quite right:
-- Select version();   -- PostgreSQL 9.6.7
-- https://habr.com/en/company/postgrespro/blog/448746/
-- create index gin1 on docs using gin(string_to_array(doc, ' ')  _text_ops);
-- select doc from docs where '{SQL}' <@ string_to_array(doc, ' ');
-- explain select doc from docs where '{SQL}' <@ string_to_array(doc, ' ');

-- SELECT am.amname AS index_method, opc.opcname AS opclass_name FROM pg_am am, pg_opclass opc WHERE opc.opcmethod = am.oid ORDER BY index_method, opclass_name;

-- But we can have a smaller index if we know that we are dealing with language
-- (1) Ignore the case of words 
-- (2) Don't index stop words that we won't search for

CREATE TABLE stop_words (word TEXT unique);
INSERT INTO stop_words (word) VALUES ('is'), ('this'), ('and');

SELECT DISTINCT id, lower(s.keyword) AS keyword
FROM   docs AS D, unnest(string_to_array(D.doc, ' ')) s(keyword)
WHERE s.keyword NOT IN (SELECT word FROM stop_words)
ORDER BY id;

DELETE FROM docs_gin;

INSERT INTO docs_gin (doc_id, keyword) 
SELECT DISTINCT id, lower(s.keyword) AS keyword
FROM   docs AS D, unnest(string_to_array(D.doc, ' ')) s(keyword)
WHERE s.keyword NOT IN (SELECT word FROM stop_words)
ORDER BY id;

SELECT * FROM docs_gin;

SELECT DISTINCT doc FROM docs AS D
JOIN docs_gin AS G ON D.id = G.doc_id
WHERE G.keyword = lower('UMSI');

SELECT DISTINCT doc FROM docs AS D
JOIN docs_gin AS G ON D.id = G.doc_id
WHERE G.keyword in (lower('fun'), lower('people'));

SELECT DISTINCT id, doc FROM docs AS D
JOIN docs_gin AS G ON D.id = G.doc_id
WHERE G.keyword in (lower('SQL'), lower('Python'));

-- We can make the index even smaller 
--- (3) Only store the "stems" of words

CREATE TABLE docs_stem (word TEXT, stem TEXT);
INSERT INTO docs_stem (word, stem) VALUES 
('teaching', 'teach'), ('teaches', 'teach');

SELECT id, keyword FROM (
SELECT DISTINCT id, lower(s.keyword) AS keyword
FROM   docs AS D, unnest(string_to_array(D.doc, ' ')) s(keyword)
) AS X;

SELECT id, keyword, stem FROM (
SELECT DISTINCT id, lower(s.keyword) AS keyword
FROM   docs AS D, unnest(string_to_array(D.doc, ' ')) s(keyword)
) AS K 
LEFT JOIN docs_stem AS S ON K.keyword = S.word;

SELECT id, 
CASE WHEN stem IS NOT NULL THEN stem ELSE keyword END,
keyword, stem 
FROM (
SELECT DISTINCT id, lower(s.keyword) AS keyword
FROM   docs AS D, unnest(string_to_array(D.doc, ' ')) s(keyword)
) AS K 
LEFT JOIN docs_stem AS S ON K.keyword = S.word;

DELETE FROM docs_gin;

INSERT INTO docs_gin (doc_id, keyword) 
SELECT id, 
CASE WHEN stem IS NOT NULL THEN stem ELSE keyword END
FROM (
SELECT DISTINCT id, lower(s.keyword) AS keyword
FROM   docs AS D, unnest(string_to_array(D.doc, ' ')) s(keyword)
) AS K 
LEFT JOIN docs_stem AS S ON K.keyword = S.word;

SELECT * FROM docs_gin;

-- Like Python null/false coalescing
-- x = stem or 'teaching'
SELECT COALESCE((SELECT stem FROM docs_stem WHERE word=lower('teaching')), lower('teaching'));
SELECT COALESCE((SELECT stem FROM docs_stem WHERE word=lower('SQL')), lower('SQL'));

SELECT DISTINCT doc FROM docs AS D
JOIN docs_gin AS G ON D.id = G.doc_id
WHERE G.keyword = COALESCE((SELECT stem FROM docs_stem WHERE word=lower('SQL')), lower('SQL'));

SELECT DISTINCT doc FROM docs AS D
JOIN docs_gin AS G ON D.id = G.doc_id
WHERE G.keyword = COALESCE((SELECT stem FROM docs_stem WHERE word=lower('teaching')), lower('teaching'));

-- There is an easier way :)

SELECT to_tsvector('english', 'This is SQL and Python and other fun teaching stuff');
SELECT to_tsvector('english', 'More people should learn SQL from UMSI');
SELECT to_tsvector('english', 'UMSI also teaches Python and also SQL');

SELECT to_tsquery('english', 'teaching');
SELECT to_tsquery('english', 'SQL');

-- CREATE TABLE IF NOT EXISTS messages

-- CREATE TABLE IF NOT EXISTS messages
--    (id SERIAL, email TEXT, sent_at TIMESTAMPTZ,
--     subject TEXT, headers TEXT, body TEXT)

SELECT substring(headers, '\nFrom: [^\n]*<([^>]*)') FROM messages LIMIT 10;

--- Extract from the headers and make a new column
ALTER TABLE messages ADD COLUMN sender TEXT;
UPDATE messages SET sender=substring(headers, '\nFrom: [^\n]*<([^>]*)');

CREATE INDEX messages_from ON messages (substring(headers, '\nFrom: [^\n]*<([^>]*)'));

SELECT sender,subject FROM messages WHERE substring(headers, '\nFrom: [^\n]*<([^>]*)') = 'john@caret.cam.ac.uk';

EXPLAIN ANALYZE SELECT sent_at FROM messages WHERE substring(headers, '\nFrom: [^\n]*<([^>]*)') = 'john@caret.cam.ac.uk';

SELECT subject, substring(headers, '\nLines: ([0-9]*)') AS lines FROM messages LIMIT 100;
SELECT AVG(substring(headers, '\nLines: ([0-9]*)')::integer) FROM messages;

-- A variable - actually more like a macro - escaping is tricky
\set zap 'substring(headers, \'\\nFrom: [^\\n]*<([^>]*)\')'
DROP INDEX messages_from;
EXPLAIN ANALYZE SELECT :zap FROM messages where :zap = 'john@caret.cam.ac.uk';
CREATE INDEX messages_from ON messages (:zap);
EXPLAIN ANALYZE SELECT :zap FROM messages where :zap = 'john@caret.cam.ac.uk';

SELECT to_tsvector('english', body) FROM messages LIMIT 1;

SELECT to_tsquery('english', 'monday');

SELECT to_tsquery('english', 'neon') @@ to_tsvector('english', body) FROM messages LIMIT 1;

SELECT to_tsquery('english', 'monday') @@ to_tsvector('english', body) FROM messages LIMIT 1;

-- https://www.postgresql.org/docs/9.1/textsearch-indexes.html
CREATE INDEX messages_gin ON messages USING gin(to_tsvector('english', body));
DROP INDEX messages_gin;

SELECT subject, sender FROM messages WHERE to_tsquery('english', 'monday') @@ to_tsvector('english', body);
EXPLAIN ANALYZE SELECT subject, sender FROM messages WHERE to_tsquery('english', 'monday') @@ to_tsvector('english', body);

CREATE INDEX messages_gist ON messages USING gist(to_tsvector('english', body));
DROP INDEX messages_gist;

SELECT subject, sender FROM messages WHERE to_tsquery('english', 'monday') @@ to_tsvector('english', body);
EXPLAIN ANALYZE SELECT subject, sender FROM messages WHERE to_tsquery('english', 'monday') @@ to_tsvector('english', body);

-- https://www.postgresql.org/docs/10/functions-textsearch.html
SELECT id, subject, sender FROM messages WHERE to_tsquery('english', 'personal & learning') @@ to_tsvector('english', body);
SELECT id, subject, sender FROM messages WHERE to_tsquery('english', 'learning & personal') @@ to_tsvector('english', body);
SELECT id, subject, sender FROM messages WHERE to_tsquery('english', 'personal <-> learning') @@ to_tsvector('english', body);
SELECT id, subject, sender FROM messages WHERE to_tsquery('english', 'learning <-> personal') @@ to_tsvector('english', body);
SELECT id, subject, sender FROM messages WHERE to_tsquery('english', '! personal & learning') @@ to_tsvector('english', body);

-- plainto_tsquery() Is tolerant of "syntax errors" in the expression
SELECT id, subject, sender FROM messages WHERE to_tsquery('english', '(personal learning') @@ to_tsvector('english', body);
SELECT id, subject, sender FROM messages WHERE plainto_tsquery('english', '(personal learning') @@ to_tsvector('english', body);

-- phraseto_tsquery() implies followed by
SELECT id, subject, sender FROM messages WHERE to_tsquery('english', 'I <-> think') @@ to_tsvector('english', body);
SELECT id, subject, sender FROM messages WHERE phraseto_tsquery('english', 'I think') @@ to_tsvector('english', body);

-- https://www.postgresql.org/docs/12/textsearch-controls.html#TEXTSEARCH-RANKING
SELECT id, subject, sender, 
  ts_rank(to_tsvector('english', body), to_tsquery('english', 'personal & learning')) as ts_rank
FROM messages 
WHERE to_tsquery('english', 'personal & learning') @@ to_tsvector('english', body)
ORDER BY ts_rank DESC;

-- A different ranking algorithm
SELECT id, subject, sender, 
  ts_rank_cd(to_tsvector('english', body), to_tsquery('english', 'personal & learning')) as ts_rank
FROM messages 
WHERE to_tsquery('english', 'personal & learning') @@ to_tsvector('english', body)
ORDER BY ts_rank DESC;

-- Wait for PostgreSQL 11
SELECT id, subject, sender FROM messages WHERE to_tsquery('english', '! personal & learning') @@ to_tsvector('english', body);
SELECT id, subject, sender FROM messages WHERE websearch_to_tsquery('english', '-personal learning') @@ to_tsvector('english', body);


