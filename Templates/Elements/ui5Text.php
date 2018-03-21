<?php
namespace exface\OpenUI5Template\Templates\Elements;

use exface\Core\Widgets\Text;

/**
 * Generates sap.m.Text controls for Text widgets
 * 
 * @method Text getWidget()
 *
 * @author Andrej Kabachnik
 *        
 */
class ui5Text extends ui5Display
{
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5Display::buildJsPropertyWrapping()
     */
    protected function buildJsPropertyWrapping()
    {
        return 'wrapping: true,';
    }
}
?>