-- UP

ALTER TABLE `fiori_webapp`
	ADD COLUMN `odata_adapter_class` VARCHAR(200) NOT NULL AFTER `pwa_theme_color`,
	ADD COLUMN `odata_use_batch_writes` TINYINT NOT NULL DEFAULT 0 AFTER `odata_adapter_class`,
	ADD COLUMN `odata_use_batch_deletes` TINYINT NOT NULL DEFAULT 0 AFTER `odata_use_batch_writes`,
	ADD COLUMN `odata_use_batch_function_imports` TINYINT NOT NULL DEFAULT 0 AFTER `odata_use_batch_deletes`,
	ADD COLUMN `odata_use_relative_urls` TINYINT NOT NULL DEFAULT 1 AFTER `odata_use_batch_function_imports`;

	
-- DOWN

ALTER TABLE `fiori_webapp`
	DROP COLUMN `odata_adapter_class`,
	DROP COLUMN `odata_use_batch_writes`,
	DROP COLUMN `odata_use_batch_deletes`,
	DROP COLUMN `odata_use_batch_function_imports`,
	DROP COLUMN `odata_use_relative_urls`;
