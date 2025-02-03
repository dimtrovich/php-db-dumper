DROP TABLE IF EXISTS `countries`;
CREATE TABLE `countries` (`id` integer primary key AUTOINCREMENT, `name` varchar(100) default NULL, `currency` varchar(100) default NULL);
INSERT INTO `countries` (`name`,`currency`) VALUES ("Turkey","$18.43"), ("Philippines","$73.56"), ("Poland","$5.71"), ("Turkey","$2.73"), ("United States","$76.27");
