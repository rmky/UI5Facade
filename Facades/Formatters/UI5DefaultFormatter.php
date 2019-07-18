<?php
namespace exface\UI5Facade\Facades\Formatters;

/**
 * 
 * @author Andrej Kabachnik
 *
 */
class UI5DefaultFormatter extends AbstractUI5BindingFormatter
{    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Interfaces\UI5BindingFormatterInterface::buildJsBindingProperties()
     */
    public function buildJsBindingProperties()
    {
        return '';
    }
}