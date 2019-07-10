/*!
 * OpenUI5
 * (c) Copyright 2009-2019 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define([],function(){"use strict";var S={};S.render=function(r,s){var o=s._getSearchField(),c=s._getCancelButton(),a=s._getSearchButton(),i=s.getIsOpen(),p=s.getPhoneMode(),b=s.getWidth();r.write("<div");r.writeControlData(s);if(i){r.addClass("sapFShellBarSearch");}if(p){r.addClass("sapFShellBarSearchFullWidth");}if(b&&i&&!p){r.addStyle("width",b);}r.writeClasses();r.writeStyles();r.write(">");if(i){r.renderControl(o);}r.renderControl(a);if(i&&p){r.renderControl(c);}r.write("</div>");};return S;},true);
