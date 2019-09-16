/*!
 * OpenUI5
 * (c) Copyright 2009-2019 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define([
    "sap/ui/integration/designtime/controls/BaseEditor",
    "./DefaultCardConfig"
], function (
    BaseEditor,
    oDefaultCardConfig
) {
    "use strict";

    /**
     * @constructor
     * @private
     * @experimental
     */
    var CardEditor = BaseEditor.extend("sap.ui.integration.designtime.controls.CardEditor", {
        init: function() {
            this.addDefaultConfig(oDefaultCardConfig);
            return BaseEditor.prototype.init.apply(this, arguments);
        },
        renderer: BaseEditor.getMetadata().getRenderer()
    });

    return CardEditor;
});
