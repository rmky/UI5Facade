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