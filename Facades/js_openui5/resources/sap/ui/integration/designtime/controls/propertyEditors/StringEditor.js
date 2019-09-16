/*!
 * OpenUI5
 * (c) Copyright 2009-2019 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(['sap/ui/integration/designtime/controls/PropertyEditor','sap/ui/base/BindingParser'],function(P,B){"use strict";var S=P.extend("sap.ui.integration.designtime.controls.propertyEditors.StringEditor",{init:function(){this._oInput=new sap.m.Input({value:"{value}"});this._oInput.attachLiveChange(function(e){if(this._validate()){this.firePropertyChanged(this._oInput.getValue());}}.bind(this));this.addContent(this._oInput);},_validate:function(p){var v=this._oInput.getValue();var i=false;try{B.complexParser(v);}catch(e){i=true;}finally{if(i){this._oInput.setValueState("Error");this._oInput.setValueStateText(sap.ui.getCore().getLibraryResourceBundle("sap.ui.integration").getText("STRING_EDITOR.INVALID_BINDING"));return false;}else{this._oInput.setValueState("None");return true;}}},renderer:P.getMetadata().getRenderer().render});return S;});
