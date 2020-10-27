/*!
* OpenUI5
 * (c) Copyright 2009-2020 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
*/
sap.ui.require(["sap/ui/integration/widgets/Card","sap/ui/integration/customElements/CustomElementBase","sap/ui/integration/customElements/CustomElementHostConfiguration","sap/m/BadgeCustomData"],function(C,a,b,B){"use strict";var c=a.extend(C,{privateProperties:["width","height"],customProperties:{"badge":{set:function(o,v){o.addCustomData(new B({value:v}));}}}});c.prototype.refresh=function(){this._getControl().refresh();};c.prototype.loadDesigntime=function(){return this._getControl().loadDesigntime();};var d=["ui-integration-host-configuration"];a.define("ui-integration-card",c,d);});
