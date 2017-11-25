<?php
namespace exface\OpenUI5Template\Template\Elements;

/**
 * Generates OpenUI5 selects
 *
 * @author Andrej Kabachnik
 *        
 */
class ui5InputSelect extends ui5Input
{
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Template\Elements\ui5Input::buildJsElementConstructor()
     */
    protected function buildJsElementConstructor()
    {
        return <<<JS
        new sap.m.Select("{$this->getId()}", {
			{$this->buildJsProperties()}
        })
JS;
    }
			
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Template\Elements\ui5AbstractElement::buildJsProperties()
     */
    public function buildJsProperties()
    {
        $widget = $this->getWidget();
        $options = '
            width: "100%",
            forceSelection: true
';
        return $options;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Template\Elements\ui5AbstractElement::buildJsValueGetterMethod()
     */
    public function buildJsValueGetterMethod()
    {
        return "getSelectedKey()";
    }
}
?>