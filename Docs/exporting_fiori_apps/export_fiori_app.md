# Export Fiori App

PowerUI supports the feature to export UI5 apps, that use OData2 services as data source, as a Fiori Webapp. Those exported apps then work as standalone apps without the need of PowerUI.

## Export requirements
The app that should be exported has to meet some requirements.

For now only CRUD operations and function imports are supported. Also make sure tables in the app views do not have footers as those are not supported currently.

## How to export
To Export an existing Power UI App as a Fiori App go to `Menu->Administration->SAP Fiori Webapps`.

The pages shows an overview of the already existing Fiori webapp export projects on the machine. To create a new project click the `New` button.
The opening window shows the configuration for that new app export with the following important configurations:

- `Project Name` - name of the project as it appears in the list of Fiori Webapps projects
- `Root Page` - desired landing page for the exported app
- `Export Folder` - folder the app will get exported to, default is `export/fiori/[#app_id#]`
- `Current Version` - version of the exported app, default is `1.0.0`
- `Built on` - date when the app got exported the last time, is filled automaticaly
- `Min. UI5 version required` - TODO , default is `1.70`
- `UI5 Source URI` - uri to get the UI5 version from, default is: `https://openui5.hana.ondemand.com/resources/sap-ui-core.js`, if you want to use an older version of UI5 you can add the desired version to the uri, like: `https://openui5.hana.ondemand.com/1.68.0/resources/sap-ui-core.js`
- `UI5 Theme` - UI5 theme that should be used, default is `sap_belize` 
- `UI5 App Control` UI5 app control that should be used, default is `sap.m.App (stand-alone)`

All other configurations should be left at their default value.

It is possible to export the app so it uses relative connection paths, instead of absolut ones, to access OData2 services. To enable that feature you have to change the following option:
- `WEBAPP_EXPORT.MANIFEST.DATASOURCES_USE_RELATIVE_URLS`

The option is located in the configuration json file `exface.UI5Facade.config.json`. Setting it to true will enable relative paths, setting it to of will enable absolut paths.

After creating the project export the app by clicking the `Export` button. The app will be exported to the folder given in the `Export folder` option.

To deploy the exported app on a NetWeaver see [Deploy an exported app on ABAP NetWeaver Gateway](deploy_on_netweaver.md).

## How the export works
All pages that belong to the same app as the  `Root Page` get exported and therefore are accessible in the exported app.
The export changes the default server adapter, which uses PowerUI to communicate with the OData2 services, to the `OData2ServerAdapter`. This server adapter communicates directly with the OData2 services, without the need of PowerUI.
The export also disables the global actions of the datatoolbar (`ExportCSV, ExportCSV, FavoritesAdd, ObjectBasketAdd`) as those are not supported by the `OData2ServerAdapter`.

For more information on how the `OData2ServerAdapter` works see [OData2ServerAdapter](odata2serveradapter.md).


