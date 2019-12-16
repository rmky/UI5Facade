/*!
 * OpenUI5
 * (c) Copyright 2009-2019 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(["sap/ui/integration/designtime/baseEditor/propertyEditor/BasePropertyEditor","sap/ui/base/BindingParser","sap/m/Input"],function(B,a,I){"use strict";var S=B.extend("sap.ui.integration.designtime.baseEditor.propertyEditor.stringEditor.StringEditor",{constructor:function(){B.prototype.constructor.apply(this,arguments);this._oInput=new I({value:"{value}"});this._oInput.attachLiveChange(function(e){if(this._validate()){this.firePropertyChange(this._oInput.getValue());}}.bind(this));this.addContent(this._oInput);},_validate:function(){var v=this._oInput.getValue();var i=false;try{a.complexParser(v);}catch(e){i=true;}finally{if(i){this._oInput.setValueState("Error");this._oInput.setValueStateText(this.getI18nProperty("BASE_EDITOR.STRING.INVALID_BINDING"));return false;}else{this._oInput.setValueState("None");return true;}}},renderer:B.getMetadata().getRenderer().render});return S;});
