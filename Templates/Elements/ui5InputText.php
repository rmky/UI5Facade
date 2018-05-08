<?php
namespace exface\OpenUI5Template\Templates\Elements;

/**
 * Generates OpenUI5 inputs
 *
 * @author Andrej Kabachnik
 *        
 */
class ui5InputText extends ui5Input
{
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5Text::buildJsConstructorForMainControl()
     */
    public function buildJsConstructorForMainControl($oControllerJs = 'oController')
    {
        return <<<JS
        new sap.m.TextArea("{$this->getId()}", {
            {$this->buildJsProperties()}
        })
JS;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5Input::getHeight()
     */
    public function getHeight()
    {
        if ($this->getWidget()->getHeight()->isUndefined()) {
            return (2 * $this->getHeightRelativeUnit()) . 'px';
        }
        return parent::getHeight();
    }
}
?>