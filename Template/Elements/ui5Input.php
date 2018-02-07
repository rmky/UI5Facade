<?php
namespace exface\OpenUI5Template\Template\Elements;

use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryLiveReferenceTrait;

/**
 * Generates OpenUI5 inputs
 *
 * @author Andrej Kabachnik
 *        
 */
class ui5Input extends ui5Value
{
    use JqueryLiveReferenceTrait;
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::init()
     */
    protected function init()
    {
        parent::init();
        // If the input's value is bound to another element via an expression, we need to make sure, that other element will
        // change the input's value every time it changes itself. This needs to be done on init() to make sure, the other element
        // has not generated it's JS code yet!
        $this->registerLiveReferenceAtLinkedElement();
        
        // Register an onChange-Script on the element linked by a disable condition.
        $this->registerDisableConditionAtLinkedElement();
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Template\Elements\ui5AbstractElement::buildJsConstructor()
     */
    public function buildJsConstructor()
    {
        return $this->buildJsLabelWrapper($this->buildJsConstructorForMainControl());
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Template\Elements\ui5Value::buildJsConstructorForMainControl()
     */
    public function buildJsConstructorForMainControl()
    {
        return <<<JS
        new sap.m.Input("{$this->getId()}", {
            {$this->buildJsProperties()}
        })
        {$this->buildJsPseudoEventHandlers()}
JS;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Template\Elements\ui5AbstractElement::buildJsProperties()
     */
    public function buildJsProperties()
    {
        if ($height = $this->getHeight()) {
            $height_option = 'height: "' . $height . '",';
        }
        $options = parent::buildJsProperties() . <<<JS
            width: "100%",
            {$this->buildJsPropertyChange()}
            {$this->buildJsPropertyRequired()}
            {$this->buildJsPropertyValue()}
            {$height_option}
            {$this->buildJsPropertyVisibile()}
JS;
        return $options;
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
        return <<<JS

            change: function(event) {
                console.log('change');
                {$this->getOnChangeScript()}
                
            },
JS;
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
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::getHeight()
     */
    public function getHeight()
    {
        if ($this->getWidget()->getHeight()->isUndefined()) {
            return '';
        }
        return parent::getHeight();
    }
    
    /**
     * TODO merge this with the corresponding method in ui5Value to support all cases.
     * 
     * Currently the input can use it's own value with defaults and can inherit this
     * value from a linked widget if a value live reference is defined. 
     * 
     * TODO #binding use model binding for element values and live references.
     * For live references, Fetching the value is done in PHP for initialization and 
     * in JS for every chage of the referenced value. This is ugly, but since there
     * seems to be no init event for input controls in ui5, there is no way to tell
     * a control to get it's value from another one. Using onAfterRendering on the
     * base element does not work for filters in dialogs as they are not rendered
     * when the data element is loaded, but only when the dialog is opened. These
     * problems should be when moving values to the model.
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Template\Elements\ui5Value::buildJsPropertyValue()
     */
    protected function buildJsPropertyValue()
    {
        $widget = $this->getWidget();
        if ($widget->getValueWidgetLink()) {
            $value = $this->escapeJsTextValue($widget->getValueWidgetLink()->getWidget()->getValueWithDefaults());
            $value = '"' . str_replace("\n", '', $value) . '"';
        } else {
            $value = $this->getWidget()->getValueWithDefaults();
        }
        return ($value ? 'value: "' . $this->escapeJsTextValue($value) . '",' : '');
    }
    
    /**
     * 
     * @return string
     */
    protected function buildJsPropertyEditable()
    {
        return 'editable: true, ';
    }
    
    protected function buildJsPropertyType()
    {
        // TODO derive other types from data type
        return 'Text';
    }
}
?>