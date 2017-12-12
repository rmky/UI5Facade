var oDialogStack = [];
var oShell = new sap.ui.unified.Shell({
	icon: "exface/vendor/exface/OpenUI5Template/Template/images/sap_50x26.png",
	content: [
		
	]
});

function closeTopDialog() {
	var oDialogStackTop = oDialogStack.pop();
	oShell.removeAllContent();
    for (var i in oDialogStackTop.content) {
        oShell.addContent(
            oDialogStackTop.content[i]
        );
    }
    oDialogStackTop.dialog.destroy(true);
    delete oDialogStackTop;
}

function showHtmlInDialog(title, content, state) {
	console.log('here');
	var dialog = new sap.m.Dialog({
		title: title,
		state: state,
		content: new sap.ui.core.HTML({
			content: content
		}),
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
}