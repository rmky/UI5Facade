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
		showAjaxErrorDialog : function(sBody, sTitle) {
			var view = '';
		    var errorBody = sBody;
		    var viewMatch = errorBody.match(/sap.ui.jsview\("(.*)"/i);
		    if (viewMatch !== null) {
		        view = viewMatch[1];
		        var randomizer = window.performance.now().toString();
		        errorBody = errorBody.replace(view, view+randomizer);
		        view = view+randomizer;
		        $('body').append(errorBody);
		        showDialog(sTitle, sap.ui.view({type:sap.ui.core.mvc.ViewType.JS, viewName:view}), 'Error');
		    } else {
		        showHtmlInDialog(sTitle, errorBody, 'Error');
		    }
		}

	});

});
