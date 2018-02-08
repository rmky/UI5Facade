<?php
namespace exface\OpenUI5Template\Template\Elements;

use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryLiveReferenceTrait;
use exface\Core\DataTypes\NumberDataType;
use exface\Core\Widgets\InputNumber;

/**
 * Generates OpenUI5 inputs
 *
 * @method InputNumber getWidget()
 * 
 * @author Andrej Kabachnik
 *        
 */
class ui5InputNumber extends ui5Input
{    
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
            $value = "1.01";
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
     * @see \exface\OpenUI5Template\Template\Elements\ui5Input::buildJsPropertyType()
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
        $rawGetter = parent::buildJsValueGetter();
        return <<<JS

function(){
    var val = {$rawGetter};
    return (val === 0 ? '' : val);
}()

JS;
    }
        
    protected function isStepInput()
    {
        $dataType = $this->getWidget()->getValueDataType();
        return (($dataType instanceof NumberDataType) && $dataType->getBase() === 10);
    }
}
?>