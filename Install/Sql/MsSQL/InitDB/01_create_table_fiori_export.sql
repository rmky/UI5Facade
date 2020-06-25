CREATE TABLE [dbo].[fiori_webapp](
  [oid] [binary](16) NOT NULL,
  [created_on] [datetime2](0) NOT NULL,
  [modified_on] [datetime2](0) NOT NULL,
  [created_by_user_oid] binary(16) NULL,
  [modified_by_user_oid] binary(16) NULL,
  [app_title] [nvarchar](100) NULL,
  [app_subTitle] [nvarchar](100) NULL,
  [app_shortTitle] [nvarchar](100) NULL,
  [app_info] [nvarchar](100) NULL,
  [app_description] [nvarchar](200) NULL,
  [name] [nvarchar](100) NOT NULL,
  [export_folder] [nvarchar](50) NOT NULL,
  [root_page_alias] [nvarchar](128) NOT NULL,
  [root_url] [nvarchar](50) NULL,
  [current_version] [nvarchar](10) NULL,
  [current_version_date] [datetime2](0) NULL,
  [ui5_min_version] [nvarchar](5) NOT NULL,
  [ui5_source] [nvarchar](100) NOT NULL,
  [ui5_theme] [nvarchar](50) NOT NULL,
  [ui5_app_control] [nvarchar](50) NOT NULL,
  [pwa_flag] tinyint NOT NULL,
  [pwa_background_color] [nvarchar](20) NULL,
  [pwa_theme_color] [nvarchar](20) NULL,
  CONSTRAINT [PK_fiori_webapp_oid] PRIMARY KEY CLUSTERED 
(
	[oid] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY];