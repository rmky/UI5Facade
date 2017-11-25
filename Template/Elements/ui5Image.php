<?php
namespace exface\OpenUI5Template\Template\Elements;

use exface\Core\Widgets\Html;
use exface\Core\Widgets\Image;

/**
 * Generates OpenUI5 HTML
 * 
 * @method Image getWidget()
 *
 * @author Andrej Kabachnik
 *        
 */
class ui5Image extends ui5AbstractElement
{
    function generateJs()
    {
        return '';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Template\Elements\ui5AbstractElement::buildJsConstructor()
     */
    public function buildJsConstructor()
    {
        return <<<JS

    new sap.m.Image("{$this->getid()}", {
		src: "{$this->getWidget()->getUri()}",
        {$this->buildJsProperties()}
	})

JS;
    }
}
?>