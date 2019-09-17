<?php
namespace exface\UI5Facade\Exceptions;

use exface\Core\Exceptions\InvalidArgumentException;
use exface\Core\Interfaces\Exceptions\WidgetExceptionInterface;
use exface\Core\Exceptions\Widgets\WidgetExceptionTrait;

/**
 * Exception thrown if the Fiori exporter cannot deal with a widget.
 * 
 * @author Andrej Kabachnik
 *
 */
class UI5ExportUnsupportedWidgetException extends InvalidArgumentException implements WidgetExceptionInterface
{
    use WidgetExceptionTrait;
    
    public function getDefaultAlias()
    {
        return '77FTNCB';
    }
}