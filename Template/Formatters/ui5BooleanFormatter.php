<?php
namespace exface\OpenUI5Template\Template\Formatters;

use exface\Core\Templates\AbstractAjaxTemplate\Formatters\JsBooleanFormatter;

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
     * @see \exface\OpenUI5Template\Template\Interfaces\ui5BindingFormatterInterface::buildJsBindingProperties()
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