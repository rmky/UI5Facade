-- UP

ALTER TABLE [dbo].[fiori_webapp] ADD  DEFAULT ((0)) FOR [pwa_flag];
ALTER TABLE [dbo].[fiori_webapp] ADD  DEFAULT ((0)) FOR [odata_export_sap_client];

-- DOWN