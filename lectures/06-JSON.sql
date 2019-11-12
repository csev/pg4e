
-- Indexing within structured data - Email messages from address

-- wget https://www.pg4e.com/code/gmane.py
-- python3 gmane.py
-- Pulls data and puts it into messages table

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
