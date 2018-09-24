<?php
namespace exface\OpenUI5Template\Templates\Elements;

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

/**
 * In OpenUI5 dialog widgets are either rendered as an object page layout (if the dialog is maximized) or
 * as a popover dialog.
 *
 * @method Dialog getWidget()
 *        
 * @author Andrej Kabachnik
 *        
 */
class ui5Dialog extends ui5Form
{
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        if ($this->isMaximized() === false) {
            return $this->buildJsDialog();
        } else {
            return $this->buildJsPage($this->buildJsObjectPageLayout());
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
                $action_setting = $this->getTemplate()->getConfigMaximizeDialogByDefault($action);
                return $action_setting;
            }
            return false;
        }
        return $widget_setting;
    }
    
    protected function buildJsObjectPageLayout()
    {
        // useIconTabBar: true did not work for some reason as tables were not shown when
        // entering a tab for the first time - just at the second time. There was also no
        // difference between creating tables with new sap.ui.table.table or function(){ ... }()
        return <<<JS

        new sap.uxap.ObjectPageLayout({
            useIconTabBar: false,
            upperCaseAnchorBar: false,
            enableLazyLoading: false,
			{$this->buildJsHeader()},
			sections: [
				{$this->buildJsObjectPageSections()}
			]
		})

JS;
    }
        
    protected function buildJsHeader()
    {
        $widget = $this->getWidget();
        
        if ($widget->hasHeader()) {
            foreach ($widget->getHeader()->getChildren() as $child) {
                if ($child instanceof Image) {
                    $imageElement = $this->getTemplate()->getElement($child);
                    $image = <<<JS

                    objectImageURI: {$imageElement->buildJsValue()},
			        objectImageShape: "Circle",
JS;
                    $child->setHidden(true);
                    break;
                }
            }
            
            
            $header_content = $this->getTemplate()->getElement($widget->getHeader())->buildJsConstructor();
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
        
        // If the dialog has a header and it has a fixed or prefilled title, take it as is.
        if ($widget->hasHeader()) {
            $header = $widget->getHeader();
            if ($header->isPrefilled() || ! $header->isTitleBoundToAttribute()) {
                return '"' . $this->escapeJsTextValue($widget->getCaption()) . '"';
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
            return '"' . $this->escapeJsTextValue($object->getName()) . '"';
        }
        
        // Once a title attribute is found, create an invisible display widget and
        // let it's element produce a binding.
        $titleWidget = WidgetFactory::createFromUxon($widget->getPage(), new UxonObject([
            'widget_type' => 'Display',
            'hidden' => true,
            'attribute_alias' => $title_attr->getAliasWithRelationPath()
        ]), $widget);
        $titleElement = $this->getTemplate()->getElement($titleWidget);
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
        if ($this->getWidget()->countWidgetsVisible() === 1) {
            $content = $this->buildJsChildrenConstructors();
        } else {
            $content = $this->buildJsLayoutForm($this->buildJsChildrenConstructors()); 
        }
        $icon = $this->getWidget()->getIcon() ? 'icon: "' . $this->getIconSrc($this->getWidget()->getIcon()) . '",' : '';
        return <<<JS

        new sap.m.Dialog("{$this->getId()}", {
			modal : true,
            {$icon}
            stretch: jQuery.device.is.phone,
            title: "{$this->getCaption()}",
			buttons : [ {$this->buildJsDialogButtons()} ],
			content : [ {$content} ]
		});

JS;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::getCaption()
     */
    protected function getCaption()
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
    protected function buildJsPage($content_js)
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
        return <<<JS
        
            {$this->buildJsBusyIconShow()}
            var oRouteParams = {$oViewJs}.getModel('view').getProperty('/_route');
            var data = $.extend({}, {
                action: "exface.Core.ReadPrefill",
				resource: "{$this->getWidget()->getPage()->getAliasWithNamespace()}",
				element: "{$this->getWidget()->getId()}",
            }, oRouteParams.params);
			$.ajax({
                url: "{$this->getAjaxUrl()}",
                type: "POST",
				data: data,
                success: function(response, textStatus, jqXHR) {
                    if (response.data && response.data && response.data.length === 1) {
                        {$oViewJs}.setModel(new sap.ui.model.json.JSONModel(response.data[0]));
                    }
                    {$this->buildJsBusyIconHide()}
                },
                error: function(jqXHR, textStatus, errorThrown){
                    {$this->buildJsBusyIconHide()}
                    {$this->getController()->buildJsComponentGetter()}.showAjaxErrorDialog(jqXHR.responseText, jqXHR.status + " " + jqXHR.statusText)
                }
			})
			
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
    protected function buildJsObjectPageSections()
    {
        $widget = $this->getWidget();
        $js = '';
        $non_tab_children_constructors = '';
        
        foreach ($widget->getWidgets() as $content) {
            if ($content instanceof Tabs) {
                foreach ($content->getTabs() as $tab) {
                    $js .= $this->buildJsObjectPageSectionFromTab($tab);
                }
            } else {
                $non_tab_children_constructors .= ($non_tab_children_constructors ? ',' : '') . $this->getTemplate()->getElement($content)->buildJsConstructor();
            }
        }
        
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
        $tabElement = $this->getTemplate()->getElement($tab);
        return <<<JS

                // BOF ObjectPageSection
                new sap.uxap.ObjectPageSection({
					title:"{$tab->getCaption()}",
                    titleUppercase: false,
					subSections: new sap.uxap.ObjectPageSubSection({
						blocks: [
                            {$tabElement->buildJsLayoutConstructor($tabElement->buildJsChildrenConstructors())}
                        ]
					})
				}),
                // EOF ObjectPageSection
                
JS;
    }
    
    /**
     * Returns the constructor for an OverflowToolbar representing the main toolbar of the dialog.
     * 
     * @return string
     */
    protected function buildJsFloatingToolbar()
    {
        return $this->getTemplate()->getElement($this->getWidget()->getToolbarMain())->buildJsConstructor();
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
                    $js = $this->getTemplate()->getElement($subbtn)->buildJsConstructor() . ",\n" . $js;
                }
                continue;
            }
            $js = $this->getTemplate()->getElement($btn)->buildJsConstructor() . ",\n" . $js;
        }
        return $js;
    }
}
?>