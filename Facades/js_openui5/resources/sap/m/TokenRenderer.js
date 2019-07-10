/*!

* OpenUI5
 * (c) Copyright 2009-2019 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.

*/
sap.ui.define(["sap/ui/core/library","sap/ui/core/InvisibleText"],function(c,I){"use strict";var T=c.TextDirection;var a={};a.render=function(r,C){var t=C._getTooltip(C,C.getEditable()),A=[],o={};r.write("<div tabindex=\"-1\"");r.writeControlData(C);r.addClass("sapMToken");r.writeAttribute("role","listitem");if(C.getSelected()){r.addClass("sapMTokenSelected");}if(!C.getEditable()){r.addClass("sapMTokenReadOnly");}r.writeClasses();if(t){r.writeAttributeEscaped('title',t);}A.push(I.getStaticId("sap.m","TOKEN_ARIA_LABEL"));if(C.getEditable()){A.push(I.getStaticId("sap.m","TOKEN_ARIA_DELETABLE"));}o.describedby={value:A.join(" "),append:true};r.writeAccessibilityState(C,o);r.write(">");a._renderInnerControl(r,C);if(C.getEditable()){r.renderControl(C._deleteIcon);}r.write("</div>");};a._renderInnerControl=function(r,C){var t=C.getTextDirection();r.write("<span");r.addClass("sapMTokenText");r.writeClasses();if(t!==T.Inherit){r.writeAttribute("dir",t.toLowerCase());}r.write(">");var b=C.getText();if(b){r.writeEscaped(b);}r.write("</span>");};return a;},true);
