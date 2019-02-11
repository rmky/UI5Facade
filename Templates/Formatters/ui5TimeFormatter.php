<?php
namespace exface\OpenUI5Template\Templates\Formatters;

use exface\Core\Templates\AbstractAjaxTemplate\Formatters\JsDateFormatter;

/**
 * 
 * @method JsTimeFormatter getJsFormatter()
 * 
 * @author Andrej Kabachnik
 *
 */
class ui5TimeFormatter extends ui5DateFormatter
{    
    
    protected function buildPatternSource()
    {
        return 'HH:mm:ss';
    }
    
    protected function getSapDataType()
    {
        return 'sap.ui.model.type.Time';
    }
}