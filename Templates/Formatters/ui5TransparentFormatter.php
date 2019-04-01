<?php
namespace exface\UI5Facade\Facades\Formatters;

use exface\Core\Facades\AbstractAjaxFacade\Formatters\JsEnumFormatter;
use exface\Core\Interfaces\DataTypes\EnumDataTypeInterface;

/**
 * The transparent formatter simply passes the value to it's JS counterpart. 
 *  
 * @author Andrej Kabachnik
 *
 */
class ui5TransparentFormatter extends AbstractUi5BindingFormatter
{    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Interfaces\ui5BindingFormatterInterface::buildJsBindingProperties()
     */
    public function buildJsBindingProperties()
    {
        return <<<JS

                formatter: function(value) {
                    return {$this->getJsFormatter()->buildJsFormatter('value')}
                },

JS;
    }
}