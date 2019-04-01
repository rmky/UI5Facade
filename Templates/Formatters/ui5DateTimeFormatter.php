<?php
namespace exface\UI5Facade\Facades\Formatters;

/**
 * 
 * @author Andrej Kabachnik
 *
 */
class ui5DateTimeFormatter extends ui5DateFormatter
{    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Formatters\ui5DateFormatter::buildPatternSource()
     */
    protected function buildPatternSource()
    {
        return 'yyyy-MM-dd HH:mm:ss';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Formatters\ui5DateFormatter::getSapDataType()
     */
    protected function getSapDataType()
    {
        return 'sap.ui.model.type.DateTime';
    }
}