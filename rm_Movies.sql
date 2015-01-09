--
-- Drop all tables in the right order.
--
DROP TABLE IF EXISTS rm_Movie2Genre;
DROP TABLE IF EXISTS rm_Genre;
DROP TABLE IF EXISTS rm_Movie;


--
-- Create table for my own movie database
--
DROP TABLE IF EXISTS rm_Movie;
CREATE TABLE rm_Movie
(
    id INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
    added TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    title VARCHAR(100) NOT NULL,
    year INT NOT NULL DEFAULT 1900,
    imdb VARCHAR(9),
    youtube VARCHAR(11),
    image VARCHAR(100) DEFAULT NULL,
    plot TEXT,
    price INT NOT NULL DEFAULT 49
);

INSERT INTO rm_Movie (added, title, imdb, youtube, year, image, plot) VALUES
    (DATE_SUB(NOW(), INTERVAL 9 day), 'Pulp fiction', 'tt0110912', 's7EdQ4FqbhY', 1994, 'pulp-fiction', 'The lives of two mob hit men, a boxer, a gangster\'s wife, and a pair of diner bandits intertwine in four tales of violence and redemption.'),
    (DATE_SUB(NOW(), INTERVAL 8 day), 'Kopps', 'tt0339230', 'aJFdePDqKrY', 2003, 'kopps', 'When a small town police station is threatened with shutting down because of too little crime, the police realise that something has to be done...'),
    (DATE_SUB(NOW(), INTERVAL 7 day), 'Die Hard', 'tt0095016', '-qxBXm7ZUTM', 1988, 'die-hard', 'John McClane, officer of the NYPD, tries to save wife Holly Gennaro and several others, taken hostage by German terrorist Hans Gruber during a Christmas party at the Nakatomi Plaza in Los Angeles.'),
    (DATE_SUB(NOW(), INTERVAL 6 day), 'En enda man', 'tt1315981', '-tCxRO67gyk', 2009, 'a-single-man', 'An English professor, one year after the sudden death of his boyfriend, is unable to cope with his typical days in 1960s Los Angeles.'),
    (DATE_SUB(NOW(), INTERVAL 5 day), 'The Internship', 'tt2234155', 'NyfSMnMBGiM', 2013, 'the-internship', 'Two salesmen whose careers have been torpedoed by the digital age find their way into a coveted internship at Google, where they must compete with a group of young, tech-savvy geniuses for a shot at employment.'),
    (DATE_SUB(NOW(), INTERVAL 4 day), 'Frost', 'tt2294629', 'TbQm5doF_Uc', 2013, 'frozen', 'When a princess with the power to turn things into ice curses her home in infinite winter, her sister, Anna teams up with a mountain man, his playful reindeer, and a snowman to change the weather condition.'),
    (DATE_SUB(NOW(), INTERVAL 3 day), 'Hundraåringen som klev ut genom fönstret och försvann', 'tt2113681', 'pjiJ1cL3Uss', 2013, 'hundraaringen', 'Dynamite expert Allan Karlsson\'s life, and the unlikely events following his escape from the old folk\'s home on his 100th birthday.'),
    (DATE_SUB(NOW(), INTERVAL 2 day), 'Anger Management', 'tt0305224', 'wTH9CQGy0tQ', 2003, 'anger-management', 'Sandler plays a businessman who is wrongly sentenced to an anger-management program, where he meets an aggressive instructor.'),
    (DATE_SUB(NOW(), INTERVAL 1 day), 'Ocean\'s Eleven', 'tt0240772', 'b_bzUIbE5jo', 2001, 'oceans-eleven', 'Danny Ocean and his eleven accomplices plan to rob three Las Vegas casinos simultaneously.'),
    (NOW(), 'Skyfall', 'tt1074638', '6kw1UVovByw', 2012, 'skyfall', 'Bond\'s loyalty to M is tested when her past comes back to haunt her. Whilst MI6 comes under attack, 007 must track down and destroy the threat, no matter how personal the cost.');


--
-- Add tables for genre
--
DROP TABLE IF EXISTS rm_Genre;
CREATE TABLE rm_Genre
(
    id INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
    name CHAR(20) NOT NULL -- crime, svenskt, college, drama, etc
);

INSERT INTO rm_Genre (name) VALUES 
    ('comedy'), ('romance'), ('college'), ('crime'), ('drama'), ('thriller'),
    ('animation'), ('adventure'), ('family'), ('svenskt'), ('action'), ('horror');


DROP TABLE IF EXISTS rm_Movie2Genre;
CREATE TABLE rm_Movie2Genre
(
    idMovie INT NOT NULL,
    idGenre INT NOT NULL,
    FOREIGN KEY (idMovie) REFERENCES rm_Movie (id),
    FOREIGN KEY (idGenre) REFERENCES rm_Genre (id),
    PRIMARY KEY (idMovie, idGenre)
);

INSERT INTO rm_Movie2Genre (idMovie, idGenre) VALUES
    (1, 4), (1, 5), (1, 6),
    (2, 11), (2, 1), (2, 10),
    (3, 11), (3, 6),
    (4, 5),
    (5, 1),
    (6, 7), (6, 8), (6, 1),
    (7, 8), (7, 1), (7, 10),
    (8, 1),
    (9, 4), (9, 6),
    (10, 11), (10, 8);


DROP VIEW IF EXISTS rm_VMovie;
CREATE VIEW rm_VMovie
AS
SELECT 
    M.*,
    GROUP_CONCAT(G.name) AS genre
FROM rm_Movie AS M
    LEFT OUTER JOIN rm_Movie2Genre AS M2G
        ON M.id = M2G.idMovie
    LEFT OUTER JOIN rm_Genre AS G
         ON M2G.idGenre = G.id
GROUP BY M.id;