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
        $widget = $this->getWidget();
        
        if ($this->isValueBoundToModel()) {
            $value = $this->buildJsValueBinding();
        } else {
            if ($widget->getMultiSelect() === true) {
                $val = $widget->getValueWithDefaults();
                switch (true) {
                    case is_array($val) === true:
                        $value = json_encode($val);
                        break;
                    case $val === null:
                        $value = '[]';
                        break;
                    case (stripos($val, $widget->getMultiSelectValueDelimiter()) !== false):
                        $vals = explode($widget->getMultiSelectValueDelimiter(), $val);
                        $value = json_encode($vals);
                        break;
                    default:
                        $value = '["' . $this->escapeJsTextValue($val) . '"]';
                        break;
                }
            } else {
                $value = '"' . $this->escapeJsTextValue($widget->getValueWithDefaults()) . '"';
            }
        }
        
        $property = $widget->getMultiSelect() ? 'selectedKeys' : 'selectedKey';
        return ($value ? $property . ': ' . $value . ',' : '');
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
            return "setSelectedKeys(function(){
    var val = $value;
    if (Array.isArray(val)) {
        return val;
    } else if (val === undefined || val === null || val === '') {
        return [];
    } else if (val.toString().indexOf('{$this->getWidget()->getMultiSelectValueDelimiter()}') > -1) {
        return val.toString().split('{$this->getWidget()->getMultiSelectValueDelimiter()}');
    } else {
        return [val]
    }
}).fireSelectionChange()";
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