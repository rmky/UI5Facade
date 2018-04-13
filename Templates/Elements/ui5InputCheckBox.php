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

        new sap.m.CheckBox("{$this->getId()}", {
            {$this->buildJsProperties()}                
        })

JS;
    }
    
    protected function buildJsPropertyValue()
    {
        if ($this->isValueBoundToModel()) {
            $value = $this->buildJsValueBinding();
        } else {
            $value = $this->getWidget()->getValueWithDefaults() ? 'true' : 'false';
        }
        return ($value ? 'selected: ' . $value . ', ' : '');
    }
    
    public function buildJsValueGetterMethod()
    {
        return 'getSelected()';
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Interfaces\ui5ValueBindingInterface::buildJsValueBindingOptions()
     */
    public function buildJsValueBindingOptions()
    {
        return $this->getValueBindingFormatter()->buildJsBindingProperties();
    }
    
    /**
     *
     * @return ui5BindingFormatterInterface
     */
    protected function getValueBindingFormatter()
    {
        return $this->getTemplate()->getDataTypeFormatter($this->getWidget()->getValueDataType());
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5Input::buildJsPropertyChange()
     */
    protected function buildJsPropertyChange()
    {
        $script = $this->getOnChangeScript();
        // If data binding is used, it won't work together with the boolean formatter for some
        // reason. The value in the model simply never changes. This hack manually changes the
        // model every time the checkbox is checked or unchecked.
        // TODO restrict this to only two-way-binding somehow
        if ($this->isValueBoundToModel()) {
            $script .= <<<JS

            var oCtxt = event.getSource().getBindingContext();
            var path = oCtxt.sPath;
            var row = oCtxt.getModel().getProperty(path);
            var index = parseInt(path.substring(path.lastIndexOf('/')+1));
            row["{$this->getValueBindingPath()}"] = event.getParameters().selected ? 1 : 0;
            oCtxt.getModel().setProperty(path, row);
JS;
        }
        
        if (empty($script)) {
            return '';
        } else {
            return <<<JS
        
            select: function(event) {
                {$script}
            },
JS;
        }
    }
    
    public function buildJsValueSetterMethod($value)
    {
        return "setSelected({$value} ? true : false)";
    }
}
?>