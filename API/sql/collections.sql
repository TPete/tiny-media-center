CREATE TABLE `collections` (
 `ID` int(11) NOT NULL AUTO_INCREMENT,
 `MOVIE_DB_ID` int(11) NOT NULL,
 `NAME` varchar(200) NOT NULL,
 `OVERVIEW` varchar(2000) DEFAULT NULL,
 PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8