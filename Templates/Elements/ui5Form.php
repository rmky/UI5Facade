<?php
namespace exface\OpenUI5Template\Templates\Elements;

/**
 * Generates OpenUI5 inputs
 *
 * @author Andrej Kabachnik
 *        
 */
class ui5Form extends ui5Panel
{
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5Panel::buildJsConstructor()
     */
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        return  $this->buildJsLayoutForm($this->buildJsChildrenConstructors());
    }
    
}
?>