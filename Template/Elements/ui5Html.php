<?php
namespace exface\OpenUI5Template\Template\Elements;

use exface\Core\Widgets\Html;

/**
 * Generates OpenUI5 HTML
 * 
 * @method Html getWidget()
 *
 * @author Andrej Kabachnik
 *        
 */
class ui5Html extends ui5Text
{
    function generateJs()
    {
        return '';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Template\Elements\ui5AbstractElement::buildJsConstructor()
     */
    public function buildJsConstructor()
    {
        return $this->buildJsLabelWrapper($this->buildJsElementConstructor());
    }
    
    /**
     * Returns the constructor of the text/input element without the label
     * @return string
     */
    protected function buildJsElementConstructor()
    {
        $widget = $this->getWidget();
        $css = $widget->getCss() ? '<style>' . $widget->getCss() . '</style>' : '';
        return <<<JS
        new sap.ui.core.HTML("{$this->getId()}", {
            content: "{$css}{$widget->getHtml()}"
        })
JS;
    }
}
?>