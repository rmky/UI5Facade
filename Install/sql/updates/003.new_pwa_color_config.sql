ALTER TABLE `fiori_webapp` 
	ADD `pwa_background_color` VARCHAR(20) NULL AFTER `pwa_flag`, 
	ADD `pwa_theme_color` VARCHAR(20) NULL AFTER `pwa_background_color`;