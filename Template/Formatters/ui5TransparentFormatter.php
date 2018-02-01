<?php
namespace exface\OpenUI5Template\Template\Formatters;

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
     * @see \exface\OpenUI5Template\Template\Interfaces\ui5BindingFormatterInterface::buildJsBindingProperties()
     */
    public function buildJsBindingProperties()
    {
        return '';
    }
}