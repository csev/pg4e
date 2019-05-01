Python for Everybody Database Handout

https://www.pg4e.com/lectures/02-Database-Design-Many-to-Many.txt

sudo -u postgres psql postgres
\l       -- list databases
-- Should already be done: CREATE USER pg4e WITH PASSWORD 'secret';
CREATE DATABASE music WITH OWNER 'pg4e' ENCODING 'UTF8';
\q       -- quit

psql music pg4e

\dt      -- List relations (tables)

CREATE TABLE artist (
  id SERIAL,
  name VARCHAR(128) UNIQUE,
  PRIMARY KEY(id)
);

CREATE TABLE album (
  id SERIAL,
  title VARCHAR(128) UNIQUE,
  artist_id INTEGER REFERENCES artist(id) ON DELETE CASCADE,
  PRIMARY KEY(id)
);

CREATE TABLE genre (
  id SERIAL,
  name VARCHAR(128) UNIQUE,
  PRIMARY KEY(id)
);

CREATE TABLE track (
    id SERIAL,
    title VARCHAR(128),
    len INTEGER, rating INTEGER, count INTEGER,
    album_id INTEGER REFERENCES album(id) ON DELETE CASCADE,
    genre_id INTEGER REFERENCES genre(id) ON DELETE CASCADE,
    UNIQUE(title, album_id),
    PRIMARY KEY(id)
);

-- Describe table
\d track

INSERT INTO artist (name) VALUES ('Led Zeppelin');
INSERT INTO artist (name) VALUES ('AC/DC');

INSERT INTO album (title, artist_id) VALUES ('Who Made Who', 2);
INSERT INTO album (title, artist_id) VALUES ('IV', 1);

INSERT INTO genre (name) VALUES ('Rock');
INSERT INTO genre (name) VALUES ('Metal');

INSERT INTO track (title, rating, len, count, album_id, genre_id) 
    VALUES ('Black Dog', 5, 297, 0, 2, 1) ;
INSERT INTO track (title, rating, len, count, album_id, genre_id) 
    VALUES ('Stairway', 5, 482, 0, 2, 1) ;
INSERT INTO track (title, rating, len, count, album_id, genre_id) 
    VALUES ('About to Rock', 5, 313, 0, 1, 2) ;
INSERT INTO track (title, rating, len, count, album_id, genre_id) 
    VALUES ('Who Made Who', 5, 207, 0, 1, 2) ;

SELECT album.title, artist.name FROM album JOIN artist 
    ON album.artist_id = artist.id;

SELECT album.title, album.artist_id, artist.id, artist.name 
    FROM album INNER JOIN artist ON album.artist_id = artist.id;

SELECT track.title, track.genre_id, genre.id, genre.name 
    FROM track CROSS JOIN genre;

SELECT track.title, genre.name FROM track JOIN genre 
    ON track.genre_id = genre.id;

SELECT track.title, artist.name, album.title, genre.name 
FROM track 
    JOIN genre ON track.genre_id = genre.id 
    JOIN album ON track.album_id = album.id 
    JOIN artist ON album.artist_id = artist.id;
 
DELETE FROM genre WHERE name='Metal';

Many-Many Relationship

CREATE TABLE student (
  id SERIAL,
  name VARCHAR(128),
  email VARCHAR(128) UNIQUE,
  PRIMARY KEY(id)
) ;

CREATE TABLE course (
  id SERIAL,
  title VARCHAR(128) UNIQUE,
  PRIMARY KEY(id)
) ;

-- We could put 'id SERIAL' in this table, but it is not essential
CREATE TABLE member (
    student_id INTEGER REFERENCES student(id) ON DELETE CASCADE,
    course_id INTEGER REFERENCES course(id) ON DELETE CASCADE,
	role        INTEGER,
    PRIMARY KEY (student_id, course_id)
) ;

INSERT INTO student (name, email) VALUES ('Jane', 'jane@tsugi.org');
INSERT INTO student (name, email) VALUES ('Ed', 'ed@tsugi.org');
INSERT INTO student (name, email) VALUES ('Sue', 'sue@tsugi.org');

INSERT INTO course (title) VALUES ('Python');
INSERT INTO course (title) VALUES ('SQL');
INSERT INTO course (title) VALUES ('PHP');

INSERT INTO member (student_id, course_id, role) VALUES (1, 1, 1);
INSERT INTO member (student_id, course_id, role) VALUES (2, 1, 0);
INSERT INTO member (student_id, course_id, role) VALUES (3, 1, 0);

INSERT INTO member (student_id, course_id, role) VALUES (1, 2, 0);
INSERT INTO member (student_id, course_id, role) VALUES (2, 2, 1);

INSERT INTO member (student_id, course_id, role) VALUES (2, 3, 1);
INSERT INTO member (student_id, course_id, role) VALUES (3, 3, 0);

SELECT student.name, member.role, course.title
  FROM student 
  JOIN member ON member.student_id = student.id 
  JOIN course ON member.course_id = course.id
  ORDER BY course.title, member.role DESC, student.name;


