<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Widgets\WidgetGrid;
use exface\Core\Interfaces\Widgets\iHaveValue;
use exface\Core\Interfaces\Widgets\iContainOtherWidgets;
use exface\Core\Interfaces\Widgets\iDisplayValue;

/**
 * Generates the controls inside a sap.uxap.ObjectPageHeader.
 *
 * @author Andrej Kabachnik
 *        
 */
class UI5DialogHeader extends UI5Container
{
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        foreach ($this->getWidget()->getWidgets() as $widget) {
            if ($widget instanceof iHaveValue) {
                $js .= $this->buildJsObjectStatus($widget) . ',';
            } elseif ($widget instanceof WidgetGrid) {
                $js .= $this->buildJsVerticalLayout($widget) . ',';
            }
        }
        
        return $js;
    }   
                    
    protected function buildJsObjectStatus(iHaveValue $widget)
    {
        if ($widget->isHidden()){
            return '';
        }
        
        $element = new UI5ObjectStatus($widget, $this->getFacade());
        
        return $element->buildJsConstructor();
    }
        
    protected function buildJsVerticalLayout(iContainOtherWidgets $widget)
    {
        if ($widget->isHidden()){
            return '';
        }
        
        $title = $widget->getCaption() ? 'new sap.m.Title({text: "' . $widget->getCaption() . '"}),' : '';
        foreach ($widget->getWidgets() as $w) {
            if ($w instanceof WidgetGrid) {
                $content .= $this->buildJsVerticalLayout($w) . ',';
            } elseif ($w->getWidgetType() !== 'Display' && $w instanceof iDisplayValue) {
                $content .= $this->getFacade()->getElement($w)->buildJsConstructor('oController') . ',';
            } elseif ($w instanceof iHaveValue) {
                $content .= $this->buildJsObjectStatus($w) . ',';
            }
        }
        return <<<JS
        
            new sap.ui.layout.VerticalLayout({
                content: [
                    {$title}
                    {$content}
                ]
            }),
            
JS;
    }
}
?>