<?php
namespace exface\OpenUI5Template\Template\Elements;

use exface\Core\Widgets\DataColumn;
use exface\Core\Widgets\DataTable;

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
            return $this->buildJsConstructorForMTable();
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
	    template: new sap.ui.commons.TextField({
            {$this->buildJsPropertyAlignment('textAlign')}
        }).bindProperty("value", "{$widget->getDataColumnName()}"),
	    sortProperty: "{$widget->getAttributeAlias()}",
	    filterProperty: "{$widget->getAttributeAlias()}",
		{$this->buildJsPropertyVisibile()}
	})
JS;
    }
    
    /**
     * Returns the constructor for a sap.m.Column for this DataColumn widget.
     * 
     * @return string
     */
    public function buildJsConstructorForMTable()
    {
        $col = $this->getWidget();
        
        return <<<JS
        
                    new sap.m.Column({
						popinDisplay: "Inline",
						demandPopin: true,
						header: [
                            new sap.m.Label({
                                text: "{$col->getCaption()}"
                            })
                        ],
                        {$this->buildJsPropertyAlignment('hAlign')}
                        {$this->buildJsPropertyVisibile()}
					})
					
JS;
    }
    
    /**
     * Returns the constructor for a regular read-only cell template for sap.m.Table
     * 
     * @return string
     */
    public function buildJsCellWithLabel()
    {
        $col = $this->getWidget();
        return <<<JS
        
                        new sap.m.Label({
                            text: "{{$col->getDataColumnName()}}",
                            tooltip: "{{$col->getDataColumnName()}}"
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
    protected function buildJsPropertyAlignment($propertyName = 'hAlign')
    {
        switch ($this->getWidget()->getAlign()) {
            case EXF_ALIGN_RIGHT:
            case EXF_ALIGN_OPPOSITE:
                $alignment = 'End';
                break;
            case EXF_ALIGN_CENTER:
                $alignment = 'Center';
                break;
            case EXF_ALIGN_LEFT:
            case EXF_ALIGN_DEFAULT:
            default:
                $alignment = 'Begin';
                
        }
        
        return $propertyName . ': "' . $alignment . '",';
    }
}
?>