<?php
namespace exface\UI5Facade\Facades\Elements;

/**
 * Generates OpenUI5 inputs
 *
 * @author Andrej Kabachnik
 *        
 */
class UI5InputCheckBox extends UI5Input
{
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Text::buildJsConstructorForMainControl()
     */
    public function buildJsConstructorForMainControl($oControllerJs = 'oController')
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
     * @see \exface\UI5Facade\Facades\Interfaces\UI5ValueBindingInterface::buildJsValueBindingOptions()
     */
    public function buildJsValueBindingOptions()
    {
        return $this->getValueBindingFormatter()->buildJsBindingProperties();
    }
    
    /**
     *
     * @return UI5BindingFormatterInterface
     */
    protected function getValueBindingFormatter()
    {
        return $this->getFacade()->getDataTypeFormatterForUI5Bindings($this->getWidget()->getValueDataType());
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Input::buildJsPropertyChange()
     */
    protected function buildJsPropertyChange()
    {
        // If data binding is used, it won't work together with the boolean formatter for some
        // reason. The value in the model simply never changes. This hack manually changes the
        // model every time the checkbox is checked or unchecked.
        // TODO restrict this to only two-way-binding somehow
        if ($this->isValueBoundToModel()) {
            if ($this->getWidget()->isInTable()) {
                $script = <<<JS

            var oCtxt = oEvent.getSource().getBindingContext();
            var path = oCtxt.sPath;
            var row = oCtxt.getModel().getProperty(path);
            row["{$this->getValueBindingPath()}"] = oEvent.getParameters().selected ? 1 : 0;
            oCtxt.getModel().setProperty(path, row);
            
JS;
            } else {
                $script = <<<JS
                
            var oCtxt = oEvent.getSource().getBindingContext();
            var path = oCtxt.sPath;
            oCtxt.getModel().setProperty(path, oEvent.getParameters().selected ? 1 : 0);
            
JS;
            }
                
            $this->getController()->addOnEventScript($this, 'change', $script);
        }
        
        return 'select: ' . $this->getController()->buildJsEventHandler($this, 'change', true) . ',';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Input::buildJsValueSetterMethod()
     */
    public function buildJsValueSetterMethod($value)
    {
        return "setSelected({$value} ? true : false).fireSelect({selected: (){$value} ? true : false)})";
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Input::buildJsValueBindingPropertyName()
     */
    public function buildJsValueBindingPropertyName() : string
    {
        return 'selected';
    }
    
    /**
     * Checkboxes cannot be required in UI5!
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Input::buildJsPropertyRequired()
     */
    protected function buildJsPropertyRequired()
    {
        return '';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsValidator()
     */
    public function buildJsValidator()
    {
        return 'true';
    }
}
?>