CREATE TABLE IF NOT EXISTS `collection_parts` (
 `COLLECTION_ID` int(11) NOT NULL,
 `MOVIE_ID` int(11) NOT NULL,
 FOREIGN KEY (`COLLECTION_ID`) 
 	REFERENCES `collections` (`ID`) 
 	ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8