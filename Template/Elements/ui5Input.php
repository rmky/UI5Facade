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
        $options = parent::buildJsProperties() . '
            width: "100%"
            ' . $this->buildJsPropertyValue() . '
            ' . $this->buildJsPropertyVisibile();
        return $options;
    }
    
    protected function buildJsPropertyVisibile()
    {
        if ($this->getWidget()->isHidden()) {
            return ', visible: false';
        }
        return '';
    }
    
    protected function buildJsPropertyValue()
    {
        $value = $this->getWidget()->getValueWithDefaults();
        return ($value ? ', value: "' . $this->buildJsTextValue($value) . '"' : '');
    }
    
    protected function buildJsPropertyEditable()
    {
        return 'editable: true, ';
    }
}
?>