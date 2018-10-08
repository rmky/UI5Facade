/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2018 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.predefine('sap/ui/suite/library',['jquery.sap.global','sap/ui/core/Core','sap/ui/core/library'],function(q,C){"use strict";sap.ui.getCore().initLibrary({name:"sap.ui.suite",version:"1.56.6",dependencies:["sap.ui.core"],types:["sap.ui.suite.TaskCircleColor"],interfaces:[],controls:["sap.ui.suite.TaskCircle","sap.ui.suite.VerticalProgressIndicator"],elements:[]});var t=sap.ui.suite;t.TaskCircleColor={Red:"Red",Yellow:"Yellow",Green:"Green",Gray:"Gray"};return t;});
sap.ui.require.preload({
	"sap/ui/suite/manifest.json":'{"_version":"1.9.0","sap.app":{"id":"sap.ui.suite","type":"library","embeds":[],"applicationVersion":{"version":"1.56.6"},"title":"SAP UI library: sap.ui.suite (by SAP, Author)","description":"SAP UI library: sap.ui.suite (by SAP, Author)","ach":"CA-UI5-CTR","resources":"resources.json","offline":true},"sap.ui":{"technology":"UI5","supportedThemes":["base","sap_hcb"]},"sap.ui5":{"dependencies":{"minUI5Version":"1.56","libs":{"sap.ui.core":{"minVersion":"1.56.6"}}},"library":{"i18n":false,"content":{"controls":["sap.ui.suite.TaskCircle","sap.ui.suite.VerticalProgressIndicator"],"elements":[],"types":["sap.ui.suite.TaskCircleColor"],"interfaces":[]}}}}'
},"sap/ui/suite/library-h2-preload"
);
sap.ui.loader.config({depCacheUI5:{
"sap/ui/suite/QuickViewUtils.js":["jquery.sap.global.js","sap/ui/core/Control.js","sap/ui/core/Element.js"],
"sap/ui/suite/TaskCircle.js":["jquery.sap.global.js","sap/ui/core/Control.js","sap/ui/core/EnabledPropagator.js","sap/ui/suite/TaskCircleRenderer.js","sap/ui/suite/library.js"],
"sap/ui/suite/TaskCircleRenderer.js":["jquery.sap.global.js","sap/ui/core/Core.js","sap/ui/suite/library.js"],
"sap/ui/suite/VerticalProgressIndicator.js":["jquery.sap.global.js","sap/ui/core/Control.js","sap/ui/core/EnabledPropagator.js","sap/ui/suite/VerticalProgressIndicatorRenderer.js","sap/ui/suite/library.js"],
"sap/ui/suite/library.js":["jquery.sap.global.js","sap/ui/core/Core.js","sap/ui/core/library.js"]
}});
//# sourceMappingURL=library-h2-preload.js.map