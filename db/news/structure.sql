CREATE TABLE `news` (`id` SERIAL NOT NULL AUTO_INCREMENT ,`title` TINYTEXT NOT NULL ,`url` TINYTEXT NOT NULL ,`order` INT( 11 ) NOT NULL DEFAULT '1',PRIMARY KEY ( `id` )) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci COMMENT = 'Noticias en la cabecera';