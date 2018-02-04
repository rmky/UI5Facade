<?php
namespace exface\OpenUI5Template\Template\Formatters;

use exface\Core\Templates\AbstractAjaxTemplate\Formatters\JsNumberFormatter;
use exface\Core\DataTypes\NumberDataType;

/**
 * 
 * @method JsNumberFormatter getJsFormatter()
 * @method NumberDataType getDataType()
 * 
 * @author Andrej Kabachnik
 *
 */
class ui5NumberFormatter extends AbstractUi5BindingFormatter
{    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Template\Interfaces\ui5BindingFormatterInterface::buildJsBindingProperties()
     */
    public function buildJsBindingProperties()
    {
        $type = $this->getDataType();
        $options = '';
        
        if (! is_null($type->getPrecisionMin())){
            $options .= <<<JS

                    minFractionDigits: {$type->getPrecisionMin()},
JS;
        }
            
        if (! is_null($type->getPrecisionMax())){
            $options .= <<<JS

                    maxFractionDigits: {$type->getPrecisionMax()},
                    precision: {$type->getPrecisionMax()},
JS;
        }
         
        if ($type->getGroupDigits()) {
            $options .= <<<JS

                    groupingEnabled: true,
                    groupingSize: {$type->getGroupLength()},
                    groupingSeparator: "{$type->getGroupSeparator()}",
                    
JS;
        } else {
            $options .= <<<JS

                    groupingEnabled: false,
                    groupingSeparator: "",
JS;
        }
        
        return <<<JS

                type: '{$this->getSapDataType()}',
                formatOptions: {
                    {$options}
                },

JS;
    }
        
    protected function getSapDataType()
    {
        $type = $this->getDataType();
        if ($type->getPrecisionMax() === 0) {
            return 'sap.ui.model.type.Integer';
        } else {
            return 'sap.ui.model.type.Float';
        }
    }
}