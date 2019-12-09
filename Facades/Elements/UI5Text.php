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
    protected $alignmentProperty = null;
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Display::buildJsPropertyWrapping()
     */
    protected function buildJsPropertyWrapping()
    {
        return 'wrapping: true,';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Display::setAlignment()
     */
    public function setAlignment($propertyValue)
    {
        $this->alignmentProperty = $propertyValue;
        return $this;
    }
    
    /**
     *
     * @return string
     */
    protected function buildJsPropertyAlignment()
    {
        return $this->alignmentProperty ? 'textAlign: ' . $this->alignmentProperty . ',' : '';
    }
}
?>