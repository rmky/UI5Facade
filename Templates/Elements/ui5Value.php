<?php
namespace exface\OpenUI5Template\Templates\Elements;

use exface\Core\Widgets\Value;
use exface\OpenUI5Template\Templates\Interfaces\ui5ValueBindingInterface;
use exface\OpenUI5Template\Templates\Interfaces\ui5CompoundControlInterface;

/**
 * Generates sap.m.Text controls for Value widgets
 * 
 * @method Value getWidget()
 *
 * @author Andrej Kabachnik
 *        
 */
class ui5Value extends ui5AbstractElement implements ui5ValueBindingInterface, ui5CompoundControlInterface
{
    private $valueBindingPath = null;
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5AbstractElement::buildJsConstructor()
     */
    public function buildJsConstructor($oController = 'oController') : string
    {
        return $this->buildJsConstructorForMainControl();
    }
    
    /**
     * Returns the constructor of the text/input control without the label
     * 
     * @return string
     */
    public function buildJsConstructorForMainControl()
    {
        return <<<JS

        new sap.m.Text("{$this->getId()}", {
            {$this->buildJsProperties()}
            {$this->buildJsPropertyValue()}
        })

JS;
    }
            
    public function buildJsProperties()
    {
        return parent::buildJsProperties() . <<<JS
            {$this->buildJsPropertyTooltip()}
JS;
    }
    
    /**
     * Returns the value property with property name and value followed by a comma.
     * 
     * @return string
     */
    protected function buildJsPropertyValue()
    {
        return <<<JS
            text: {$this->buildJsValue()},
JS;
    }
    
    /**
     * Returns inline javascript code for the value of the value property (without the property name).
     * 
     * Possible results are a quoted JS string, a binding expression or a binding object.
     * 
     * @return string
     */
    protected function buildJsValue()
    {
        if (! $this->isValueBoundToModel()) {
            $value = $this->escapeJsTextValue($this->getWidget()->getValue());
            $value = '"' . str_replace("\n", '', $value) . '"';
        } else {
            $value = $this->buildJsValueBinding();
        }
        return $value;
    }
    
    /**
     * Wraps the element constructor in a layout with a label.
     * 
     * @param string $element_constructor
     * @return string
     */
    protected function buildJsLabelWrapper($element_constructor)
    {
        if (! $this->getWidget()->getHideCaption()) {
            $js = <<<JS
        new sap.m.Label({
            text: "{$this->getCaption()}",
            {$this->buildJsPropertyTooltip()}
        }),

JS;
        }
        return $js . $element_constructor;
    }
    
    /**
     * 
     * @return boolean
     */
    protected function isValueBoundToModel()
    {
        return $this->getWidget()->hasValue() ? false : true;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Interfaces\ui5ValueBindingInterface::buildJsValueBindingOptions()
     */
    public function buildJsValueBindingOptions()
    {
        return '';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Interfaces\ui5ValueBindingInterface::buildJsValueBinding()
     */
    public function buildJsValueBinding($customOptions = '')
    {
        $js = <<<JS
            {
                path: "{$this->getValueBindingPath()}",
                {$this->buildJsValueBindingOptions()}
                {$customOptions}
            }
JS;
                return $js;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Interfaces\ui5ValueBindingInterface::setValueBindingPath()
     */
    public function setValueBindingPath($string)
    {
        $this->valueBindingPath = $string;
        return $this;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Interfaces\ui5ValueBindingInterface::getValueBindingPath()
     */
    public function getValueBindingPath() : string
    {
        if (is_null($this->valueBindingPath)) {
            return '/' . $this->getWidget()->getDataColumnName();
        }
        return $this->valueBindingPath;
    }
    
    protected function buildJsPropertyWidth()
    {
        $dim = $this->getWidget()->getWidth();
        if ($dim->isTemplateSpecific() || $dim->isPercentual()) {
            $val = $dim->getValue();
        } else {
            // TODO add support for relative units
            $val = $this->buildCssWidthDefaultValue();
        }
        if (! is_null($val) && $val !== '') {
            return 'width: "' . $val . '",';
        } else {
            return '';
        }
    }
    
    protected function buildCssWidthDefaultValue()
    {
        return '100%';
    }
    
    protected function buildJsPropertyHeight()
    {
        $dim = $this->getWidget()->getHeight();
        if ($dim->isTemplateSpecific() || $dim->isPercentual()) {
            $val = $dim->getValue();
        } else {
            // TODO add support for relative units
            $val = $this->buildCssHeightDefaultValue();
        }
        if (! is_null($val) && $val !== '') {
            return 'height: "' . $val . '",';
        } else {
            return '';
        }
    }
    
    protected function buildCssHeightDefaultValue()
    {
        return '';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Interfaces\ui5ValueBindingInterface::buildJsValueBindingPropertyName()
     */
    public function buildJsValueBindingPropertyName() : string
    {
        return 'text';
    }
}
?>