/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2017 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(['jquery.sap.global','sap/ui/core/Control','./library','sap/ui/Device','sap/ui/core/InvisibleText'],function(q,C,l,D,I){"use strict";var a=l.CalendarDayType;var S=l.StandardCalendarLegendItem;var b=C.extend("sap.ui.unified.CalendarLegend",{metadata:{library:"sap.ui.unified",properties:{columnWidth:{type:"sap.ui.core.CSSSize",group:"Misc",defaultValue:'120px'}},aggregations:{items:{type:"sap.ui.unified.CalendarLegendItem",multiple:true,singularName:"item"},_standardItems:{type:"sap.ui.unified.CalendarLegendItem",multiple:true,visibility:"hidden"}}}});b.prototype.init=function(){this._addStandardItems(b._All_Standard_Items);};b.prototype.onAfterRendering=function(){if(D.browser.msie){if(D.browser.version<10){q(".sapUiUnifiedLegendItem").css("width",this.getColumnWidth()+4+"px").css("display","inline-block");}}};b.prototype._addStandardItems=function(s,r){var i,c=sap.ui.getCore().getLibraryResourceBundle("sap.ui.unified"),d=this.getId();if(r){this.destroyAggregation("_standardItems");}for(i=0;i<s.length;i++){var o=new sap.ui.unified.CalendarLegendItem(d+"-"+s[i],{text:c.getText(b._Standard_Items_TextKeys[s[i]])});this.addAggregation("_standardItems",o);}};b._All_Standard_Items=[S.Today,S.Selected,S.WorkingDay,S.NonWorkingDay];b._Standard_Items_TextKeys={"Today":"LEGEND_TODAY","Selected":"LEGEND_SELECTED","WorkingDay":"LEGEND_NORMAL_DAY","NonWorkingDay":"LEGEND_NON_WORKING_DAY"};b.prototype._getItemType=function(i,c){var t=i.getType(),n,f;if(t&&t!==a.None){return t;}f=this._getUnusedItemTypes(c);n=c.filter(function(d){return!d.getType()||d.getType()===a.None;}).indexOf(i);if(n<0){q.sap.log.error('Legend item is not in the legend',this);return t;}if(f[n]){t=f[n];}else{t="Type"+(Object.keys(a).length+n-f.length-1);}return t;};b.prototype._getItemByType=function(t){var o,c=this.getItems(),i;for(i=0;i<c.length;i++){if(this._getItemType(c[i],c)===t){o=c[i];break;}}return o;};b.prototype._getUnusedItemTypes=function(c){var f=q.extend({},a),t,i;delete f[a.None];delete f[a.NonWorking];for(i=0;i<c.length;i++){t=c[i].getType();if(f[t]){delete f[t];}}return Object.keys(f);};b.typeARIATexts={};b.getTypeAriaText=function(t){var r,T;if(t.indexOf("Type")!==0){return;}if(!b.typeARIATexts[t]){r=sap.ui.getCore().getLibraryResourceBundle("sap.ui.unified");T=r.getText("LEGEND_UNNAMED_TYPE",parseInt(t.slice(4),10).toString());b.typeARIATexts[t]=new I({text:T});b.typeARIATexts[t].toStatic();}return b.typeARIATexts[t];};return b;});