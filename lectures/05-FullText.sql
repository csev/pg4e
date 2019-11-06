
-- wget https://www.pg4e.com/lectures/mbox-short.txt

CREATE TABLE mbox (id SERIAL, line TEXT);

-- E'\007' is the BEL character and not in the data so each row is one column
\copy mbox FROM 'mbox-short.txt' with delimiter E'\007';

\copy mbox (line) FROM PROGRAM 'wget -q -O - "$@" https://www.pg4e.com/lectures/mbox-short.txt' with delimiter E'\007';

SELECT substring(line, ' (.+@[^ ]+) ') FROM mbox WHERE line ~ '^From ';

-- Indeses are about WHERE clauses
CREATE INDEX mbox_r ON mbox (substring(line, ' (.+@[^ ]+) '));

SELECT id FROM mbox WHERE substring(line, ' (.+@[^ ]+) ') = 'zqian@umich.edu';
SELECT id FROM mbox WHERE substring(line, ' (.+@[^ !]+) ') = 'zqian@umich.edu';

EXPLAIN ANALYZE SELECT COUNT(*) FROM mbox WHERE substring(line, ' (.+@[^ ]+) ') = 'zqian@umich.edu';
EXPLAIN ANALYZE SELECT COUNT(*) FROM mbox WHERE substring(line, ' (.+@[^ !]+) ') = 'zqian@umich.edu';

SELECT line FROM mbox WHERE line ~ '^From ';
SELECT substring(line, ' (.+@[^ ]+) ') FROM mbox WHERE line ~ '^From ';

SELECT substring(line, ' (.+@[^ ]+) '), count(substring(line, ' (.+@[^ ]+) ')) FROM mbox WHERE line ~ '^From ' GROUP BY substring(line, ' (.+@[^ ]+) ') ORDER BY count(substring(line, ' (.+@[^ ]+) ')) DESC;

SELECT email, count(email) FROM
( SELECT substring(line, ' (.+@[^ ]+) ') AS email FROM mbox WHERE line ~ '^From '
) AS badsub
GROUP BY email ORDER BY count(email) DESC;


