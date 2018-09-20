<?php
namespace exface\OpenUI5Template\Templates\Elements;

use exface\Core\Widgets\WidgetGrid;
use exface\Core\Interfaces\WidgetInterface;
use exface\Core\Interfaces\Widgets\iHaveValue;
use exface\Core\Interfaces\Widgets\iLayoutWidgets;
use exface\Core\Interfaces\Widgets\iContainOtherWidgets;
use exface\Core\Interfaces\Widgets\iShowSingleAttribute;
use exface\Core\Widgets\InputSelect;
use exface\Core\Widgets\InputHidden;

/**
 * Generates OpenUI5 inputs
 *
 * @author Andrej Kabachnik
 *        
 */
class ui5DialogHeader extends ui5Container
{
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        foreach ($this->getWidget()->getWidgets() as $widget) {
            if ($widget instanceof iHaveValue) {
                $js .= $this->buildJsObjectStatus($widget);
            } elseif ($widget instanceof WidgetGrid) {
                $js .= $this->buildJsVerticalLayout($widget);
            }
        }
        
        return $js;
    }   
                    
    protected function buildJsObjectStatus(iHaveValue $widget)
    {
        if ($widget->isHidden()){
            return '';
        }
        
        $element = new ui5ObjectStatus($widget, $this->getTemplate());
        
        return $element->buildJsConstructor();
    }
        
    protected function buildJsVerticalLayout(iContainOtherWidgets $widget)
    {
        if ($widget->isHidden()){
            return '';
        }
        
        $title = $this->getCaption() ? 'title: "' . $this->getCaption() . '",' : '';
        foreach ($widget->getWidgets() as $w) {
            if ($w instanceof WidgetGrid) {
                $content .= $this->buildJsVerticalLayout($w) . ',';
            } elseif ($w instanceof iHaveValue) {
                $content .= $this->buildJsObjectStatus($w) . ',';
            }
        }
        return <<<JS
        
            new sap.ui.layout.VerticalLayout({
                {$title}
                content: [
                    {$content}
                ]
            }),
            
JS;
    }
}
?>