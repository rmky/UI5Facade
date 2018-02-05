var oDialogStack = [];
var oShell = new sap.ui.unified.Shell({
	header: [
		new sap.m.OverflowToolbar({
            design: "Transparent",
			content: [
				new sap.m.Button({
                    icon: "sap-icon://menu2",
                    layoutData: new sap.m.OverflowToolbarLayoutData({priority: "NeverOverflow"}),
                    press: function() {
                    	oShell.setShowPane(! oShell.getShowPane());
            		}
                }),
                new sap.m.Image({
					src: "exface/vendor/exface/OpenUI5Template/Template/images/sap_50x26.png",
					height: "26px",
					width: "50px"
                }),
                new sap.m.ToolbarSpacer(),
                new sap.m.Label("exf_pagetitle", {
                    text: "",
                    design: "Bold",
                    layoutData: new sap.m.OverflowToolbarLayoutData({priority: "NeverOverflow"})
                }),
                new sap.m.ToolbarSpacer(),
                new sap.m.Button("exf_connection", {
                    icon: "sap-icon://connected",
                    text: "3/1",
                    layoutData: new sap.m.OverflowToolbarLayoutData({priority: "NeverOverflow"}),
                    press: function(oEvent){
						var oButton = oEvent.getSource();
						var oPopover = new sap.m.Popover({
							title: "Akt. Status: Online",
							placement: "Bottom",
							content: [
								new sap.m.List({
									items: [
										new sap.m.StandardListItem({
											title: "Sync-Puffer (3)",
											type: "Active",
											press: function(){
												var oData = {
														data: [
															{
																"action_alias": "exface.Core.CreateData",
																"caption": "Speichern",
																"object_alias": "alexa.RMS.ORDERING_REQUEST",
																"object_name": "Warenanforderung",
																"triggered": "2017-02-05 13:55:37"
															},
															{
																"action_alias": "exface.Core.UpdateData",
																"caption": "Speichern",
																"object_alias": "alexa.RMS.ORDERING_REQUEST",
																"object_name": "Warenanforderung",
																"triggered": "2017-02-05 14:23:30"
															},
															{
																"action_alias": "exface.Core.DeleteData",
																"caption": "Löschen",
																"object_alias": "alexa.RMS.ORDERING_REQUEST",
																"object_name": "Warenanforderung",
																"triggered": "2017-02-05 14:48:06"
															}
														]
												};
												
												var oTable = new sap.m.Table({
													fixedLayout: false,
													mode: sap.m.ListMode.MultiSelect,
													headerToolbar: [
														new sap.m.OverflowToolbar({
															design: "Transparent",
															content: [
																new sap.m.Label({
																	text: "Wartende Online-Aktionen"
																}),
																new sap.m.ToolbarSpacer(),
																new sap.m.Button({
																	text: "Abbrechen",
																	icon: "sap-icon://cancel"
																}),
																new sap.m.Button({
																	text: "Exportieren",
																	icon: "sap-icon://download"
																})
															]
														})
													],
													columns: [
														new sap.m.Column({
															header: [
																new sap.m.Label({
																	text: "Objekt"
																})
															]
														}),
														new sap.m.Column({
															header: [
																new sap.m.Label({
																	text: "Aktion"
																})
															]
														}),
														new sap.m.Column({
															header: [
																new sap.m.Label({
																	text: "Alias"
																})
															],
															minScreenWidth: "Tablet",
															demandPopin: true
														}),
													],
													items: {
														path: "/data",
														template: new sap.m.ColumnListItem({
															cells: [
																new sap.m.Text({
																	text: "{object_name}"
																}),
																new sap.m.Text({
																	text: "{caption}"
																}),
																new sap.m.Text({
																	text: "{action_alias}"
																})
															]
														})
													}
												}).setModel(function(){return new sap.ui.model.json.JSONModel(oData)}());
												
												showDialog('Sync-Puffer', oTable, undefined, undefined, true);
											},
										}),
										new sap.m.StandardListItem({
											title: "Ausgecheckte Objekte (0)",
											type: "Active",
											press: function(){alert('click 2!')},
										}),
										new sap.m.StandardListItem({
											title: "Sync-Fehler (1)",
											type: "Active",
											press: function(){alert('click 3!')},
										})
									]
								})
							]
						});
						jQuery.sap.delayedCall(0, this, function () {
							oPopover.openBy(oButton);
						});
					}
                }),
                new sap.f.Avatar("exf_avatar", {
					displaySize: "XS",
					press: function(){
						alert('clicked!');
					}
                })
			]
		})
	],
	content: [
		
	]
});
contextBarInit();

function closeTopDialog() {
	var oDialogStackTop = oDialogStack.pop();
	oShell.removeAllContent();
    for (var i in oDialogStackTop.content) {
        oShell.addContent(
            oDialogStackTop.content[i]
        );
    }
    oDialogStackTop.dialog.destroy(true);
    oDialogStackTop.onClose();
    delete oDialogStackTop;
}

function showDialog(title, content, state, onCloseCallback, responsive) {
	var stretchOnPhone = responsive ? true : false;
	var dialog = new sap.m.Dialog({
		title: title,
		state: state,
		stretchOnPhone: stretchOnPhone,
		content: content,
		beginButton: new sap.m.Button({
			text: 'OK',
			press: function () {
				dialog.close();
			}
		}),
		afterClose: function() {
			if (onCloseCallback) {
				onCloseCallback();
			}
			dialog.destroy();
		}
	});

	dialog.open();
}

function showHtmlInDialog(title, html, state) {
	var content = new sap.ui.core.HTML({
		content: html
	});
	showDialog(title, content, state);
}

function contextBarInit(){
	$(document).ajaxSuccess(function(event, jqXHR, ajaxOptions, data){
		var extras = {};
		if (jqXHR.responseJson){
			extras = jqXHR.responseJson.extras;
		} else {
			try {
				extras = $.parseJSON(jqXHR.responseText).extras;
			} catch (err) {
				extras = {};
			}
		}
		if (extras && extras.ContextBar){
			contextBarRefresh(extras.ContextBar);
		}
	});
	
	contextBarLoad();
}

function contextBarLoad(delay){
	if (delay == undefined) delay = 100;
	setTimeout(function(){
		// IDEA had to disable adding context bar extras to every request due to
		// performance issues. This will be needed for asynchronous contexts like
		// user messaging, external task management, etc. So put the line back in
		// place to fetch context data with every request instead of a dedicated one.
		// if ($.active == 0 && $('#contextBar .context-bar-spinner').length > 0){
		//if ($('#contextBar .context-bar-spinner').length > 0){
			$.ajax({
				type: 'POST',
				url: 'exface/exface.php?exftpl=exface.OpenUI5Template',
				dataType: 'json',
				data: {
					action: 'exface.Core.ShowWidget',
					resource: getPageId(),
					element: 'ContextBar'
				},
				success: function(data, textStatus, jqXHR) {
					contextBarRefresh(data);
				},
				error: function(jqXHR, textStatus, errorThrown){
					contextBarRefresh({});
				}
			});
		/*} else {
			contextBarLoad(delay*3);
		}*/
	}, delay);
}

function contextBarRefresh(data){
	var oToolbar = oShell.getHeader();
	var aItemsOld = oShell.getHeader().getContent();
	var iItemsIndex = 5;
	var oControl = {};
	oToolbar.removeAllContent();
	
	for (var i=0; i<aItemsOld.length; i++) {
		oControl = aItemsOld[i];
		if (i < iItemsIndex || oControl.getId() == 'exf_connection' || oControl.getId() == 'exf_pagetitle' || oControl.getId() == 'exf_avatar') {
			oToolbar.addContent(oControl);
		} else {
			oControl.destroy();
		}
	}
	
	for (var id in data){
		var sColor = data[id].color ? 'background-color:'+data[id].color+' !important;' : '';
		oToolbar.insertContent(
				new sap.m.Button(id, { 
					icon: data[id].icon,
					tooltip: data[id].hint,
					text: data[id].indicator,
					press: function(oEvent) {
						var oButton = oEvent.getSource();
						contextShowMenu(oButton);
					}
				}).data('widget', data[id].bar_widget_id, true), 
				iItemsIndex);
	}
}

function contextShowMenu(oButton){
	var sPopoverId = oButton.data('widget')+"_popover";
	var iPopoverWidth = "350px";
	var iPopoverHeight = "300px";
	var oPopover = sap.ui.getCore().byId(sPopoverId);
	if (oPopover) {
		return;
	} else {
		oPopover = new sap.m.Popover(sPopoverId, {
			title: oButton.getTooltip(),
			placement: "Bottom",
			busy: true,
			contentWidth: iPopoverWidth,
			contentHeight: iPopoverHeight,
			horizontalScrolling: false,
			afterClose: function(oEvent) {
				oEvent.getSource().destroy();
			},
			content: [
				new sap.m.NavContainer({
					pages: [
						new sap.m.Page({
							showHeader: false,
							content: [
								
							]
						})
					]
				})
			]
		}).setBusyIndicatorDelay(0);
		
		jQuery.sap.delayedCall(0, this, function () {
			oPopover.openBy(oButton);
		});
	}
	$.ajax({
		type: 'POST',
		url: 'exface/exface.php?exftpl=exface.OpenUI5Template',
		dataType: 'html',
		data: {
			action: 'exface.Core.ShowContextPopup',
			resource: getPageId(),
			element: oButton.data('widget')
		},
		success: function(data, textStatus, jqXHR) {			
			var viewMatch = data.match(/sap.ui.jsview\("(.*)"/i);
            if (viewMatch !== null) {
                var view = viewMatch[1];
                data = data.replace(view, view+'.'+oButton.data('widget'));
                view = view+'.'+oButton.data('widget');
                $('body').append(data);
            }
            
            var page = oPopover.getContent()[0].getPages()[0];
            page.removeAllContent();
            page.addContent(sap.ui.view({type:sap.ui.core.mvc.ViewType.JS, viewName:view}));
        	oPopover.setBusy(false);
			
		},
		error: function(jqXHR, textStatus, errorThrown){
			oButton.setBusy(false);
			console.log(textStatus);
			//adminLteCreateDialog($("body"), "error", jqXHR.responseText, jqXHR.status + " " + jqXHR.statusText, "error_tab_layouter()");
		}
	});
}

function getPageId(){
	return $("meta[name='page_id']").attr("content");
}

/**
 * Define a custom control to display filters in the P13nDialog as the default filters are always
 * plain text inputs - no combos etc. possible.
 */
(function () {
	"use strict";

	/** @lends sap.m.sample.P13nDialogWithCustomPanel.CustomPanel */
	sap.m.P13nPanel.extend("exface.core.P13nLayoutPanel", {
		constructor: function (sId, mSettings) {
			sap.m.P13nPanel.apply(this, arguments);
		},
		metadata: {
			library: "sap.m",
			aggregations: {
				content: {
					type: "sap.ui.core.Control",
					multiple: false,
					singularName: "content"
				}
			}
		},
		renderer: function (oRm, oControl) {
			if (!oControl.getVisible()) {
				return;
			}
			oRm.renderControl(oControl.getContent());
		}
	});
})();