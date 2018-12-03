/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2018 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
/**
 * Adds support rules of the sap.m library to the support infrastructure.
 */
sap.ui.predefine('sap/m/library.support',[
	"sap/ui/support/library",
	"./rules/Breadcrumbs.support",
	"./rules/Button.support",
	"./rules/CheckBox.support",
	"./rules/Dialog.support",
	"./rules/IconTabBar.support",
	"./rules/Image.support",
	"./rules/Input.support",
	"./rules/Link.support",
	"./rules/MessagePage.support",
	"./rules/ObjectHeader.support",
	"./rules/ObjectListItem.support",
	"./rules/ObjectMarker.support",
	"./rules/ObjectStatus.support",
	"./rules/Panel.support",
	"./rules/Select.support",
	"./rules/SelectDialog.support",
	"./rules/Table.support",
	"./rules/Title.support",
	"./rules/Tokenizer.support"
],
	function(
		SupportLib,
		BreadcrumbsSupport,
		ButtonSupport,
		CheckBoxSupport,
		DialogSupport,
		IconTabBarSupport,
		ImageSupport,
		InputSupport,
		LinkSupport,
		MessagePageSupport,
		ObjectHeaderSupport,
		ObjectListItemSupport,
		ObjectMarkerSupport,
		ObjectStatusSupport,
		PanelSupport,
		SelectSupport,
		SelectDialogSupport,
		TableSupport,
		TitleSupport,
		TokenizerSupport
	) {
	"use strict";

	return {
		name: "sap.m",
		niceName: "UI5 Main Library",
		ruleset: [
			BreadcrumbsSupport,
			ButtonSupport,
			CheckBoxSupport,
			DialogSupport,
			IconTabBarSupport,
			ImageSupport,
			InputSupport,
			LinkSupport,
			MessagePageSupport,
			ObjectHeaderSupport,
			ObjectListItemSupport,
			ObjectMarkerSupport,
			ObjectStatusSupport,
			PanelSupport,
			SelectSupport,
			SelectDialogSupport,
			TableSupport,
			TitleSupport,
			TokenizerSupport
		]
	};

}, true);
/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2018 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
/**
 * Defines support rules of the Select control of sap.m library.
 */
sap.ui.predefine('sap/m/rules/Breadcrumbs.support',["sap/ui/support/library"],
	function(SupportLib) {
	"use strict";

	// shortcuts
	var Categories = SupportLib.Categories, // Accessibility, Performance, Memory, ...
		Severity = SupportLib.Severity, // Low, Medium, High
		Audiences = SupportLib.Audiences; // Control, Internal, Application

	//**********************************************************
	// Rule Definitions
	//**********************************************************

	/**
	 * Checks if the Breadcrumbs control is placed in OverflowToolbar
	 */
	var oBreadcrumbsRule = {
		id : "breadcrumbsInOverflowToolbar",
		audiences: [Audiences.Control],
		categories: [Categories.Usability],
		enabled: true,
		minversion: "1.34",
		title: "Breadcrumbs in OverflowToolbar",
		description: "The Breadcrumbs should not be placed inside an OverflowToolbar",
		resolution: "Place breadcrumbs in another container.",
		resolutionurls: [{
			text: "SAP Fiori Design Guidelines: Breadcrumbs",
			href: "https://experience.sap.com/fiori-design-web/breadcrumb/#guidelines"
		}],
		check: function (oIssueManager, oCoreFacade, oScope) {
			oScope.getElementsByClassName("sap.m.Breadcrumbs")
				.forEach(function(oElement) {

					var sElementId = oElement.getId(),
						sElementName = oElement.getMetadata().getElementName();

					if (oElement.getParent() instanceof sap.m.OverflowToolbar) {
						oIssueManager.addIssue({
							severity: Severity.Medium,
							details: "Breadcrumbs '" + sElementName + "' (" + sElementId + ") is placed inside an OverflowToolbar.",
							context: {
								id: sElementId
							}
						});
					}
				});
		}
	};

	return [oBreadcrumbsRule];

}, true);
/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2018 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
/**
 * Defines support rules of the Button control of sap.m library.
 */
sap.ui.predefine('sap/m/rules/Button.support',["sap/ui/support/library"],
	function(SupportLib) {
	"use strict";

	// shortcuts
	var Categories = SupportLib.Categories, // Accessibility, Performance, Memory, ...
		Severity = SupportLib.Severity,	// Hint, Warning, Error
		Audiences = SupportLib.Audiences; // Control, Internal, Application

	//**********************************************************
	// Rule Definitions
	//**********************************************************

	/**
	 *Checks, if a button consisting of only an icon has a tooltip (design guideline)
	 */
	var oButtonRule = {
		id : "onlyIconButtonNeedsTooltip",
		audiences: [Audiences.Control],
		categories: [Categories.Usability],
		enabled: true,
		minversion: "1.28",
		title: "Button: Consists of only an icon, needs a tooltip",
		description: "A button without text needs a tooltip, so that the user knows what the button does",
		resolution: "Add a value to the tooltip property of the button",
		resolutionurls: [{
			text: "SAP Fiori Design Guidelines: Button",
			href: "https://experience.sap.com/fiori-design-web/button/#guidelines"
		}],
		check: function (oIssueManager, oCoreFacade, oScope) {
			oScope.getElementsByClassName("sap.m.Button")
				.forEach(function(oElement) {
					if (oElement.getProperty("icon")
						&& !oElement.getProperty("text")
						&& !oElement.getAggregation("tooltip")) {

						var sElementId = oElement.getId(),
							sElementName = oElement.getMetadata().getElementName();

						oIssueManager.addIssue({
							severity: Severity.Medium,
							details: "Button '" + sElementName + "' (" + sElementId + ") consists of only an icon but has no tooltip",
							context: {
								id: sElementId
							}
						});
					}
				});
		}
	};

	return [oButtonRule];

}, true);
/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2018 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
/**
 * Defines support rules of the CheckBox control of sap.m library.
 */
sap.ui.predefine('sap/m/rules/CheckBox.support',["sap/ui/support/library"],
	function(SupportLib) {
		"use strict";

		var Categories = SupportLib.Categories, // Accessibility, Performance, Memory, ...
			Severity = SupportLib.Severity, // Low, Medium, High
			Audiences = SupportLib.Audiences; // Control, Internal, Application

		//**********************************************************
		// Rule Definitions
		//**********************************************************

		/**
		* Checks if the control is <code>enabled</code>, when the <code>editable</code> property is true.
		*/
		var oCheckBoxRule = {
			id : "checkBoxDisabledAndEditable",
			audiences: [Audiences.Control],
			categories: [Categories.Functionality],
			enabled: true,
			minversion: "-",
			title: "CheckBox: the control is editable, while the control is disabled",
			description: "Disabled control can`t be edited",
			resolution: "Either set enabled to true ot set editable to false",
			resolutionurls: [{
				text: "API Reference: sap.m.CheckBox",
				href: "https://sapui5.hana.ondemand.com/#/api/sap.m.CheckBox"
			}],
			check: function (oIssueManager, oCoreFacade, oScope) {
				oScope.getElementsByClassName("sap.m.CheckBox")
					.forEach(function(oElement) {
						var sElementId,
							sElementName;

						if (oElement.getEditable() && !oElement.getEnabled()) {
								sElementId = oElement.getId();
								sElementName = oElement.getMetadata().getElementName();

								oIssueManager.addIssue({
									severity: Severity.Low,
									details: "CheckBox '" + sElementName + "' (" + sElementId + ") is editable, but disabled",
									context: {
										id: sElementId
									}
								});
							}
						});
			}
		};

		return [oCheckBoxRule];

	}, true);
/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2018 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
/**
 * Defines support rules of the Dialog control of sap.m library.
 */
sap.ui.predefine('sap/m/rules/Dialog.support',["sap/ui/support/library"],
	function(SupportLib) {
	"use strict";

	//shortcuts
	var Categories = SupportLib.Categories, // Accessibility, Performance, Memory, ...
		Severity = SupportLib.Severity,	// Hint, Warning, Error
		Audiences = SupportLib.Audiences; // Control, Internal, Application

	//**********************************************************
	// Rule Definitions
	//**********************************************************

	var oDialogRuleForJaws = {
		id: "dialogAriaLabelledBy",
		audiences: [Audiences.Application],
		categories: [Categories.Accessibility],
		enabled: true,
		minversion: "*",
		title: "Dialog: The content will not be read if there is no focusable control inside it unless ariaLabelledBy is set",
		description: "When the Dialog is opened and ariaLabelledBy is not set, if there are focusable controls the first focusable control will be read, if there are no focusable controls in the content, JAWS will read only the footer and header of the Dialog ",
		resolution: "Add ariaLabelledBy for the Dialog, with value - IDs of the non focusable control(s) which are inside the Dialog content",
		resolutionurls: [{
			text: "Dialog controls: Accessibility",
			href: "https://ui5.sap.com/#/topic/5709e73d51f2401a9a5a89d8f5479132"
		}],
		check: function (oIssueManager, oCoreFacade, oScope) {
			oScope.getElementsByClassName("sap.m.Dialog")
				.forEach(function(oElement) {
					if (!oElement.getAssociation("ariaLabelledBy")) {
						var sElementId = oElement.getId(),
							sElementName = oElement.getMetadata().getElementName();

						oIssueManager.addIssue({
							severity: Severity.Medium,
							details: "Dialog '" + sElementName + "' (" + sElementId + ") has no ariaLabelledBy association set",
							context: {
								id: sElementId
							}
						});
					}
				});
		}
	};

	return [oDialogRuleForJaws];

}, true);
/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2018 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
/**
 * Defines support rules of the IconTabBar control of sap.m library.
 */
sap.ui.predefine('sap/m/rules/IconTabBar.support',["sap/ui/support/library"],
	function(SupportLib) {
	"use strict";

	// shortcuts
	var Categories = SupportLib.Categories, // Accessibility, Performance, Memory, ...
		Severity = SupportLib.Severity,	// Hint, Warning, Error
		Audiences = SupportLib.Audiences; // Control, Internal, Application

	//**********************************************************
	// Rule Definitions
	//**********************************************************

	var oIconTabBarRuleHDesign = {
		id: "iconTabFilterWithHorizontalDesingShouldHaveIcons",
		audiences: [Audiences.Application],
		categories: [Categories.FioriGuidelines],
		enabled: true,
		minversion: "*",
		title: "IconTabBar: tab filters with horizontal design should always have icons",
		description: "According to Fiori guidelines tab filters with horizontal design shall always have icons",
		resolution: 'Add icons to all tabs \n Note: There is one exception - if "showAll" is set to true, icon may not be set',
		resolutionurls: [{
			text: "SAP Fiori Design Guidelines: IconTabBar",
			href: "https://experience.sap.com/fiori-design-web/icontabbar/#guidelines"
		}],
		check: function (oIssueManager, oCoreFacade, oScope) {
			oScope.getElementsByClassName("sap.m.IconTabFilter")
				.forEach(function(oElement) {
					if (oElement.getProperty("design") === sap.m.IconTabFilterDesign.Horizontal
						&& !oElement.getProperty("icon")
						&& !oElement.getProperty("showAll")) {

						var sElementId = oElement.getId(),
							sElementName = oElement.getMetadata().getElementName();

						oIssueManager.addIssue({
							severity: Severity.High,
							details: "IconTabFilter '" + sElementName + "' (" + sElementId + ") consists only of text, icon needs to be set",
							context: {
								id: sElementId
							}
						});
					}
				});
		}
	};

	var oIconTabBarRuleIcons = {
		id: "iconTabBarIconsRule",
		audiences: [Audiences.Application],
		categories: [Categories.FioriGuidelines],
		enabled: true,
		minversion: "*",
		title: "IconTabBar: Icons rule for tabs",
		description: 'Either all tabs should have icons or none of them. Note: There is one exception - There is one exception - if "showAll" is set to true, icon may not be set',
		resolution: "Make all tabs the same type",
		resolutionurls: [{
			text: "SAP Fiori Design Guidelines: IconTabBar",
			href: "https://experience.sap.com/fiori-design-web/icontabbar/#guidelines"
		}],
		check: function (oIssueManager, oCoreFacade, oScope) {
			oScope.getElementsByClassName("sap.m.IconTabBar")
				.forEach(function(oElement) {
					var aIconTabFilters = oElement.getItems();
					var bHasIconFirstTab;
					var bHasIconSomeTab;
					var bHasDifference = false;
					var bFirstCheckedTab = true;

					for (var index = 0; index < aIconTabFilters.length; index++) {
						if (aIconTabFilters[index].isA('sap.m.IconTabFilter') && !aIconTabFilters[index].getProperty("showAll")) {
							if (bFirstCheckedTab) {
								bHasIconFirstTab = !!aIconTabFilters[index].getIcon();
								bFirstCheckedTab = false;
							} else {
								bHasIconSomeTab = !!aIconTabFilters[index].getIcon();
								if (bHasIconFirstTab !== bHasIconSomeTab) {
									bHasDifference = true;
									break;
								}
							}
						}
					}

					if (bHasDifference) {

						var sElementId = oElement.getId(),
							sElementName = oElement.getMetadata().getElementName();

						oIssueManager.addIssue({
							severity: Severity.High,
							details: "In one IconTabBar '" + sElementName + "' (" + sElementId + ") all tabs should have icons or all tabs shouldn't have icons",
							context: {
								id: sElementId
							}
						});
					}
				});
		}
	};

	var oIconTabBarRuleIconsLongCount = {
		id: "iconTabFilterWithIconsAndLongCount",
		audiences: [Audiences.Application],
		categories: [Categories.FioriGuidelines],
		enabled: true,
		minversion: "*",
		title: "IconTabBar: IconTabFilters with icons and long count number should have horizontal design",
		description: "Note: All filters in one IconTabBar should have the same design",
		resolution: "Change the design property to horizontal for all tabs in the IconTabBar",
		resolutionurls: [{
			text: "SAP Fiori Design Guidelines: IconTabBar",
			href: "https://experience.sap.com/fiori-design-web/icontabbar/#guidelines"
		}],
		check: function (oIssueManager, oCoreFacade, oScope) {
			oScope.getElementsByClassName("sap.m.IconTabFilter")
				.forEach(function(oElement) {
					if (oElement.getProperty("design") === sap.m.IconTabFilterDesign.Vertical
						&& oElement.getProperty("icon")
						&& oElement.getProperty("count").length > 4) {

						var sElementId = oElement.getId(),
							sElementName = oElement.getMetadata().getElementName();

						oIssueManager.addIssue({
							severity: Severity.High,
							details: "IconTabFilter '" + sElementName + "' (" + sElementId + ") has long count and should have horizontal design",
							context: {
								id: sElementId
							}
						});
					}
				});
		}
	};


	return [oIconTabBarRuleHDesign, oIconTabBarRuleIcons, oIconTabBarRuleIconsLongCount];

}, true);
/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2018 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
/**
 * Defines support rules of the Image control of sap.m library.
 */
sap.ui.predefine('sap/m/rules/Image.support',["sap/ui/support/library"],
	function(SupportLib) {
	"use strict";

	// shortcuts
	var Categories = SupportLib.Categories, // Accessibility, Performance, Memory, ...
		Severity = SupportLib.Severity, // Low, Medium, High
		Audiences = SupportLib.Audiences; // Control, Internal, Application


	//**********************************************************
	// Utils
	//**********************************************************
	var HIGH_DENSITIES = [1.5, 2], // these are the densities mostly expected to be supported by the app (3x and 4x will be skipped to avoid too many requests that prolong the check)
		REQUEST_TIMEOUT = 3000; //ms

	function downloadHighDensityImage(oImage, iDensity) {

		return new Promise(function(resolve, reject) {

			var sSrc = oImage.getSrc(),
				sDensityAwareSrc = oImage._generateSrcByDensity(sSrc, iDensity),
				oDomElement = document.createElement("IMG"),
				bDone = false;

			// check src availability using src property of a dummy dom element
			// to avoid making AJAX request (may be forbidden if conflicts with CORS)
			oDomElement.setAttribute("src", sDensityAwareSrc);
			oDomElement.style.position = "absolute";
			oDomElement.style.left = "-10000px";
			oDomElement.style.top = "-10000px";

			function onLoad() {
				cleanup();
				resolve(true);
			}

			function onError() {
				cleanup();
				resolve(false);
			}

			function cleanup() {
				oDomElement.remove(); // allow this element and its attached listeners be picked up by the GC
				bDone = true;
			}

			oDomElement.addEventListener("load", onLoad);
			oDomElement.addEventListener("error", onError);
			document.body.appendChild(oDomElement);

			// ensure check is completed even if none of the events are called
			// (e.g. iOS may not fire load for an already loaded and cached image)
			setTimeout(function() {
				if (!bDone) {
					reject(); // densityAwareSrc availability is not confirmed
				}
			}, REQUEST_TIMEOUT);

		});
	}

	//**********************************************************
	// Rule Definitions
	//**********************************************************

	/**
	 * Checks if the <code>densityAware</code> property of <code>sap.m.Image</code> is enabled when density-perfect image version exists
	 */
	var oImageRule = {
		id : "densityAwareImage",
		audiences: [Audiences.Control],
		categories: [Categories.Usability],
		enabled: true,
		async: true,
		minversion: "1.60",
		title: "Image: Density awareness disabled",
		description: "We checked that your application provides high-density version(s) of the listed image(s). "
					+ "However, the high-density version(s) will be ignored, because the \"densityAware\" property of this image is disabled. "
					+ "Since UI5 1.60, the \"densityAware\" property is no longer enabled by default. You need to enable it explicitly.",
		resolution: "Enable the \"densityAware\" property of this image control",
		resolutionurls: [{
			text: "API Refrence for sap.m.Image",
			href: "https://sapui5.hana.ondemand.com/#/api/sap.m.Image"
		}],
		check: function (oIssueManager, oCoreFacade, oScope, fnResolve) {

			var aAsyncTasks = [],
				aIssuedImageIds = [],
				oTask,
				sImageId,
				sImageName;

			oScope.getElementsByClassName("sap.m.Image")
				.forEach(function(oImage) {
					if (!oImage.getDensityAware()) {

						HIGH_DENSITIES.forEach(function(iDensity) {

							oTask = downloadHighDensityImage(oImage, iDensity);

							aAsyncTasks.push(oTask);

							oTask.then(function(bSuccess) {
								if (!bSuccess) {
									return;
								}

								sImageId = oImage.getId();

								if (aIssuedImageIds.indexOf(sImageId) > -1) {
									return; // already issued warning for this image
								}

								aIssuedImageIds.push(sImageId);

								sImageName = oImage.getMetadata().getElementName();

								oIssueManager.addIssue({
									severity: Severity.Low,
									details: "Image '" + sImageName + "' (" + sImageId + ") has 'densityAware' disabled even though high-density version is also available",
									context: {
										id: sImageId
									}
								});
							})
							.catch(function() {
								// ignore as only the cases of successful executions are of interest to this rule
							});
						});
					}
				});

			Promise.all(aAsyncTasks).then(fnResolve);
		}
	};

	return [oImageRule];

}, true);
/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2018 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
/**
 * Defines support rules of the List, Table and Tree controls of sap.m library.
 */
sap.ui.predefine('sap/m/rules/Input.support',["sap/ui/support/library"],
	function(SupportLib) {
	"use strict";

	// shortcuts
	var Categories = SupportLib.Categories, // Accessibility, Performance, Memory, ...
		Severity = SupportLib.Severity,	// Hint, Warning, Error
		Audiences = SupportLib.Audiences; // Control, Internal, Application

	//**********************************************************
	// Rule Definitions
	//**********************************************************

	/**
	 * Input field needs to have a label association
	 */
	var oInputNeedsLabelRule = {
		id: "inputNeedsLabel",
		audiences: [Audiences.Control],
		categories: [Categories.Usability],
		enabled: true,
		minversion: "1.28",
		title: "Input field: Missing label",
		description:"An input field needs a label",
		resolution: "Define a sap.m.Label for the input field in the xml view and set the labelFor property to this input field Id.",
		resolutionurls: [{
			text: "SAP Fiori Design Guidelines: Input field",
			href:"https://experience.sap.com/fiori-design-web/input-field/#guidelines"
		}],
		check: function (issueManager, oCoreFacade, oScope) {

			var aInputIds = oScope.getElementsByClassName("sap.m.Input")
				.map(function(oInput) {
					return oInput.getId();
				});

			oScope.getElementsByClassName("sap.m.Label")
				.forEach(function (oLabel){
					var sLabelFor = oLabel.getLabelFor();
					if (aInputIds.indexOf(sLabelFor) > -1) {
						var iIndex = aInputIds.indexOf(sLabelFor);
						aInputIds.splice(iIndex, 1);
					}
				});

			if (aInputIds.length > 0) {
				aInputIds.forEach(function(sInputId) {
					issueManager.addIssue({
						severity: Severity.Medium,
						details: "Input field" + " (" + sInputId + ") is missing a label.",
						context: {
							id: sInputId
						}
					});
				});
			}
		}
	};

	return [oInputNeedsLabelRule];

}, true);
/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2018 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
/**
 * Defines support rules of the Link control of sap.m library.
 */
sap.ui.predefine('sap/m/rules/Link.support',["sap/ui/support/library"],
	function(SupportLib) {
	"use strict";

	// shortcuts
	var Categories = SupportLib.Categories, // Accessibility, Performance, Memory, ...
		Severity = SupportLib.Severity,	// Hint, Warning, Error
		Audiences = SupportLib.Audiences; // Control, Internal, Application

	//**********************************************************
	// Rule Definitions
	//**********************************************************

	/**
	 *Checks, if a link with attached press handler has no href property set
	 */
	var oLinkRule = {
		id : "linkWithPressHandlerNoHref",
		audiences: [Audiences.Control],
		categories: [Categories.Usability],
		enabled: true,
		minversion: "1.28",
		title: "Link: If a press handler is attached, the href property should not be set",
		description: "If a JavaScript action should be triggered using the press event, the href property should not be set",
		resolution: "Remove the href property of the link",
		resolutionurls: [{
			text: "API Reference: sap.m.Link",
			href: "https://sapui5.hana.ondemand.com/#/api/sap.m.Link"
		}],
		check: function (oIssueManager, oCoreFacade, oScope) {
			oScope.getElementsByClassName("sap.m.Link")
				.forEach(function(oElement) {
					if (oElement.getProperty("href")
						&& oElement.mEventRegistry.hasOwnProperty("press")) {

						var sElementId = oElement.getId(),
							sElementName = oElement.getMetadata().getElementName();

						oIssueManager.addIssue({
							severity: Severity.Medium,
							details: "Link '" + sElementName + "' (" + sElementId + ") has both press handler attached and href property set",
							context: {
								id: sElementId
							}
						});
					}
				});
		}
	};

	return [oLinkRule];

}, true);
/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2018 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
/**
 * Defines support rules of the MessagePage control of sap.m library.
 */
sap.ui.predefine('sap/m/rules/MessagePage.support',["sap/ui/support/library"],
function(SupportLib) {
	"use strict";

	// shortcuts
	var Categories = SupportLib.Categories, // Accessibility, Performance, Memory, ...
		Severity = SupportLib.Severity, // Hint, Warning, Error
		Audiences = SupportLib.Audiences; // Control, Internal, Application

	//**********************************************************
	// Rule Definitions
	//**********************************************************

	/**
	 * Determines <code>Control</code> computed height.
	 * @param {sap.ui.core.Control} oControl
	 * @returns {Number}
	 */
	var getControlHeight = function(oControl) {
		return oControl.getDomRef().getBoundingClientRect().height;
	};

	/**
	 * Checks, if MessagePage is in a container which has no set height
	 */
	var oMessagePageHeightRule = {
		id: "messagePageShouldNotBeInAContainerWithoutSetHeight",
		audiences: [Audiences.Application],
		categories: [Categories.Usability],
		enabled: true,
		minversion: "1.28",
		title: "Message Page: In a container without set height",
		description: "Message Page should not be used in a container which has no set height",
		resolution: "Use Message Page in a container with set height, such as sap.m.App",
		resolutionurls: [{
			text: "sap.m.MessagePage API Reference",
			href: "https://openui5.hana.ondemand.com/#/api/sap.m.MessagePage"
		}],
		check: function (oIssueManager, oCoreFacade, oScope) {
			oScope.getElementsByClassName("sap.m.MessagePage").forEach(function(oMPage) {

				var sMPageId = oMPage.getId(),
					iMPageHeight = getControlHeight(oMPage),
					iMPageHeaderHeight = oMPage.getShowHeader() ? getControlHeight(oMPage.getAggregation("_internalHeader")) : 0,
					iMPageContentHeight = iMPageHeight - iMPageHeaderHeight;

				if (oMPage.getParent() === oMPage.getUIArea() && iMPageContentHeight <= 0) {
					oIssueManager.addIssue({
						severity: Severity.High,
						details: "Message Page" + " (" + sMPageId + ") is used in a container which has no height set.",
						context: {
							id: sMPageId
						}
					});
				}
			});
		}
	};

	/**
	 * Checks, if MessagePage is a top-level control
	 */
	var oMessagePageHierarchyRule = {
		id: "messagePageShouldNotBeTopLevel",
		audiences: [Audiences.Application],
		categories: [Categories.Usability],
		enabled: true,
		minversion: "1.28",
		title: "Message Page: Top-level control",
		description: "Message Page should not be a top-level control",
		resolution: "Use Message Page as described in the SAP Fiori Design Guidelines",
		resolutionurls: [{
			text: "SAP Fiori Design Guidelines: Message Page",
			href: "https://experience.sap.com/fiori-design-web/message-page"
		}],
		check: function (oIssueManager, oCoreFacade, oScope) {
			oScope.getElementsByClassName("sap.m.MessagePage").forEach(function(oMPage) {
				var oMPageUIAreaControls = oMPage.getUIArea().getAggregation("content"),
					sMPageId = oMPage.getId();

				if (oMPageUIAreaControls.length > 1 && oMPage.getParent() === oMPage.getUIArea()) {
					oIssueManager.addIssue({
						severity: Severity.Medium,
						details: "Message Page" + " (" + sMPageId + ") is a top-level control.",
						context: {
							id: sMPageId
						}
					});
				}
			});
		}
	};

	return [oMessagePageHeightRule, oMessagePageHierarchyRule];

}, true);
/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2018 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
/**
 * Defines support rules of the ObjectHeader control of sap.m library.
 */
sap.ui.predefine('sap/m/rules/ObjectHeader.support',["sap/ui/support/library"],
	function(SupportLib) {
		"use strict";

		// shortcuts
		var Categories = SupportLib.Categories, // Accessibility, Performance, Memory, ...
			Severity = SupportLib.Severity, // Low, Medium, High
			Audiences = SupportLib.Audiences; // Control, Internal, Application

		//**********************************************************
		// Rule Definitions
		//**********************************************************

		/**
		 * Checks if the ObjectHeader control uses both markers and deprecated markedFlagged or markedFavorite
		 */
		var oObjHeaderMarkersRule = {
			id : "objectHeaderMarkers",
			audiences: [Audiences.Control],
			categories: [Categories.Usage],
			enabled: true,
			minversion: "1.42",
			title: "ObjectHeader: markers aggregation",
			description: "Checks if markers aggregation is used together with deprecated properties markFlagged or markFavorite",
			resolution: "Use markers aggregation",
			resolutionurls: [{
				text: "API Reference: sap.m.ObjectHeader",
				href: "https://sapui5.hana.ondemand.com/#/api/sap.m.ObjectHeader"
			}],
			check: function (oIssueManager, oCoreFacade, oScope) {
				oScope.getElementsByClassName("sap.m.ObjectHeader")
					.forEach(function(oElement) {

						var sElementId = oElement.getId(),
							sElementName = oElement.getMetadata().getElementName(),
							iDeprecatedMark = oElement.getMarkFlagged() + oElement.getMarkFavorite();

						if (oElement.getMarkers().length > iDeprecatedMark && iDeprecatedMark > 0) {
							oIssueManager.addIssue({
								severity: Severity.High,
								details: "ObjectHeader '" + sElementName + "' (" + sElementId + ") uses both markers aggregation and deprecated properties markFlagged or markFavorite.",
								context: {
									id: sElementId
								}
							});
						}
					});
			}
		};

		/**
		 * Checks if the ObjectHeader control uses both statuses and deprecated firstStatus or secondStatus
		 */
		var oObjHeaderStatusessRule = {
			id : "objectHeaderStatuses",
			audiences: [Audiences.Control],
			categories: [Categories.Usage],
			enabled: true,
			minversion: "1.16",
			title: "ObjectHeader: statuses aggregation",
			description: "Checks if statuses aggregation is used together with deprecated aggregation firstStatus or secondStatus",
			resolution: "Use statuses aggregation",
			resolutionurls: [{
				text: "API Reference: sap.m.ObjectHeader",
				href: "https://sapui5.hana.ondemand.com/#/api/sap.m.ObjectHeader"
			}],
			check: function (oIssueManager, oCoreFacade, oScope) {
				oScope.getElementsByClassName("sap.m.ObjectHeader")
					.forEach(function(oElement) {

						var sElementId = oElement.getId(),
							sElementName = oElement.getMetadata().getElementName();

						if (oElement.getStatuses().length && (oElement.getFirstStatus() || oElement.getSecondStatus())) {
							oIssueManager.addIssue({
								severity: Severity.Medium,
								details: "ObjectHeader '" + sElementName + "' (" + sElementId + ") uses both statuses aggregation and deprecated aggregations firstStatus or secondStatus.",
								context: {
									id: sElementId
								}
							});
						}
					});
			}
		};

		/**
		 * Checks if the responsive property is set to false when condensed property is used
		 */
		var oObjHeaderCondensedRule = {
			id : "objectHeaderCondensed",
			audiences: [Audiences.Control],
			categories: [Categories.Usage],
			enabled: true,
			minversion: "1.21",
			title: "ObjectHeader: condensed property",
			description: "Checks if condensed property is set to true and responsive property is set to false",
			resolution: "Change the responsive property to false",
			resolutionurls: [{
				text: "API Reference: sap.m.ObjectHeader",
				href: "https://sapui5.hana.ondemand.com/#/api/sap.m.ObjectHeader"
			}],
			check: function (oIssueManager, oCoreFacade, oScope) {
				oScope.getElementsByClassName("sap.m.ObjectHeader")
					.forEach(function(oElement) {

						var sElementId = oElement.getId(),
							sElementName = oElement.getMetadata().getElementName();

						if (oElement.getCondensed() && oElement.getResponsive()) {
							oIssueManager.addIssue({
								severity: Severity.Medium,
								details: "ObjectHeader '" + sElementName + "' (" + sElementId + ") sets both condensed and responsive property to true.",
								context: {
									id: sElementId
								}
							});
						}
					});
			}
		};

		/**
		 * Checks if the responsive property is set to true when fullScreenOptimized property is used
		 */
		var oObjHeaderFullScreenOptimizedRule = {
			id : "objectHeaderFullScreenOptimized",
			audiences: [Audiences.Control],
			categories: [Categories.Usage],
			enabled: true,
			minversion: "1.28",
			title: "ObjectHeader: fullScreenOptimized property",
			description: "Checks if fullScreenOptimized property is set to true and responsive property is set to true",
			resolution: "Change the responsive property to true",
			resolutionurls: [{
				text: "API Reference: sap.m.ObjectHeader",
				href: "https://sapui5.hana.ondemand.com/#/api/sap.m.ObjectHeader"
			}],
			check: function (oIssueManager, oCoreFacade, oScope) {
				oScope.getElementsByClassName("sap.m.ObjectHeader")
					.forEach(function(oElement) {

						var sElementId = oElement.getId(),
							sElementName = oElement.getMetadata().getElementName();

						if (oElement.getFullScreenOptimized() && !oElement.getResponsive()) {
							oIssueManager.addIssue({
								severity: Severity.Medium,
								details: "ObjectHeader '" + sElementName + "' (" + sElementId + ") sets fullScreenOptimized to true but responsive property is false.",
								context: {
									id: sElementId
								}
							});
						}
					});
			}
		};

		/**
		 * Checks if the responsive property is set to false when additionalNumbers aggregation is used
		 */
		var oObjHeaderAdditionalNumbersRule = {
			id : "objectHeaderAdditionalNumbers",
			audiences: [Audiences.Control],
			categories: [Categories.Usage],
			enabled: true,
			minversion: "1.38",
			title: "ObjectHeader: additionalNumbers aggregation",
			description: "Checks if additionalNumbers aggregation is used and responsive property is set to false",
			resolution: "Change the responsive property to false",
			resolutionurls: [{
				text: "API Reference: sap.m.ObjectHeader",
				href: "https://sapui5.hana.ondemand.com/#/api/sap.m.ObjectHeader"
			}],
			check: function (oIssueManager, oCoreFacade, oScope) {
				oScope.getElementsByClassName("sap.m.ObjectHeader")
					.forEach(function(oElement) {

						var sElementId = oElement.getId(),
							sElementName = oElement.getMetadata().getElementName();

						if (oElement.getAdditionalNumbers().length && oElement.getResponsive()) {
							oIssueManager.addIssue({
								severity: Severity.Medium,
								details: "ObjectHeader '" + sElementName + "' (" + sElementId + ") uses additionalNumbers aggregation and responsive property is true.",
								context: {
									id: sElementId
								}
							});
						}
					});
			}
		};

		/**
		 * Checks if the responsive property is set to true when headerContainer aggregation is used
		 */
		var oObjHeaderHeaderContainerRule = {
			id : "objectHeaderHeaderContainer",
			audiences: [Audiences.Control],
			categories: [Categories.Usage],
			enabled: true,
			minversion: "1.21",
			title: "ObjectHeader: headerContainer aggregation",
			description: "Checks if headerContainer aggregation is used and responsive property is set to true",
			resolution: "Change the responsive property to true",
			resolutionurls: [{
				text: "API Reference: sap.m.ObjectHeader",
				href: "https://sapui5.hana.ondemand.com/#/api/sap.m.ObjectHeader"
			}],
			check: function (oIssueManager, oCoreFacade, oScope) {
				oScope.getElementsByClassName("sap.m.ObjectHeader")
					.forEach(function(oElement) {

						var sElementId = oElement.getId(),
							sElementName = oElement.getMetadata().getElementName();

						if (oElement.getHeaderContainer() && !oElement.getResponsive()) {
							oIssueManager.addIssue({
								severity: Severity.Medium,
								details: "ObjectHeader '" + sElementName + "' (" + sElementId + ") sets headerContainer aggregation but responsive property is false.",
								context: {
									id: sElementId
								}
							});
						}
					});
			}
		};


		return [
			oObjHeaderMarkersRule,
			oObjHeaderStatusessRule,
			oObjHeaderCondensedRule,
			oObjHeaderFullScreenOptimizedRule,
			oObjHeaderAdditionalNumbersRule,
			oObjHeaderHeaderContainerRule
		];

	}, true);
/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2018 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
/**
 * Defines support rules of the ObjectListItem control of sap.m library.
 */
sap.ui.predefine('sap/m/rules/ObjectListItem.support',["sap/ui/support/library"],
	function(SupportLib) {
		"use strict";

		// shortcuts
		var Categories = SupportLib.Categories, // Accessibility, Performance, Memory, ...
			Severity = SupportLib.Severity, // Low, Medium, High
			Audiences = SupportLib.Audiences; // Control, Internal, Application

		//**********************************************************
		// Rule Definitions
		//**********************************************************

		/**
		 * Checks if the ObjectListItem control uses both markers and deprecated markedFlagged or markedFavorite
		 */
		var oObjListItemMarkersRule = {
			id : "objectListItemMarkers",
			audiences: [Audiences.Control],
			categories: [Categories.Usage],
			enabled: true,
			minversion: "*",
			title: "ObjectListItem: markers aggregation",
			description: "Checks if markers aggregation is used together with deprecated properties markFlagged or markFavorite",
			resolution: "Use markers aggregation",
			resolutionurls: [{
				text: "API Reference: sap.m.ObjectListItem",
				href: "https://sapui5.hana.ondemand.com/#/api/sap.m.ObjectListItem"
			}],
			check: function (oIssueManager, oCoreFacade, oScope) {
				oScope.getElementsByClassName("sap.m.ObjectListItem")
					.forEach(function(oElement) {

						var sElementId = oElement.getId(),
							sElementName = oElement.getMetadata().getElementName(),
							iDeprecatedMark = oElement.getMarkFlagged() + oElement.getMarkFavorite();

						if (oElement.getMarkers().length > iDeprecatedMark && iDeprecatedMark > 0) {
							oIssueManager.addIssue({
								severity: Severity.High,
								details: "ObjectListItem '" + sElementName + "' (" + sElementId + ") uses both markers aggregation and deprecated properties markFlagged or markFavorite.",
								context: {
									id: sElementId
								}
							});
						}
					});
			}
		};

		return [
			oObjListItemMarkersRule
		];

	}, true);
/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2018 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
/**
 * Defines support rules of the ObjectMarker control of sap.m library.
 */
sap.ui.predefine('sap/m/rules/ObjectMarker.support',["sap/ui/support/library"],
	function(SupportLib) {
		"use strict";

		// shortcuts
		var Categories = SupportLib.Categories, // Accessibility, Performance, Memory, ...
			Severity = SupportLib.Severity, // Low, Medium, High
			Audiences = SupportLib.Audiences; // Control, Internal, Application

		//**********************************************************
		// Rule Definitions
		//**********************************************************

		/**
		 * Checks if the ObjectMarker sets type property when additionalInfo use used
		 */
		var oObjMarkerAdditionalInfoRule = {
			id : "objectMarkerAdditionalInfo",
			audiences: [Audiences.Control],
			categories: [Categories.Usage],
			enabled: true,
			minversion: "*",
			title: "ObjectMarker: additionalInfo property",
			description: "Checks if additionalInfo property is used but no type is set",
			resolution: "Set type of the ObjectMarker",
			resolutionurls: [{
				text: "API Reference: sap.m.ObjectMarker",
				href: "https://sapui5.hana.ondemand.com/#/api/sap.m.ObjectMarker"
			}],
			check: function (oIssueManager, oCoreFacade, oScope) {
				oScope.getElementsByClassName("sap.m.ObjectMarker")
					.forEach(function(oElement) {

						var sElementId = oElement.getId(),
							sElementName = oElement.getMetadata().getElementName();

						if (oElement.getAdditionalInfo() && !oElement.getType()) {
							oIssueManager.addIssue({
								severity: Severity.Medium,
								details: "ObjectMarker '" + sElementName + "' (" + sElementId + ") sets additionalInfo but has no type.",
								context: {
									id: sElementId
								}
							});
						}
					});
			}
		};

		return [
			oObjMarkerAdditionalInfoRule
		];

	}, true);
/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2018 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
/**
 * Defines support rules of the ObjectStatus control of sap.m library.
 */
sap.ui.predefine('sap/m/rules/ObjectStatus.support',["sap/ui/support/library"],
	function(SupportLib) {
		"use strict";

		// shortcuts
		var Categories = SupportLib.Categories, // Accessibility, Performance, Memory, ...
			Severity = SupportLib.Severity, // Low, Medium, High
			Audiences = SupportLib.Audiences; // Control, Internal, Application

		//**********************************************************
		// Rule Definitions
		//**********************************************************

		/**
		 * Checks if the ObjectStatus control sets text or icon when active property is set
		 */
		var oObjStatusActiveRule = {
			id : "objectStatusActive",
			audiences: [Audiences.Control],
			categories: [Categories.Usage],
			enabled: true,
			minversion: "*",
			title: "ObjectStatus: active property",
			description: "Checks if active property is set to true but no icon or text are set.",
			resolution: "Set text or icon when active property is true",
			resolutionurls: [{
				text: "API Reference: sap.m.ObjectStatus",
				href: "https://sapui5.hana.ondemand.com/#/api/sap.m.ObjectStatus"
			}],
			check: function (oIssueManager, oCoreFacade, oScope) {
				oScope.getElementsByClassName("sap.m.ObjectStatus")
					.forEach(function(oElement) {

						var sElementId = oElement.getId(),
							sElementName = oElement.getMetadata().getElementName();

						if (oElement.getActive() && !oElement.getText() && !oElement.getIcon()) {
							oIssueManager.addIssue({
								severity: Severity.Medium,
								details: "ObjectStatus '" + sElementName + "' (" + sElementId + ") sets active to true but no icon or text.",
								context: {
									id: sElementId
								}
							});
						}
					});
			}
		};

		return [
			oObjStatusActiveRule
		];

	}, true);
/* eslint-disable linebreak-style */
/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2018 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
/**
 * Defines support rules of the Panel control of sap.m library.
 */
sap.ui.predefine('sap/m/rules/Panel.support',["sap/ui/support/library"],
	function(SupportLib) {
		"use strict";
		// shortcuts
		var Categories = SupportLib.Categories, // Accessibility, Performance, Memory, ...
			Severity = SupportLib.Severity,	// Hint, Warning, Error
			Audiences = SupportLib.Audiences; // Control, Internal, Application

		//**********************************************************
		// Rule Definitions
		//**********************************************************

		/**
		 *Checks if a panel has a title or a header toolbar with a title
		 */
		var oPanelNeedHeaderRule = {
			id : "panelWithheaderTextOrWithHeaderToolbarWithTitle",
			audiences: [Audiences.Control],
			categories: [Categories.Usability],
			enabled: true,
			minversion: "1.28",
			title: "Panel: Header text is missing",
			description: "According to the SAP Fiori Guidelines, a panel needs a header text or a header toolbar.",
			resolution: "Add a title directly to the panel or use a headerToolbar with title element",
			resolutionurls: [{
				text: "SAP Fiori Design Guidelines: Panel",
				href: "https://experience.sap.com/fiori-design-web/panel/#components",
				text2: "Explored Sample",
				href2: "https://openui5beta.hana.ondemand.com/#/sample/sap.m.sample.Panel/preview"
			}],
			check: function (oIssueManager, oCoreFacade, oScope) {
				oScope.getElementsByClassName("sap.m.Panel")
					.forEach(function(oElement) {
						if (!jQuery.isEmptyObject(oElement.getAggregation("Title text"))
							|| !jQuery.isEmptyObject(oElement.getAggregation("Toolbar"))) {

							var sElementId = oElement.getId(),
								sElementName = oElement.getMetadata().getElementName();

							oIssueManager.addIssue({
								severity: Severity.Medium,
								details: "Panel '" + sElementName + "' (" + sElementId + ") does not have a title or a toolbar aggregation",
								context: {
									id: sElementId
								}
							});
						}
					});
			}
		};

		return [oPanelNeedHeaderRule];

	}, true);
/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2018 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
/**
 * Defines support rules of the Select control of sap.m library.
 */
sap.ui.predefine('sap/m/rules/Select.support',["sap/ui/support/library"],
	function(SupportLib) {
	"use strict";

	// shortcuts
	var Categories = SupportLib.Categories, // Accessibility, Performance, Memory, ...
		Severity = SupportLib.Severity, // Low, Medium, High
		Audiences = SupportLib.Audiences; // Control, Internal, Application

	// const
	var DEFAULT_MODEL_SIZE_LIMIT = 100;

	//**********************************************************
	// Rule Definitions
	//**********************************************************

	/**
	 *Checks if the 'items' aggregation binding of sap.m.Select is limited to 100 items
	 */
	var oSelectRule = {
		id : "selectItemsSizeLimit",
		audiences: [Audiences.Control],
		categories: [Categories.Usability],
		enabled: true,
		minversion: "1.28",
		title: "Select: Items have size limit of 100",
		description: "The 'items' model imposes a default size limit of 100",
		resolution: "Use the sap.ui.model.Model.prototype.setSizeLimit to adjust the size limit of the 'items' model if you expect more than 100 items",
		resolutionurls: [{
			text: "API Reference for sap.ui.model.Model",
			href: "https://sapui5.hana.ondemand.com/#/api/sap.ui.model.Model"
		}],
		check: function (oIssueManager, oCoreFacade, oScope) {
			oScope.getElementsByClassName("sap.m.Select")
				.forEach(function(oElement) {

					var oBinding = oElement.getBinding("items"),
						oModel = oBinding && oBinding.oModel;

					if (oModel && (oModel.iSizeLimit === DEFAULT_MODEL_SIZE_LIMIT)) {

						var sElementId = oElement.getId(),
							sElementName = oElement.getMetadata().getElementName();

						oIssueManager.addIssue({
							severity: Severity.Low,
							details: "Select '" + sElementName + "' (" + sElementId + ") model has a default limit of 100 items",
							context: {
								id: sElementId
							}
						});
					}
				});
		}
	};

	return [oSelectRule];

}, true);
/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2018 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
/**
 * Defines support rules of the SelectDialog control of sap.m library.
 */
sap.ui.predefine('sap/m/rules/SelectDialog.support',["sap/ui/support/library"],
	function(SupportLib) {
		"use strict";

		// shortcuts
		var Categories = SupportLib.Categories, // Accessibility, Performance, Memory, ...
			Severity = SupportLib.Severity,	// Hint, Warning, Error
			Audiences = SupportLib.Audiences; // Control, Internal, Application

		//**********************************************************
		// Rule Definitions
		//**********************************************************

		/**
		 *Checks, if a selectDialog does not contain inactive list items
		 */
		var oSelectDialogNonActiveItem = {
			id : "noContainInactiveItemsInSelectDialog",
			audiences: [Audiences.Control],
			categories: [Categories.Usability],
			enabled: true,
			minversion: "1.28",
			title: "SelectDialog: Select Dialog should not contain inactive items",
			description: "All items in a Select Dialog should be interactable/selectable",
			resolution: "Make all items interactable/selectable or remove the inactive ones",
			resolutionurls: [{
				text: "SAP Fiori Design Guidelines: SelectDialog",
				href: "https://experience.sap.com/fiori-design-web/select-dialog/#behavior-and-interaction"
			}],
			check: function (oIssueManager, oCoreFacade, oScope) {
				oScope.getElementsByClassName("sap.m.SelectDialog")
					.forEach(function(oElement) {
						var aListItems = oElement.getItems(),
							sListOfInactiveItems = "";

						aListItems.forEach(function(oListItem){
							if (oListItem.getType() === sap.m.ListType.Inactive) {
								var sListItemId = oListItem.getId(),
									sListItemName = oListItem.getMetadata().getElementName();

								sListOfInactiveItems += sListItemName + " (" + sListItemId + "); ";

							}
						});

						if (sListOfInactiveItems) {
							var sElementId = oElement.getId(),
								sElementName = oElement.getMetadata().getElementName();

							oIssueManager.addIssue({
								severity: Severity.Medium,
								details: "SelectDialog '" + sElementName + "' (" + sElementId + ") contains one or more items of type 'Inactive' : " + sListOfInactiveItems,
								context: {
									id: sElementId
								}
							});
						}
					});
			}
		};

		return [oSelectDialogNonActiveItem];

	}, true);
/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2018 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
/**
 * Defines support rules of the Link control of sap.m Table.
 */
sap.ui.predefine('sap/m/rules/Table.support',["sap/ui/support/library"],
	function(SupportLib) {
		"use strict";

		// shortcuts
		var Categories = SupportLib.Categories, // Accessibility, Performance, Memory, ...
			Severity = SupportLib.Severity,	// Hint, Warning, Error
			Audiences = SupportLib.Audiences; // Control, Internal, Application

		//**********************************************************
		// Rule Definitions
		//**********************************************************

		/**
		 *Checks, if a link with attached press handler has no href property set
		 */
		var oTableRule = {
			id: "definingColumnWidths",
			audiences: [Audiences.Control],
			categories: [Categories.Usability],
			enabled: true,
			minversion: "1.28",
			title: "Table: Defining column widths",
			description: "Defining column widths",
			resolution: "Configure at least 1 column with width=auto or do not configure the width at all",
			resolutionurls: [{
				text: "Documentation: Defining Column Widths",
				href: "https://sapui5.hana.ondemand.com/#/topic/6f778a805bc3453dbb66e246d8271839"
			}],
			check: function (oIssueManager, oCoreFacade, oScope) {
				var count = 0;
				oScope.getElementsByClassName("sap.m.Table").forEach(function (oTable) {
					var aColumn = oTable.getColumns();
					aColumn.forEach(function (oColumn) {
						var sWidth = oColumn.getWidth();
						if (sWidth !== "auto" || sWidth !== "") {
							count++;
						}
					});
					if (count === aColumn.length) {
						oIssueManager.addIssue({
							severity: Severity.Medium,
							details: "All the columns are configured with a width. This should be avoided.",
							context: {
								id: oTable.getId()
							}
						});
					}
				});
			}
		};

		return [oTableRule];
	}, true);
/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2018 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
/**
 * Defines support rules of the Title control of sap.m library.
 */
sap.ui.predefine('sap/m/rules/Title.support',["sap/ui/support/library"],
	function(SupportLib) {
	"use strict";

	// shortcuts
	var Categories = SupportLib.Categories, // Accessibility, Performance, Memory, ...
		Severity = SupportLib.Severity,	// Hint, Warning, Error
		Audiences = SupportLib.Audiences; // Control, Internal, Application

	//**********************************************************
	// Rule Definitions
	//**********************************************************

	var oTitleRule = {
		id: "titleLevelProperty",
		audiences: [Audiences.Internal],
		categories: [Categories.FioriGuidelines, Categories.Accessibility],
		enabled: true,
		minversion: "*",
		title: "Title: It is recommended to set the level property",
		description: "Level defines the semantic level of the title. This information is used by assistive technologies like screen readers to create a hierarchical site map for faster navigation.",
		resolution: "Add value to the level property",
		resolutionurls: [
		{
			text: "SAP Fiori Design Guidelines: Title",
			href: "https://experience.sap.com/fiori-design-web/title/#guidelines"
		},
		{
			text: "API Reference: Title",
			href: "https://ui5.sap.com/#/api/sap.m.Title/controlProperties"
		}],
		check: function (oIssueManager, oCoreFacade, oScope) {
			oScope.getElementsByClassName("sap.m.Title")
				.forEach(function(oElement) {
					if (oElement.getProperty("level") === sap.ui.core.TitleLevel.Auto) {

						var sElementId = oElement.getId(),
							sElementName = oElement.getMetadata().getElementName();

						oIssueManager.addIssue({
							severity: Severity.Low,
							details: "Title '" + sElementName + "' (" + sElementId + ") has no level property set",
							context: {
								id: sElementId
							}
						});
					}
				});
		}
	};


	return [oTitleRule];

}, true);
/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2018 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
/**
 * Defines support rules of the Tokenizer control of sap.m library.
 */
sap.ui.predefine('sap/m/rules/Tokenizer.support',["sap/ui/support/library"],
function(SupportLib) {
	"use strict";

	// shortcuts
	var Categories = SupportLib.Categories, // Accessibility, Performance, Memory, ...
		Severity = SupportLib.Severity,	// Hint, Warning, Error
		Audiences = SupportLib.Audiences; // Control, Internal, Application

	var oTokenizerParentRule = {
			id : "tokenizerParentRule",
			audiences: [Audiences.Application],
			categories: [Categories.Usage],
			enabled: true,
			minversion: "1.28",
			title : "Tokenizer: Tokenizer parent control",
			description : "The tokenizer can only be used as part of MultiComboBox, MultiInput or ValueHelpDialog.",
			resolution : "Do not use the Tokenizer control standalone.",
			check : function(oIssueManager, oCoreFacade, oScope) {
				var oTokenizers = oScope.getElementsByClassName("sap.m.Tokenizer"),
					bParent,
					sParentControlName,
					oParent;
				oTokenizers.forEach(function (oTokenizer) {
					oParent = oTokenizer.getParent();
					sParentControlName = oParent && oParent.getMetadata().getName();
					bParent = oParent && sParentControlName === "sap.m.MultiInput" ||
								sParentControlName === "sap.m.MultiComboBox" ||
								// Value Help Dialog uses the tokenizer in a vertical layout
								(sParentControlName === "sap.ui.layout.VerticalLayout" &&
								oParent.hasStyleClass("compVHTokenizerHLayout"));

					if (!bParent) {
						oIssueManager.addIssue({
							severity: Severity.High,
							details: "Tokenizer with id: " + oTokenizer.getId() + " is not inside a MultiComboBox, MultiInput or ValueHelpDialog",
							context: {
								id: oTokenizer.getId()
							}
						});
					}
				});
			}
		};

	return [oTokenizerParentRule];
}, true);
//# sourceMappingURL=library-preload.support.js.map