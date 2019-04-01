<?php
namespace exface\UI5Facade\Facades\Formatters;

use exface\Core\Facades\AbstractAjaxFacade\Formatters\JsEnumFormatter;
use exface\Core\Interfaces\DataTypes\EnumDataTypeInterface;

/**
 * The EnumFormatter just uses it's regular JS counterpart
 * 
 * @method JsEnumFormatter getJsFormatter()
 * @method EnumDataTypeInterface getDataType()
 * 
 * @author Andrej Kabachnik
 *
 */
class ui5EnumFormatter extends ui5TransparentFormatter
{    
}