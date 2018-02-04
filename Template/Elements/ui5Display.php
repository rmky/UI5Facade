<?php
namespace exface\OpenUI5Template\Template\Elements;

use exface\Core\Widgets\Display;
use exface\OpenUI5Template\Template\Interfaces\ui5BindingFormatterInterface;
use exface\Core\DataTypes\BooleanDataType;

/**
 * Generates sap.m.Text controls for Display widgets
 * 
 * @method Display getWidget()
 *
 * @author Andrej Kabachnik
 *        
 */
class ui5Display extends ui5Value
{
    private $alignmentProperty = null;
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Template\Elements\ui5AbstractElement::buildJsConstructor()
     */
    public function buildJsConstructor()
    {
        return $this->buildJsLabelWrapper($this->buildJsConstructorForMainControl());
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Template\Elements\ui5Value::buildJsConstructorForMainControl()
     */
    public function buildJsConstructorForMainControl()
    {
        if ($this->getWidget()->getValueDataType() instanceof BooleanDataType) {
            return <<<JS

        new sap.ui.core.Icon({
            width: "100%",
            src: {$this->buildJsValueBinding('formatter: function(value) {
                    if (value === "1" || value === "true" || value === 1 || value === true) return "sap-icon://accept";
                    else return "";
                }')}
        })

JS;
        }
        
        return parent::buildJsConstructorForMainControl();
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Template\Interfaces\ui5ValueBindingInterface::buildJsValueBindingOptions()
     */
    public function buildJsValueBindingOptions()
    {
        return $this->getValueBindingFormatter()->buildJsBindingProperties();
    }
    
    /**
     * 
     * @return ui5BindingFormatterInterface
     */
    protected function getValueBindingFormatter()
    {
        return $this->getTemplate()->getDataTypeFormatter($this->getWidget()->getValueDataType());
    }
    
    /**
     * Sets the alignment for the content within the display: Begin, End, Center, Left or Right.
     * 
     * @param $propertyValue
     * @return ui5Display
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
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Template\Elements\ui5AbstractElement::buildJsProperties()
     */
    public function buildJsProperties()
    {
        return parent::buildJsProperties() . <<<JS
            width: "100%",
            {$this->buildJsPropertyAlignment()}
            {$this->buildJsPropertyWrapping()}
JS;
    }
    
    /**
     * Returns "wrapping: false/true," with tailing comma.
     * 
     * @return string
     */
    protected function buildJsPropertyWrapping()
    {
        return 'wrapping: false,';
    }

}
?>