/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2018 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define([],function(){"use strict";var O={};O.render=function(r,h){r.write("<div");r.writeControlData(h);r.addClass("sapMOnePerHead");r.writeClasses();r.write(">");var a=h.getAggregation("_actionsToolbar");if(a){r.renderControl(a);}var n=h.getAggregation("_navigationToolbar");if(n){r.renderControl(n);}r.write("</div>");};return O;},true);
