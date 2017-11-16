<?php
namespace exface\OpenUI5Template\Template\Elements;

/**
 * Generates OpenUI5 inputs
 *
 * @author Andrej Kabachnik
 *        
 */
class ui5InputHidden extends ui5Input
{    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Template\Elements\ui5Input::buildPropertyVisibile()
     */
    protected function buildPropertyVisibile()
    {
        return ', visible: false';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Template\Elements\ui5Text::buildJsLabelWrapper()
     */
    protected function buildJsLabelWrapper($element_constructor)
    {
        return $element_constructor;
    }
}
?>