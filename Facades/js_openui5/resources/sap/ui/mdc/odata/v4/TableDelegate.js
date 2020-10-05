/*
 * ! OpenUI5
 * (c) Copyright 2009-2020 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(["sap/ui/mdc/TableDelegate",'sap/ui/core/Core','sap/ui/mdc/util/FilterUtil','sap/ui/mdc/odata/v4/util/DelegateUtil','sap/ui/mdc/odata/v4/FilterBarDelegate','./ODataMetaModelUtil','sap/ui/mdc/odata/v4/TypeUtil'],function(T,C,F,D,a,O,b){"use strict";var c=Object.assign({},T);c.fetchProperties=function(t){var m=t.getDelegate().payload,p=[],P,o,e,M,d,f;e="/"+m.collectionName;M=t.getModel(m.model);d=M.getMetaModel();return Promise.all([d.requestObject(e+"/"),d.requestObject(e+"@")]).then(function(r){var E=r[0],g=r[1];var s=g["@Org.OData.Capabilities.V1.SortRestrictions"]||{};var n=(s["NonSortableProperties"]||[]).map(function(j){return j["$PropertyPath"];});var h=g["@Org.OData.Capabilities.V1.FilterRestrictions"];var i=O.getFilterRestrictionsInfo(h);for(var k in E){o=E[k];if(o&&o.$kind==="Property"){f=d.getObject(e+"/"+k+"@");P={name:k,label:f["@com.sap.vocabularies.Common.v1.Label"],description:f["@com.sap.vocabularies.Common.v1.Text"]&&f["@com.sap.vocabularies.Common.v1.Text"].$Path,maxLength:o.$MaxLength,precision:o.$Precision,scale:o.$Scale,type:o.$Type,sortable:n.indexOf(k)==-1,filterable:i.propertyInfo[k]?i.propertyInfo[k].filterable:true,typeConfig:t.getTypeUtil().getTypeConfig(o.$Type),fieldHelp:undefined,maxConditions:O.isMultiValueFilterExpression(i.propertyInfo[k])?-1:1};p.push(P);}}t.data("$tablePropertyInfo",p);return p;});};c.updateBindingInfo=function(m,M,B){if(!m){return;}if(M&&B){B.path=B.path||M.collectionPath||"/"+M.collectionName;B.model=B.model||M.model;}if(!B){B={};}var f=C.byId(m.getFilter()),d=m.isFilteringEnabled(),e;var i,o;var g=[];if(d){e=m.getConditions();var t=m.data("$tablePropertyInfo");i=F.getFilterInfo(m,e,t);if(i.filters){g.push(i.filters);}}if(f){e=f.getConditions();if(e){var p=f.getPropertyInfoSet?f.getPropertyInfoSet():null;var P=D.getParameterNames(f);o=F.getFilterInfo(f,e,p,P);if(o.filters){g.push(o.filters);}var s=D.getParametersInfo(f,e);if(s){B.path=s;}}var S=f.getSearch();if(S){if(!B.parameters){B.parameters={};}B.parameters.$search=S;}}B.filters=new sap.ui.model.Filter(g,true);};c.getFilterDelegate=function(){return{addFilterItem:function(p,t){return a._createFilterField(p,t);}};};c.getTypeUtil=function(p){return b;};return c;});