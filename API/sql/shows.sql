CREATE TABLE `shows` (
 `ID` int(11) NOT NULL AUTO_INCREMENT,
 `CATEGORY` varchar(100) NOT NULL,
 `TITLE` varchar(100) NOT NULL,
 `FOLDER` varchar(100) NOT NULL,
 `TVDB_ID` int(11) DEFAULT NULL,
 PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8