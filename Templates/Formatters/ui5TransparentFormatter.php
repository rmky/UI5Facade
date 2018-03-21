<?php
namespace exface\OpenUI5Template\Templates\Formatters;

/**
 * 
 * @author Andrej Kabachnik
 *
 */
class ui5TransparentFormatter extends AbstractUi5BindingFormatter
{    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Interfaces\ui5BindingFormatterInterface::buildJsBindingProperties()
     */
    public function buildJsBindingProperties()
    {
        return '';
    }
}