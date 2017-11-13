<?php
namespace exface\OpenUI5Template\Template\Elements;

/**
 * Generates OpenUI5 inputs
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
     * @see \exface\OpenUI5Template\Template\Elements\ui5AbstractElement::generateJsConstructor()
     */
    public function generateJsConstructor()
    {
        return $this->buildJsLabelWrapper($this->buildJsElementConstructor());
    }
    
    /**
     * Returns the constructor of the text/input element without the label
     * @return string
     */
    protected function buildJsElementConstructor()
    {
        return <<<JS
        new sap.m.Input("{$this->getId()}", {
            {$this->buildJsInitOptions()}
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
        return <<<JS
new sap.ui.layout.VerticalLayout({
    width: "100%",
    content: [
	    new sap.m.Label({
            text: "{$this->getCaption()}:"
        }),
        {$element_constructor}
    ]
})
JS;
    }
}
?>