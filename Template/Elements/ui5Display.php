<?php
namespace exface\OpenUI5Template\Template\Elements;

use exface\Core\Widgets\Display;
use exface\OpenUI5Template\Template\Interfaces\ui5BindingFormatterInterface;
use exface\Core\DataTypes\BooleanDataType;
use exface\Core\Widgets\DataColumn;

/**
 * Generates sap.m.Text controls for Display widgets.
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
            if ($this->getWidget()->getParent() instanceof DataColumn) {
                $icon_yes = 'sap-icon://accept';
                $icon_no = '';
                $icon_width = '"100%"';
            } else {
                $icon_yes = 'sap-icon://message-success';
                $icon_no = 'sap-icon://border';
                $icon_width = '"14px"';
            }
            $js = <<<JS

        new sap.ui.core.Icon({
            width: {$icon_width},
            {$this->buildJsPropertyTooltip()}
            src: {$this->buildJsValueBinding('formatter: function(value) {
                    if (value === "1" || value === "true" || value === 1 || value === true) return "' . $icon_yes . '";
                    else return "' . $icon_no . '";
                }')}
        })

JS;
        } else {
            $js = parent::buildJsConstructorForMainControl();
        }

        // TODO #binding store values in real model
        if($this->getWidget()->hasValue()) {
            $value = $this->escapeJsTextValue($this->getWidget()->getValue());
            $value = '"' . str_replace("\n", '', $value) . '"';
            $js .= <<<JS

            .setModel(function(){
                var oModel = new sap.ui.model.json.JSONModel();
                oModel.setProperty("/{$this->getWidget()->getDataColumnName()}", {$value});
                return oModel;
            }())
JS;
        }
        
        return $js;
    }
    
    protected function isValueBoundToModel()
    {
        return true;
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
    
    /**
     * {@inheritDoc}
     * 
     * If the display is used as cell widget in a DataColumn, the tooltip will
     * contain the value instead of a description, because ui5 tables tend to
     * cut off long values on smaller screens. On the other hande, the description 
     * is already there in the column header.
     * 
     * @see \exface\OpenUI5Template\Template\Elements\ui5AbstractElement::buildJsPropertyTooltip()
     */
    protected function buildJsPropertyTooltip()
    {
        if ($this->getWidget()->getParent() instanceof DataColumn) {
            if ($this->isValueBoundToModel()) {
                $value = $this->buildJsValueBinding('formatter: function(value){return (value === null || value === undefined) ? value : value.toString();},');
            } else {
                $value = $this->buildJsValue();
            }
            
            return 'tooltip: ' . $value .',';
        }
        
        return parent::buildJsPropertyTooltip();
    }

}
?>