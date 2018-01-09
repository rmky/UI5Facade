<?php
namespace exface\OpenUI5Template\Template\Elements;

use exface\Core\Widgets\Text;

/**
 * Generates OpenUI5 text elements
 * 
 * @method Text getWidget()
 *
 * @author Andrej Kabachnik
 *        
 */
class ui5Text extends ui5AbstractElement
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
        return $this->buildJsLabelWrapper($this->buildJsControlConstructor());
    }
    
    /**
     * Returns the constructor of the text/input control without the label
     * 
     * @return string
     */
    protected function buildJsControlConstructor()
    {
        $text = $this->buildJsTextValue($this->getWidget()->getText());
        $text = str_replace("\n", '', $text);
        return <<<JS
        new sap.m.Text("{$this->getId()}", {
            text: "{$text}",
            {$this->buildJsProperties()}
        })
JS;
    }
    
    /**
     * Wraps the element constructor in a layout with a label.
     * 
     * @param string $element_constructor
     * @return string
     */
    protected function buildJsLabelWrapper($element_constructor)
    {
        if (! $this->getWidget()->getHideCaption()) {
            $js = <<<JS
        new sap.m.Label({
            text: "{$this->getCaption()}"
        }),

JS;
        }
        return $js . $element_constructor;
    }
}
?>