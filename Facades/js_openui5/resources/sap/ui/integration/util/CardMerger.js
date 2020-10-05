/*!
 * OpenUI5
 * (c) Copyright 2009-2020 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(["sap/base/util/merge"],function(m){"use strict";var C={mergeCardDelta:function(M,c){var i=m({},M),s="sap.card";c.forEach(function(o){m(i[s],o.content);});return i;},mergeCardDesigntimeMetadata:function(d,c){var i=m({},d);c.forEach(function(o){var I=o.content.entityPropertyChange||[];I.forEach(function(a){var p=a.propertyPath;switch(a.operation){case"UPDATE":if(i.hasOwnProperty(p)){i[p]=a.propertyValue;}break;case"DELETE":delete i[p];break;case"INSERT":if(!i.hasOwnProperty(p)){i[p]=a.propertyValue;}break;default:break;}});});return i;}};return C;});
