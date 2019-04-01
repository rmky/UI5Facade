<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Widgets\Text;

/**
 * Generates sap.m.Text controls for Text widgets
 * 
 * @method Text getWidget()
 *
 * @author Andrej Kabachnik
 *        
 */
class UI5Text extends UI5Display
{
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Display::buildJsPropertyWrapping()
     */
    protected function buildJsPropertyWrapping()
    {
        return 'wrapping: true,';
    }
}
?>