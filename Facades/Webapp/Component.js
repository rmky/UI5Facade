sap.ui.define([
	"sap/ui/core/UIComponent"
], function (UIComponent) {
	"use strict";

	return UIComponent.extend("[#app_id#].Component", {

		metadata: {
			manifest: "json"
		},

        init: function () {
            // call the init function of the parent
            UIComponent.prototype.init.apply(this, arguments);

            // create the views based on the url/hash
            this.getRouter().initialize();
        },
		
        /**
         * Returns the view, that the given control belongs to
         * 
         * @param sap.ui.core.Control oControl
         * 
         * @return sap.ui.core.mvc.View
         */
		findViewOfControl: function(oControl) {
			while (oControl && oControl.getParent) {
				oControl = oControl.getParent();
				if (oControl instanceof sap.ui.core.mvc.View){
					return oControl;
				}
		    }
		},
        
        convertConditionOperationToConditionGroupOperator: function(operation) {
			var map = {
				//Ascending
				//Average
				//BT
				Contains: '=',
				//Descending
				//Empty
				//EndsWith
				EQ: '==',
				GE: '>=',
				//GroupAscending
				//GroupDescending
				GT: '>',
				//Initial
				LE: '<=',
				LT: '<',
				//Maximum
				//Minimum
				//NotEmpty
				//StartsWith
				//Total
			}; 
			if (map[operation] !== undefined) {
				return map[operation];
			} else {
				throw 'UI5 Condintion operation "'+operation+'" cannot be mapped to a condition group operator!';
			}
        },
        
        /**
		 * Convenience method to create and open a dialog.
		 * 
		 * The dialog is automatically destroyed when closed.
		 * 
		 * @param string|sap.ui.core.Control mContent
		 * @param string sTitle
		 * @param string sState
		 * @param string bResponsive
		 * 
		 * @return sap.m.Dialog
		 */
        showDialog : function (sTitle, mContent, sState, onCloseCallback, bResponsive) {
    		var bStretch = bResponsive ? jQuery.device.is.phone : false;
    		var sType = sap.m.DialogType.Standard;
    		var oContent;
    		if (typeof mContent === 'string' || mContent instanceof String) {
    			oContent = new sap.m.Text({
    				text: mContent
    			});
    			sType = sap.m.DialogType.Message;
    		} else {
    			oContent = mContent;
    		}
    		var oDialog = new sap.m.Dialog({
    			title: sTitle,
    			state: sState,
    			type: sType,
    			stretch: bStretch,
    			content: oContent,
    			endButton: new sap.m.Button({
    				text: 'OK',
    				type: sap.m.ButtonType.Emphasized,
    				press: function () {
    					oDialog.close();
    				}
    			}),
    			afterClose: function() {
    				if (onCloseCallback) {
    					onCloseCallback();
    				}
    				oDialog.destroy();
    			}
    		}).setModel(this.getModel('i18n'), 'i18n');;
    	
    		oDialog.open();
    		return oDialog;
    	},

    	/**
    	 * Creates and opens a dialog with the given HTML as content
    	 * 
    	 * @param String sTitle
    	 * @param String sHtml
    	 * @param String sState
    	 * 
    	 * @return sap.m.Dialog
    	 */
    	showHtmlInDialog : function (sTitle, sHtml, sState) {
    		try {
	    		var oContent = new sap.ui.core.HTML({
	    			content: sHtml
	    		});
    		} catch (e) {
    			return this.showErrorDialog('Unkown error', sTitle, 'string');
    		}
    		return this.showDialog(sTitle, oContent, sState);
    	},
		
		/**
		 * Shows an error dialog for an AJAX error with either HTML or a UI5 JSView in the response body.
		 * 
		 * @param String sBody
		 * @param String sTitle
		 * @param String sContentType string|html|view|json
		 * 
		 * @return sap.m.Dialog
		 */
		showErrorDialog : function(sBody, sTitle, sContentType) {
			var sViewName, oBody, sDialogType = 'Error';
			
			sBody = sBody ? sBody.trim() : '';
			
			if (! sContentType) {
				if (sBody.startsWith('{') && sBody.endsWith('}')) {
					try {
						oBody = JSON.parse(sBody);
						sContentType = 'json';
					} catch (e) {
						sContentType = 'string';
					}
				} else if (sBody.startsWith('<') && sBody.endsWith('>')) {
					sContentType = 'html';
				} else if (sViewName = this._findViewInString(sBody)) {
					sContentType = 'view';
				} else {
					sContentType = 'string';
				}
			}
			
			switch (sContentType) {
				case 'view':
					if (! sViewName) {
						sViewName = this._findViewInString(sBody);
					}
					var randomizer = window.performance.now().toString();
					var sViewNameUnique = sViewName+randomizer;
			        sBody = sBody.replace(sViewName, sViewNameUnique);
			        $('body').append(sBody);
			        return this.showDialog(sTitle, sap.ui.view({type:sap.ui.core.mvc.ViewType.JS, viewName:sViewNameUnique}), 'Error');
				case 'json':
					var sMessage, sDetails, oDetailsControl;
					
					try {
						oBody = oBody ? oBody : JSON.parse(sBody);
						if (oBody.error) {
							var oError = oBody.error;
						} else {
							throw {};
						}
					} catch (e) {
						var oError = {
							message: sBody
						};
					}
					
					// Message
					if (oError.code || oError.title) {
						sTitle = "{i18n>MESSAGE.TYPE." + oError.type + "} {i18n>" + oError.code + "}";
						sMessage = '';
						if (oError.title) {
							sMessage += oError.title;
							sDetails = oError.message;
						} else {
							sMessage += oError.message;
						}
					} else {
						sMessage = oError.message;
					}
					
					// Title
					sTitle = sTitle ? sTitle : '';
					
					// Dialog content - just showing the message text
					var oDialogContent = new sap.m.VBox({
						items: [
							new sap.m.Text({
								text: sMessage
							})
						]
					}).addStyleClass('sapUiSmallMargin');
					
					// Add details if applicable
					if (sDetails && sDetails !== sMessage) {
						oDetailsControl = new sap.m.Text({
								text: sDetails,
								visible: false
							}).addStyleClass('sapUiSmallMarginTop');
						oDialogContent.addItem(oDetailsControl);
					}
					
					// Add Log-ID reminder
					if (oError.logid) {
						oDialogContent.addItem(
							new sap.m.MessageStrip({
								text: "Log-ID " + oError.logid + ": {i18n>ERROR.LOG_ID_HINT}",
								type: "Information",
								showIcon: true
							}).addStyleClass('sapUiSmallMarginTop')
						);
					}
					
					// Show the dialog
					switch (oError.type) {
						case 'WARNING': sDialogType = 'Warning'; break;
						case 'SUCCESS': sDialogType = 'Success'; break;
						case 'INFO': case 'HINT': sDialogType = 'Information'; break;
					}
					
					var oDialog = this.showDialog(sTitle, oDialogContent, sDialogType);
					if (oDetailsControl) {
						oDialog.setBeginButton(
							new sap.m.Button({
								text: "{i18n>ERROR.DETAILS}",
								icon: "sap-icon://slim-arrow-down",
								press: function(oEvent) {
									var oBtn = oEvent.getSource();
									if (oDetailsControl.getVisible() === true) {
										oDetailsControl.setVisible(false);
										oBtn.setIcon("sap-icon://slim-arrow-down");
									} else {
										oDetailsControl.setVisible(true);
										oBtn.setIcon("sap-icon://slim-arrow-up");
									}
								}
							})
						);
					}
					return oDialog;
					
				default:
					if (sContentType === 'string') {
						sBody = '<p class="sapUiSmallMargin">' + sBody + '</p>';
					}
					return this.showHtmlInDialog(sTitle, sBody, 'Error');
			}
		},
		
		/**
		 * 
		 * @private
		 * @param String sString
		 * @return String|Boolean
		 */
		_findViewInString : function (sString) {
			var viewMatch = sString.match(/sap.ui.jsview\("(.*)"/i);
		    if (viewMatch !== null) {
		        return viewMatch[1];
		    }
		    return false;
		},
		
		/**
		 * Shows an error dialog for an AJAX error with either HTML, JSON or a UI5 JSView in the response body.
		 * 
		 * @param jqXHR jqXHR
		 * @param String sMessage
		 * 
		 * @return sap.m.Dialog
		 */
		showAjaxErrorDialog : function (jqXHR, sMessage) {
			var sContentType = jqXHR.getResponseHeader('Content-Type');
			var sBodyType;
			
			if (sContentType.match(/json/i)) {
				sBodyType = 'json';
			} else if (sContentType.match(/html/i)) {
				sBodyType = 'html';
			}
			return this.showErrorDialog(jqXHR.responseText, (sMessage ? sMessage : jqXHR.status + " " + jqXHR.statusText), sBodyType);
		}

	});

});
