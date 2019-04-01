<?php
namespace exface\UI5Facade\Facades\Formatters;

use exface\Core\Facades\AbstractAjaxFacade\Formatters\JsBooleanFormatter;

/**
 * 
 * @method JsBooleanFormatter getJsFormatter()
 * 
 * @author Andrej Kabachnik
 *
 */
class ui5BooleanFormatter extends AbstractUi5BindingFormatter
{    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Interfaces\ui5BindingFormatterInterface::buildJsBindingProperties()
     */
    public function buildJsBindingProperties()
    {
        return <<<JS

                type: 'sap.ui.model.type.Boolean',
                formatter: function(value) {
                    if (value === "1" || value === "true" || value === 1 || value === true) return true;
                    else return false;
                },

JS;
    }
}