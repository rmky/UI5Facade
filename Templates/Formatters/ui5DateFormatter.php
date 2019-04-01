<?php
namespace exface\UI5Facade\Facades\Formatters;

use exface\Core\Facades\AbstractAjaxFacade\Formatters\JsDateFormatter;

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
     * @see \exface\UI5Facade\Facades\Interfaces\ui5BindingFormatterInterface::buildJsBindingProperties()
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