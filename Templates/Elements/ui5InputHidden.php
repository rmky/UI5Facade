<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryInputTrait;

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
     * @see \exface\UI5Facade\Facades\Elements\ui5AbstractElement::isVisible()
     */
    protected function isVisible()
    {
        return false;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\ui5Value::buildJsLabelWrapper()
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