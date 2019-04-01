<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Widgets\ProgressBar;
use exface\Core\Facades\AbstractAjaxFacade\Elements\HtmlProgressBarTrait;

/**
 *
 * @method ProgressBar getWidget()
 *        
 * @author Andrej Kabachnik
 *        
 */
class ui5ProgressBar extends ui5Display
{
    /**
     *
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\ui5AbstractElement::buildJsConstructor()
     */
    public function buildJsConstructorForMainControl($oControllerJs = 'oController')
    {
        return <<<JS
        
        new sap.m.ProgressIndicator("{$this->getid()}", {
            showValue: true,
            state: "None",
    		percentValue: {$this->buildJsValuePercent()},
            displayValue: {$this->buildJsValue()},
            {$this->buildJsProperties()}
    	})
    	
JS;
    }
            
    public function buildJsValuePercent() : string
    {
       if (! $this->isValueBoundToModel()) {
            $value = $this->getWidget()->getValueDataType()->parse($this->getWidget()->getValue());
        } else {
            $bindingOptions = <<<JS
                formatter: function(value){
                    return parseFloat(value);
                }

JS;
            $value = $this->buildJsValueBinding($bindingOptions);
        }
        return $value;
    }
            
    /**
     *
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\ui5Value::buildJsValueBindingPropertyName()
     */
    public function buildJsValueBindingPropertyName() : string
    {
        return 'percentValue';
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\ui5Display::buildJsPropertyAlignment()
     */
    protected function buildJsPropertyAlignment()
    {
        return '';
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\ui5Display::buildJsPropertyWrapping()
     */
    protected function buildJsPropertyWrapping()
    {
        return '';
    }
}