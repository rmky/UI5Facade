//@ui5-bundle sap/ui/support/library-h2-preload.js
/*!
 * OpenUI5
 * (c) Copyright 2009-2019 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.predefine('sap/ui/support/library',["sap/ui/core/library"],function(l){"use strict";sap.ui.getCore().initLibrary({name:"sap.ui.support",dependencies:["sap.ui.core"],types:["sap.ui.support.Severity"],interfaces:[],controls:[],elements:[],noLibraryCSS:true,version:"1.68.1",extensions:{"sap.ui.support":{internalRules:true}}});sap.ui.support.Severity={Medium:"Medium",High:"High",Low:"Low"};sap.ui.support.Audiences={Control:"Control",Internal:"Internal",Application:"Application"};sap.ui.support.Categories={Accessibility:"Accessibility",Performance:"Performance",Memory:"Memory",Bindings:"Bindings",Consistency:"Consistency",FioriGuidelines:"FioriGuidelines",Functionality:"Functionality",Usability:"Usability",DataModel:"DataModel",Modularization:"Modularization",Usage:"Usage",Other:"Other"};sap.ui.support.HistoryFormats={Abap:"Abap",String:"String"};sap.ui.support.SystemPresets={Accessibility:{id:"Accessibility",title:"Accessibility",description:"Accessibility related rules",selections:[{ruleId:"dialogAriaLabelledBy",libName:"sap.m"},{ruleId:"onlyIconButtonNeedsTooltip",libName:"sap.m"},{ruleId:"inputNeedsLabel",libName:"sap.m"},{ruleId:"titleLevelProperty",libName:"sap.m"},{ruleId:"formTitleOrAriaLabel",libName:"sap.ui.layout"},{ruleId:"formTitleInToolbarAria",libName:"sap.ui.layout"},{ruleId:"formMissingLabel",libName:"sap.ui.layout"},{ruleId:"gridTableAccessibleLabel",libName:"sap.ui.table"},{ruleId:"gridTableColumnTemplateIcon",libName:"sap.ui.table"},{ruleId:"smartFormLabelOrAriaLabel",libName:"sap.ui.comp"},{ruleId:"icontabbarlabels",libName:"sap.m"},{ruleId:"labeltooltip",libName:"sap.m"},{ruleId:"labelfor",libName:"sap.m"},{ruleId:"labelInDisplayMode",libName:"sap.m"},{ruleId:"texttooltip",libName:"sap.m"},{ruleId:"rbText",libName:"sap.m"}]}};return sap.ui.support;});
sap.ui.require.preload({
	"sap/ui/support/manifest.json":'{"_version":"1.9.0","sap.app":{"id":"sap.ui.support","type":"library","embeds":[],"applicationVersion":{"version":"1.68.1"},"title":"UI5 library: sap.ui.support","description":"UI5 library: sap.ui.support","resources":"resources.json","offline":true},"sap.ui":{"technology":"UI5","supportedThemes":[]},"sap.ui5":{"dependencies":{"minUI5Version":"1.68","libs":{"sap.ui.core":{"minVersion":"1.68.1"}}},"library":{"i18n":false,"css":false,"content":{"controls":[],"elements":[],"types":["sap.ui.support.Severity"],"interfaces":[]}}}}'
},"sap/ui/support/library-h2-preload"
);
sap.ui.loader.config({depCacheUI5:{
"sap/ui/support/Bootstrap.js":["jquery.sap.global.js"],
"sap/ui/support/RuleAnalyzer.js":["sap/ui/support/Bootstrap.js","sap/ui/support/supportRules/Main.js","sap/ui/support/supportRules/RuleSetLoader.js"],
"sap/ui/support/jQuery.sap.support.js":["sap/ui/support/supportRules/Main.js","sap/ui/support/supportRules/RuleSetLoader.js"],
"sap/ui/support/library.js":["sap/ui/core/library.js"],
"sap/ui/support/supportRules/Analyzer.js":["jquery.sap.global.js","sap/ui/support/supportRules/Constants.js","sap/ui/support/supportRules/IssueManager.js"],
"sap/ui/support/supportRules/CoreFacade.js":["sap/ui/core/Component.js"],
"sap/ui/support/supportRules/ExecutionScope.js":["jquery.sap.global.js","sap/ui/core/Component.js","sap/ui/core/Element.js"],
"sap/ui/support/supportRules/History.js":["sap/ui/support/library.js","sap/ui/support/supportRules/IssueManager.js","sap/ui/support/supportRules/RuleSetLoader.js","sap/ui/support/supportRules/report/AbapHistoryFormatter.js","sap/ui/support/supportRules/report/StringHistoryFormatter.js"],
"sap/ui/support/supportRules/IssueManager.js":["jquery.sap.global.js","sap/ui/base/Object.js","sap/ui/support/supportRules/Constants.js"],
"sap/ui/support/supportRules/Main.js":["jquery.sap.global.js","sap/ui/base/ManagedObject.js","sap/ui/core/Component.js","sap/ui/core/Element.js","sap/ui/support/library.js","sap/ui/support/supportRules/Analyzer.js","sap/ui/support/supportRules/Constants.js","sap/ui/support/supportRules/CoreFacade.js","sap/ui/support/supportRules/ExecutionScope.js","sap/ui/support/supportRules/History.js","sap/ui/support/supportRules/IssueManager.js","sap/ui/support/supportRules/RuleSerializer.js","sap/ui/support/supportRules/RuleSetLoader.js","sap/ui/support/supportRules/WCBChannels.js","sap/ui/support/supportRules/WindowCommunicationBus.js","sap/ui/support/supportRules/report/DataCollector.js","sap/ui/support/supportRules/ui/external/Highlighter.js"],
"sap/ui/support/supportRules/RuleSet.js":["jquery.sap.global.js","sap/ui/support/supportRules/Constants.js","sap/ui/support/supportRules/Storage.js"],
"sap/ui/support/supportRules/RuleSetLoader.js":["jquery.sap.global.js","sap/ui/support/supportRules/Constants.js","sap/ui/support/supportRules/RuleSerializer.js","sap/ui/support/supportRules/RuleSet.js","sap/ui/support/supportRules/WCBChannels.js","sap/ui/support/supportRules/WindowCommunicationBus.js","sap/ui/support/supportRules/util/Utils.js"],
"sap/ui/support/supportRules/Storage.js":["sap/ui/support/supportRules/Constants.js","sap/ui/support/supportRules/RuleSerializer.js"],
"sap/ui/support/supportRules/WindowCommunicationBus.js":["jquery.sap.script.js","sap/base/Log.js","sap/ui/thirdparty/URI.js"],
"sap/ui/support/supportRules/report/Archiver.js":["jquery.sap.global.js","sap/ui/core/util/File.js","sap/ui/thirdparty/jszip.js"],
"sap/ui/support/supportRules/report/DataCollector.js":["jquery.sap.global.js","sap/ui/core/Component.js","sap/ui/core/support/ToolsAPI.js","sap/ui/thirdparty/URI.js"],
"sap/ui/support/supportRules/report/IssueRenderer.js":["jquery.sap.global.js"],
"sap/ui/support/supportRules/report/ReportProvider.js":["jquery.sap.global.js","sap/ui/support/supportRules/report/Archiver.js","sap/ui/support/supportRules/report/IssueRenderer.js","sap/ui/thirdparty/handlebars.js"],
"sap/ui/support/supportRules/ui/IFrameController.js":["jquery.sap.global.js","sap/ui/base/ManagedObject.js","sap/ui/support/supportRules/Constants.js","sap/ui/support/supportRules/WCBChannels.js","sap/ui/support/supportRules/WindowCommunicationBus.js"],
"sap/ui/support/supportRules/ui/Overlay.js":["sap/m/Page.js","sap/ui/core/Core.js","sap/ui/core/mvc/XMLView.js"],
"sap/ui/support/supportRules/ui/controllers/Analysis.controller.js":["jquery.sap.global.js","sap/m/Button.js","sap/m/InputListItem.js","sap/m/Label.js","sap/m/List.js","sap/m/ListItemBase.js","sap/m/MessageToast.js","sap/m/Panel.js","sap/m/StandardListItem.js","sap/m/Toolbar.js","sap/m/ToolbarSpacer.js","sap/ui/model/json/JSONModel.js","sap/ui/support/supportRules/Constants.js","sap/ui/support/supportRules/RuleSerializer.js","sap/ui/support/supportRules/Storage.js","sap/ui/support/supportRules/WCBChannels.js","sap/ui/support/supportRules/WindowCommunicationBus.js","sap/ui/support/supportRules/ui/controllers/BaseController.js","sap/ui/support/supportRules/ui/controllers/PresetsController.js","sap/ui/support/supportRules/ui/models/CustomJSONListSelection.js","sap/ui/support/supportRules/ui/models/PresetsUtils.js","sap/ui/support/supportRules/ui/models/SelectionUtils.js","sap/ui/support/supportRules/ui/models/SharedModel.js"],
"sap/ui/support/supportRules/ui/controllers/BaseController.js":["sap/ui/core/mvc/Controller.js","sap/ui/support/supportRules/Constants.js","sap/ui/support/supportRules/Storage.js","sap/ui/support/supportRules/ui/models/PresetsUtils.js","sap/ui/support/supportRules/ui/models/SelectionUtils.js"],
"sap/ui/support/supportRules/ui/controllers/Issues.controller.js":["jquery.sap.global.js","sap/m/OverflowToolbarAssociativePopoverControls.js","sap/ui/model/json/JSONModel.js","sap/ui/support/supportRules/Constants.js","sap/ui/support/supportRules/IssueManager.js","sap/ui/support/supportRules/WCBChannels.js","sap/ui/support/supportRules/WindowCommunicationBus.js","sap/ui/support/supportRules/ui/controllers/BaseController.js","sap/ui/support/supportRules/ui/external/ElementTree.js","sap/ui/support/supportRules/ui/models/SharedModel.js","sap/ui/support/supportRules/ui/models/formatter.js"],
"sap/ui/support/supportRules/ui/controllers/Main.controller.js":["sap/ui/model/json/JSONModel.js","sap/ui/support/supportRules/Constants.js","sap/ui/support/supportRules/Storage.js","sap/ui/support/supportRules/WCBChannels.js","sap/ui/support/supportRules/WindowCommunicationBus.js","sap/ui/support/supportRules/ui/controllers/BaseController.js","sap/ui/support/supportRules/ui/models/Documentation.js","sap/ui/support/supportRules/ui/models/SharedModel.js","sap/ui/thirdparty/URI.js"],
"sap/ui/support/supportRules/ui/controllers/PresetsController.js":["sap/m/GroupHeaderListItem.js","sap/m/MessageBox.js","sap/m/MessageToast.js","sap/ui/core/Fragment.js","sap/ui/core/library.js","sap/ui/support/library.js","sap/ui/support/supportRules/ui/controllers/BaseController.js","sap/ui/support/supportRules/ui/models/Documentation.js","sap/ui/support/supportRules/ui/models/PresetsUtils.js","sap/ui/support/supportRules/ui/models/SelectionUtils.js","sap/ui/support/supportRules/util/Utils.js","sap/ui/thirdparty/jquery.js"],
"sap/ui/support/supportRules/ui/external/ElementTree.js":["jquery.sap.global.js"],
"sap/ui/support/supportRules/ui/models/CustomJSONListSelection.js":["sap/ui/support/supportRules/Constants.js","sap/ui/support/supportRules/Storage.js","sap/ui/support/supportRules/ui/models/CustomListSelection.js","sap/ui/support/supportRules/ui/models/SelectionUtils.js"],
"sap/ui/support/supportRules/ui/models/CustomListSelection.js":["sap/ui/base/EventProvider.js","sap/ui/model/SelectionModel.js","sap/ui/support/supportRules/ui/models/SharedModel.js"],
"sap/ui/support/supportRules/ui/models/Documentation.js":["jquery.sap.global.js","sap/m/library.js"],
"sap/ui/support/supportRules/ui/models/PresetsUtils.js":["sap/ui/core/util/File.js","sap/ui/support/library.js","sap/ui/support/supportRules/Constants.js","sap/ui/support/supportRules/Storage.js","sap/ui/support/supportRules/ui/models/SelectionUtils.js","sap/ui/support/supportRules/ui/models/SharedModel.js","sap/ui/thirdparty/jquery.js"],
"sap/ui/support/supportRules/ui/models/SelectionUtils.js":["jquery.sap.global.js","sap/ui/support/supportRules/Constants.js","sap/ui/support/supportRules/Storage.js","sap/ui/support/supportRules/ui/models/SharedModel.js"],
"sap/ui/support/supportRules/ui/models/SharedModel.js":["sap/ui/model/json/JSONModel.js","sap/ui/support/library.js"],
"sap/ui/support/supportRules/ui/models/formatter.js":["sap/ui/support/supportRules/Constants.js"],
"sap/ui/support/supportRules/ui/views/Analysis.view.xml":["sap/m/Bar.js","sap/m/Button.js","sap/m/FormattedText.js","sap/m/IconTabFilter.js","sap/m/IconTabHeader.js","sap/m/Label.js","sap/m/Link.js","sap/m/List.js","sap/m/NavContainer.js","sap/m/Page.js","sap/m/StandardListItem.js","sap/m/Text.js","sap/m/Toolbar.js","sap/m/ToolbarLayoutData.js","sap/ui/core/mvc/XMLView.js","sap/ui/layout/Splitter.js","sap/ui/layout/VerticalLayout.js","sap/ui/support/supportRules/ui/controllers/Analysis.controller.js"],
"sap/ui/support/supportRules/ui/views/AnalyzeSettings.fragment.xml":["sap/m/CheckBox.js","sap/m/Input.js","sap/m/Popover.js","sap/m/RadioButton.js","sap/m/VBox.js","sap/ui/core/CustomData.js","sap/ui/core/Fragment.js","sap/ui/layout/VerticalLayout.js"],
"sap/ui/support/supportRules/ui/views/Issues.view.xml":["sap/m/Bar.js","sap/m/Button.js","sap/m/FlexBox.js","sap/m/FlexItemData.js","sap/m/FormattedText.js","sap/m/HBox.js","sap/m/Label.js","sap/m/Link.js","sap/m/Menu.js","sap/m/MenuButton.js","sap/m/MenuItem.js","sap/m/OverflowToolbarLayoutData.js","sap/m/Page.js","sap/m/Panel.js","sap/m/Select.js","sap/m/Text.js","sap/m/Title.js","sap/m/Toolbar.js","sap/m/ToolbarSpacer.js","sap/ui/core/HTML.js","sap/ui/core/Icon.js","sap/ui/core/ListItem.js","sap/ui/core/mvc/XMLView.js","sap/ui/layout/Splitter.js","sap/ui/layout/VerticalLayout.js","sap/ui/layout/form/SimpleForm.js","sap/ui/support/supportRules/ui/controllers/Issues.controller.js"],
"sap/ui/support/supportRules/ui/views/Main.view.xml":["sap/m/Bar.js","sap/m/Button.js","sap/m/FlexBox.js","sap/m/FlexItemData.js","sap/m/Image.js","sap/m/NavContainer.js","sap/m/Page.js","sap/m/ProgressIndicator.js","sap/m/Text.js","sap/m/Toolbar.js","sap/ui/core/Icon.js","sap/ui/core/mvc/XMLView.js","sap/ui/layout/FixFlex.js","sap/ui/support/supportRules/ui/controllers/Main.controller.js","sap/ui/support/supportRules/ui/views/Analysis.view.xml"],
"sap/ui/support/supportRules/ui/views/PresetExport.fragment.xml":["sap/m/Button.js","sap/m/Dialog.js","sap/m/Input.js","sap/m/Label.js","sap/m/Text.js","sap/m/TextArea.js","sap/ui/core/Fragment.js","sap/ui/layout/form/SimpleForm.js"],
"sap/ui/support/supportRules/ui/views/PresetImport.fragment.xml":["sap/m/Button.js","sap/m/Dialog.js","sap/m/HBox.js","sap/m/Label.js","sap/m/MessageStrip.js","sap/m/ScrollContainer.js","sap/m/Text.js","sap/m/VBox.js","sap/ui/core/Fragment.js","sap/ui/layout/form/SimpleForm.js","sap/ui/unified/FileUploader.js"],
"sap/ui/support/supportRules/ui/views/Presets.fragment.xml":["sap/m/Bar.js","sap/m/Button.js","sap/m/CustomListItem.js","sap/m/FormattedText.js","sap/m/HBox.js","sap/m/List.js","sap/m/Page.js","sap/m/ResponsivePopover.js","sap/m/Title.js","sap/m/Toolbar.js","sap/m/ToolbarSpacer.js","sap/ui/core/Fragment.js","sap/ui/core/Icon.js"],
"sap/ui/support/supportRules/ui/views/RuleDetails.fragment.xml":["sap/m/Bar.js","sap/m/IconTabFilter.js","sap/m/IconTabHeader.js","sap/m/Label.js","sap/m/Link.js","sap/m/Page.js","sap/m/Panel.js","sap/m/Text.js","sap/m/Title.js","sap/ui/codeeditor/CodeEditor.js","sap/ui/core/Fragment.js","sap/ui/layout/SplitterLayoutData.js","sap/ui/layout/VerticalLayout.js","sap/ui/layout/form/SimpleForm.js"],
"sap/ui/support/supportRules/ui/views/RuleUpdate.fragment.xml":["sap/m/Bar.js","sap/m/Button.js","sap/m/FlexItemData.js","sap/m/HBox.js","sap/m/IconTabFilter.js","sap/m/IconTabHeader.js","sap/m/Input.js","sap/m/Label.js","sap/m/Link.js","sap/m/MultiComboBox.js","sap/m/Page.js","sap/m/Panel.js","sap/m/RadioButton.js","sap/m/Text.js","sap/m/TextArea.js","sap/ui/codeeditor/CodeEditor.js","sap/ui/core/CustomData.js","sap/ui/core/Fragment.js","sap/ui/core/Icon.js","sap/ui/core/Item.js","sap/ui/layout/HorizontalLayout.js","sap/ui/layout/VerticalLayout.js","sap/ui/layout/form/SimpleForm.js"],
"sap/ui/support/supportRules/ui/views/StorageSettings.fragment.xml":["sap/m/Button.js","sap/m/CheckBox.js","sap/m/FlexBox.js","sap/m/Popover.js","sap/m/Text.js","sap/ui/core/Fragment.js","sap/ui/core/Icon.js","sap/ui/layout/HorizontalLayout.js","sap/ui/layout/VerticalLayout.js"]
}});
//# sourceMappingURL=library-h2-preload.js.map