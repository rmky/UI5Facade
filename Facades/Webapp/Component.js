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
		 * Shows an error dialog for an AJAX error with either HTML or a UI5 JSView in the response body.
		 * 
		 * @param String sBody
		 * @param String sTitle
		 * 
		 * @return void
		 */
		showErrorDialog : function(sBody, sTitle) {
			var view = '';
		    var errorBody = sBody ? sBody : '';
		    var viewMatch = errorBody.match(/sap.ui.jsview\("(.*)"/i);
		    if (viewMatch !== null) {
		        view = viewMatch[1];
		        var randomizer = window.performance.now().toString();
		        errorBody = errorBody.replace(view, view+randomizer);
		        view = view+randomizer;
		        $('body').append(errorBody);
		        exfLauncher.showDialog(sTitle, sap.ui.view({type:sap.ui.core.mvc.ViewType.JS, viewName:view}), 'Error');
		    } else {
		    	exfLauncher.showHtmlInDialog(sTitle, errorBody, 'Error');
		    }
		},
		
		/**
		 * Shows an error dialog for an AJAX error with either HTML, JSON or a UI5 JSView in the response body.
		 * 
		 * @param String sBody
		 * @param String sTitle
		 * 
		 * @return void
		 */
		showAjaxErrorDialog : function (jqXHR, sMessage) {
			var sContentType = jqXHR.getResponseHeader('Content-Type');
			
			// 
			if (sContentType.match(/json/i)) {
				try {
					var oData = JSON.parse(jqXHR.responseText);
					if (oData.error) {
						var oError = oData.error;
					} else {
						throw {};
					}
				} catch (e) {
					var oError = {
						message: jqXHR.responseText
					};
				}
				sMessage = sMessage ? sMessage : oError.message;
				var sTitle = oError.code ? oError.type + ' ' + oError.code + ': ' + oError.title : jqXHR.status + " " + jqXHR.statusText;
				var oDialogContent = new sap.m.VBox({
					items: [
						new sap.m.Text({
							text: sMessage
						})
					]
				}).addStyleClass('sapUiResponsiveMargin');
				if (oError.logid) {
					oDialogContent.addItem(
						new sap.m.MessageStrip({
							text: "Log-ID " + oError.logid + ": Please use the it in all support requests!",
							type: "Information",
							showIcon: true
						}).addStyleClass('sapUiSmallMarginTop')
					);
				}
				exfLauncher.showDialog(sTitle, oDialogContent, 'Error');
			} else {
				var view = '';
			    var errorBody = sBody ? sBody : '';
			    var viewMatch = errorBody.match(/sap.ui.jsview\("(.*)"/i);
			    if (viewMatch !== null) {
			        view = viewMatch[1];
			        var randomizer = window.performance.now().toString();
			        errorBody = errorBody.replace(view, view+randomizer);
			        view = view+randomizer;
			        $('body').append(errorBody);
			        exfLauncher.showDialog(sTitle, sap.ui.view({type:sap.ui.core.mvc.ViewType.JS, viewName:view}), 'Error');
			    } else {
			    	exfLauncher.showHtmlInDialog(sTitle, errorBody, 'Error');
			    }
			}
		},
		
		/**
		 * Convenience method to create and open a dialog.
		 * 
		 * The dialog is automatically destroyed when closed.
		 * 
		 * @param string|sap.ui.core.Control content
		 * @param string sTitle
		 * @param string sState
		 * @param string bResponsive
		 * 
		 * @return sap.m.Dialog
		 */
		showDialog : function (content, sTitle, sState, bResponsive) {
			var stretch = bResponsive ? jQuery.device.is.phone : false;
			var type = sap.m.DialogType.Standard;
			if (typeof content === 'string' || content instanceof String) {
				content = new sap.m.Text({
					text: content
				});
				type = sap.m.DialogType.Message;
			} 
			var dialog = new sap.m.Dialog({
				title: sTitle,
				state: sState,
				type: type,
				stretch: stretch,
				content: content,
				beginButton: new sap.m.Button({
					text: 'OK',
					press: function () {
						dialog.close();
					}
				}),
				afterClose: function() {
					dialog.destroy();
				}
			});
		
			dialog.open();
			
			return dialog;
		}

	});

});
