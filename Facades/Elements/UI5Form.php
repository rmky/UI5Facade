<?php
namespace exface\UI5Facade\Facades\Elements;

/**
 * Generates OpenUI5 inputs
 *
 * @author Andrej Kabachnik
 *        
 */
class UI5Form extends UI5Panel
{
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Panel::buildJsConstructor()
     */
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        return  $this->buildJsLayoutForm($this->buildJsChildrenConstructors(true));
    }
    
}
?>