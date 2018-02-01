<?php
namespace exface\OpenUI5Template\Template\Formatters;

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
     * @see \exface\OpenUI5Template\Template\Formatters\ui5DateFormatter::buildPatternSource()
     */
    protected function buildPatternSource()
    {
        return 'yyyy-MM-dd HH:mm:ss';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Template\Formatters\ui5DateFormatter::getSapDataType()
     */
    protected function getSapDataType()
    {
        return 'sap.ui.model.type.DateTime';
    }
}