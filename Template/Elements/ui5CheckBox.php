<?php
namespace exface\OpenUI5Template\Template\Elements;

/**
 * Generates OpenUI5 inputs
 *
 * @author Andrej Kabachnik
 *        
 */
class ui5CheckBox extends ui5Input
{
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Template\Elements\ui5Text::buildJsControlConstructor()
     */
    protected function buildJsControlConstructor()
    {
        return <<<JS
        new sap.m.CheckBox("{$this->getId()}", {
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