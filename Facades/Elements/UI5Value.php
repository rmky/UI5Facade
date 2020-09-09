<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Widgets\Value;
use exface\UI5Facade\Facades\Interfaces\UI5ValueBindingInterface;
use exface\UI5Facade\Facades\Interfaces\UI5CompoundControlInterface;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryLiveReferenceTrait;
use exface\Core\Interfaces\Widgets\iTakeInput;
use exface\Core\Widgets\Input;

/**
 * Generates sap.m.Text controls for Value widgets
 * 
 * @method Value getWidget()
 *
 * @author Andrej Kabachnik
 *        
 */
class UI5Value extends UI5AbstractElement implements UI5ValueBindingInterface, UI5CompoundControlInterface
{
    use JqueryLiveReferenceTrait;
    
    private $valueBindingPath = null;
    
    private $valueBindingPrefix = '/';
    
    private $valueBindingDisabled = false;
    
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
            {$this->buildJsPropertyLayoutData()}
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
            $value = str_replace("\n", '', $this->getWidget()->getValue());
            $value = '"' . $this->escapeJsTextValue($value) . '"';
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
        
        $labelAppearance = '';
        if ($widget->getHideCaption() === true) {
            $labelAppearance .= 'visible: false,';
        } else {
            if ($widget instanceof iTakeInput) {
                if ($widget->isRequired()) {
                    $labelAppearance .= 'required: true,';
                }
            }
        }
        
        $label = <<<JS
        new sap.m.Label({
            text: "{$this->getCaption()}",
            {$this->buildJsPropertyTooltip()}
            {$labelAppearance}
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
        
        // If the the binding was disabled explicitly, return false
        if ($this->getValueBindingDisabled() === true) {
            return false;
        }
        
        // Otherwise assume model binding unless the widget has an explicit value
        if ($widget->hasValue() === true) {
            $valueExpr = $widget->getValueExpression();
        } elseif ($widget instanceof Input && $widget->hasDefaultValue()) {
            $valueExpr = $widget->getDefaultValueExpression();
        } 
        
        if ($valueExpr && $valueExpr->isStatic() === true) {
            return false;
        }
        
        return true;
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
            return $this->getValueBindingPrefix() . $this->getWidget()->getDataColumnName();
        }
        return $this->valueBindingPath;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Interfaces\UI5ValueBindingInterface::getValueBindingPrefix()
     */
    public function getValueBindingPrefix() : string
    {
        return $this->valueBindingPrefix;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Interfaces\UI5ValueBindingInterface::setValueBindingPrefix()
     */
    public function setValueBindingPrefix(string $value) : UI5ValueBindingInterface
    {
        $this->valueBindingPrefix = $value;
        return $this;
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
    
    protected function buildCssWidthDefaultValue() : string
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

    /**
     * Returns the widget property, that is used for the value binding (i.e. "value" for value-widgets).
     * 
     * NOTE: this is different from buildJsValueBindingPropertyName()! While the latter returns the name
     * of the UI5 control property for the main value, this method returns the name of the widget property,
     * that is used in this binding. I.e. for a simple Value widget (sap.m.Text), the widget property `value`
     * is bound to the control property `text`.
     * 
     * @return string
     */
    protected function getValueBindingWidgetPropertyName() : string
    {
        return 'value';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Interfaces\UI5ValueBindingInterface::getValueBindingDisabled()
     */
    public function getValueBindingDisabled() : bool
    {
        return $this->valueBindingDisabled;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Interfaces\UI5ValueBindingInterface::setValueBindingDisabled()
     */
    public function setValueBindingDisabled(bool $value) : UI5ValueBindingInterface
    {
        $this->valueBindingDisabled = $value;
        return $this;
    }
    
    /**
     * Returns a JS snippt to add a listener to the given binding's change-event to call the on-change-handlers.
     * 
     * The trouble is, that the change-event of UI5 controls only fires when changes are
     * made by a user. If a change is caused by an update of the model, the native event
     * does not fire. On the other hand, the binding-change-event is not triggered by
     * manual changes, so we actually need both in most cases.
     * 
     * This method is meant to be used within buildJsConstructor() or similar.
     * 
     * @param string $bindingName
     * @return UI5Value
     */
    protected function registerChangeEventOnBindingChange(string $bindingName) : UI5Value
    {
        if ($this->isValueBoundToModel() && $this->getUseWidgetId()) {
            $bindChangeWatcherJs = <<<JS
            
                sap.ui.getCore().byId('{$this->getId()}').getBinding('{$bindingName}').attachChange(function(oEvent){
                    {$this->getController()->buildJsEventHandler($this, self::EVENT_NAME_CHANGE, false)};
                });
JS;
            $this->getController()->addOnInitScript($bindChangeWatcherJs);
        }
        return $this;
    }
}