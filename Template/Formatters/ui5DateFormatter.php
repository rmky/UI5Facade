<?php
namespace exface\OpenUI5Template\Template\Formatters;

use exface\Core\Templates\AbstractAjaxTemplate\Formatters\JsDateFormatter;

/**
 * 
 * @method JsDateFormatter getJsFormatter()
 * 
 * @author Andrej Kabachnik
 *
 */
class ui5DateFormatter extends AbstractUi5BindingFormatter
{    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Template\Interfaces\ui5BindingFormatterInterface::buildJsBindingProperties()
     */
    public function buildJsBindingProperties()
    {
        return <<<JS

                type: '{$this->getSapDataType()}',
                formatOptions: {
                    source: {
                        pattern: '{$this->buildPatternSource()}'
                    }
                },

JS;
    }
        
    protected function buildPatternSource()
    {
        return 'yyyy-MM-dd HH:mm:ss';
    }
    
    protected function getSapDataType()
    {
        return 'sap.ui.model.type.Date';
    }
}