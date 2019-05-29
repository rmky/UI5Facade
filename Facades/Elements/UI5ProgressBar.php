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
class UI5ProgressBar extends UI5Display
{
    private $textBindingPath = null;
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5AbstractElement::buildJsConstructor()
     */
    public function buildJsConstructorForMainControl($oControllerJs = 'oController')
    {
        return <<<JS
        
        new sap.m.ProgressIndicator("{$this->getid()}", {
            showValue: true,
            state: "None",
    		percentValue: {$this->buildJsValuePercent()},
            displayValue: {$this->buildJsDisplayValue()},
            {$this->buildJsProperties()}
    	})
    	
JS;
    }
            
    public function buildJsDisplayValue() : string
    {
        $widget = $this->getWidget();
        
        if ($widget->isTextBoundToAttribute() === false) {
            return $this->buildJsValue();
        }
        
        if (! $this->isValueBoundToModel()) {
            $value = $widget->getText($widget->getValue());
        } else {
            $textAttribute = $widget->getTextAttribute();
            $value = <<<JS
            {
                path: "{$this->getTextBindingPath()}",
                {$this->getFacade()->getDataTypeFormatterForUI5Bindings($textAttribute->getDataType())->buildJsBindingProperties()}
            }
JS;
        }
        return $value;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Interfaces\UI5ValueBindingInterface::getValueBindingPath()
     */
    public function getTextBindingPath() : string
    {
        if ($this->textBindingPath === null) {
            $widget = $this->getWidget();
            $model = $this->getView()->getModel();
            if ($model->hasBinding($widget, 'text')) {
                return $model->getBindingPath($widget, 'text');
            }
            return $this->getValueBindingPrefix() . $this->getWidget()->getTextDataColumnName();
        }
        return $this->textBindingPath;
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
     * @see \exface\UI5Facade\Facades\Elements\UI5Value::buildJsValueBindingPropertyName()
     */
    public function buildJsValueBindingPropertyName() : string
    {
        return 'percentValue';
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Display::buildJsPropertyAlignment()
     */
    protected function buildJsPropertyAlignment()
    {
        return '';
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Display::buildJsPropertyWrapping()
     */
    protected function buildJsPropertyWrapping()
    {
        return '';
    }
}