-- registro de contenido a enviar, fecha que se inici� el env�o
CREATE TABLE `mailer_content` (
`id` int(1) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`active` int(1) NOT NULL DEFAULT 1 ,
`mail` int(20) NOT NULL ,
`subject` TEXT NOT NULL,
`content` LONGTEXT NOT NULL,
`datetime` timestamp default CURRENT_TIMESTAMP
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci COMMENT = 'Contenido a enviar';

-- Tabla para marcar los enviados
CREATE TABLE `mailer_send` (
  `id` SERIAL NOT NULL auto_increment,
  `user` varchar(50) collate utf8_general_ci NOT NULL,
  `email` varchar(256) collate utf8_general_ci NOT NULL,
  `name` varchar(100) COLLATE utf8_general_ci NOT NULL,
  `datetime` timestamp default CURRENT_TIMESTAMP,
  `sended` int(1) default NULL,
  `error` text collate utf8_general_ci
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci COMMENT = 'Destinatarios pendientes y realizados';

-- alter
ALTER TABLE `mailer_content` ADD `blocked` INT( 1 ) NULL;

