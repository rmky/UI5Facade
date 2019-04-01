<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Widgets\Value;
use exface\UI5Facade\Facades\Interfaces\UI5ValueBindingInterface;
use exface\UI5Facade\Facades\Interfaces\UI5CompoundControlInterface;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryLiveReferenceTrait;

/**
 * Generates sap.m.Text controls for Value widgets
 * 
 * @method Value getWidget()
 *
 * @author Andrej Kabachnik
 *        
 */
class UI5Value extends UI5AbstractElement implements ui5ValueBindingInterface, ui5CompoundControlInterface
{
    use JqueryLiveReferenceTrait;
    
    private $valueBindingPath = null;
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::init()
     */
    protected function init()
    {
        parent::init();
        
        // If the input's value is bound to another element via an expression, we need to make sure, that other element will
        // change the input's value every time it changes itself. This needs to be done on init() to make sure, the other element
        // has not generated it's JS code yet!
        $this->registerLiveReferenceAtLinkedElement();
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5AbstractElement::buildJsConstructor()
     */
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        return $this->buildJsConstructorForMainControl($oControllerJs);
    }
    
    /**
     * Returns the constructor of the text/input control without the label
     * 
     * @return string
     */
    public function buildJsConstructorForMainControl($oControllerJs = 'oController')
    {
        if ($this->getWidget()->getVisibility() === EXF_WIDGET_VISIBILITY_PROMOTED) {
            $this->addElementCssClass('exf-promoted');
        }
        
        return <<<JS

        new sap.m.Text("{$this->getId()}", {
            {$this->buildJsProperties()}
            {$this->buildJsPropertyValue()}
        }).addStyleClass("{$this->buildCssElementClass()}")

JS;
    }
            
    public function buildJsProperties()
    {
        return parent::buildJsProperties() . <<<JS
            {$this->buildJsPropertyTooltip()}
JS;
    }
    
    /**
     * Returns the value property with property name and value followed by a comma.
     * 
     * @return string
     */
    protected function buildJsPropertyValue()
    {
        return <<<JS
            text: {$this->buildJsValue()},
JS;
    }
    
    /**
     * Returns inline javascript code for the value of the value property (without the property name).
     * 
     * Possible results are a quoted JS string, a binding expression or a binding object.
     * 
     * @return string
     */
    public function buildJsValue()
    {
        if (! $this->isValueBoundToModel()) {
            $value = $this->escapeJsTextValue($this->getWidget()->getValue());
            $value = '"' . str_replace("\n", '', $value) . '"';
        } else {
            $value = $this->buildJsValueBinding();
        }
        return $value;
    }
    
    /**
     * Wraps the element constructor in a layout with a label.
     * 
     * @param string $element_constructor
     * @return string
     */
    protected function buildJsLabelWrapper($element_constructor)
    {
        $widget = $this->getWidget();
        if ($widget->getHideCaption() === true) {
            $labelVisible = 'visible: false,';
        }
        
        $label = <<<JS
        new sap.m.Label({
            text: "{$this->getCaption()}",
            {$this->buildJsPropertyTooltip()}
            {$labelVisible}
        }),

JS;
        
        return $label . $element_constructor;
    }
    
    /**
     * 
     * @return boolean
     */
    protected function isValueBoundToModel()
    {
        $widget = $this->getWidget();
        $model = $this->getView()->getModel();
        
        // If there is a model binding, obviously return true
        if ($model->hasBinding($widget, $this->getValueBindingWidgetPropertyName())) {
            return true;
        }
        
        // Otherwise assume model binding unless the widget has an explicit value
        return $widget->hasValue() ? false : true;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Interfaces\UI5ValueBindingInterface::buildJsValueBindingOptions()
     */
    public function buildJsValueBindingOptions()
    {
        return '';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Interfaces\UI5ValueBindingInterface::buildJsValueBinding()
     */
    public function buildJsValueBinding($customOptions = '')
    {
        $js = <<<JS
            {
                path: "{$this->getValueBindingPath()}",
                {$this->buildJsValueBindingOptions()}
                {$customOptions}
            }
JS;
                return $js;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Interfaces\UI5ValueBindingInterface::setValueBindingPath()
     */
    public function setValueBindingPath($string)
    {
        $this->valueBindingPath = $string;
        return $this;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Interfaces\UI5ValueBindingInterface::getValueBindingPath()
     */
    public function getValueBindingPath() : string
    {
        if ($this->valueBindingPath === null) {
            $widget = $this->getWidget();
            $model = $this->getView()->getModel();
            if ($model->hasBinding($widget, $this->getValueBindingWidgetPropertyName())) {
                return $model->getBindingPath($widget, $this->getValueBindingWidgetPropertyName());
            }
            return '/' . $this->getWidget()->getDataColumnName();
        }
        return $this->valueBindingPath;
    }
    
    protected function buildJsPropertyWidth()
    {
        $dim = $this->getWidget()->getWidth();
        if ($dim->isFacadeSpecific() || $dim->isPercentual()) {
            $val = $dim->getValue();
        } else {
            // TODO add support for relative units
            $val = $this->buildCssWidthDefaultValue();
        }
        if (! is_null($val) && $val !== '') {
            return 'width: "' . $val . '",';
        } else {
            return '';
        }
    }
    
    protected function buildCssWidthDefaultValue()
    {
        return '100%';
    }
    
    protected function buildJsPropertyHeight()
    {
        $dim = $this->getWidget()->getHeight();
        if ($dim->isFacadeSpecific() || $dim->isPercentual()) {
            $val = $dim->getValue();
        } else {
            // TODO add support for relative units
            $val = $this->buildCssHeightDefaultValue();
        }
        if (! is_null($val) && $val !== '') {
            return 'height: "' . $val . '",';
        } else {
            return '';
        }
    }
    
    protected function buildCssHeightDefaultValue()
    {
        return '';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Interfaces\UI5ValueBindingInterface::buildJsValueBindingPropertyName()
     */
    public function buildJsValueBindingPropertyName() : string
    {
        return 'text';
    }
    
    protected function getValueBindingWidgetPropertyName() : string
    {
        return 'value';
    }
}
?>