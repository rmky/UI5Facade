/*!
 * OpenUI5
 * (c) Copyright 2009-2020 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(["sap/ui/base/Object","sap/m/library","sap/m/HBox","sap/m/Select","sap/ui/core/ListItem"],function(B,l,H,S,L){"use strict";var F=l.FlexWrap;var a=B.extend("sap.ui.integration.util.FilterBarFactory",{metadata:{library:"sap.ui.integration"},constructor:function(c){B.call(this);this._oCard=c;}});a.prototype.create=function(f,m){var o,s=[],k,c,v;for(k in f){c=f[k];v=m.get(k)||c.value;s.push(this._createSelect(k,c,v));}if(!s.length){return null;}o=new H({wrap:F.Wrap,items:s});return o;};a.prototype._createSelect=function(k,c,v){var i=c.items||[],s;s=new S();i.forEach(function(I){s.addItem(new L({key:I.key,text:I.title}));});s.setSelectedKey(v);s.attachChange(function(){this._oCard._setFilterValue(k,s.getSelectedKey());}.bind(this));return s;};return a;});
