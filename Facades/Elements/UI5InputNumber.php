<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\DataTypes\NumberDataType;
use exface\UI5Facade\Facades\Interfaces\UI5ControllerInterface;

/**
 * Renders a numeric input widget as a custom version of sap.m.StepInput for regular 
 * numbers or sap.m.Input for hexadecimal numbers or numeric ids.
 * 
 * A custom version of sap.m.StepInput is used because the original does not allow
 * empty values, which is unexceptable in various situation like in filters (no
 * way to deaktivate the filter) or optional inputs (empty inputs are treated as 
 * zero!).
 *
 * @method InputNumber getWidget()
 * 
 * @author Andrej Kabachnik
 *        
 */
class UI5InputNumber extends UI5Input
{
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Input::buildJsConstructorForMainControl()
     */
    public function buildJsConstructorForMainControl($oControllerJs = 'oController')
    {
        if (! $this->isStepInput()) {
            return parent::buildJsConstructorForMainControl();
        }
        
        $this->registerExternalModules($this->getController());
        
        return <<<JS

            new exface.ui5Custom.StepInputCustom("{$this->getId()}", {
                {$this->buildJsProperties()}
                {$this->buildJsPropertiesMinMax()}
                {$this->buildJsPropertyStep()}
                {$this->buildJsPropertyPrecision()}
            })
            {$this->buildJsPseudoEventHandlers()}

JS;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5AbstractElement::registerExternalModules()
     */
    public function registerExternalModules(UI5ControllerInterface $controller) : UI5AbstractElement
    {
        $controller->addExternalModule('libs.exface.ui5Custom.StepInputCustom', 'vendor/exface/UI5Facade/Facades/js/ui5Custom/StepInputCustom');
        return $this;
    }
    
    /**
     * @return string
     */
    protected function buildJsPropertyStep() : string
    {
        $widget = $this->getWidget();
        if (! is_null($widget->getStep())) {
            $value = $widget->getStep();
        } else {
            return '';
        }
        
        return 'step: ' . $value . ',';
    }
    
    /**
     * 
     * @return string
     */
    protected function buildJsPropertyPrecision() : string
    {
        if (is_null($val = $this->getWidget()->getPrecisionMax())) {
            $val = 2;
        }
        
        return 'displayValuePrecision: ' . $val . ',';
    }
    
    protected function buildJsPropertiesMinMax() : string
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
        
    /**
     * Returns the initial value defined in UXON as number or an quoted empty string
     * if not initial value was set.
     * 
     * @return string|NULL
     */
    protected function buildJsInitialValue() : string
    {
        $val = $this->getWidget()->getValueWithDefaults();
        return (is_null($val) || $val === '') ? '""' : $val;
    }
        
    /**
     * 
     * @return boolean
     */
    protected function isStepInput() : bool
    {
        $dataType = $this->getWidget()->getValueDataType();
        return (! ($dataType instanceof NumberDataType) || ($dataType->getBase() === 10 && ! $dataType->is('exface.Core.NumericId')));
    }
}
?>