ALTER TABLE `fiori_webapp` ADD `root_url` VARCHAR(50) NULL AFTER `root_page_alias`;
ALTER TABLE `fiori_webapp` ADD `pwa_flag` TINYINT(1) NOT NULL DEFAULT '0' AFTER `ui5_app_control`;