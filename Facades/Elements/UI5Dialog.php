<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Widgets\Dialog;
use exface\Core\Factories\DataSheetFactory;
use exface\Core\Widgets\Tabs;
use exface\Core\Widgets\Tab;
use exface\Core\Widgets\Image;
use exface\Core\Widgets\MenuButton;
use exface\Core\Interfaces\Widgets\iTriggerAction;
use exface\Core\Interfaces\Widgets\iHaveValue;
use exface\Core\Interfaces\Actions\iShowWidget;
use exface\Core\Interfaces\Model\MetaAttributeInterface;
use exface\Core\Factories\WidgetFactory;
use exface\Core\CommonLogic\UxonObject;
use exface\Core\Interfaces\WidgetInterface;
use exface\Core\Interfaces\Widgets\iFillEntireContainer;
use exface\UI5Facade\Facades\Elements\Traits\UI5HelpButtonTrait;

/**
 * In OpenUI5 dialog widgets are either rendered as an object page layout (if the dialog is maximized) or
 * as a popover dialog.
 *
 * @method Dialog getWidget()
 *        
 * @author Andrej Kabachnik
 *        
 */
class UI5Dialog extends UI5Form
{
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Form::buildJsConstructor()
     */   
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        // If we need a prefill, we need to let the view model know this, so all the wigdget built
        // for this dialog can see, that a prefill will be done. This is especially important for
        // widget with lazy loading (like tables), that should postpone loading until the prefill data
        // is there.
        if ($this->needsPrefill()) {
            $this->getController()->addOnInitScript('this.getView().getModel("view").setProperty("/_prefill/pending", true);');
        }
        
        if ($this->isMaximized() === false) {
            return $this->buildJsDialog();
        } else {
            return $this->buildJsPage($this->buildJsObjectPageLayout($oControllerJs), $oControllerJs);
        }
    }
    
    /**
     * Returns TRUE if the dialog is maximized (i.e. should be rendered as a page) and FALSE otherwise (i.e. rendering as dialog).
     * @return boolean
     */
    public function isMaximized()
    {
        $widget = $this->getWidget();
        $widget_setting = $widget->isMaximized();
        if (is_null($widget_setting)) {
            if ($widget->hasParent() && $widget->getParent() instanceof iTriggerAction) {
                $action = $widget->getParent()->getAction();
                $action_setting = $this->getFacade()->getConfigMaximizeDialogByDefault($action);
                return $action_setting;
            }
            return false;
        }
        return $widget_setting;
    }
    
    protected function buildJsObjectPageLayout(string $oControllerJs = 'oController')
    {
        // useIconTabBar: true did not work for some reason as tables were not shown when
        // entering a tab for the first time - just at the second time. There was also no
        // difference between creating tables with new sap.ui.table.table or function(){ ... }()
        return <<<JS

        new sap.uxap.ObjectPageLayout({
            useIconTabBar: false,
            upperCaseAnchorBar: false,
            enableLazyLoading: false,
			{$this->buildJsHeader($oControllerJs)},
			sections: [
				{$this->buildJsObjectPageSections($oControllerJs)}
			]
		})

JS;
    }
				
    protected function buildJsPageHeaderContent(string $oControllerJs = 'oController') : string
    {
        return $this->buildJsHelpButtonConstructor($oControllerJs);
    }
        
    protected function buildJsHeader(string $oControllerJs = 'oController')
    {
        $widget = $this->getWidget();
        
        if ($widget->hasHeader()) {
            foreach ($widget->getHeader()->getChildren() as $child) {
                if ($child instanceof Image) {
                    $imageElement = $this->getFacade()->getElement($child);
                    $image = <<<JS

                    objectImageURI: {$imageElement->buildJsValue()},
			        objectImageShape: "Circle",
JS;
                    $child->setHidden(true);
                    break;
                }
            }
            
            
            $header_content = $this->getFacade()->getElement($widget->getHeader())->buildJsConstructor();
        }
        
        return <<<JS

            showTitleInHeaderContent: true,
            headerTitle:
				new sap.uxap.ObjectPageHeader({
					objectTitle: {$this->buildJsObjectTitle()},
				    showMarkers: false,
				    isObjectIconAlwaysVisible: false,
				    isObjectTitleAlwaysVisible: false,
				    isObjectSubtitleAlwaysVisible: false,
                    {$image}
					actions: [
						
					]
				}),
			headerContent:[
				{$header_content}
			]
JS;
    }
				
    protected function buildJsObjectTitle() : string
    {
        $widget = $this->getWidget();

        if ($widget->getHideCaption()) {
            return '""';
        }
        
        // If the dialog has a header and it has a fixed or prefilled title, take it as is.
        if ($widget->hasHeader()) {
            $header = $widget->getHeader();
            if ($header->getHideCaption() === true) {
                return '""';
            }
            if (! $header->isTitleBoundToAttribute()) {
                $caption = $header->getCaption() ? $header->getCaption() : $widget->getCaption();
                return '"' . $this->escapeJsTextValue($caption) . '"';
            }
        }
        
        // Otherwise try to find a good title
        $object = $widget->getMetaObject();
        if ($widget->hasHeader()) {
            $title_attr = $widget->getHeader()->getTitleAttribute();
        } elseif ($object->hasLabelAttribute()) {
            $title_attr = $object->getLabelAttribute();
        } elseif ($object->hasUidAttribute()) {
            $title_attr = $object->getUidAttribute();
        } else {
            // If no suitable attribute can be found, use the object name as static title
            return '"' . $this->escapeJsTextValue($widget->getCaption() ? $widget->getCaption() : $object->getName()) . '"';
        }
        
        // Once a title attribute is found, create an invisible display widget and
        // let it's element produce a binding.
        /* @var $titleElement \exface\UI5Facade\Facades\Elements\UI5Display */
        $titleWidget = WidgetFactory::createFromUxon($widget->getPage(), new UxonObject([
            'widget_type' => 'Display',
            'hidden' => true,
            'attribute_alias' => $title_attr->getAliasWithRelationPath()
        ]), $widget);
        $titleElement = $this->getFacade()->getElement($titleWidget);
        
        // If there is a caption binding in the view model, use it in the title element
        if ($header !== null) {
            $model = $this->getView()->getModel();
            if ($model->hasBinding($header, 'caption')) {
                $titleElement->setValueBindingPath($model->getBindingPath($header, 'caption'));
            }
        }
        
        return $titleElement->buildJsValue();
    }
    
    /**
     * 
     * @param MetaAttributeInterface $attribute
     * @return iHaveValue|NULL
     */
    protected function findWidgetByAttribute(MetaAttributeInterface $attribute) : ?iHaveValue
    {
        $widget = $this->getWidget();
        $found = null;
        
        $found = $widget->findChildrenByAttribute($attribute)[0];
        if ($found === null) {
            if ($widget->hasHeader()) {
                $found = $widget->getHeader()->findChildrenByAttribute($attribute)[0];
            }
        }
        
        return $found;
    }
				
    protected function buildJsDialog()
    {
        $widget = $this->getWidget();
        $icon = $widget->getIcon() ? 'icon: "' . $this->getIconSrc($widget->getIcon()) . '",' : '';
        
        // The content of the dialog is either a single widget or a layout with multiple widgets
        if ($widget->countWidgetsVisible() === 1 && $widget->getWidgetFirst(function(WidgetInterface $widget){return $widget->isHidden() === false;}) instanceof iFillEntireContainer) {
            $content = $this->buildJsChildrenConstructors(false);
        } else {
            $content = $this->buildJsLayoutForm($this->buildJsChildrenConstructors(true)); 
        }
        
        // If the dialog requires a prefill, we need to load the data once the dialog is opened.
        if ($this->needsPrefill()) {
            $prefill = <<<JS

            beforeOpen: function(oEvent) {
                var oDialog = oEvent.getSource();
                var oView = {$this->getController()->getView()->buildJsViewGetter($this)};
                {$this->buildJsPrefillLoader('oView')}
            },

JS;
        } else {
            $prefill = '';
        }
        
        // Finally, instantiate the dialog
        return <<<JS

        new sap.m.Dialog("{$this->getId()}", {
			{$icon}
            stretch: jQuery.device.is.phone,
            title: "{$this->getCaption()}",
			buttons : [ {$this->buildJsDialogButtons()} ],
			content : [ {$content} ],
            {$prefill}
		});
JS;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::getCaption()
     */
    protected function getCaption() : string
    {
        $caption = parent::getCaption();
        $objectName = $this->getWidget()->getMetaObject()->getName();
        return $caption === $objectName ? $caption : $caption . ': ' . $objectName;
    }
    
    /**
     * Returns the JS constructor for the sap.m.Page used as the top-level control when rendering
     * the dialog as an object page layout. 
     * 
     * The page will have a floating toolbar with all dialog buttons and a header with a title and
     * the close/back button.
     * 
     * @param string $content_js
     * @return string
     */
    protected function buildJsPage($content_js, string $oControllerJs = 'oController')
    {
        if ($this->needsPrefill()) {
            $this->getController()->addOnRouteMatchedScript($this->buildJsPrefillLoader('oView'), 'loadPrefill');
        }
        
        return <<<JS
        
        new sap.m.Page("{$this->getId()}", {
            title: "{$this->getCaption()}",
            showNavButton: true,
            navButtonPress: [oController.onNavBack, oController],
            content: [
                {$content_js}
            ],
            headerContent: [
                {$this->buildJsPageHeaderContent($oControllerJs)}
            ],
            footer: {$this->buildJsFloatingToolbar()}
        })
JS;
    }
        
    /**
     * Returns TRUE if the dialog needs to be prefilled and FALSE otherwise.
     * 
     * @return bool
     */
    protected function needsPrefill() : bool
    {
        $widget = $this->getWidget();
        if ($widget->getParent() instanceof iTriggerAction) {
            $action = $widget->getParent()->getAction();
            if (($action instanceof iShowWidget) && $action->getPrefillWithInputData()) {
                return true;
            }
        } 
        
        return false;
    }
          
    /**
     * Returns the JS code to load prefill data for the dialog. 
     * 
     * TODO will this work with with explicit prefill data too? 
     * 
     * @param string $oViewJs
     * @return string
     */
    protected function buildJsPrefillLoader(string $oViewJs = 'oView') : string
    {
        $widget = $this->getWidget();
        $triggerWidget = $widget->getParent() instanceof iTriggerAction ? $widget->getParent() : $widget;
        
        // FIXME #DataPreloader this will force the form to use any preload - regardless of the columns.
        if ($widget->isPreloadDataEnabled() === true) {
            $this->getController()->addOnDefineScript("exfPreloader.addPreload('{$this->getMetaObject()->getAliasWithNamespace()}');");
            $loadPrefillData = $this->buildJsPrefillLoaderFromPreload($triggerWidget, $oViewJs, 'oViewModel');
        } else {
            $loadPrefillData = $this->buildJsPrefillLoaderFromServer($triggerWidget, $oViewJs, 'oViewModel');
        }
        
        return <<<JS
        
            {$this->buildJsBusyIconShow()}
            {$oViewJs}.getModel().setData({});
            var oViewModel = {$oViewJs}.getModel('view');
            oViewModel.setProperty('/_prefill/pending', true);
            {$loadPrefillData}
			
JS;
    }
            
    protected function buildJsPrefillLoaderFromPreload(WidgetInterface $triggerWidget, string $oViewJs = 'oView', string $oViewModelJs = 'oViewModel') : string
    {
        $widget = $this->getWidget();
        return <<<JS
        
                exfPreloader
                .getPreload('{$widget->getMetaObject()->getAliasWithNamespace()}')
                .then(preload => {
                    var failed = false;
                    if (preload !== undefined && preload.response !== undefined && preload.response.data !== undefined) {
                        var uid = {$oViewModelJs}.getProperty('/_route').params.data.rows[0]['{$widget->getMetaObject()->getUidAttributeAlias()}'];
                        var aData = preload.response.data.filter(oRow => {
                            return oRow['{$widget->getMetaObject()->getUidAttributeAlias()}'] == uid;
                        });
                        if (aData.length === 1) {
                            var response = $.extend({}, preload.response, {data: aData});
                            {$this->buildJsPrefillLoaderSuccess('response', $oViewJs, $oViewModelJs)}
                        } else {
                            failed = true;
                        }
                    } else {
                        failed = true;
                    }

                    if (failed == true) {
                        console.warn('Failed to prefill dialog from preload data: falling back to server request');
                        {$this->buildJsPrefillLoaderFromServer($triggerWidget, $oViewJs, $oViewModelJs)}
                    }
                });
                
JS;
    }
                        
    protected function buildJsPrefillLoaderFromServer(WidgetInterface $triggerWidget, string $oViewJs = 'oView', string $oViewModelJs = 'oViewModel') : string
    {
        $widget = $this->getWidget();
        // If the prefill cannot be fetched due to being offline, show the offline message view
        // (if the dialog is a page) or an error-popup (if the dialog is a regular dialog).
        if ($this->isMaximized()) {
            $offlineError = $oViewJs . '.getController().getRouter().getTargets().display("offline")';
        } else {
            $offlineError = <<<JS
            
            {$this->getController()->buildJsComponentGetter()}.showDialog('{$this->translate('WIDGET.DATATABLE.OFFLINE_ERROR')}', '{$this->translate('WIDGET.DATATABLE.OFFLINE_ERROR_TITLE')}', 'Error');
            sap.ui.getCore().byId("{$this->getId()}").close();
            
JS;
        }
        
        return <<<JS

            var oRouteParams = {$oViewModelJs}.getProperty('/_route');
            var data = $.extend({}, {
                action: "exface.Core.ReadPrefill",
				resource: "{$widget->getPage()->getAliasWithNamespace()}",
				element: "{$triggerWidget->getId()}",
            }, oRouteParams.params);
			$.ajax({
                url: "{$this->getAjaxUrl()}",
                type: "POST",
				data: data,
                success: function(response, textStatus, jqXHR) {
                    {$this->buildJsPrefillLoaderSuccess('response', $oViewJs, $oViewModelJs)}
                },
                error: function(jqXHR, textStatus, errorThrown){
                    oViewModel.setProperty('/_prefill/pending', false);
                    {$this->buildJsBusyIconHide()}
                    if (navigator.onLine === false) {
                        {$offlineError}
                    } else {
                        {$this->getController()->buildJsComponentGetter()}.showAjaxErrorDialog(jqXHR)
                    }
                }
			})
JS;
    }
                        
    protected function buildJsPrefillLoaderSuccess(string $responseJs = 'response', string $oViewJs = 'oView', string $oViewModelJs = 'oViewModel') : string
    {
        // IMPORTANT: We must ensure, ther is no model data before replacing it with the prefill! 
        // Otherwise the model will not fire binding changes properly: InputComboTables will loose 
        // their values! But only reset the model if it has data, because the reset will trigger
        // an update of all bindings.
        return <<<JS

                    {$oViewModelJs}.setProperty('/_prefill/pending', false);
                    var oDataModel = {$oViewJs}.getModel();
                    if (Object.keys(oDataModel.getData()).length !== 0) {
                        oDataModel.setData({});
                    }
                    if ({$responseJs}.data && {$responseJs}.data && {$responseJs}.data.length === 1) {
                        oDataModel.setData({$responseJs}.data[0]);
                    }
                    {$this->buildJsBusyIconHide()}

JS;
    }
    
    /**
     * Returns JS constructors for page sections of the object page layout.
     * 
     * If the dialog contains tabs, page sections will be generated automatically for
     * every tab. Otherwise all widgets will be placed in a single page section.
     * 
     * @return string
     */
    protected function buildJsObjectPageSections(string $oControllerJs = 'oController')
    {
        $widget = $this->getWidget();
        $js = '';
        $non_tab_children_constructors = '';
        $non_tab_hidden_constructors = '';
        
        foreach ($widget->getWidgets() as $child) {
            // Tabs are transformed to PageSections and all other widgets are collected and put into a separate page section
            // lager on.
            if ($child instanceof Tabs) {
                foreach ($child->getTabs() as $tab) {
                    $js .= $this->buildJsObjectPageSectionFromTab($tab);
                }
            } else {
                // Most dialogs will have hidden system fields at top level. They need to be placed at the very end - otherwise
                // they break the SimpleForm generated for the non-tab PageSection. If they come first, the SimpleForm will allocate
                // space for them (even though not visible) and put the actual content way in the back.
                if ($child->isHidden() === true) {
                    $non_tab_hidden_constructors .= ($non_tab_hidden_constructors ? ',' : '') . $this->getFacade()->getElement($child)->buildJsConstructor();
                } else {
                    if ((($child instanceof iFillEntireContainer) || $child->getWidth()->isMax()) && $widget->countWidgetsVisible() !== 1) {
                        $non_tab_children_constructors .= ($non_tab_children_constructors ? ',' : '') . $this->buildJsFormRowDelimiter();
                    }
                    $non_tab_children_constructors .= ($non_tab_children_constructors ? ',' : '') . $this->getFacade()->getElement($child)->buildJsConstructor();
                }
            }
        }
        
        // Append hidden non-tab elements after the visible ones
        if ($non_tab_hidden_constructors) {
            $non_tab_children_constructors .= ($non_tab_children_constructors ? ',' : '') . $non_tab_hidden_constructors;
        }
        
        // Build an ObjectPageSection for the non-tab elements
        if ($non_tab_children_constructors) {
            $js .= $this->buildJsObjectPageSection($this->buildJsLayoutConstructor($non_tab_children_constructors), 'sapUiTinyMarginTop');
        }
        
        return $js;
    }
    
    /**
     * Returns the JS constructor for a general page section with no title and a single subsection.
     * 
     * The passed content is placed in the blocks aggregation of the subsection.
     * 
     * @param string $content_js
     * @return string
     */
    protected function buildJsObjectPageSection($content_js, $cssClass = null)
    {
        $suffix = $cssClass !== null ? '.addStyleClass("' . $cssClass . '")' : '';
        return <<<JS

                // BOF ObjectPageSection
                new sap.uxap.ObjectPageSection({
                    showTitle: false,
                    subSections: new sap.uxap.ObjectPageSubSection({
						blocks: [
                            {$content_js}
                        ]
					})
				}){$suffix}
                // EOF ObjectPageSection

JS;
    }
    
    /**
     * Returns the JS constructor for a page section representing the given tab widget.
     * 
     * @param Tab $tab
     * @return string
     */
    protected function buildJsObjectPageSectionFromTab(Tab $tab) 
    {
        $tabElement = $this->getFacade()->getElement($tab);
        return <<<JS

                // BOF ObjectPageSection
                new sap.uxap.ObjectPageSection({
					title:"{$tab->getCaption()}",
                    titleUppercase: false,
					subSections: new sap.uxap.ObjectPageSubSection({
						blocks: [
                            {$tabElement->buildJsLayoutConstructor()}
                        ]
					})
				}),
                // EOF ObjectPageSection
                
JS;
    }
    
    /**
     * Returns the button constructors for the dialog buttons.
     * 
     * @return string
     */
    protected function buildJsDialogButtons()
    {
        $js = '';
        $buttons = array_reverse($this->getWidget()->getButtons());
        foreach ($buttons as $btn) {
            if ($btn instanceof MenuButton) {
                foreach ($btn->getButtons() as $subbtn) {
                    $js = $this->getFacade()->getElement($subbtn)->buildJsConstructor() . ",\n" . $js;
                }
                continue;
            }
            $js = $this->getFacade()->getElement($btn)->buildJsConstructor() . ",\n" . $js;
        }
        return $js;
    }
}
?>