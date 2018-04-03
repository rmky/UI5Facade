# Routing in the OpenUI5 template

## Generic Webapp

As long as the app runs on the plattform (is not exported), any landing page acts as a UI5 web app. 

This means, that the first time you load a page, a shell and an app are created and loaded into the browser. All subsequent navivgation just loads views via the plattform API unless the user actually presses a link to another page forcing the browser to reload everything - when and how this happens depends on the CMS template used. However, in most cases, all children of a page will automatically become views, that will be loaded asynchronously.

The root page (i.e. the entry point of the user) becomes part of the URL and remains there even if other pages are loaded as views. So, if the URL is copied or bookmarked, it will allways lead to exactly the same app.

### manifest.json

### component.js

## Views

Once the app is loaded, the regular UI5 router is used to pull in the actual content. Since the internal routing in UI5 works via view names, these are gereated in such a way, that view requests result in an API call to the UI5 template. 

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

In the code, these views are accessible via

```
this.getRouter().navTo('pageVendor.pageApp.PageAlias.Widget_id');
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