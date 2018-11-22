# Creating UI models for Fiori apps

**Quick links**

- [Examples for typical Fiori UI elements](fiori_elements/index.md)
- [Navigating between apps, Launchpads](navigation.md)
- [Building exportable apps](../exporting_fiori_apps/index.md)

UI5 apps are as easy to create as any other UI: just configure the widgets on every page you need and start using them! The generated UIs follow the Fiori 2.0 design guidelines as close as possible. Here are some [UXON snippets for typical Fiori UI elements](fiori_elements/index.md) - this is a good starting point for a new app. 

Whenever a UI page with the OpenUI5Template is opened in a browser, the template automatically generates an app for this page and all it's decendants. This (directly called) page becomes the root of this app. All pages beneath it are concidered views. The same goes for any pages linked within the app's hierarchy. To navigate between apps, you can use the nav menu of the launchpad or create your own [custom app selection screens](navigation.md).

You can use any data source for your UI5 app (except for exportable apps, which have [limitations](../exporting_fiori_apps/index.md)). If you are working with an SAP backend, you will probably want to use an OData services - refer to the [documentation of the SAP connector](https://github.com/exface/SapConnector/blob/master/Docs/Connecting_via_oData/index.md) for instructions.

## SAPUI5 vs. OpenUI5

By default, the UI5 template uses one of the most recent stable releases of OpenUI5, which is being shipped with the template. To switch to another version (or SAPUI5) you can copy and modify the template file for your CMS.

When exporting a UI5 app, the path to the UI5 library to be used is part of the export configuration. This path will only be used in the exported version of the app when it is being run stand-alone.