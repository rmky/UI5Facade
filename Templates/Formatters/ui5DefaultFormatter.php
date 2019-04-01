<?php
namespace exface\UI5Facade\Facades\Formatters;

/**
 * 
 * @author Andrej Kabachnik
 *
 */
class ui5DefaultFormatter extends AbstractUi5BindingFormatter
{    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Interfaces\ui5BindingFormatterInterface::buildJsBindingProperties()
     */
    public function buildJsBindingProperties()
    {
        return '';
    }
}