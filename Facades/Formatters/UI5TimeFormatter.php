<?php
namespace exface\UI5Facade\Facades\Formatters;

use exface\Core\Facades\AbstractAjaxFacade\Formatters\JsDateFormatter;

/**
 * 
 * @method JsTimeFormatter getJsFormatter()
 * 
 * @author Andrej Kabachnik
 *
 */
class UI5TimeFormatter extends UI5DateFormatter
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