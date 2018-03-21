<?php
namespace exface\OpenUI5Template\Templates\Elements;

use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryLiveReferenceTrait;
use exface\Core\DataTypes\NumberDataType;
use exface\Core\Widgets\InputNumber;

/**
 * Renders a numeric input widget as sap.m.StepInput for regular numbers or sap.m.Input
 * for hexadecimal numbers or numeric ids.
 *
 * @method InputNumber getWidget()
 * 
 * @author Andrej Kabachnik
 *        
 */
class ui5InputNumber extends ui5Input
{
    protected function init()
    {
        parent::init();
        
        // The sap.m.StepInput cannot be empty for some reason, so we need this workaround
        // script to empty the value when it is not set or explicitly removed by the user.
        // The script keeps a "raw value" as a data-attribute of the control and updates it
        // with every keyup event. Whenever UI5 would check for a change (that is on blur or enter),
        // the script checks if the raw value is empty and empties the control - regardless
        // of whether UI5 thinks it's a change or not (the UI5 change event was not enough
        // because it only fires when the value actually did change). 
        if ($this->isStepInput()) {
            $onAfterRendering = <<<JS

                    var oStepInput =  oEvent.srcControl;
                    var val = oStepInput.data('_rawValue');
                    var eInput = $('#{$this->getId()}-input-inner');
                    eInput.val({$this->buildJsInitialValue()});
                    eInput
                        .keyup(function(event){
                            oStepInput.data('_rawValue', eInput.val());
                        })
                        .blur(function(event){
                            if (oStepInput.data('_rawValue') === ''){
                                setTimeout(function(){eInput.val('')}, 5);
                                event.stopPropagation();
                                event.preventDefault();
                                return false;
                            }
                        })
                        .keypress(function(e) {
                            var keycode = (e.keyCode ? e.keyCode : e.which);
                            if (keycode == '13') {
                                if (oStepInput.data('_rawValue') === ''){
                                    setTimeout(function(){eInput.val('')}, 5);
                                    event.stopPropagation();
                                    event.preventDefault();
                                    return false;
                                }
                            }
                        });
                    oStepInput.data('_rawValue', '');

JS;
            $this->addPseudoEventHandler('onAfterRendering', $onAfterRendering);
            
            // On the other hand, once UI5 does fire a change, the raw value is updated to 
            // make sure the + and - buttons do not loose their functionality.
            $onChange = <<<JS

                    if (event.getSource().data('_rawValue') !== '' || event.getParameters().value !== 0) {
                        event.getSource().data('_rawValue', event.getParameters().value);
                    }

JS;
            $this->addOnChangeScript($onChange);
        }
    }
    public function buildJsConstructorForMainControl()
    {
        if (! $this->isStepInput()) {
            return parent::buildJsConstructorForMainControl();
        }
        
        return <<<JS

            new sap.m.StepInput("{$this->getId()}", {
                {$this->buildJsProperties()}
                {$this->buildJsPropertiesMinMax()}
                {$this->buildJsPropertyStep()}
                {$this->buildJsPropertyPrecision()}
            })
            {$this->buildJsPseudoEventHandlers()}

JS;
    }
    
    /**
     * @return string
     */
    protected function buildJsPropertyStep()
    {
        $widget = $this->getWidget();
        if (! is_null($widget->getStep())) {
            $value = $widget->getStep();
        } else {
            return '';
        }
        
        return 'step: ' . $value . ',';
    }
    
    protected function buildJsPropertyPrecision()
    {
        if (is_null($val = $this->getWidget()->getPrecisionMax())) {
            $val = 2;
        }
        
        return 'displayValuePrecision: ' . $val . ',';
    }
    
    protected function buildJsPropertiesMinMax()
    {
        $widget = $this->getWidget();
        $options = '';
        if (! is_null($widget->getMinValue())) {
            $options .= 'min: ' . $widget->getMinValue() . ',';
        }
        if (! is_null($widget->getMaxValue())) {
            $options .= 'max: ' . $widget->getMaxValue() . ',';
        }
        return $options;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5Input::buildJsPropertyType()
     */
    protected function buildJsPropertyType()
    {
        if (! $this->isStepInput()) {
            return parent::buildJsPropertyType();
        }
        
        return 'type: sap.m.InputType.Number,';
    }
    
    public function buildJsValueGetter(){
        if (! $this->isStepInput() || $this->getWidget()->isRequired()) {
            return parent::buildJsValueGetter();
        }
        return <<<JS

function(){
    var oStepInput = sap.ui.getCore().byId('{$this->getId()}');
    if (oStepInput.data('_rawValue') === '') {
        return '';
    } else if (oStepInput.data('_rawValue') === null) {
        return {$this->buildJsInitialValue()};
    }
    return oStepInput.getValue();
}()

JS;
    }
        
    protected function buildJsInitialValue()
    {
        $val = $this->getWidget()->getValueWithDefaults();
        return (is_null($val) || $val === '') ? '""' : $val;
    }
        
    protected function isStepInput()
    {
        $dataType = $this->getWidget()->getValueDataType();
        return (($dataType instanceof NumberDataType) && $dataType->getBase() === 10 && ! $dataType->is('exface.Core.NumericId'));
    }
}
?>