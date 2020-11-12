-- UP

ALTER TABLE [dbo].[fiori_webapp]
	ADD 
		[odata_adapter_class] tinyint NOT NULL,
		[odata_use_batch_writes] tinyint NOT NULL CONSTRAINT odata_use_batch_writes_default DEFAULT ((0)),
		[odata_use_batch_deletes] tinyint NOT NULL  CONSTRAINT odata_use_batch_deletes_default DEFAULT ((0)),
		[odata_use_batch_function_imports] tinyint NOT NULL  CONSTRAINT odata_use_batch_function_imports_default DEFAULT ((0)),
		[odata_use_relative_urls] tinyint NOT NULL CONSTRAINT odata_use_relative_urls_default DEFAULT ((0)),
		[odata_export_credentials] tinyint NOT NULL CONSTRAINT odata_export_credentials_default DEFAULT ((0)),
		[odata_export_sap_client] tinyint NOT NULL CONSTRAINT odata_export_sap_client_default DEFAULT ((0));

UPDATE [dbo].[fiori_webapp] SET [odata_export_sap_client] = 1;
	
-- DOWN

ALTER TABLE [dbo].[fiori_webapp]
	DROP
		CONSTRAINT odata_use_batch_writes_default,
		CONSTRAINT odata_use_batch_deletes_default,
		CONSTRAINT odata_use_batch_function_imports_default,
		CONSTRAINT odata_use_relative_urls_default,
		CONSTRAINT odata_export_credentials_default,
		CONSTRAINT odata_export_sap_client_default,
		COLUMN [odata_adapter_class],
		COLUMN [odata_use_batch_writes],
		COLUMN [odata_use_batch_deletes],
		COLUMN [odata_use_batch_function_imports],
		COLUMN [odata_use_relative_urls],
		COLUMN [odata_export_credentials],
		COLUMN [odata_export_sap_client];
