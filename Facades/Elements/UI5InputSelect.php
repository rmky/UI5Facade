<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Widgets\InputSelect;

/**
 * Generates OpenUI5 CobmoBox or MultiComboBox to represent a select widget
 *
 * @method InputSelect getWidget()
 * 
 * @author Andrej Kabachnik
 *        
 */
class UI5InputSelect extends UI5Input
{
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Input::buildJsConstructorForMainControl()
     */
    public function buildJsConstructorForMainControl($oControllerJs = 'oController')
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
     * @see \exface\UI5Facade\Facades\Elements\UI5AbstractElement::buildJsProperties()
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
     * @see \exface\UI5Facade\Facades\Elements\UI5Input::buildJsPropertyValue()
     */
    protected function buildJsPropertyValue()
    {
        if ($this->isValueBoundToModel()) {
            $value = $this->buildJsValueBinding();
        } else {
            $value = '"' . $this->escapeJsTextValue($this->getWidget()->getValueWithDefaults()) . '"';
        }
        return ($value ? 'selectedKey: ' . $value . ',' : '');
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5AbstractElement::buildJsValueGetterMethod()
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
     * @see \exface\UI5Facade\Facades\Elements\UI5AbstractElement::buildJsValueGetterMethod()
     */
    public function buildJsValueSetterMethod($value)
    {
        if ($this->getWidget()->getMultiSelect()) {
            return "setSelectedKeys().fireSelectionChange()";
        } else {
            return "setSelectedKey({$value}).fireChange({value: {$value}})";
        }
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Input::buildJsValueBindingPropertyName()
     */
    public function buildJsValueBindingPropertyName() : string
    {
        return 'selectedKey';
    }
}
?>