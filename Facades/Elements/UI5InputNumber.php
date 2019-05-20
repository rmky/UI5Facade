<?php
namespace exface\UI5Facade\Facades\Elements;

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
class UI5InputNumber extends UI5Input
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
                    oStepInput.data('_rawValue', {$this->buildJsInitialValue()});

JS;
            $this->addPseudoEventHandler('onAfterRendering', $onAfterRendering);
            
            // On the other hand, once UI5 does fire a change, the raw value is updated to 
            // make sure the + and - buttons do not loose their functionality.
            $onChange = <<<JS

                    if (oEvent.getSource && (oEvent.getSource().data('_rawValue') !== '' || oEvent.getParameters().value !== 0)) {
                        oEvent.getSource().data('_rawValue', oEvent.getParameters().value);
                    }

JS;
            $this->addOnChangeScript($onChange);
        }
    }
    public function buildJsConstructorForMainControl($oControllerJs = 'oController')
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
     * @see \exface\UI5Facade\Facades\Elements\UI5Input::buildJsPropertyType()
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
        
    /**
     * Returns the initial value defined in UXON as number or an quoted empty string
     * if not initial value was set.
     * 
     * @return string|NULL
     */
    protected function buildJsInitialValue()
    {
        $val = $this->getWidget()->getValueWithDefaults();
        return (is_null($val) || $val === '') ? '""' : $val;
    }
        
    protected function isStepInput()
    {
        $dataType = $this->getWidget()->getValueDataType();
        return (! ($dataType instanceof NumberDataType) || ($dataType->getBase() === 10 && ! $dataType->is('exface.Core.NumericId')));
    }
}
?>