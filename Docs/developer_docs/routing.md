# Routing in the OpenUI5 facade

## Generic Webapp

As long as the app runs on the plattform (is not exported), any landing page acts as a UI5 web app. 

This means, that the first time you load a page, a shell and an app are created and loaded into the browser. All subsequent navivgation just loads views via the plattform API unless the user actually presses a link to another page forcing the browser to reload everything - when and how this happens depends on the CMS facade used. However, in most cases, all children of a page will automatically become views, that will be loaded asynchronously.

The root page (i.e. the entry point of the user) becomes part of the URL and remains there even if other pages are loaded as views. So, if the URL is copied or bookmarked, it will allways lead to exactly the same app.

### manifest.json

### component.js

## Views

Once the app is loaded, the regular UI5 router is used to pull in the actual content. Since the internal routing in UI5 works via view names, these are gereated in such a way, that view requests result in an API call to the UI5 facade. 

### Page views

The view corresponding to a UI page can be accessed via the following URL where "entry.point.alias" corresponds to the webapp root as described above

```
api/ui5/webapp/entry.point.alias/view/pageVendor/pageApp/PageAlias.view.js
```

In the code, these views are accessible via

```
this.getRouter().navTo('pageVendor.pageApp.PageAlias');
```

### Widget views

Any widget within the page can be represented as a view itself. This is used for lazy loading - in particular for dialogs. These views reside in a virtual subfolder named after the page: e.g.

```
api/ui5/webapp/entry.point.alias/view/pageVendor/pageApp/PageAlias/Widget_id.view.js
```

In the code of a controller, these views are accessible via

```
this.navTo('pageVendor.pageApp.PageAlias.Widget_id');
```

### Static views

Apart from dynamically generated views, there are the following static views, that do not depend on the UI configuration:

- "App" view
- "NotFound" view
- "Offline" view

These views reside directly in the "view" virtual folder and can be referenced by the above names (e.g. <code>this.getRouter().navTo('Offline')</code>).

## Controllers

Controllers are generated for each view and use the same routing patterns but under <code>entry.point.alias/controller/</code> instead of <code>entry.point.alias/view/</code>.

### BaseController

## Combined ViewControllers

In addition to regular views and controllers, the facade API can also supplie both combined into a single JS document:

```
api/ui5/webapp/entry.point.alias/viewcontroller/pageVendor/pageApp/PageAlias.viewcontroller.js
```

or in the case of a specific widget:

```
api/ui5/webapp/entry.point.alias/viewcontroller/pageVendor/pageApp/PageAlias/Widget_id.viewcontroller.js
```

## Working with the UI5 Router

Each app uses an instance of <code>sap.m.routing.Router</code> for routing, which is accessible via <code>this.getRouter()</code> from any controller (inherited from <code>Basecontroller</code>.

The router is initially empty (except for built-in routes to the static views - see above). Routes to pages and widgets are added dynamically by button handlers in the controller. This is especially important for complex data management apps, that often have deeply nested dialogs (e.g. Administration > Metamodel > Objects > Dialog "edit object" > Dialog "edit attribute" > Dialog "show related object" > ...): in this case, the generation of a predefined routing configuration would become overly complex or even impossible if recursions lead to an infinite depth.

### Route names

The following routing configuration (in <code>manifest.json</code>) shows some examples for route names and corresponding targets.

```
"routing": {
	"config": {},
	"routes": [
		{
			"pattern": "",
			"name": "rootVendor.rootApp.rootPageAlias",
			"target": "rootVendor.rootApp.rootPageAlias"
		},
		{
			"pattern": "pageVendor.pageApp.Page1Alias/:params:",
			"name": "pageVendor.pageApp.Page1Alias",
			"target": "pageVendor.pageApp.PageAlias"
		},
		{
			"pattern": "pageVendor.pageApp.Page1Alias.Widget_Id/:params:",
			"name": "pageVendor.pageApp.Page1Alias.Widget_Id",
			"target": "pageVendor.pageApp.Page1Alias.Widget_Id"
		}
	],
	"targets": {
		"rootVendor.rootApp.rootPageAlias": {
		   "viewId": "rootVendor.rootApp.rootPageAlias",
		   "viewName": "rootVendor.rootApp.rootPageAlias",
		   "viewLevel" : 1
		},
		"pageVendor.pageApp.Page1Alias": {
		   "viewId": "pageVendor.pageApp.Page1Alias",
		   "viewName": "pageVendor.pageApp.Page1Alias",
		   "viewLevel" : 2
		},
		"pageVendor.pageApp.Page1Alias.Widget_Id": {
		   "viewId": "pageVendor.pageApp.Page1Alias.Widget_Id",
		   "viewName": "pageVendor.pageApp.Page1Alias.Widget_Id",
		   "viewLevel" : 3
		},
		"notFound": {
		   "viewId": "notFound",
		   "viewName": "NotFound",
		   "transition": "show"
		},
		"offline": {
		   "viewId": "offline",
		   "viewName": "Offline",
		   "transition": "show"
		}
	}
}
```

Basically every view has a default route matching it's view name. Thus, you can call <code>this.getRouter().getRoute("vendor.app.PageAlias")</code> in a controller to get the route to a specific page. Keep in mind, though, that routes are only created when the view is loaded via <code>BaseController.navTo()</code> (see below), so when getting a route, you must be sure, it was already loaded!

Every route also has a generic optional parameter <code>params</code>, which accepts a JSON encoded object with parameters like data, prefill, etc.

### Custom async view loader

The <code>BaseController</code> provides a custom resource loader, that preloads view and controller files before the router performs the actual navigation. This was neccessary because UI5 uses synchronous AJAX requests to fetch the files and these are incompatible with offline PWAs. 

### Routing functions

Routing can be performed programmatically via nav-functions of the <code>BaseController</code>:

```
this.navTo('pageVendor.pageApp.PageAlias.Widget_id');
```

This method should be generally preferred to <code>this.getRouter().navTo()</code> becuase it makes sure all required resources are loaded asynchronously.

### Customizing the AJAX calls

The <code>navTo</code> method of the <code>BaseController</code> allows a lot of customization for the AJAX request used to load the view from the server: it's third parameter accepts the same settings object, your would pass to <code>jQuery.ajax()</code>. Here is an example controller code:

```
this.navTo('pageVendor.pageApp.PageAlias', '', {
	method: 'POST',
	headers: [/* ... */]
});
```

### Passing data

Every route has a generic optional parameter <code>params</code>, that accepts a JSON encoded object with any kind of parameters. Thes parameter object is automatically made available in the view model and, thus, the target controller via <code>this.getView().getModel('view').getProperty('_route/params')</code>. This way, you can allways access data, prefill and other parameters passed when the view was loaded.

### Modifying/extending fetched views

Here is an example, how an event handler can be attached to a view after it had been loaded:

```
	onPressButton : function (oEvent){
		var jqXHR = this.navTo("exface.testapp.ajax-example");
		if (jqXHR) {
			var sViewId = this.getViewId(this.getViewName("exface.testapp.ajax-example"));
			jqXHR.done(function(){
				var oView = sap.ui.getCore().byId(sViewId);
				oView.addEventDelegate({
					onBeforeHide: function(oEvent) {
						console.log("Leaving view " + oView.getViewName() + "!");
					}
				});
			});
		}
	}
```

Be careful: this will only work for views loaded dynamically. While navigation would also work with preloaded views, this type of modification will not!

## Preloading resources

TODO

## Routing in exported Fiori apps

In exported apps, all views and controllers required for the app are allways exported as JS files and als packed into <code>Component-preload.js</code>. Since the app structre is fixed, there is no need for dynamic routing. All routes are statically configured in the <code>manifest.json</code> and all code is preloaded when the app is launched - no async loading, no combined viewcontrollers, etc.
