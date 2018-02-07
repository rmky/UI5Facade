<?php
namespace exface\OpenUI5Template\Template\Elements;

use exface\Core\Widgets\DataColumn;
use exface\Core\Widgets\DataTable;
use exface\OpenUI5Template\Template\Interfaces\ui5BindingFormatterInterface;
use exface\OpenUI5Template\Template\Interfaces\ui5ValueBindingInterface;
use exface\OpenUI5Template\Template\Interfaces\ui5CompoundControlInterface;

/**
 *
 * @method DataColumn getWidget()
 *        
 * @author Andrej Kabachnik
 *        
 */
class ui5DataColumn extends ui5AbstractElement
{
    public function generateJs()
    {
        return '';
    }
    
    public function buildJsConstructor()
    {
        $data_widget = $this->getWidget()->getParent();
        if (($data_widget instanceof DataTable) && $data_widget->isResponsive()) {
            return $this->buildJsConstructorForMColumn();
        }
        return $this->buildJsConstructorForUiColumn();
    }

    /**
     * Returns the constructor for a sap.ui.table.Column for this DataColumn widget
     * 
     * @return string
     */
    public function buildJsConstructorForUiColumn()
    {
        $widget = $this->getWidget();
        
        return <<<JS
	 new sap.ui.table.Column({
	    label: new sap.ui.commons.Label({
            text: "{$this->getCaption()}"
        }),
        autoResizable: true,
        tooltip: "{$this->escapeJsTextValue($this->buildJsPropertyTooltip())}",
	    template: {$this->buildJsConstructorForCell()},
	    sortProperty: "{$widget->getAttributeAlias()}",
	    filterProperty: "{$widget->getAttributeAlias()}",
		{$this->buildJsPropertyVisibile()}
	})
JS;
    }
	
    /**
     * Returns the javascript constructor for a cell control to be used in cell template aggregations.
     * 
     * @return string
     */
    public function buildJsConstructorForCell()
    {
        $tpl = $this->getTemplate()->getElement($this->getWidget()->getCellWidget());
        // Disable using widget id as control id because this is a template for multiple controls
        $tpl->setUseWidgetId(false);
        if ($tpl instanceof ui5Display) {
            $tpl->setValueBindingPath($this->getWidget()->getDataColumnName());
            $tpl->setAlignment($this->buildJsAlignment());
        }
        if ($tpl instanceof ui5CompoundControlInterface) {
            return $tpl->buildJsConstructorForMainControl();
        } else {
            return $tpl->buildJsConstructor();
        }
    }
		
    /**
     * Returns the constructor for a sap.m.Column for this DataColumn widget.
     * 
     * @return string
     */
    public function buildJsConstructorForMColumn()
    {
        $col = $this->getWidget();
        $alignment = 'hAlign: ' . $this->buildJsAlignment() . ',';
        return <<<JS
        
                    new sap.m.Column({
						popinDisplay: "Inline",
						demandPopin: true,
						header: [
                            new sap.m.Label({
                                text: "{$col->getCaption()}"
                            })
                        ],
                        {$alignment}
                        {$this->buildJsPropertyVisibile()}
					})
					
JS;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Template\Elements\ui5AbstractElement::buildJsPropertyTooltip()
     */
    protected function buildJsPropertyTooltip()
    {
        return $this->getWidget()->getCaption() . ($this->getWidget()->getCaption() ? ': ' : '') . $this->getWidget()->getHint();
    }
    
    /**
     * Builds alignment options like 'hAlign: "Begin",' etc. - allways ending with a comma.
     * 
     * @param string $propertyName
     * @return string
     */
    protected function buildJsAlignment()
    {
        switch ($this->getWidget()->getAlign()) {
            case EXF_ALIGN_RIGHT:
            case EXF_ALIGN_OPPOSITE:
                $alignment = '"End"';
                break;
            case EXF_ALIGN_CENTER:
                $alignment = '"Center"';
                break;
            case EXF_ALIGN_LEFT:
            case EXF_ALIGN_DEFAULT:
            default:
                $alignment = '"Begin"';
                
        }
        
        return $alignment;
    }
}
?>