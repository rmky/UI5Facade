/*!
 * OpenUI5
 * (c) Copyright 2009-2020 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(["sap/ui/thirdparty/jquery"],function(q){"use strict";var G={};function c(e,d,D){return{setIndicatorConfig:q.noop,getDragControl:function(){return d;}};}function g(C){var p=C.getParent(),s=(C.getDragDropConfig)?C.getDragDropConfig():[],P=(p&&p.getDragDropConfig)?p.getDragDropConfig():[];return s.concat(P);}function a(d){var D=g(d);return D.filter(function(o){return o.isDraggable(d);});}function b(d,D,e){var f=g(d);D=D||[];return f.filter(function(o){return!o.isA("sap.ui.core.dnd.IDragInfo");}).concat(D).filter(function(o){if(!o.isDroppable(d,e)){return false;}var s=o.getGroupName();if(!s){return true;}return D.some(function(h){return h.getGroupName()==s;});});}G.fireDnDByKeyboard=function(d,D,s,e){var v=a(d);e.dragSession=c(e,d,D);if(!v.length){return;}v=e.isMarked("NonDraggable")?[]:v.filter(function(o){return o.fireDragStart(e);});if(!v.length){return;}var V=b(D.getParent(),v,e);V=V.filter(function(o){return o.fireDragEnter(e);});V.forEach(function(o){o.fireDropEvent(null,e.originalEvent,s,d,D);});};return G;},true);
