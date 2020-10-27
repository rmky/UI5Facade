<?php
namespace exface\UI5Facade\Facades\Elements;

/**
 * Generates sap.m.Text controls for Text widgets
 * 
 * @method \exface\Core\Widgets\Text getWidget()
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
     * @see \exface\UI5Facade\Facades\Elements\UI5Display::getWrapping()
     */
    protected function getWrapping() : bool
    {
        return true;
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