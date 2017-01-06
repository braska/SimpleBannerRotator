CREATE TABLE IF NOT EXISTS `banners` (
`id` int(11) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `width` int(11) unsigned DEFAULT NULL,
  `height` int(11) unsigned DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `target_blank` tinyint(1) NOT NULL DEFAULT '1',
  `priority` int(11) NOT NULL DEFAULT '0',
  `type` enum('image','flash','html') NOT NULL,
  `content` text,
  `max_impressions` int(10) unsigned DEFAULT NULL,
  `start_date` int(11) unsigned DEFAULT NULL,
  `end_date` int(11) unsigned DEFAULT NULL,
  `url_mask` varchar(255) DEFAULT NULL,
  `advertiser_id` int(11) unsigned DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `archived` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `banners_zones` (
  `banner_id` int(11) unsigned NOT NULL,
  `zone_id` int(11) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `users` (
`id` int(11) unsigned NOT NULL,
  `email` varchar(254) NOT NULL,
  `fname` varchar(255) NOT NULL,
  `lname` varchar(255) DEFAULT '',
  `thname` varchar(255) DEFAULT '',
  `password` varchar(64) NOT NULL,
  `type` enum('admin','advertiser') NOT NULL,
  `confirmed` tinyint(1) DEFAULT NULL,
  `logins` int(10) unsigned DEFAULT '0',
  `last_login` int(10) unsigned DEFAULT NULL,
  `last_login_ip` varchar(255) DEFAULT NULL,
  `created` int(10) DEFAULT NULL,
  `created_ip` varchar(255) DEFAULT NULL,
  `last_edit` int(10) DEFAULT NULL,
  `last_edit_ip` varchar(255) DEFAULT NULL,
  `last_activity` int(10) DEFAULT NULL,
  `last_activity_ip` varchar(255) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

INSERT INTO `users` (`id`, `email`, `fname`, `lname`, `thname`, `password`, `type`, `confirmed`, `logins`, `last_login`, `last_login_ip`, `created`, `created_ip`, `last_edit`, `last_edit_ip`, `last_activity`, `last_activity_ip`) VALUES
(1, 'test@test.ru', 'Администратор', '', '', 'f829b3fd99bf0c250b59c4668cdf5ae6ba7aaadd7b687279e9772743886b9311', 'admin', 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

CREATE TABLE IF NOT EXISTS `user_tokens` (
`id` int(11) unsigned NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  `user_agent` varchar(40) NOT NULL,
  `token` varchar(40) NOT NULL,
  `created` int(10) unsigned NOT NULL,
  `expires` int(10) unsigned NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `zones` (
`id` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `views` (
`id` int(11) unsigned NOT NULL,
  `date` int(11) NOT NULL,
  `banner_id` int(11) unsigned NOT NULL,
  `zone_id` INT(11) UNSIGNED NOT NULL,
  `clicked` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=319816 DEFAULT CHARSET=utf8;

ALTER TABLE `banners`
 ADD PRIMARY KEY (`id`), ADD KEY `advertiser_id` (`advertiser_id`);

ALTER TABLE `banners_zones`
 ADD PRIMARY KEY (`banner_id`,`zone_id`), ADD KEY `zone_id` (`zone_id`);


ALTER TABLE `users`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `uniq_email` (`email`);


ALTER TABLE `user_tokens`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `uniq_token` (`token`), ADD KEY `fk_user_id` (`user_id`), ADD KEY `expires` (`expires`);


ALTER TABLE `zones`
 ADD PRIMARY KEY (`id`);

ALTER TABLE `views`
 ADD PRIMARY KEY (`id`), ADD KEY `banner_id` (`banner_id`);

ALTER TABLE `banners`
MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;

ALTER TABLE `users`
MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;

ALTER TABLE `user_tokens`
MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;

ALTER TABLE `zones`
MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;

ALTER TABLE `views`
MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;

ALTER TABLE `banners`
ADD CONSTRAINT `banners_ibfk_1` FOREIGN KEY (`advertiser_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;


ALTER TABLE `banners_zones`
ADD CONSTRAINT `banners_zones_ibfk_1` FOREIGN KEY (`banner_id`) REFERENCES `banners` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `banners_zones_ibfk_2` FOREIGN KEY (`zone_id`) REFERENCES `zones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `user_tokens`
ADD CONSTRAINT `user_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `views`
ADD CONSTRAINT `views_ibfk_1` FOREIGN KEY (`banner_id`) REFERENCES `banners` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;