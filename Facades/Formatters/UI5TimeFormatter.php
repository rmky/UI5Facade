<?php
namespace exface\UI5Facade\Facades\Formatters;

/**
 * 
 * @method JsTimeFormatter getJsFormatter()
 * 
 * @author Andrej Kabachnik
 *
 */
class UI5TimeFormatter extends UI5DateFormatter
{    
    use UI5MomentFormatterTrait;
    
    protected function buildPatternSource()
    {
        return 'HH:mm:ss';
    }
    
    protected function getSapDataType()
    {
        return 'sap.ui.model.type.Time';
    }
}