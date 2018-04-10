<?php
namespace exface\OpenUI5Template\Templates\Elements;

/**
 * Generates OpenUI5 inputs
 *
 * @author Andrej Kabachnik
 *        
 */
class ui5InputCheckBox extends ui5Input
{
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5Text::buildJsConstructorForMainControl()
     */
    public function buildJsConstructorForMainControl()
    {
        return <<<JS

        new sap.m.InputCheckBox("{$this->getId()}", {
            {$this->buildJsProperties()}
        })

JS;
    }
    
    protected function buildJsPropertyValue()
    {
        $value = $this->getWidget()->getValueWithDefaults();
        return ($value ? 'selected: true, ' : '');
    }
    
    public function buildJsValueGetterMethod()
    {
        return 'getSelected()';
    }
}
?>