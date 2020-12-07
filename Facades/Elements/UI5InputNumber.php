<?php
namespace exface\UI5Facade\Facades\Elements;

/**
 * Renders a sap.m.Input with input type Number.
 * 
 * @method InputNumber getWidget()
 * 
 * @author Andrej Kabachnik
 *        
 */
class UI5InputNumber extends UI5Input
{    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Input::buildJsPropertyType()
     */
    protected function buildJsPropertyType()
    {        
        return 'type: sap.m.InputType.Number,';
    }
        
    /**
     * Returns the initial value defined in UXON as number or an quoted empty string
     * if not initial value was set.
     * 
     * @return string|NULL
     */
    protected function buildJsInitialValue() : string
    {
        $val = $this->getWidget()->getValueWithDefaults();
        return (is_null($val) || $val === '') ? '""' : $val;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Input::buildJsProperties()
     */
    public function buildJsProperties()
    {
        return parent::buildJsProperties() . <<<JS
            textAlign: sap.ui.core.TextAlign.Right,
JS;
    }
}