<?php
namespace exface\OpenUI5Template\Template\Elements;

use exface\Core\Widgets\Dialog;
use exface\Core\Interfaces\Widgets\iLayoutWidgets;
use exface\Core\Widgets\AbstractWidget;
use exface\Core\Interfaces\Widgets\iContainOtherWidgets;
use exface\Core\Factories\DataSheetFactory;
use exface\Core\Widgets\Tabs;
use exface\Core\Widgets\Tab;
use exface\Core\Widgets\Image;

/**
 *
 * @method Dialog getWidget()
 *        
 * @author aka
 *        
 */
class ui5Dialog extends ui5Container
{
    public function buildJsConstructor()
    {
        return $this->buildJsPage($this->buildJsObjectPageLayout());
    }
    
    protected function buildJsObjectPageLayout()
    {
        return <<<JS

        new sap.uxap.ObjectPageLayout({
            useIconTabBar: true,
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
        
        if (($uid_widget = $this->getWidget()->findChildrenByAttribute($this->getMetaObject()->getUidAttribute())[0]) && !is_null($uid_widget->getValue())) {
            $uid_data_sheet = DataSheetFactory::createFromObject($this->getMetaObject());
            $uid_data_sheet->getColumns()->addFromAttribute($this->getMetaObject()->getLabelAttribute());
            $uid_data_sheet->addFilterFromString($this->getMetaObject()->getUidAttributeAlias(), $uid_widget->getValue());
            $uid_data_sheet->dataRead();
            $label = $uid_data_sheet->getCellValue($this->getMetaObject()->getLabelAttribute()->getAlias(), 0);
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
    				  showMarkers: true,
    				  markFavorite: true,
    				  markFlagged: true,
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
    
    protected function buildJsPage($content)
    {
        $pageTitle = $this->getCaption() . ': ' . $this->getWidget()->getMetaObject()->getName();
        return <<<JS
        
        new sap.m.Page("{$this->getId()}", {
            title: "{$pageTitle}",
            showNavButton: true,
            navButtonPress: function(){
                closeTopDialog();
            },
            content: [
                {$content}
            ],
            footer: {$this->buildJsToolbar()}
        })
JS;
    }
                
    protected function buildJsObjectPageSections()
    {
        $widget = $this->getWidget();
        $js = '';
        
        if ($widget->getWidgetFirst() instanceof Tabs) {
            foreach ($widget->getWidgetFirst()->getTabs() as $tab) {
                $js .= $this->buildJsObjectPageSectionFromTab($tab);
            }
        }
        
        return $js;
    }
    
    protected function buildJsObjectPageSectionFromTab(Tab $tab) 
    {
        $tabElement = $this->getTemplate()->getElement($tab);
        return <<<JS
                // BOF ObjectPageSection
                new sap.uxap.ObjectPageSection({
					title:"{$tab->getCaption()}",
					subSections: new sap.uxap.ObjectPageSubSection({
						blocks: [
                            {$tabElement->buildJsLayoutConstructor($tabElement->buildJsChildrenConstructors())}
                        ]
					})
				}),
                // EOF ObjectPageSection
                
JS;
    }
                            
    protected function buildJsToolbar()
    {
        return $this->getTemplate()->getElement($this->getWidget()->getToolbarMain())->buildJsConstructor();
    }
                
    public function getViewName()
    {
        return 'view.' . $this->getId();
    }
}
?>