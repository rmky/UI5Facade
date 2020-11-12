-- UP

ALTER TABLE [dbo].[fiori_webapp]
	ADD COLUMN [odata_adapter_class] tinyint NOT NULL AFTER [odata_use_relative_urls]`,
	ADD COLUMN [odata_use_batch_writes] tinyint NOT NULL AFTER [odata_adapter_class]`,
	ADD COLUMN [odata_use_batch_deletes] tinyint NOT NULL AFTER [odata_use_batch_writes]`,
	ADD COLUMN [odata_use_batch_function_imports] tinyint NOT NULL AFTER [odata_use_batch_deletes]`,
	ADD COLUMN [odata_use_relative_urls] tinyint NOT NULL AFTER [odata_use_batch_function_imports]`,
	ADD COLUMN [odata_export_credentials] tinyint NOT NULL AFTER [odata_use_relative_urls]`,
	ADD COLUMN [odata_export_sap_client] tinyint NOT NULL AFTER [odata_export_credentials]`;
	
ALTER TABLE [dbo].[fiori_webapp] ADD  DEFAULT ((0)) FOR [odata_use_batch_writes];
ALTER TABLE [dbo].[fiori_webapp] ADD  DEFAULT ((0)) FOR [odata_use_batch_deletes];
ALTER TABLE [dbo].[fiori_webapp] ADD  DEFAULT ((0)) FOR [odata_use_batch_function_imports];
ALTER TABLE [dbo].[fiori_webapp] ADD  DEFAULT ((1)) FOR [odata_use_relative_urls];
ALTER TABLE [dbo].[fiori_webapp] ADD  DEFAULT ((0)) FOR [odata_export_credentials];
ALTER TABLE [dbo].[fiori_webapp] ADD  DEFAULT ((0)) FOR [odata_export_sap_client];
	
UPDATE [dbo].[fiori_webapp] SET [odata_export_sap_client] = 1;
	
-- DOWN

ALTER TABLE [dbo].[fiori_webapp]
	ADD COLUMN [odata_adapter_class],
	ADD COLUMN [odata_use_batch_writes],
	ADD COLUMN [odata_use_batch_deletes],
	ADD COLUMN [odata_use_batch_function_imports],
	ADD COLUMN [odata_use_relative_urls],
	ADD COLUMN [odata_export_credentials],
	ADD COLUMN [odata_export_sap_client];
