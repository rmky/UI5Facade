/*!
 * OpenUI5
 * (c) Copyright 2009-2019 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(["sap/ui/core/mvc/Controller","sap/ui/core/ValueState"],function(C,V){"use strict";var _=["sectionName","frameUrl"];var a=["frameWidth","frameHeigth"];return C.extend("sap.ui.rta.plugin.iframe.controller.SettingsDialogController",{constructor:function(j){this._oJSONModel=j;},onValidationSuccess:function(e){e.getSource().setValueState(V.None);this._oJSONModel.setProperty("/areAllFieldsValid",this._areAllTextFieldsValid()&&this._areAllValueStateNones());},onValidationError:function(e){e.getSource().setValueState(V.Error);this._oJSONModel.setProperty("/areAllFieldsValid",false);},onOKPress:function(){},onCancelPress:function(){},_areAllValueStateNones:function(){var d=this._oJSONModel.getData();return _.concat(a).every(function(f){return d[f]["valueState"]===V.None;},this);},_areAllTextFieldsValid:function(){var v=true;var d=this._oJSONModel.getData();_.forEach(function(f){if(d[f]["value"].trim()===""){v=false;this._oJSONModel.setProperty("/"+f+"/valueState",V.Error);}},this);return v;}});});
