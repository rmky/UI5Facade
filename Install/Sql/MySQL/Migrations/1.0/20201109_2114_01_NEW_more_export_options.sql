-- UP

ALTER TABLE `fiori_webapp`
	ADD COLUMN `odata_export_credentials` TINYINT(1) NOT NULL DEFAULT '0' AFTER `odata_use_relative_urls`,
	ADD COLUMN `odata_export_sap_client` TINYINT(1) NOT NULL DEFAULT '0' AFTER `odata_export_credentials`;
	
ALTER TABLE `fiori_webapp` SET `odata_export_sap_client` = 1;
	
-- DOWN

ALTER TABLE `fiori_webapp`
	DROP COLUMN `odata_export_credentials`,
	DROP COLUMN `odata_export_sap_client`;
