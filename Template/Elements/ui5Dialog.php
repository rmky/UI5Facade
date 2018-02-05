<?php
namespace exface\OpenUI5Template\Template\Elements;

use exface\Core\Widgets\Dialog;
use exface\Core\Factories\DataSheetFactory;
use exface\Core\Widgets\Tabs;
use exface\Core\Widgets\Tab;
use exface\Core\Widgets\Image;
use exface\Core\Widgets\MenuButton;
use exface\Core\Interfaces\Widgets\iTriggerAction;

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
    public function buildJsConstructor()
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
                return $this->getTemplate()->getConfigMaximizeDialogByDefault($action);
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
        
        if ($this->getMetaObject()->hasUidAttribute() && ($uid_widget = $this->getWidget()->findChildrenByAttribute($this->getMetaObject()->getUidAttribute())[0]) && !is_null($uid_widget->getValue())) {
            $uid_data_sheet = DataSheetFactory::createFromObject($this->getMetaObject());
            if ($label_attr = $this->getMetaObject()->getLabelAttribute()) {
                $uid_data_sheet->getColumns()->addFromAttribute($label_attr);
            }
            $uid_data_sheet->addFilterFromString($this->getMetaObject()->getUidAttributeAlias(), $uid_widget->getValue());
            $uid_data_sheet->dataRead();
            if ($label_attr) {
                $label = $uid_data_sheet->getCellValue($this->getMetaObject()->getLabelAttribute()->getAlias(), 0);
            } elseif ($this->getMetaObject()->hasUidAttribute()) {
                $label = $uid_data_sheet->getCellValue($this->getMetaObject()->getUidAttribute()->getAlias(), 0);
            }
        }
        $heading = $label ? $label : 'New';
        
        if ($widget->hasHeader()) {
            foreach ($widget->getHeader()->getChildren() as $child) {
                if ($child instanceof Image) {
                    $image = <<<JS
                    objectImageURI: "{$child->getUri()}",
			        objectImageShape: "Circle",
JS;
                    $child->setHidden(true);
                }
            }
            
            
            $header_content = $this->getTemplate()->getElement($widget->getHeader())->buildJsConstructor();
        }
        
        return <<<JS
            showTitleInHeaderContent: true,
            headerTitle:
				new sap.uxap.ObjectPageHeader({
					objectTitle:"{$heading}",
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
				
    protected function buildJsDialog()
    {
        if ($this->getWidget()->countWidgetsVisible() === 1) {
            $content = $this->buildJsChildrenConstructors();
        } else {
            $content = $this->buildJsLayoutForm($this->buildJsChildrenConstructors()); 
        }
        return <<<JS

        new sap.m.Dialog("{$this->getId()}", {
			modal : true,
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
        return parent::getCaption() . ': ' . $this->getWidget()->getMetaObject()->getName();
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
        return <<<JS
        
        new sap.m.Page("{$this->getId()}", {
            title: "{$this->getCaption()}",
            showNavButton: true,
            navButtonPress: function(){
                closeTopDialog();
            },
            content: [
                {$content_js}
            ],
            footer: {$this->buildJsFloatingToolbar()}
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
            $js .= $this->buildJsObjectPageSection($this->buildJsLayoutConstructor($non_tab_children_constructors));
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
    protected function buildJsObjectPageSection($content_js)
    {
        return <<<JS

                // BOF ObjectPageSection
                new sap.uxap.ObjectPageSection({
                    subSections: new sap.uxap.ObjectPageSubSection({
						blocks: [
                            {$content_js}
                        ]
					})
				}),
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
                
    public function getViewName()
    {
        return 'view.' . $this->getId();
    }
}
?>