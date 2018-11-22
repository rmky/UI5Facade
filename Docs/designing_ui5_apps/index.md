# Creating UI models for SAPUI5 and OpenUI5 apps

UI5 apps are as easy to create as any other UI: just configure the widgets on every page you need and start using them!

It is important to understand, that the UI5 template automatically generates an app, whenever you access the URL of any of it's pages from your browser. This (directly called) page becomes the root of this app. All pages beneath this page are concidered views. The same goes for any pages linked within the app's hierarchy. To navigate between apps, you can use the nav menu of the launchpad or create your [custom app selection screens](sub_launchpads.md).

You can use any data source for your UI5 app. If you are working with an SAP backend, you will probably want to use an OData services - refer to the [documentation of the SAP connector](https://github.com/exface/SapConnector/blob/master/Docs/Connecting_via_oData/index.md) for details.

## SAPUI5 vs. OpenUI5

By default, the UI5 template uses one of the most recent stable releases of OpenUI5, which is being shipped with the template. To switch to another version (or SAPUI5) you can copy and modify the template file for your CMS.

When exporting a UI5 app, the path to the UI5 library to be used is part of the export configuration. This path will only be used in the exported version of the app when it is being run stand-alone.