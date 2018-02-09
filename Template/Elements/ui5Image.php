<?php
namespace exface\OpenUI5Template\Template\Elements;

use exface\Core\Widgets\Html;
use exface\Core\Widgets\Image;

/**
 * Generates sap.m.Image
 * 
 * @method Image getWidget()
 *
 * @author Andrej Kabachnik
 *        
 */
class ui5Image extends ui5Display
{
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Template\Elements\ui5AbstractElement::buildJsConstructor()
     */
    public function buildJsConstructorForMainControl()
    {
        return <<<JS

        new sap.m.Image("{$this->getid()}", {
    		src: {$this->buildJsValue()},
            densityAware: false,
            {$this->buildJsProperties()}
    	})

JS;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Template\Elements\ui5Value::buildCssHeightDefaultValue()
     */
    protected function buildCssHeightDefaultValue()
    {
        return '';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Template\Elements\ui5Value::buildCssWidthDefaultValue()
     */
    protected function buildCssWidthDefaultValue()
    {
        return '';
    }
}
?>