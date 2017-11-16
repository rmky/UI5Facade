<?php
namespace exface\OpenUI5Template\Template\Elements;

/**
 * Generates OpenUI5 inputs
 *
 * @author Andrej Kabachnik
 *        
 */
class ui5Input extends ui5Text
{
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Template\Elements\ui5Text::buildJsElementConstructor()
     */
    protected function buildJsElementConstructor()
    {
        return <<<JS
        new sap.m.Input("{$this->getId()}", {
            {$this->buildJsInitOptions()}
        })
JS;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Template\Elements\ui5AbstractElement::buildJsInitOptions()
     */
    public function buildJsInitOptions()
    {
        $options = '
            width: "100%"
            ' . $this->buildPropertyValue() . '
            ' . $this->buildPropertyVisibile();
        return $options;
    }
    
    protected function buildPropertyVisibile()
    {
        if ($this->getWidget()->isHidden()) {
            return ', visible: false';
        }
        return '';
    }
    
    protected function buildPropertyValue()
    {
        return ($this->getValueWithDefaults() ? ', value: "' . $this->getValueWithDefaults() . '"' : '');
    }
    
    public function getValueWithDefaults()
    {
        if ($this->getWidget()->getValueExpression() && $this->getWidget()->getValueExpression()->isReference()) {
            $value = '';
        } else {
            $value = $this->getWidget()->getValue();
        }
        if (is_null($value) || $value === '') {
            $value = $this->getWidget()->getDefaultValue();
        }
        return $value;
    }
}
?>