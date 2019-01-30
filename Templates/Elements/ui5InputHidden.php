<?php
namespace exface\OpenUI5Template\Templates\Elements;

use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryInputTrait;

/**
 * Generates OpenUI5 inputs
 *
 * @author Andrej Kabachnik
 *        
 */
class ui5InputHidden extends ui5Input
{    
    use JqueryInputTrait;
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5AbstractElement::isVisible()
     */
    protected function isVisible()
    {
        return false;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5Value::buildJsLabelWrapper()
     */
    protected function buildJsLabelWrapper($element_constructor)
    {
        if (! $this->getWidget()->getHideCaption()) {
            $js = <<<JS
        new sap.m.Label({
            visible: false
        }),
        
JS;
        }
        return $js . $element_constructor;
    }
}
?>