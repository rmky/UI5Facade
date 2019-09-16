/*!
 * OpenUI5
 * (c) Copyright 2009-2019 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(["sap/ui/base/ManagedObject","sap/ui/base/BindingParser"],function(M,B){"use strict";return M.extend("sap.ui.integration.designtime.controls.utils.ObjectBinding",{constructor:function(o,m,s){this._aBindings=[];var u=function(C,p,P){C[p]=P.getValue();m.checkUpdate();};var c=function(o){Object.keys(o).forEach(function(k){if(typeof o[k]==="string"){var b=B.simpleParser(o[k]);if(b&&b.model===s){var a=m.bindProperty(b.path);u(o,k,a);a.attachChange(function(e){u(o,k,a);});this._aBindings.push(a);}}else if(o[k]&&typeof o[k]==="object"){c(o[k]);}}.bind(this));}.bind(this);c(o);},exit:function(){this._aBindings.forEach(function(b){b.destroy();});}});});
