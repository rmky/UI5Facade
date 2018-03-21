<?php
namespace exface\OpenUI5Template\Templates\Elements;

use exface\Core\Widgets\InputSelect;

/**
 * Generates OpenUI5 CobmoBox or MultiComboBox to represent a select widget
 *
 * @method InputSelect getWidget()
 * 
 * @author Andrej Kabachnik
 *        
 */
class ui5InputSelect extends ui5Input
{
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5Input::buildJsConstructorForMainControl()
     */
    public function buildJsConstructorForMainControl()
    {
        if ($this->getWidget()->getMultiSelect() === true) {
            $control = 'sap.m.MultiComboBox';
        } else {
            $control = 'sap.m.ComboBox';
        }
        return <<<JS
        new {$control}("{$this->getId()}", {
			{$this->buildJsProperties()}
        }){$this->buildJsPseudoEventHandlers()}
JS;
    }
			
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5AbstractElement::buildJsProperties()
     */
    public function buildJsProperties()
    {
        $widget = $this->getWidget();
        
        $items = '';
        foreach ($widget->getSelectableOptions() as $key => $value) {
            if ($widget->getMultiSelect() && $key === '') {
                continue;
            }
            $items .= <<<JS
                new sap.ui.core.Item({
                    key: "{$key}",
                    text: "{$value}"
                }),
JS;
        }
        
        $options = parent::buildJsProperties() . '
            items: [
                ' . $items . '
            ]
';
        return $options;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5Input::buildJsPropertyValue()
     */
    protected function buildJsPropertyValue()
    {
        $value = $this->getWidget()->getValueWithDefaults();
        return ($value ? 'selectedKey: "' . $this->escapeJsTextValue($value) . '",' : '');
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5AbstractElement::buildJsValueGetterMethod()
     */
    public function buildJsValueGetterMethod()
    {
        if ($this->getWidget()->getMultiSelect()) {
            return "getSelectedKeys().join('" . $this->getWidget()->getMultiSelectValueDelimiter() . "')";
        } else {
            return "getSelectedKey()";
        }
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5AbstractElement::buildJsValueGetterMethod()
     */
    public function buildJsValueSetterMethod($value)
    {
        if ($this->getWidget()->getMultiSelect()) {
            return "setSelectedKeys((" . $value . ").split('" . $this->getWidget()->getMultiSelectValueDelimiter() . "'))";
        } else {
            return "setSelectedKey(" . $value . ")";
        }
    }
}
?>