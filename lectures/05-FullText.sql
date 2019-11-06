
-- wget https://www.pg4e.com/code/gmane.py
-- python3 game.py
-- Pulls data and puts it into messages table

-- CREATE TABLE IF NOT EXISTS messages
--    (id SERIAL, email TEXT, sent_at TIMESTAMPTZ,
--     subject TEXT, headers TEXT, body TEXT)

SELECT substring(headers, '\nFrom: [^\n]*<([^>]*)') FROM messages LIMIT 100;

CREATE INDEX messages_f ON messages (substring(headers, '\nFrom: [^\n]*<([^>]*)'));

SELECT sent_at FROM messages WHERE substring(headers, '\nFrom: [^\n]*<([^>]*)') = 'john@caret.cam.ac.uk';

--- Yes it would be nice not to have to place that expression over and over :)
EXPLAIN ANALYZE SELECT sent_at FROM messages WHERE substring(headers, '\nFrom: [^\n]*<([^>]*)') = 'john@caret.cam.ac.uk';

ALTER TABLE messages ADD COLUMN sender TEXT;

UPDATE messages SET sender=substring(headers, '\nFrom: [^\n]*<([^>]*)');

SELECT substring(headers, '\nLines: ([0-9]*)') FROM messages LIMIT 100;
SELECT AVG(substring(headers, '\nLines: ([0-9]*)')::integer) FROM messages;

