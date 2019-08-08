CREATE TABLE IF NOT EXISTS `permission` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(15) NOT NULL,
  `read_text_1` tinyint(1) NOT NULL,
  `read_text_2` tinyint(1) NOT NULL,
  `read_text_3` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

INSERT INTO `permission` (`id`, `name`, `read_text_1`, `read_text_2`, `read_text_3`) VALUES
(1, 'administrator', 1, 1, 1),
(2, 'user', 0, 0, 0);

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_permission` int(11) NOT NULL,
  `login` varchar(20) NOT NULL,
  `hash` varchar(32) NOT NULL,
  `time` int(11) NOT NULL,
  `private_key` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;


INSERT INTO `users` (`id`, `id_permission`, `login`, `hash`, `time`, `private_key`) VALUES
(1, 1, 'admin', '202cb962ac59075b964b07152d234b70', 1565283419, '');

CREATE TABLE IF NOT EXISTS `users_remember` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `id_crypt` int(11) NOT NULL,
  `hash` varchar(32) NOT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf32 AUTO_INCREMENT=1 ;