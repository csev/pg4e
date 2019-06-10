
-- Start fresh - Cascade deletes it all

DELETE FROM account;

INSERT INTO account(email) VALUES 
('ed@umich.edu'), ('sue@umich.edu'), ('sally@umich.edu');

INSERT INTO post (title, content, account_id) VALUES
( 'Dictionaries', 'Are fun', (SELECT id FROM account WHERE email='sally@umich.edu' )),
( 'BeautifulSoup', 'Has a complex API', (SELECT id FROM account WHERE email='sue@umich.edu' )),
( 'Many to Many', 'Is elegant', (SELECT id FROM account WHERE email='sue@umich.edu' ));

INSERT INTO comment (content, post_id, account_id) VALUES
( 'I agree', 
    (SELECT id FROM post WHERE title='Dictionaries'),
    (SELECT id FROM account WHERE email='ed@umich.edu' )),
( 'Especially for counting', 
    (SELECT id FROM post WHERE title='Dictionaries'),
    (SELECT id FROM account WHERE email='sue@umich.edu' )),
( 'And I don''t understand why', 
    (SELECT id FROM post WHERE title='BeautifulSoup'),
    (SELECT id FROM account WHERE email='sue@umich.edu' )),
( 'Someone should make "EasySoup" or something like that', 
    (SELECT id FROM post WHERE title='BeautifulSoup'),
    (SELECT id FROM account WHERE email='ed@umich.edu' )),
( 'Good idea - I might just do that', 
    (SELECT id FROM post WHERE title='BeautifulSoup'),
    (SELECT id FROM account WHERE email='sally@umich.edu' ))
;


