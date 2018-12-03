/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2018 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define([],function(){"use strict";var O={};O.render=function(r,c){r.write("<div");r.writeControlData(c);r.addClass("sapMOnePerCal");r.addClass("sapUiSizeCompact");r.writeClasses();r.write(">");var h=c._getHeader();if(h){r.renderControl(h);}var g=c._getGrid();if(g){r.renderControl(g);}r.write("</div>");};return O;},true);
