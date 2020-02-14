<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryDisableConditionTrait;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryInputValidationTrait;
use exface\Core\Interfaces\Widgets\iHaveValue;

/**
 * Generates OpenUI5 inputs
 *
 * @author Andrej Kabachnik
 *        
 */
class UI5Input extends UI5Value
{
    use JqueryDisableConditionTrait;
    use JqueryInputValidationTrait;
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5AbstractElement::buildJsConstructor()
     */
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        $this->registerConditionalBehaviors();
        $this->registerOnChangeValidation();
        return $this->buildJsLabelWrapper($this->buildJsConstructorForMainControl($oControllerJs));
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Value::buildJsConstructorForMainControl()
     */
    public function buildJsConstructorForMainControl($oControllerJs = 'oController')
    {
        return <<<JS
        new sap.m.Input("{$this->getId()}", {
            {$this->buildJsProperties()}
            {$this->buildJsPropertyType()}
        })
        {$this->buildJsPseudoEventHandlers()}
JS;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5AbstractElement::buildJsProperties()
     */
    public function buildJsProperties()
    {
        $options = parent::buildJsProperties() . <<<JS
            {$this->buildJsPropertyWidth()}
            {$this->buildJsPropertyHeight()}
            {$this->buildJsPropertyChange()}
            {$this->buildJsPropertyRequired()}
            {$this->buildJsPropertyValue()}
            {$this->buildJsPropertyDisabled()}
JS;
        return $options;
    }
    

    
    /**
     * Returns the property height with name, value and tailing comma - or an empty
     * string if no height is defined.
     * 
     * @return string
     */
    protected function buildJsPropertyHeight()
    {
        if ($height = $this->getHeight()) {
            return 'height: "' . $height . '",';
        }
        return '';
    }
    
    /**
     * Returns the constructor property adding a on-change handler to the control.
     * 
     * The result is either empty or inlcudes a tailing comma.
     * 
     * @return string
     */
    protected function buildJsPropertyChange()
    {
        return 'change: ' . $this->getController()->buildJsEventHandler($this, self::EVENT_NAME_CHANGE, true) . ',';
    }
    
    /**
     * Returns the constructor property making the control required or not.
     * 
     * The result is either empty or inlcudes a tailing comma.
     * 
     * @return string
     */
    protected function buildJsPropertyRequired()
    {
        return 'required: ' . ($this->getWidget()->isRequired() ? 'true' : 'false') . ',';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::getHeight()
     */
    public function getHeight()
    {
        if ($this->getWidget()->getHeight()->isUndefined()) {
            return '';
        }
        return parent::getHeight();
    }
    
    /**
     * TODO merge this with the corresponding method in UI5Value to support all cases.
     * 
     * Currently the input can use it's own value with defaults and can inherit this
     * value from a linked widget if a value live reference is defined. 
     * 
     * TODO #binding use model binding for element values and live references.
     * For live references, Fetching the value is done in PHP for initialization and 
     * in JS for every chage of the referenced value. This is ugly, but since there
     * seems to be no init event for input controls in UI5, there is no way to tell
     * a control to get it's value from another one. Using onAfterRendering on the
     * base element does not work for filters in dialogs as they are not rendered
     * when the data element is loaded, but only when the dialog is opened. These
     * problems should be when moving values to the model.
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Value::buildJsPropertyValue()
     */
    protected function buildJsPropertyValue()
    {
        $value = null;
        $widget = $this->getWidget();
        
        if ($widget->getValueWidgetLink()) {
            $targetWidget = $widget->getValueWidgetLink()->getTargetWidget();
            if ($targetWidget instanceof iHaveValue) {
                $value = $this->escapeJsTextValue($targetWidget->getValueWithDefaults());
                $value = '"' . str_replace("\n", '', $value) . '"';
            }
        } 
        
        if ($value === null) {
            if ($this->isValueBoundToModel()) {
                $value = $this->buildJsValueBinding();
            } else {
                $value = '"' . $this->escapeJsTextValue($this->getWidget()->getValueWithDefaults()) . '"';
            }
        }
        
        return ($value ? 'value: ' . $value . ',' : '');
    }
    
    /**
     * 
     * @return string
     */
    protected function buildJsPropertyEditable()
    {
        return 'editable: true, ';
    }
    
    /**
     * Returns the type property including property name an tailing comma.
     * 
     * @return string
     */
    protected function buildJsPropertyType()
    {
        return 'type: sap.m.InputType.Text,';
    }
    
    protected function buildJsPropertyDisabled()
    {
        if ($this->getWidget()->isDisabled()) {
            return 'enabled: false,';
        }
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Value::buildJsValueBindingPropertyName()
     */
    public function buildJsValueBindingPropertyName() : string
    {
        return 'value';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5AbstractElement::buildJsValueSetter()
     */
    public function buildJsValueSetterMethod($valueJs)
    {
        return parent::buildJsValueSetterMethod($valueJs) . '.fireChange({value: ' . $valueJs . '})';
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsEnabler()
     */
    public function buildJsEnabler()
    {
        return "sap.ui.getCore().byId('{$this->getId()}').setEnabled(true)";
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsDisabler()
     */
    public function buildJsDisabler()
    {
        return "sap.ui.getCore().byId('{$this->getId()}').setEnabled(false)";
    }
    
    protected function registerConditionalBehaviors()
    {
        $contoller = $this->getController();
        // Register conditional reactions
        $this->registerDisableConditionAtLinkedElement();
        $contoller->addOnInitScript($this->buildJsDisableConditionInitializer());
        
        return;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsValidationError()
     */
    public function buildJsValidationError()
    {
        if ($this->getWidget()->isHidden() === true) {
            return $this->buildJsShowError(json_encode('Error in hidden field "' . $this->getCaption() . '": ' . $this->getValidationErrorText()));
        } else {
            return "sap.ui.getCore().byId('{$this->getId()}').setValueState('Error')";
        }
    }
    
    protected function registerOnChangeValidation()
    {
        $validator = $this->buildJsValidator();
        if ($validator !== 'true') {#
            $invalidText = json_encode($this->getValidationErrorText());
            $onChangeValidation = <<<JS

    sap.ui.getCore().byId('{$this->getId()}').setValueStateText($invalidText)           
    if(! {$this->buildJsValidator()} ) {
        {$this->buildJsValidationError()};
    } else {
        sap.ui.getCore().byId('{$this->getId()}').setValueState('None');
    }
    
JS;
            $this->addOnChangeScript($onChangeValidation);
            
        }
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsResetter()
     */
    public function buildJsResetter() : string
    {
        $widget = $this->getWidget();
        
        if ($widget->getValueWidgetLink() !== null) {
            return '';
        }
        
        // TODO reset properly if value is bound to model
        $staticDefault = $widget->getValueWithDefaults();
        $initialValueJs = json_encode($staticDefault);
        $js = $this->buildJsValueSetter($initialValueJs);
        
        // The value-setter automatically performs validation. We don't need this unless the new value
        // is actually not empty.
        if ($staticDefault === null || $staticDefault === '') {
            $js .= ".setValueState('None')";
        }
        
        return $js;
    }
    
    /**
     * Returns inline JS-code to give focus to this widget
     * @return string
     */
    public function buildJsSetFocus() : string
    {
        return "sap.ui.getCore().byId('{$this->getId()}').focus()";
    }
}
?>