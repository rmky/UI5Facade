//@ui5-bundle sap/ui/integration/designtime/library-preload.designtime.js
/*!
 * OpenUI5
 * (c) Copyright 2009-2019 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.predefine('sap/ui/integration/designtime/controls/BaseEditor',["sap/ui/core/Control","sap/ui/model/resource/ResourceModel","./utils/ObjectBinding","sap/base/util/ObjectPath",'sap/base/util/merge',"sap/base/util/deepClone","sap/ui/model/json/JSONModel","sap/base/i18n/ResourceBundle","sap/ui/model/BindingMode"],function(C,R,O,a,m,d,J,b,B){"use strict";
var c=C.extend("sap.ui.integration.designtime.controls.BaseEditor",{
metadata:{properties:{"config":{type:"object"},"json":{type:"object",multiple:false},"_defaultConfig":{type:"object",visibility:"hidden",defaultValue:{}}},aggregations:{"_propertyEditors":{type:"sap.ui.core.Control",visibility:"hidden"}},events:{jsonChanged:{parameters:{json:{type:"object"}}},propertyEditorsReady:{parameters:{propertyEditors:{type:"array"}}}}},
init:function(){this.setConfig({});},
exit:function(){this._cleanup();},
renderer:function(r,e){r.write("<div");r.writeElementData(e);r.addClass("sapUiIntegrationEditor");r.writeClasses();r.writeStyles();r.write(">");e.getPropertyEditors().forEach(function(p){p.addStyleClass("sapUiSmallMargin");r.renderControl(p);});r.write("</div>");},
setJson:function(j){if(typeof j==="string"){j=JSON.parse(j);}var r=this.setProperty("json",j,false);this._initialize();return r;},
addDefaultConfig:function(o){return this.setProperty("_defaultConfig",this._mergeConfig(this.getProperty("_defaultConfig"),o));},
setConfig:function(o){return this._setConfig(this._mergeConfig(this.getProperty("_defaultConfig"),o));},
addConfig:function(n){return this._setConfig(this._mergeConfig(this.getConfig(),n));},
getPropertyEditor:function(p){return this._mPropertyEditors[p];},
getPropertyEditors:function(t){var h=function(p,T){return p.getPropertyInfo().tags&&(p.getPropertyInfo().tags.indexOf(T)!==-1);};if(!t){return this.getAggregation("_propertyEditors");}else if(typeof t==="string"){return this.getPropertyEditors().filter(function(p){return h(p,t);});}else if(Array.isArray(t)){return this.getPropertyEditors().filter(function(p){return t.every(function(T){return h(p,T);});});}else{return[];}}
});
c.prototype._mergeConfig=function(t,s){var r=d(t);m(r,s);r.i18n=[].concat(t.i18n||[],s.i18n||[]);return r;};
c.prototype._setConfig=function(o){var r=this.setProperty("config",o,false);this._initialize();return r;};
c.prototype._cleanup=function(o){if(this._oContextModel){this._oContextModel.destroy();delete this._oContextModel;}if(this._oPropertyModel){this._oPropertyModel.destroy();delete this._oPropertyModel;}if(this._oI18nModel){this._oI18nModel.destroy();delete this._oI18nModel;}if(this._oPropertyObjectBinding){this._oPropertyObjectBinding.destroy();delete this._oPropertyObjectBinding;}this._mPropertyEditors={};this.destroyAggregation("_propertyEditors");};
c.prototype._initialize=function(){this._cleanup();if(this.getConfig()&&this.getConfig().properties){this._createModels();this._createEditors();}};
c.prototype._createModels=function(){this._createContextModel();this._createPropertyModel();this._createI18nModel();};
c.prototype._createContextModel=function(){var o=this.getJson();var e=this.getConfig();if(e.context){o=a.get(e.context.split("/"),o);}this._oContextModel=new J(o);this._oContextModel.setDefaultBindingMode(B.OneWay);};
c.prototype._createPropertyModel=function(){var o=this.getConfig();this._oPropertyModel=new J(o.properties);this._oPropertyModel.setDefaultBindingMode(B.OneWay);this._oPropertyObjectBinding=new O(o.properties,this._oPropertyModel,"properties");Object.keys(o.properties).forEach(function(p){var P=o.properties[p];if(P.path){this._syncPropertyValue(P);}}.bind(this));};
c.prototype._createI18nModel=function(){var o=this.getConfig();o.i18n.forEach(function(i){b.create({url:sap.ui.require.toUrl(i),async:true}).then(function(e){if(!this._oI18nModel){this._oI18nModel=new R({bundle:e});this.setModel(this._oI18nModel,"i18n");this._oI18nModel.setDefaultBindingMode(B.OneWay);}else{this._oI18nModel.enhance(e);}}.bind(this));}.bind(this));};
c.prototype._syncPropertyValue=function(p){var o=this._oContextModel.getData();if(o&&p.path){p.value=a.get(p.path.split("/"),o);}if(typeof p.value==="undefined"){p.value=p.defaultValue;}};
c.prototype._createEditors=function(){var o=this.getConfig();var t=Object.keys(o.propertyEditors);var M=t.map(function(T){return o.propertyEditors[T];});var e={};this.__createEditorsCallCount=(this.__createEditorsCallCount||0)+1;var i=this.__createEditorsCallCount;sap.ui.require(M,function(){if(this.__createEditorsCallCount===i){Array.from(arguments).forEach(function(E,I){e[t[I]]=E;});Object.keys(o.properties).forEach(function(p){var P=this._oPropertyModel.getContext("/"+p);var E=e[P.getObject().type];if(E){this._mPropertyEditors[p]=this._createPropertyEditor(E,P);this.addAggregation("_propertyEditors",this._mPropertyEditors[p]);}}.bind(this));this.firePropertyEditorsReady({propertyEditors:this.getPropertyEditors()});}}.bind(this));};
c.prototype._createPropertyEditor=function(E,p){var P=new E({visible:typeof p.getObject().visible!==undefined?p.getObject().visible:true});P.setModel(this._oPropertyModel);P.setBindingContext(p);P.setModel(this._oContextModel,"context");P.attachPropertyChanged(this._onPropertyChanged.bind(this));return P;};
c.prototype._onPropertyChanged=function(e){var p=e.getParameter("path");this._oContextModel.setProperty("/"+p,e.getParameter("value"));this._updatePropertyModel(p);this.fireJsonChanged({json:d(this.getJson())});};
c.prototype._updatePropertyModel=function(p){var P=this._oPropertyModel.getData();Object.keys(P).filter(function(k){return P[k].path===p;}).forEach(function(k){this._syncPropertyValue(P[k]);}.bind(this));this._oPropertyModel.checkUpdate();};
return c;});
sap.ui.predefine('sap/ui/integration/designtime/controls/CardEditor',["sap/ui/integration/designtime/controls/BaseEditor","./DefaultCardConfig"],function(B,d){"use strict";
var C=B.extend("sap.ui.integration.designtime.controls.CardEditor",{
init:function(){this.addDefaultConfig(d);return B.prototype.init.apply(this,arguments);},
renderer:B.getMetadata().getRenderer()
});
return C;});
sap.ui.predefine('sap/ui/integration/designtime/controls/PropertyEditor',['sap/ui/core/Control','sap/m/Label'],function(C,L){"use strict";
var P=C.extend("sap.ui.integration.designtime.controls.PropertyEditor",{
metadata:{properties:{"renderLabel":{type:"boolean",defaultValue:true}},aggregations:{"_label":{type:"sap.m.Label",visibility:"hidden",multiple:false},"content":{type:"sap.ui.core.Control"}},events:{propertyChanged:{parameters:{path:{type:"string"},value:{type:"any"}}}}},
getPropertyInfo:function(){return this.getBindingContext().getObject();},
getLabel:function(){var l=this.getAggregation("_label");if(!l){l=new L({text:this.getPropertyInfo().label});this.setAggregation("_label",l);}return l;},
renderer:function(r,p){r.write("<div");r.writeElementData(p);r.writeClasses();r.writeStyles();r.write(">");if(p.getRenderLabel()){r.write("<div>");r.renderControl(p.getLabel());r.write("</div><div>");}p.getContent().forEach(function(c){r.renderControl(c);});if(p.getRenderLabel()){r.write("</div>");}r.write("</div>");},
firePropertyChanged:function(v){this.fireEvent("propertyChanged",{path:this.getPropertyInfo().path,value:v});}
});
return P;});
sap.ui.predefine('sap/ui/integration/designtime/controls/propertyEditors/EnumStringEditor',['sap/ui/integration/designtime/controls/PropertyEditor','sap/ui/core/Item',"sap/ui/base/BindingParser"],function(P,I,B){"use strict";
var E=P.extend("sap.ui.integration.designtime.controls.propertyEditors.EnumStringEditor",{
init:function(){this._oCombo=new sap.m.ComboBox({selectedKey:"{value}",value:"{value}",width:"100%"});this._oCombo.bindAggregation("items","enum",function(i,c){return new I({key:c.getObject(),text:c.getObject()});});this._oCombo.attachChange(function(){if(this._validate()){this.firePropertyChanged(this._oCombo.getSelectedKey()||this._oCombo.getValue());}}.bind(this));this.addContent(this._oCombo);},
_validate:function(){var s=this._oCombo.getSelectedKey();var v=this._oCombo.getValue();if(!s&&v){var p;try{p=B.complexParser(v);}finally{if(!p){this._oCombo.setValueState("Error");this._oCombo.setValueStateText(sap.ui.getCore().getLibraryResourceBundle("sap.ui.integration").getText("ENUM_EDITOR.INVALID_SELECTION_OR_BINDING"));return false;}else{this._oCombo.setValueState("None");return true;}}}else{this._oCombo.setValueState("None");return true;}},
renderer:P.getMetadata().getRenderer().render
});
return E;});
sap.ui.predefine('sap/ui/integration/designtime/controls/propertyEditors/IconEditor',['sap/ui/integration/designtime/controls/PropertyEditor','sap/ui/core/Fragment',"sap/ui/model/json/JSONModel",'sap/ui/model/Filter','sap/ui/model/FilterOperator',"sap/ui/core/IconPool"],function(P,F,J,a,b,I){"use strict";
var c=P.extend("sap.ui.integration.designtime.controls.propertyEditors.IconEditor",{
init:function(){this._oIconModel=new J(I.getIconNames().map(function(n){return{name:n,path:"sap-icon://"+n};}));this._oInput=new sap.m.Input({value:"{value}",showSuggestion:true,showValueHelp:true,valueHelpRequest:this._handleValueHelp.bind(this)});this._oInput.setModel(this._oIconModel,"icons");this._oInput.bindAggregation("suggestionItems","icons>/",new sap.ui.core.ListItem({text:"{icons>path}",additionalText:"{icons>name}"}));this._oInput.attachLiveChange(function(e){this.firePropertyChanged(e.getParameter("value"));}.bind(this));this._oInput.attachSuggestionItemSelected(function(e){this.firePropertyChanged(e.getParameter("selectedItem").getText());}.bind(this));this.addContent(this._oInput);},
renderer:P.getMetadata().getRenderer().render
});
c.prototype._handleValueHelp=function(e){var v=e.getSource().getValue();if(!this._oDialog){F.load({name:"sap.ui.integration.designtime.controls.propertyEditors.IconSelection",controller:this}).then(function(d){this._oDialog=d;this.addDependent(this._oDialog);this._oDialog.setModel(this._oIconModel);this._filter(v);this._oDialog.open(v);}.bind(this));}else{this._filter(v);this._oDialog.open(v);}};
c.prototype.handleSearch=function(e){var v=e.getParameter("value");this._filter(v);};
c.prototype._filter=function(v){var f=new a("path",b.Contains,v);var B=this._oDialog.getBinding("items");B.filter([f]);};
c.prototype.handleClose=function(e){var s=e.getParameter("selectedItem");if(s){this.firePropertyChanged(s.getIcon());}e.getSource().getBinding("items").filter([]);};
return c;});
sap.ui.predefine('sap/ui/integration/designtime/controls/propertyEditors/StringEditor',['sap/ui/integration/designtime/controls/PropertyEditor','sap/ui/base/BindingParser'],function(P,B){"use strict";
var S=P.extend("sap.ui.integration.designtime.controls.propertyEditors.StringEditor",{
init:function(){this._oInput=new sap.m.Input({value:"{value}"});this._oInput.attachLiveChange(function(e){if(this._validate()){this.firePropertyChanged(this._oInput.getValue());}}.bind(this));this.addContent(this._oInput);},
_validate:function(p){var v=this._oInput.getValue();var i=false;try{B.complexParser(v);}catch(e){i=true;}finally{if(i){this._oInput.setValueState("Error");this._oInput.setValueStateText(sap.ui.getCore().getLibraryResourceBundle("sap.ui.integration").getText("STRING_EDITOR.INVALID_BINDING"));return false;}else{this._oInput.setValueState("None");return true;}}},
renderer:P.getMetadata().getRenderer().render
});
return S;});
sap.ui.predefine('sap/ui/integration/designtime/controls/utils/ObjectBinding',["sap/ui/base/ManagedObject","sap/ui/base/BindingParser"],function(M,B){"use strict";return M.extend("sap.ui.integration.designtime.controls.utils.ObjectBinding",{constructor:function(o,m,s){this._aBindings=[];var u=function(C,p,P){C[p]=P.getValue();m.checkUpdate();};var c=function(o){Object.keys(o).forEach(function(k){if(typeof o[k]==="string"){var b=B.simpleParser(o[k]);if(b&&b.model===s){var a=m.bindProperty(b.path);u(o,k,a);a.attachChange(function(e){u(o,k,a);});this._aBindings.push(a);}}else if(o[k]&&typeof o[k]==="object"){c(o[k]);}}.bind(this));}.bind(this);c(o);},exit:function(){this._aBindings.forEach(function(b){b.destroy();});}});});
/*!
 * OpenUI5
 * (c) Copyright 2009-2019 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 *
 * @constructor
 * @private
 * @experimental
 */
sap.ui.predefine('sap/ui/integration/designtime/controls/DefaultCardConfig',[],function(){"use strict";var D={"context":"sap.card","properties":{"headerType":{"tags":["header"],"label":"{i18n>CARD_EDITOR.HEADERTYPE}","path":"header/type","type":"enum","enum":["Default","Numeric"],"defaultValue":"Default"},"title":{"tags":["header"],"label":"{i18n>CARD_EDITOR.TITLE}","type":"string","path":"header/title"},"subTitle":{"tags":["header"],"label":"{i18n>CARD_EDITOR.SUBTITLE}","type":"string","path":"header/subTitle"},"icon":{"tags":["header defaultHeader"],"label":"{i18n>CARD_EDITOR.ICON}","type":"icon","path":"header/icon/src","visible":"{= ${context>/header/type} !== 'Numeric' }"},"statusText":{"tags":["header defaultHeader"],"label":"{i18n>CARD_EDITOR.STATUS}","type":"string","path":"header/status/text","visible":"{= ${context>/header/type} !== 'Numeric' }"},"unitOfMeasurement":{"tags":["header numericHeader"],"label":"{i18n>CARD_EDITOR.UOM}","type":"string","path":"header/unitOfMeasurement","visible":"{= ${context>/header/type} === 'Numeric' }"},"mainIndicatorNumber":{"tags":["header numericHeader mainIndicator"],"label":"{i18n>CARD_EDITOR.MAIN_INDICATOR.NUMBER}","type":"string","path":"header/mainIndicator/number","visible":"{= ${context>/header/type} === 'Numeric' }"},"mainIndicatorUnit":{"tags":["header numericHeader mainIndicator"],"label":"{i18n>CARD_EDITOR.MAIN_INDICATOR.UNIT}","type":"string","path":"header/mainIndicator/unit","visible":"{= ${context>/header/type} === 'Numeric' }"},"mainIndicatorTrend":{"tags":["header numericHeader mainIndicator"],"label":"{i18n>CARD_EDITOR.MAIN_INDICATOR.TREND}","type":"enum","enum":["Down","None","Up"],"allowBinding":true,"path":"header/mainIndicator/trend","visible":"{= ${context>/header/type} === 'Numeric' }"},"mainIndicatorState":{"tags":["header numericHeader mainIndicator"],"label":"{i18n>CARD_EDITOR.MAIN_INDICATOR.STATE}","type":"enum","enum":["Critical","Error","Good","Neutral"],"allowBinding":true,"path":"header/mainIndicator/state","visible":"{= ${context>/header/type} === 'Numeric' }"},"details":{"tags":["header numericHeader"],"label":"{i18n>CARD_EDITOR.DETAILS}","type":"string","path":"header/details","visible":"{= ${context>/header/type} === 'Numeric' }"},"listItemTitle":{"tags":["content listItem"],"label":"{i18n>CARD_EDITOR.LIST_ITEM.TITLE}","type":"string","path":"content/item/title"},"listItemDescription":{"tags":["content listItem"],"label":"{i18n>CARD_EDITOR.LIST_ITEM.DESCRIPTION}","type":"string","path":"content/item/description"},"listItemHighlight":{"tags":["content listItem"],"label":"{i18n>CARD_EDITOR.LIST_ITEM.HIGHLIGHT}","type":"string","path":"content/item/highlight"}},"propertyEditors":{"enum":"sap/ui/integration/designtime/controls/propertyEditors/EnumStringEditor","string":"sap/ui/integration/designtime/controls/propertyEditors/StringEditor","icon":"sap/ui/integration/designtime/controls/propertyEditors/IconEditor"},"i18n":"sap/ui/integration/designtime/controls/i18n/i18n.properties"};return D;},true);
//# sourceMappingURL=library-preload.designtime.js.map